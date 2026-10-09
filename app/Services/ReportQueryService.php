<?php

namespace App\Services;

use App\Helpers\DbHelper;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\EmployeePayment;
use App\Models\ProductionEntry;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Centralised data-fetching for all report pages + their CSV/PDF exports.
 * Both ReportController and ReportExportController delegate to this service
 * so every query lives in exactly one place.
 */
class ReportQueryService
{
    // =============================================
    // SHARED DEFINITIONS
    // =============================================
    /**
     * Exclude non-operating cash-book rows from a report query:
     *   - account transfers (TRANSFER-) — moving money between accounts, not a cost/income
     *   - supplier payments (PO-PAY-) — the inventory cost is COGS at sale, not the payment
     *   - wage payments (EMPPAY-) — the labour cost is the accrual (see labourCost())
     * Rows with a NULL reference (plain manual income/expense) are KEPT. The previous
     * `whereNot(reference like ...)` dropped them because NOT (NULL LIKE x) is NULL.
     */
    private function excludeNonOperating($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('reference')
              ->orWhere(function ($r) {
                  $r->where('reference', 'not like', 'TRANSFER-%')
                    ->where('reference', 'not like', 'PO-PAY-%')
                    ->where('reference', 'not like', 'EMPPAY-%');
              });
        });
    }

    /** Exclude only internal transfers (used for income, where orders/payments are separate). */
    private function excludeTransfers($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('reference')
              ->orWhere('reference', 'not like', 'TRANSFER-%');
        });
    }

    /** Base salary accrued across the months covered by [$from, $to]. */
    private function baseSalaryAccrual(int $bizId, $from, $to): float
    {
        return (float) Employee::where('business_id', $bizId)->get()
            ->sum(fn (Employee $employee) => $employee->salaryOwedForRange($from, $to));
    }

    /** Public: total operating expense for a period under the definitions above. */
    public function getOperatingExpenseTotal(int $bizId, string $from, string $to): float
    {
        return (float) $this->excludeNonOperating(
            Transaction::where('business_id', $bizId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$from, $to])
        )->sum('amount');
    }

    /** Public: total labour cost for a period = production earned + base salary accrued. */
    public function getLabourCost(int $bizId, string $from, string $to): float
    {
        $production = (float) ProductionEntry::where('business_id', $bizId)
            ->whereBetween('entry_date', [$from, $to])
            ->sum('amount');

        return round($production + $this->baseSalaryAccrual($bizId, $from, $to), 2);
    }

    /**
     * A 'Y-m-d' end date compared against a datetime column (created_at) truncates the
     * final day (it becomes 00:00:00). Reports must include the whole last day.
     */
    private function endOfDay(string $to): Carbon
    {
        return Carbon::parse($to)->endOfDay();
    }

    // =============================================
    // PROFIT & LOSS
    // =============================================
    public function getProfitLoss(int $bizId, string $from, string $to, string $groupBy = 'day'): array
    {
        $revenue = Order::where('business_id', $bizId)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$from, $this->endOfDay($to)])
            ->sum('total');

        $cogs = OrderItem::whereHas('order', fn($q) =>
            $q->where('business_id', $bizId)
              ->whereIn('status', ['completed', 'delivered'])
              ->whereBetween('created_at', [$from, $this->endOfDay($to)])
        )->selectRaw('SUM(cost_price * quantity) as total')->value('total') ?? 0;

        $expenses = $this->excludeNonOperating(
            Transaction::where('business_id', $bizId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$from, $to])
        )->sum('amount');

        // "Other income" = income NOT already counted as order revenue, and not an
        // internal account transfer. Order payments create income transactions that
        // belong to an order whose total is already in $revenue — counting them here
        // too would double-count sales.
        $otherIncome = $this->excludeTransfers(
            Transaction::where('business_id', $bizId)
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$from, $to])
                ->whereNull('order_id')
        )->sum('amount');

        // Labour is accrued once: production earned + base salary accrued for the
        // period. Wage *payments* (EMPPAY-) are excluded from $expenses above so the
        // same wages are not counted a second time.
        $productionWages = $this->getLabourCost($bizId, $from, $to);

        $grossProfit = $revenue - $cogs;
        $netProfit   = $grossProfit + $otherIncome - $expenses - $productionWages;
        $grossMargin = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0;
        $netMargin   = $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0;

        $expenseBreakdown = $this->excludeNonOperating(
            Transaction::where('business_id', $bizId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$from, $to])
        )->with('category')
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        // Chart trend data
        $trendExpr  = $groupBy === 'month' ? DbHelper::yearMonth('created_at')      : DbHelper::dateFormat('created_at');
        $trendExprT = $groupBy === 'month' ? DbHelper::yearMonth('transaction_date') : DbHelper::dateFormat('transaction_date');
        $trendExprE = $groupBy === 'month' ? DbHelper::yearMonth('entry_date')       : DbHelper::dateFormat('entry_date');

        $revenueTrend = Order::where('business_id', $bizId)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$from, $this->endOfDay($to)])
            ->selectRaw("{$trendExpr} as period, SUM(total) as total")
            ->groupBy('period')->orderBy('period')
            ->pluck('total', 'period');

        $expenseTrend = $this->excludeNonOperating(
            Transaction::where('business_id', $bizId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$from, $to])
        )->selectRaw("{$trendExprT} as period, SUM(amount) as total")
            ->groupBy('period')->orderBy('period')
            ->pluck('total', 'period');

        $wageTrend = ProductionEntry::where('business_id', $bizId)
            ->whereBetween('entry_date', [$from, $to])
            ->selectRaw("{$trendExprE} as period, SUM(amount) as total")
            ->groupBy('period')->orderBy('period')
            ->pluck('total', 'period');

        $chartData = [];
        $current   = Carbon::parse($from);
        $end       = Carbon::parse($to);
        while ($current->lte($end)) {
            $key   = $groupBy === 'month' ? $current->format('Y-m') : $current->format('Y-m-d');
            $label = $groupBy === 'month' ? $current->format('M Y') : $current->format('d M');
            $rev   = $revenueTrend[$key] ?? 0;
            $exp   = ($expenseTrend[$key] ?? 0) + ($wageTrend[$key] ?? 0);
            $chartData[] = ['label' => $label, 'revenue' => $rev, 'expense' => $exp, 'profit' => $rev - $exp];
            $groupBy === 'month' ? $current->addMonth() : $current->addDay();
        }

        return compact(
            'revenue', 'cogs', 'expenses', 'otherIncome', 'productionWages',
            'grossProfit', 'netProfit', 'grossMargin', 'netMargin',
            'expenseBreakdown', 'chartData'
        );
    }

    // =============================================
    // EXPENSE CATEGORY
    // =============================================
    public function getExpenseCategory(int $bizId, string $from, string $to): array
    {
        // Exclude internal account transfers (reference LIKE 'TRANSFER-%') — these are
        // not real operating expenses; the same exclusion is applied in getProfitLoss()
        // so both reports use a consistent definition of "expense".
        $breakdown = $this->excludeNonOperating(
            Transaction::where('business_id', $bizId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$from, $to])
        )->with('category')
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $totalExpense = $breakdown->sum('total');

        $transactions = $this->excludeNonOperating(
            Transaction::where('business_id', $bizId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$from, $to])
        )->with('category')
            ->orderBy('category_id')
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->groupBy('category_id');

        return compact('breakdown', 'totalExpense', 'transactions');
    }

    // =============================================
    // INCOME SOURCE
    // =============================================
    public function getIncomeSource(int $bizId, string $from, string $to): array
    {
        $breakdown = $this->excludeTransfers(
            Transaction::where('business_id', $bizId)
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$from, $to])
        )->with('category')
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $totalIncome = $breakdown->sum('total');

        return compact('breakdown', 'totalIncome');
    }

    // =============================================
    // BUSINESS OVERVIEW
    // =============================================
    public function getBusinessOverview(int $bizId, int $year): array
    {
        // One grouped query per metric instead of ~4 queries × 12 months (and an
        // employee query per month). Salaries are accrued in memory from a single load.
        $employees = Employee::where('business_id', $bizId)->with('salaries')->get();

        $revenueByMonth = Order::where('business_id', $bizId)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereYear('created_at', $year)
            ->selectRaw(DbHelper::yearMonth('created_at') . ' as ym, SUM(total) as total')
            ->groupBy('ym')->pluck('total', 'ym');

        $expenseByMonth = $this->excludeNonOperating(
            Transaction::where('business_id', $bizId)->where('type', 'expense')->whereYear('transaction_date', $year)
        )->selectRaw(DbHelper::yearMonth('transaction_date') . ' as ym, SUM(amount) as total')
            ->groupBy('ym')->pluck('total', 'ym');

        $incomeByMonth = $this->excludeTransfers(
            Transaction::where('business_id', $bizId)->where('type', 'income')
                ->whereNull('order_id')->whereYear('transaction_date', $year)
        )->selectRaw(DbHelper::yearMonth('transaction_date') . ' as ym, SUM(amount) as total')
            ->groupBy('ym')->pluck('total', 'ym');

        $wageByMonth = ProductionEntry::where('business_id', $bizId)
            ->whereYear('entry_date', $year)
            ->selectRaw(DbHelper::yearMonth('entry_date') . ' as ym, SUM(amount) as total')
            ->groupBy('ym')->pluck('total', 'ym');

        $ordersByMonth = Order::where('business_id', $bizId)
            ->whereYear('created_at', $year)
            ->selectRaw(DbHelper::yearMonth('created_at') . ' as ym, COUNT(*) as c')
            ->groupBy('ym')->pluck('c', 'ym');

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth();
            $key   = $start->format('Y-m');

            $rev   = (float) ($revenueByMonth[$key] ?? 0);
            $exp   = (float) ($expenseByMonth[$key] ?? 0);
            $inc   = (float) ($incomeByMonth[$key] ?? 0);
            $wages = (float) ($wageByMonth[$key] ?? 0)
                + (float) $employees->sum(fn (Employee $e) => $e->salaryForMonth($start));
            $orders = (int) ($ordersByMonth[$key] ?? 0);

            $months[] = [
                'month'   => $start->format('F'),
                'revenue' => $rev,
                'expense' => $exp + $wages,
                'income'  => $inc,
                'profit'  => $rev + $inc - $exp - $wages,
                'orders'  => $orders,
            ];
        }

        $yearStats = [
            'revenue'   => collect($months)->sum('revenue'),
            'expense'   => collect($months)->sum('expense'),
            'profit'    => collect($months)->sum('profit'),
            'orders'    => collect($months)->sum('orders'),
            'products'  => Product::where('business_id', $bizId)->count(),
            'customers' => Customer::where('business_id', $bizId)->count(),
            'employees' => Employee::where('business_id', $bizId)->where('status', 'active')->count(),
        ];

        $bestMonth = collect($months)->sortByDesc('revenue')->first();

        return compact('months', 'yearStats', 'bestMonth');
    }

    // =============================================
    // STOCK INVENTORY
    // =============================================
    public function getStockInventory(int $bizId): array
    {
        $stats = [
            'total_products' => Product::where('business_id', $bizId)->count(),
            'total_stock'    => Product::where('business_id', $bizId)->sum('stock'),
            'total_value'    => Product::where('business_id', $bizId)->selectRaw('SUM(stock * cost_price) as val')->value('val') ?? 0,
            'total_retail'   => Product::where('business_id', $bizId)->selectRaw('SUM(stock * price) as val')->value('val') ?? 0,
            'low_stock'      => Product::where('business_id', $bizId)->whereRaw('stock > 0 AND stock <= low_stock_alert')->count(),
            'out_of_stock'   => Product::where('business_id', $bizId)->where('stock', '<=', 0)->count(),
        ];

        $products = Product::where('business_id', $bizId)
            ->with('category')
            ->orderByDesc('stock')
            ->paginate(30);

        $byCategory = Product::where('business_id', $bizId)
            ->selectRaw('category_id, COUNT(*) as count, SUM(stock) as total_stock, SUM(stock * cost_price) as value')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        return compact('stats', 'products', 'byCategory');
    }

    /** Full product list without pagination (for exports). */
    public function getStockInventoryAll(int $bizId): array
    {
        $products = Product::where('business_id', $bizId)->with('category')->orderBy('name')->get();
        return compact('products');
    }

    // =============================================
    // CUSTOMER DUE
    // =============================================
    public function getCustomerDue(int $bizId, bool $paginate = true): array
    {
        $query = Customer::where('business_id', $bizId)->where('due_amount', '>', 0)->orderByDesc('due_amount');
        $customers = $paginate ? $query->paginate(30) : $query->get();
        $totalDue  = Customer::where('business_id', $bizId)->sum('due_amount');
        return compact('customers', 'totalDue');
    }

    // =============================================
    // SUPPLIER DUE
    // =============================================
    public function getSupplierDue(int $bizId, bool $paginate = true): array
    {
        $query = \App\Models\PurchaseOrder::where('business_id', $bizId)
            ->where('payment_status', '!=', 'paid')
            ->with('vendor')
            ->selectRaw('vendor_id, SUM(total - paid_amount) as due_amount, SUM(total) as total_ordered, COUNT(*) as po_count')
            ->groupBy('vendor_id')
            ->orderByDesc('due_amount');

        $vendors  = $paginate ? $query->paginate(20) : $query->get();
        $totalDue = \App\Models\PurchaseOrder::where('business_id', $bizId)
            ->where('payment_status', '!=', 'paid')
            ->selectRaw('SUM(total - paid_amount) as due')
            ->value('due') ?? 0;

        return compact('vendors', 'totalDue');
    }

    // =============================================
    // ORDER ANALYTICS
    // =============================================
    public function getOrderAnalytics(int $bizId, string $from, string $to): array
    {
        $base = Order::where('business_id', $bizId)->whereBetween('created_at', [$from, $this->endOfDay($to)]);

        $stats = [
            'total'      => (clone $base)->count(),
            'completed'  => (clone $base)->whereIn('status', ['completed', 'delivered'])->count(),
            'cancelled'  => (clone $base)->where('status', 'cancelled')->count(),
            'revenue'    => (clone $base)->whereIn('status', ['completed', 'delivered'])->sum('total'),
            'avg_value'  => (clone $base)->whereIn('status', ['completed', 'delivered'])->avg('total') ?? 0,
            'pending'    => (clone $base)->where('status', 'pending')->count(),
        ];
        $stats['completion_rate']   = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;
        $stats['cancellation_rate'] = $stats['total'] > 0 ? round(($stats['cancelled'] / $stats['total']) * 100) : 0;

        $bySource  = (clone $base)->selectRaw('source, COUNT(*) as count, SUM(total) as revenue')->groupBy('source')->get();
        $byPayment = (clone $base)->selectRaw('payment_method, COUNT(*) as count')->groupBy('payment_method')->orderByDesc('count')->get();
        $byStatus  = (clone $base)->selectRaw('status, COUNT(*) as count, SUM(total) as revenue')->groupBy('status')->get();

        $dailyOrders = (clone $base)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count, SUM(total) as revenue")
            ->groupBy('date')->orderBy('date')
            ->get()->keyBy('date');

        $chartData = [];
        $current = Carbon::parse($from);
        $end     = Carbon::parse($to);
        while ($current->lte($end)) {
            $key = $current->format('Y-m-d');
            $chartData[] = [
                'label'   => $current->format('d M'),
                'count'   => $dailyOrders[$key]->count ?? 0,
                'revenue' => $dailyOrders[$key]->revenue ?? 0,
            ];
            $current->addDay();
        }

        $topProducts = OrderItem::whereHas('order', fn($q) =>
            $q->where('business_id', $bizId)
              ->whereIn('status', ['completed', 'delivered'])
              ->whereBetween('created_at', [$from, $this->endOfDay($to)])
        )->selectRaw('product_name, SUM(quantity) as qty_sold, SUM(total) as revenue')
         ->groupBy('product_name')
         ->orderByDesc('revenue')
         ->limit(10)->get();

        // For exports: full order list
        $orders = (clone $base)->with('customer')->orderByDesc('created_at')->get();

        return compact('stats', 'bySource', 'byPayment', 'byStatus', 'chartData', 'topProducts', 'orders');
    }

    // =============================================
    // EMPLOYEE PRODUCTION
    // =============================================
    public function getEmployeeProduction(int $bizId, string $from, string $to, ?int $empId = null): array
    {
        $employees = Employee::where('business_id', $bizId)->orderBy('name')->get();

        $query = ProductionEntry::where('production_entries.business_id', $bizId)
            ->whereBetween('entry_date', [$from, $to])
            ->with(['employee', 'productionRate']);

        if ($empId) {
            $query->where('employee_id', $empId);
        }

        $entries = $query->orderBy('entry_date', 'desc')->get();

        // Payments for the same period (grouped by employee)
        $payQuery = EmployeePayment::where('business_id', $bizId)
            ->whereBetween('payment_date', [$from, $to]);
        if ($empId) {
            $payQuery->where('employee_id', $empId);
        }
        $paymentsByEmployee = $payQuery->get()->groupBy('employee_id');

        $summaryByEmployee = $entries->groupBy('employee_id')->map(function ($group) use ($paymentsByEmployee) {
            $emp        = $group->first()->employee;
            $totalAmt   = (float) $group->sum('amount');
            $totalPaid  = (float) ($paymentsByEmployee->get($emp?->id ?? 0, collect())->sum('amount'));
            $balance    = $totalAmt - $totalPaid; // >0 = due to worker, <0 = overpaid

            $products = $group->groupBy(fn($e) => implode('|', array_filter([
                $e->productionRate?->product_name ?? 'Manual Entry',
                $e->productionRate?->color ?? '',
                $e->productionRate?->size  ?? '',
            ])))->map(fn($g) => [
                'product_name' => $g->first()->productionRate?->product_name ?? 'Manual Entry',
                'color'        => $g->first()->productionRate?->color ?? '—',
                'size'         => $g->first()->productionRate?->size  ?? '—',
                'quantity'     => $g->sum('quantity'),
                'amount'       => $g->sum('amount'),
                'entry_count'  => $g->count(),
            ])->values();

            return [
                'employee'     => $emp,
                'total_qty'    => $group->sum('quantity'),
                'total_amount' => $totalAmt,
                'total_paid'   => $totalPaid,
                'balance'      => $balance,
                'entry_count'  => $group->count(),
                'products'     => $products,
                'entries'      => $group->sortByDesc('entry_date')->values(),
            ];
        })->sortByDesc('total_amount')->values();

        $totalQty    = $entries->sum('quantity');
        $totalAmount = (float) $entries->sum('amount');
        $totalPaid   = (float) $payQuery->sum('amount');
        $totalBalance = $totalAmount - $totalPaid;

        return compact('entries', 'summaryByEmployee', 'employees', 'totalQty', 'totalAmount', 'totalPaid', 'totalBalance');
    }

    // =============================================
    // SALES CHANNEL
    // =============================================
    public function getSalesChannel(int $bizId, string $from, string $to): array
    {
        $base = Order::where('business_id', $bizId)->whereBetween('created_at', [$from, $this->endOfDay($to)]);

        $bySource = (clone $base)
            ->selectRaw("COALESCE(NULLIF(source,''),'direct') as source,
                         COUNT(*) as total_count,
                         SUM(total) as total_revenue,
                         SUM(CASE WHEN status IN ('completed','delivered') THEN total ELSE 0 END) as completed_revenue,
                         SUM(CASE WHEN status IN ('completed','delivered') THEN 1 ELSE 0 END) as completed_count,
                         SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
            ->groupBy('source')
            ->orderByDesc('total_revenue')
            ->get();

        $totalRevenue = $bySource->sum('completed_revenue');
        $totalOrders  = $bySource->sum('total_count');
        $sources      = $bySource->pluck('source')->unique()->values()->toArray();

        $dailyBySource = (clone $base)
            ->whereIn('status', ['completed', 'delivered'])
            ->selectRaw("DATE(created_at) as date, COALESCE(NULLIF(source,''),'direct') as source, SUM(total) as revenue, COUNT(*) as count")
            ->groupBy('date', 'source')
            ->orderBy('date')
            ->get();

        $chartData = [];
        $current = Carbon::parse($from);
        $end     = Carbon::parse($to);
        while ($current->lte($end)) {
            $key   = $current->format('Y-m-d');
            $point = ['label' => $current->format('d M')];
            foreach ($sources as $src) {
                $point[$src] = (float) ($dailyBySource->where('date', $key)->where('source', $src)->first()?->revenue ?? 0);
            }
            $chartData[] = $point;
            $current->addDay();
        }

        $topBySource = OrderItem::whereHas('order', fn($q) =>
            $q->where('business_id', $bizId)
              ->whereIn('status', ['completed', 'delivered'])
              ->whereBetween('created_at', [$from, $this->endOfDay($to)])
        )->join('orders', 'order_items.order_id', '=', 'orders.id')
         ->selectRaw("COALESCE(NULLIF(orders.source,''),'direct') as source, order_items.product_name, SUM(order_items.quantity) as qty, SUM(order_items.total) as revenue")
         ->groupBy('source', 'order_items.product_name')
         ->orderByDesc('revenue')
         ->get()
         ->groupBy('source');

        $paymentBySource = (clone $base)
            ->whereIn('status', ['completed', 'delivered'])
            ->selectRaw("COALESCE(NULLIF(source,''),'direct') as source, payment_method, COUNT(*) as count, SUM(total) as revenue")
            ->groupBy('source', 'payment_method')
            ->get()
            ->groupBy('source');

        return compact('bySource', 'totalRevenue', 'totalOrders', 'sources', 'chartData', 'topBySource', 'paymentBySource', 'dailyBySource');
    }

    // =============================================
    // CUSTOMER ANALYTICS
    // =============================================
    public function getCustomerAnalytics(int $bizId, string $from, string $to): array
    {
        $stats = [
            'total'     => Customer::where('business_id', $bizId)->count(),
            'new'       => Customer::where('business_id', $bizId)->whereBetween('created_at', [$from, $this->endOfDay($to)])->count(),
            'with_due'  => Customer::where('business_id', $bizId)->where('due_amount', '>', 0)->count(),
            'total_due' => Customer::where('business_id', $bizId)->sum('due_amount'),
        ];

        $topCustomers = Customer::where('business_id', $bizId)
            ->where('total_purchase', '>', 0)
            ->orderByDesc('total_purchase')
            ->limit(10)->get();

        $dueCustomers = Customer::where('business_id', $bizId)
            ->where('due_amount', '>', 0)
            ->orderByDesc('due_amount')
            ->limit(10)->get();

        $newPerMonth = Customer::where('business_id', $bizId)
            ->selectRaw(DbHelper::yearMonth('created_at') . ' as month, COUNT(*) as count')
            ->groupBy('month')->orderBy('month')
            ->limit(6)->pluck('count', 'month');

        return compact('stats', 'topCustomers', 'dueCustomers', 'newPerMonth');
    }
}
