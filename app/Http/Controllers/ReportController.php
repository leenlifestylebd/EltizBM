<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Employee;
use App\Models\ProductionLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // =============================================
    // ALL REPORTS HUB
    // =============================================
    public function all()
    {
        return view('reports.all');
    }

    // =============================================
    // PROFIT & LOSS
    // =============================================
    public function profitLoss(Request $request)
    {
        $bizId   = auth()->user()->business_id;
        $from    = $request->from    ?? now()->startOfMonth()->format('Y-m-d');
        $to      = $request->to      ?? now()->endOfMonth()->format('Y-m-d');
        $groupBy = $request->group_by ?? 'day';

        // Revenue from completed/delivered orders
        $revenue = Order::where('business_id', $bizId)
            ->whereIn('status', ['completed','delivered'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('total');

        // Cost of goods sold
        $cogs = OrderItem::whereHas('order', fn($q) =>
            $q->where('business_id', $bizId)
              ->whereIn('status', ['completed','delivered'])
              ->whereBetween('created_at', [$from, $to])
        )->selectRaw('SUM(cost_price * quantity) as total')->value('total') ?? 0;

        // Operating expenses (from transactions)
        $expenses = Transaction::where('business_id', $bizId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        // Other income
        $otherIncome = Transaction::where('business_id', $bizId)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        $grossProfit  = $revenue - $cogs;
        $netProfit    = $grossProfit + $otherIncome - $expenses;
        $grossMargin  = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0;
        $netMargin    = $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0;

        // Expense breakdown by category
        $expenseBreakdown = Transaction::where('business_id', $bizId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        // Daily/Monthly trend
        $trendFormat = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $trendLabel  = $groupBy === 'month' ? 'M Y' : 'd M';

        $revenueTrend = Order::where('business_id', $bizId)
            ->whereIn('status', ['completed','delivered'])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("strftime('{$trendFormat}', created_at) as period, SUM(total) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');

        $expenseTrend = Transaction::where('business_id', $bizId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])
            ->selectRaw("strftime('{$trendFormat}', transaction_date) as period, SUM(amount) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');

        // Build chart data
        $chartData = [];
        $current = Carbon::parse($from);
        $end     = Carbon::parse($to);

        while ($current->lte($end)) {
            $key   = $groupBy === 'month' ? $current->format('Y-m') : $current->format('Y-m-d');
            $label = $groupBy === 'month' ? $current->format('M Y') : $current->format('d M');
            $chartData[] = [
                'label'   => $label,
                'revenue' => $revenueTrend[$key] ?? 0,
                'expense' => $expenseTrend[$key] ?? 0,
                'profit'  => ($revenueTrend[$key] ?? 0) - ($expenseTrend[$key] ?? 0),
            ];
            $groupBy === 'month' ? $current->addMonth() : $current->addDay();
        }

        return view('reports.profit-loss', compact(
            'revenue', 'cogs', 'expenses', 'otherIncome',
            'grossProfit', 'netProfit', 'grossMargin', 'netMargin',
            'expenseBreakdown', 'chartData', 'from', 'to', 'groupBy'
        ));
    }

    // =============================================
    // ORDER ANALYTICS
    // =============================================
    public function orderAnalytics(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $base = Order::where('business_id', $bizId)->whereBetween('created_at', [$from, $to]);

        $stats = [
            'total'      => (clone $base)->count(),
            'completed'  => (clone $base)->whereIn('status',['completed','delivered'])->count(),
            'cancelled'  => (clone $base)->where('status','cancelled')->count(),
            'revenue'    => (clone $base)->whereIn('status',['completed','delivered'])->sum('total'),
            'avg_value'  => (clone $base)->whereIn('status',['completed','delivered'])->avg('total') ?? 0,
            'pending'    => (clone $base)->where('status','pending')->count(),
        ];
        $stats['completion_rate'] = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;
        $stats['cancellation_rate'] = $stats['total'] > 0 ? round(($stats['cancelled'] / $stats['total']) * 100) : 0;

        // Orders by source
        $bySource = (clone $base)->selectRaw('source, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('source')->get();

        // Orders by payment method
        $byPayment = (clone $base)->selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')->orderByDesc('count')->get();

        // Orders by status
        $byStatus = (clone $base)->selectRaw('status, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('status')->get();

        // Daily orders trend
        $dailyOrders = (clone $base)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count, SUM(total) as revenue")
            ->groupBy('date')->orderBy('date')
            ->get()->keyBy('date');

        $chartData = [];
        $current = Carbon::parse($from);
        $end = Carbon::parse($to);
        while ($current->lte($end)) {
            $key = $current->format('Y-m-d');
            $chartData[] = [
                'label'   => $current->format('d M'),
                'count'   => $dailyOrders[$key]->count ?? 0,
                'revenue' => $dailyOrders[$key]->revenue ?? 0,
            ];
            $current->addDay();
        }

        // Top products by orders
        $topProducts = OrderItem::whereHas('order', fn($q) =>
            $q->where('business_id', $bizId)->whereBetween('created_at', [$from, $to])
        )->selectRaw('product_name, SUM(quantity) as qty_sold, SUM(total) as revenue')
         ->groupBy('product_name')
         ->orderByDesc('revenue')
         ->limit(10)->get();

        return view('reports.order-analytics', compact(
            'stats', 'bySource', 'byPayment', 'byStatus',
            'chartData', 'topProducts', 'from', 'to'
        ));
    }

    // =============================================
    // CUSTOMER ANALYTICS
    // =============================================
    public function customerAnalytics(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->subMonths(3)->format('Y-m-d');
        $to    = $request->to   ?? now()->format('Y-m-d');

        $stats = [
            'total'     => Customer::where('business_id',$bizId)->count(),
            'new'       => Customer::where('business_id',$bizId)->whereBetween('created_at',[$from,$to])->count(),
            'with_due'  => Customer::where('business_id',$bizId)->where('due_amount','>',0)->count(),
            'total_due' => Customer::where('business_id',$bizId)->sum('due_amount'),
        ];

        // Top customers by purchase
        $topCustomers = Customer::where('business_id', $bizId)
            ->where('total_purchase', '>', 0)
            ->orderByDesc('total_purchase')
            ->limit(10)
            ->get();

        // Customers with highest due
        $dueCustomers = Customer::where('business_id', $bizId)
            ->where('due_amount', '>', 0)
            ->orderByDesc('due_amount')
            ->limit(10)
            ->get();

        // New customers per month
        $newPerMonth = Customer::where('business_id', $bizId)
            ->selectRaw("strftime('%Y-%m', created_at) as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(6)
            ->pluck('count','month');

        return view('reports.customer-analytics', compact(
            'stats', 'topCustomers', 'dueCustomers', 'newPerMonth', 'from', 'to'
        ));
    }

    // =============================================
    // STOCK & INVENTORY REPORT
    // =============================================
    public function stockInventory(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $stats = [
            'total_products'  => Product::where('business_id',$bizId)->count(),
            'total_stock'     => Product::where('business_id',$bizId)->sum('stock'),
            'total_value'     => Product::where('business_id',$bizId)->selectRaw('SUM(stock * cost_price) as val')->value('val') ?? 0,
            'total_retail'    => Product::where('business_id',$bizId)->selectRaw('SUM(stock * price) as val')->value('val') ?? 0,
            'low_stock'       => Product::where('business_id',$bizId)->whereRaw('stock > 0 AND stock <= low_stock_alert')->count(),
            'out_of_stock'    => Product::where('business_id',$bizId)->where('stock','<=',0)->count(),
        ];

        $products = Product::where('business_id', $bizId)
            ->with('category')
            ->orderByDesc('stock')
            ->paginate(30);

        // Stock by category
        $byCategory = Product::where('business_id',$bizId)
            ->selectRaw('category_id, COUNT(*) as count, SUM(stock) as total_stock, SUM(stock * cost_price) as value')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        return view('reports.stock-inventory', compact('stats','products','byCategory'));
    }

    // =============================================
    // BUSINESS OVERVIEW
    // =============================================
    public function businessOverview(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $year  = $request->year ?? now()->year;

        // Monthly P&L for the year
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year,$m,1)->startOfMonth();
            $end   = Carbon::create($year,$m,1)->endOfMonth();

            $rev  = Order::where('business_id',$bizId)->whereIn('status',['completed','delivered'])->whereBetween('created_at',[$start,$end])->sum('total');
            $exp  = Transaction::where('business_id',$bizId)->where('type','expense')->whereBetween('transaction_date',[$start,$end])->sum('amount');
            $inc  = Transaction::where('business_id',$bizId)->where('type','income')->whereBetween('transaction_date',[$start,$end])->sum('amount');
            $orders = Order::where('business_id',$bizId)->whereBetween('created_at',[$start,$end])->count();

            $months[] = [
                'month'   => $start->format('M'),
                'revenue' => $rev,
                'expense' => $exp,
                'income'  => $inc,
                'profit'  => $rev + $inc - $exp,
                'orders'  => $orders,
            ];
        }

        $yearStats = [
            'revenue'    => collect($months)->sum('revenue'),
            'expense'    => collect($months)->sum('expense'),
            'profit'     => collect($months)->sum('profit'),
            'orders'     => collect($months)->sum('orders'),
            'products'   => Product::where('business_id',$bizId)->count(),
            'customers'  => Customer::where('business_id',$bizId)->count(),
            'employees'  => Employee::where('business_id',$bizId)->where('status','active')->count(),
        ];

        // Best month
        $bestMonth = collect($months)->sortByDesc('revenue')->first();

        return view('reports.business-overview', compact('months','yearStats','bestMonth','year'));
    }

    // =============================================
    // CUSTOMER DUE
    // =============================================
    public function customerDue()
    {
        $bizId = auth()->user()->business_id;
        $customers = Customer::where('business_id',$bizId)
            ->where('due_amount','>',0)
            ->orderByDesc('due_amount')
            ->paginate(30);
        $totalDue = Customer::where('business_id',$bizId)->sum('due_amount');
        return view('reports.customer-due', compact('customers','totalDue'));
    }

    // =============================================
    // SUPPLIER DUE
    // =============================================
    public function supplierDue()
    {
        $bizId = auth()->user()->business_id;
        $vendors = \App\Models\PurchaseOrder::where('business_id',$bizId)
            ->where('payment_status','!=','paid')
            ->with('vendor')
            ->selectRaw('vendor_id, SUM(total - paid_amount) as due_amount, COUNT(*) as po_count')
            ->groupBy('vendor_id')
            ->orderByDesc('due_amount')
            ->paginate(20);
        $totalDue = \App\Models\PurchaseOrder::where('business_id',$bizId)->where('payment_status','!=','paid')->selectRaw('SUM(total - paid_amount) as due')->value('due') ?? 0;
        return view('reports.supplier-due', compact('vendors','totalDue'));
    }

    // =============================================
    // EXPENSE CATEGORY REPORT
    // =============================================
    public function expenseCategory(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $breakdown = Transaction::where('business_id',$bizId)
            ->where('type','expense')
            ->whereBetween('transaction_date',[$from,$to])
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $totalExpense = $breakdown->sum('total');

        return view('reports.expense-category', compact('breakdown','totalExpense','from','to'));
    }

    // =============================================
    // INCOME SOURCE REPORT
    // =============================================
    public function incomeSource(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $breakdown = Transaction::where('business_id',$bizId)
            ->where('type','income')
            ->whereBetween('transaction_date',[$from,$to])
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $totalIncome = $breakdown->sum('total');

        return view('reports.income-source', compact('breakdown','totalIncome','from','to'));
    }

    // =============================================
    // PURCHASE ORDERS REPORT
    // =============================================
    public function purchaseOrders(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $pos = \App\Models\PurchaseOrder::where('business_id',$bizId)
            ->whereBetween('created_at',[$from,$to])
            ->with('vendor')
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total'    => \App\Models\PurchaseOrder::where('business_id',$bizId)->whereBetween('created_at',[$from,$to])->sum('total'),
            'paid'     => \App\Models\PurchaseOrder::where('business_id',$bizId)->whereBetween('created_at',[$from,$to])->sum('paid_amount'),
            'count'    => \App\Models\PurchaseOrder::where('business_id',$bizId)->whereBetween('created_at',[$from,$to])->count(),
            'received' => \App\Models\PurchaseOrder::where('business_id',$bizId)->whereBetween('created_at',[$from,$to])->where('status','received')->count(),
        ];

        return view('reports.purchase-orders', compact('pos','stats','from','to'));
    }

    // =============================================
    // RETURN & REFUND
    // =============================================
    public function returnRefund(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $returns = Shipment::where('business_id',$bizId)
            ->where('status','returned')
            ->whereBetween('created_at',[$from,$to])
            ->with('order')
            ->paginate(20);

        $returnedOrders = Order::where('business_id',$bizId)
            ->where('status','returned')
            ->whereBetween('created_at',[$from,$to])
            ->paginate(20);

        $stats = [
            'total_returns'  => $returns->total(),
            'return_value'   => $returns->sum('cod_amount'),
            'order_returns'  => $returnedOrders->total(),
        ];

        return view('reports.return-refund', compact('returns','returnedOrders','stats','from','to'));
    }

    // =============================================
    // ACCOUNT BALANCE
    // =============================================
    public function accountBalance()
    {
        $bizId = auth()->user()->business_id;

        $balances = [];
        foreach (['cash','bkash','nagad','rocket','bank','card'] as $method) {
            $income  = Transaction::where('business_id',$bizId)->where('type','income')->where('payment_method',$method)->sum('amount');
            $expense = Transaction::where('business_id',$bizId)->where('type','expense')->where('payment_method',$method)->sum('amount');
            $balances[$method] = [
                'method'  => ucfirst($method),
                'income'  => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ];
        }

        return view('reports.account-balance', compact('balances'));
    }
}
