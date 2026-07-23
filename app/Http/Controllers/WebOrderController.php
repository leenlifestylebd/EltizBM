<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CourierSetting;
use App\Models\WoocommerceSetting;
use App\Services\WooCommerceService;
use Illuminate\Http\Request;

class WebOrderController extends Controller
{
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $query = Order::where('business_id', $bizId)
            ->where('source', 'woocommerce')
            ->with(['shipments' => fn ($q) => $q->where('courier_name', 'Steadfast')])
            ->orderByDesc('created_at');

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $orders      = $query->paginate(20);
        $wooSettings = WoocommerceSetting::where('business_id', $bizId)->first();

        $statusCounts = [
            'all'        => Order::where('business_id', $bizId)->where('source', 'woocommerce')->count(),
            'pending'    => Order::where('business_id', $bizId)->where('source', 'woocommerce')->where('status', 'pending')->count(),
            'processing' => Order::where('business_id', $bizId)->where('source', 'woocommerce')->where('status', 'processing')->count(),
            'completed'  => Order::where('business_id', $bizId)->where('source', 'woocommerce')->where('status', 'completed')->count(),
            'cancelled'  => Order::where('business_id', $bizId)->where('source', 'woocommerce')->where('status', 'cancelled')->count(),
        ];

        // Steadfast connection status — enables the "Send to Steadfast" action here.
        $steadfast   = CourierSetting::where('business_id', $bizId)
            ->where('provider', 'steadfast')->where('is_active', true)->first();
        $isConnected = (bool) ($steadfast && $steadfast->api_key && $steadfast->api_secret);

        return view('web-orders.index', compact('orders', 'wooSettings', 'statusCounts', 'isConnected'));
    }

    public function show(Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
        $order->load(['items.product', 'shipments']);
        $business = auth()->user()->business;
        $isConnected = CourierSetting::where('business_id', $order->business_id)
            ->where('provider', 'steadfast')->where('is_active', true)
            ->whereNotNull('api_key')->whereNotNull('api_secret')->exists();
        return view('orders.show', compact('order', 'business', 'isConnected'));
    }

    public function sync(Request $request)
    {
        $bizId       = auth()->user()->business_id;
        $wooSettings = WoocommerceSetting::where('business_id', $bizId)->first();

        if (!$wooSettings?->is_active) {
            return back()->with('error', 'WooCommerce not connected. Go to Integrations > WooCommerce.');
        }

        $service = new WooCommerceService($wooSettings);
        $after   = $wooSettings->last_synced_at?->toIso8601String();
        $result  = $service->syncOrdersFromWoo($bizId, $after);

        $msg = "{$result['imported']} new orders imported.";
        if (!empty($result['errors'])) {
            $msg .= ' ' . count($result['errors']) . ' errors.';
        }

        return back()->with('success', $msg);
    }

    public function import(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
        // Mark as manually confirmed
        $order->update(['status' => 'confirmed']);
        return back()->with('success', 'Order confirmed!');
    }
}
