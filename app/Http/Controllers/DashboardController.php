<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Shipment;

class DashboardController extends Controller
{
    public function index()
    {
        $bizId = auth()->user()->business_id;
        $today = today();

        $stats = [
            'total_orders' => Order::where('business_id', $bizId)->whereDate('created_at', $today)->count(),
            'revenue'      => Order::where('business_id', $bizId)->whereDate('created_at', $today)
                                   ->whereIn('status', ['completed', 'delivered'])->sum('total'),
            // Internal account transfers are not income/expense; exclude them so the
            // dashboard totals match the reports.
            'income'       => Transaction::where('business_id', $bizId)->where('type', 'income')
                                         ->whereDate('transaction_date', $today)->notInternalTransfer()->sum('amount'),
            'expenses'     => Transaction::where('business_id', $bizId)->where('type', 'expense')
                                         ->whereDate('transaction_date', $today)->notInternalTransfer()->sum('amount'),
            'products'     => Product::where('business_id', $bizId)->count(),
            'shipments'    => Shipment::where('business_id', $bizId)->whereDate('created_at', $today)->count(),
            'low_stock'    => Product::where('business_id', $bizId)->where('track_stock', true)
                                     ->whereRaw('stock <= low_stock_alert')->count(),
        ];

        $lowStockProducts = Product::where('business_id', $bizId)
            ->where('track_stock', true)
            ->whereRaw('stock <= low_stock_alert')
            ->orderBy('stock')
            ->limit(6)
            ->get(['id', 'name', 'sku', 'stock', 'low_stock_alert']);

        $recentOrders = Order::where('business_id', $bizId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $todayShipments = Shipment::where('business_id', $bizId)
            ->whereDate('created_at', $today)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact('stats', 'recentOrders', 'todayShipments', 'lowStockProducts'));
    }

    public function apiStats(Request $request)
    {
        $bizId  = auth()->user()->business_id;
        $period = $request->get('period', 'today');

        $query = Order::where('business_id', $bizId);

        match ($period) {
            'today'   => $query->whereDate('created_at', today()),
            'week'    => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month'   => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            default   => $query->whereDate('created_at', today()),
        };

        return response()->json([
            'orders'  => $query->count(),
            'revenue' => $query->whereIn('status', ['completed', 'delivered'])->sum('total'),
        ]);
    }
}
