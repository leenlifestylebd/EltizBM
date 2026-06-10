<?php

namespace App\Http\Controllers;

use App\Services\ReportQueryService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\Transaction;
use App\Models\ProductionEntry;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportQueryService $rqs) {}

    // =============================================
    // ALL REPORTS HUB
    // =============================================
    public function all()
    {
        $bizId = auth()->user()->business_id;
        $from  = now()->startOfMonth()->format('Y-m-d');
        $to    = now()->endOfMonth()->format('Y-m-d');

        $revenue  = Order::where('business_id', $bizId)->whereIn('status', ['completed','delivered'])->whereBetween('created_at', [$from, $to])->sum('total');
        $expenses = Transaction::where('business_id', $bizId)->where('type', 'expense')->whereBetween('transaction_date', [$from, $to])->sum('amount');
        $wages    = ProductionEntry::where('business_id', $bizId)->whereBetween('entry_date', [$from, $to])->sum('amount');
        $orders   = Order::where('business_id', $bizId)->whereBetween('created_at', [$from, $to])->count();
        $customers = Customer::where('business_id', $bizId)->count();
        $totalDue  = Customer::where('business_id', $bizId)->sum('due_amount');

        $thisMonth = [
            'revenue'   => $revenue,
            'expenses'  => $expenses + $wages,
            'profit'    => $revenue - $expenses - $wages,
            'orders'    => $orders,
            'customers' => $customers,
            'total_due' => $totalDue,
            'from'      => $from,
            'to'        => $to,
        ];

        return view('reports.all', compact('thisMonth'));
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

        $data = $this->rqs->getProfitLoss($bizId, $from, $to, $groupBy);

        return view('reports.profit-loss', array_merge($data, compact('from', 'to', 'groupBy')));
    }

    // =============================================
    // ORDER ANALYTICS
    // =============================================
    public function orderAnalytics(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $data = $this->rqs->getOrderAnalytics($bizId, $from, $to);

        return view('reports.order-analytics', array_merge($data, compact('from', 'to')));
    }

    // =============================================
    // CUSTOMER ANALYTICS
    // =============================================
    public function customerAnalytics(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->subMonths(3)->format('Y-m-d');
        $to    = $request->to   ?? now()->format('Y-m-d');

        $data = $this->rqs->getCustomerAnalytics($bizId, $from, $to);

        return view('reports.customer-analytics', array_merge($data, compact('from', 'to')));
    }

    // =============================================
    // STOCK & INVENTORY REPORT
    // =============================================
    public function stockInventory(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $data  = $this->rqs->getStockInventory($bizId);

        return view('reports.stock-inventory', $data);
    }

    // =============================================
    // BUSINESS OVERVIEW
    // =============================================
    public function businessOverview(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $year  = (int) ($request->year ?? now()->year);

        $data = $this->rqs->getBusinessOverview($bizId, $year);

        return view('reports.business-overview', array_merge($data, compact('year')));
    }

    // =============================================
    // CUSTOMER DUE
    // =============================================
    public function customerDue()
    {
        $bizId = auth()->user()->business_id;
        $data  = $this->rqs->getCustomerDue($bizId);

        return view('reports.customer-due', $data);
    }

    // =============================================
    // SUPPLIER DUE
    // =============================================
    public function supplierDue()
    {
        $bizId = auth()->user()->business_id;
        $data  = $this->rqs->getSupplierDue($bizId);

        return view('reports.supplier-due', $data);
    }

    // =============================================
    // EXPENSE CATEGORY REPORT
    // =============================================
    public function expenseCategory(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $data = $this->rqs->getExpenseCategory($bizId, $from, $to);

        return view('reports.expense-category', array_merge($data, compact('from', 'to')));
    }

    // =============================================
    // EMPLOYEE PRODUCTION REPORT
    // =============================================
    public function employeeProduction(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from     ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to       ?? now()->endOfMonth()->format('Y-m-d');
        $empId = $request->employee ? (int) $request->employee : null;

        $data = $this->rqs->getEmployeeProduction($bizId, $from, $to, $empId);

        return view('reports.employee-production', array_merge($data, compact('from', 'to', 'empId')));
    }

    // =============================================
    // SALES BY CHANNEL REPORT
    // =============================================
    public function salesChannel(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $data = $this->rqs->getSalesChannel($bizId, $from, $to);

        return view('reports.sales-channel', array_merge($data, compact('from', 'to')));
    }

    // =============================================
    // INCOME SOURCE REPORT
    // =============================================
    public function incomeSource(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $data = $this->rqs->getIncomeSource($bizId, $from, $to);

        return view('reports.income-source', array_merge($data, compact('from', 'to')));
    }

    // =============================================
    // PURCHASE ORDERS REPORT
    // =============================================
    public function purchaseOrders(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $pos = \App\Models\PurchaseOrder::where('business_id', $bizId)
            ->whereBetween('created_at', [$from, $to])
            ->with('vendor')
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total'    => \App\Models\PurchaseOrder::where('business_id', $bizId)->whereBetween('created_at', [$from, $to])->sum('total'),
            'paid'     => \App\Models\PurchaseOrder::where('business_id', $bizId)->whereBetween('created_at', [$from, $to])->sum('paid_amount'),
            'count'    => \App\Models\PurchaseOrder::where('business_id', $bizId)->whereBetween('created_at', [$from, $to])->count(),
            'received' => \App\Models\PurchaseOrder::where('business_id', $bizId)->whereBetween('created_at', [$from, $to])->where('status', 'received')->count(),
        ];

        return view('reports.purchase-orders', compact('pos', 'stats', 'from', 'to'));
    }

    // =============================================
    // RETURN & REFUND
    // =============================================
    public function returnRefund(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $returns = Shipment::where('business_id', $bizId)
            ->where('status', 'returned')
            ->whereBetween('created_at', [$from, $to])
            ->with('order')
            ->paginate(20);

        $returnedOrders = Order::where('business_id', $bizId)
            ->where('status', 'returned')
            ->whereBetween('created_at', [$from, $to])
            ->paginate(20);

        $stats = [
            'total_returns' => $returns->total(),
            // Sum across the whole range, not just the current page of the paginator.
            'return_value'  => Shipment::where('business_id', $bizId)
                ->where('status', 'returned')
                ->whereBetween('created_at', [$from, $to])
                ->sum('cod_amount'),
            'order_returns' => $returnedOrders->total(),
        ];

        return view('reports.return-refund', compact('returns', 'returnedOrders', 'stats', 'from', 'to'));
    }

    // =============================================
    // ACCOUNT BALANCE
    // =============================================
    public function accountBalance()
    {
        $bizId = auth()->user()->business_id;

        $balances = [];
        foreach (['cash', 'bkash', 'nagad', 'rocket', 'bank', 'card', 'cod', 'other'] as $method) {
            $income  = Transaction::where('business_id', $bizId)->where('type', 'income')->where('payment_method', $method)->sum('amount');
            $expense = Transaction::where('business_id', $bizId)->where('type', 'expense')->where('payment_method', $method)->sum('amount');
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
