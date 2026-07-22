<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\Transaction;
use App\Models\CourierSetting;
use App\Services\SteadfastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SteadfastController extends Controller
{
    private function getService(): ?SteadfastService
    {
        $setting = CourierSetting::where('business_id', auth()->user()->business_id)
            ->where('provider', 'steadfast')
            ->where('is_active', true)
            ->first();

        if (!$setting || !$setting->api_key || !$setting->api_secret) {
            return null;
        }

        return new SteadfastService($setting->api_key, $setting->api_secret);
    }

    private function addIncomeTransaction(Shipment $shipment, float $amount): void
    {
        // Check if transaction already exists for this order
        $exists = Transaction::where('order_id', $shipment->order_id)
            ->where('type', 'income')
            ->where('payment_method', 'cod')
            ->exists();

        if (!$exists) {
            Transaction::create([
                'business_id'      => $shipment->business_id,
                'created_by'       => auth()->id() ?? $shipment->order->created_by,
                'order_id'         => $shipment->order_id,
                'type'             => 'income',
                'title'            => 'COD collected - Order #' . $shipment->order->order_number,
                'amount'           => $amount,
                'payment_method'   => 'cod',
                'transaction_date' => today(),
            ]);
        }
    }

    public function index()
    {
        $bizId   = auth()->user()->business_id;
        $service = $this->getService();

        $balance     = null;
        $isConnected = $service !== null;

        if ($service) {
            $balanceRes = $service->getBalance();
            $balance    = $balanceRes['current_balance'] ?? null;
        }

        $statusFilter   = request('shipment_status', '');
        $shipmentsQuery = Shipment::where('business_id', $bizId)
            ->where('courier_name', 'Steadfast')
            ->with(['order.items.product'])
            ->orderByDesc('created_at');

        if ($statusFilter) {
            $shipmentsQuery->where('status', $statusFilter);
        }

        $shipments = $shipmentsQuery->paginate(20);

        $shipmentCounts = [
            ''                  => Shipment::where('business_id', $bizId)->where('courier_name', 'Steadfast')->count(),
            'pending'           => Shipment::where('business_id', $bizId)->where('courier_name', 'Steadfast')->where('status', 'pending')->count(),
            'approval_pending'  => Shipment::where('business_id', $bizId)->where('courier_name', 'Steadfast')->where('status', 'approval_pending')->count(),
            'delivered'         => Shipment::where('business_id', $bizId)->where('courier_name', 'Steadfast')->where('status', 'delivered')->count(),
            'partial_delivered' => Shipment::where('business_id', $bizId)->where('courier_name', 'Steadfast')->where('status', 'partial_delivered')->count(),
            'cancelled'         => Shipment::where('business_id', $bizId)->where('courier_name', 'Steadfast')->where('status', 'cancelled')->count(),
            'in_review'         => Shipment::where('business_id', $bizId)->where('courier_name', 'Steadfast')->where('status', 'in_review')->count(),
        ];

        $readyOrders = Order::where('business_id', $bizId)
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->whereNotIn('source', ['pos'])
            ->whereDoesntHave('shipments', fn($q) => $q->where('courier_name', 'Steadfast'))
            ->with('customer')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('integrations.steadfast.index', compact(
            'isConnected', 'balance', 'shipments', 'readyOrders', 'shipmentCounts', 'statusFilter'
        ));
    }

    public function sendOrder(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);

        $service = $this->getService();
        if (!$service) {
            return back()->with('error', 'Steadfast is not connected.');
        }

        $response = $service->placeOrder([
            'invoice'           => $order->order_number,
            'recipient_name'    => $order->customer_name,
            'recipient_phone'   => $order->customer_phone,
            'recipient_address' => trim(implode(', ', array_filter([
                $order->shipping_address,
                $order->shipping_area,
                $order->shipping_district,
            ]))),
            'cod_amount'        => $order->due_amount > 0 ? $order->due_amount : 0,
            'note'              => $order->notes,
        ]);

        if (($response['status'] ?? 0) === 200) {
            $consignment = $response['consignment'];
            \Illuminate\Support\Facades\Log::info('Steadfast response', ['consignment' => $consignment]);

            Shipment::create([
                'business_id'    => auth()->user()->business_id,
                'order_id'       => $order->id,
                'courier_name'   => 'Steadfast',
                'tracking_id'    => $consignment['tracking_code'],
                'consignment_id' => $consignment['consignment_id'],
                'status'         => 'pending',
                'charge'         => 0,
                'cod_amount'     => $consignment['cod_amount'] ?? 0,
                'notes'          => 'Consignment ID: ' . $consignment['consignment_id'],
                'shipped_at'     => now(),
            ]);

            $order->update(['status' => 'shipped']);

            return back()->with('success', "✅ Sent! Tracking: {$consignment['tracking_code']} | Consignment: {$consignment['consignment_id']}");
        }

        return back()->with('error', '❌ Failed: ' . ($response['message'] ?? 'Unknown error'));
    }

    public function bulkSend(Request $request)
    {
        $request->validate(['order_ids' => 'required|array|min:1|max:500']);

        $bizId   = auth()->user()->business_id;
        $service = $this->getService();

        if (!$service) {
            return back()->with('error', 'Steadfast is not connected.');
        }

        $orders = Order::where('business_id', $bizId)
            ->whereIn('id', $request->order_ids)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No orders found.');
        }

        $bulkData = $orders->map(fn($order) => [
            'invoice'           => $order->order_number,
            'recipient_name'    => $order->customer_name,
            'recipient_phone'   => $order->customer_phone ?? '01000000000',
            'recipient_address' => trim(implode(', ', array_filter([
                $order->shipping_address,
                $order->shipping_area,
                $order->shipping_district,
            ]))) ?: 'N/A',
            'cod_amount'        => $order->due_amount > 0 ? $order->due_amount : 0,
            'note'              => $order->notes,
        ])->toArray();

        $results = $service->bulkCreate($bulkData);

        $success = 0;
        $failed  = 0;

        foreach ($results as $result) {
            if (($result['status'] ?? '') === 'success' && $result['consignment_id']) {
                $order = $orders->firstWhere('order_number', $result['invoice']);
                if ($order) {
                    Shipment::create([
                        'business_id'    => $bizId,
                        'order_id'       => $order->id,
                        'courier_name'   => 'Steadfast',
                        'tracking_id'    => $result['tracking_code'],
                        'consignment_id' => $result['consignment_id'],
                        'status'         => 'pending',
                        'charge'         => 0,
                        'cod_amount'     => $result['cod_amount'] ?? 0,
                        'notes'          => 'Consignment ID: ' . $result['consignment_id'],
                        'shipped_at'     => now(),
                    ]);
                    $order->update(['status' => 'shipped']);
                    $success++;
                }
            } else {
                $failed++;
            }
        }

        $msg = "✅ {$success} orders sent!";
        if ($failed > 0) $msg .= " ❌ {$failed} failed.";

        return back()->with('success', $msg);
    }

    public function checkStatus(Request $request)
    {
        $service = $this->getService();
        if (!$service) {
            return response()->json(['error' => 'Not connected'], 400);
        }

        $type  = $request->type ?? 'tracking';
        $value = $request->value;

        $result = match($type) {
            'consignment' => $service->statusByConsignmentId($value),
            'invoice'     => $service->statusByInvoice($value),
            default       => $service->statusByTrackingCode($value),
        };

        if (isset($result['delivery_status'])) {
            $shipment = Shipment::where('courier_name', 'Steadfast')
                ->where(fn($q) => $q->where('tracking_id', $value)->orWhere('consignment_id', $value))
                ->where('business_id', auth()->user()->business_id)
                ->first();

            if ($shipment) {
                $newStatus = SteadfastService::mapStatus($result['delivery_status']);
                $shipment->update(['status' => $newStatus]);

                if ($newStatus === 'delivered') {
                    $amount = $shipment->order->total;
                    $shipment->order->update([
                        'status'         => 'delivered',
                        'paid_amount'    => $amount,
                        'due_amount'     => 0,
                        'payment_status' => 'paid',
                    ]);
                    $this->addIncomeTransaction($shipment, $amount);
                }
            }
        }

        return response()->json($result);
    }

    public function syncStatus()
    {
        $bizId   = auth()->user()->business_id;
        $service = $this->getService();

        if (!$service) {
            return back()->with('error', 'Steadfast is not connected.');
        }

        $shipments = Shipment::where('business_id', $bizId)
            ->where('courier_name', 'Steadfast')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->whereNotNull('tracking_id')
            ->with('order')
            ->get();

        $updated = 0;

        foreach ($shipments as $shipment) {
            $result = $service->statusByTrackingCode($shipment->tracking_id);

            if (isset($result['delivery_status'])) {
                $newStatus = SteadfastService::mapStatus($result['delivery_status']);

                if ($newStatus !== $shipment->status) {
                    $shipment->update(['status' => $newStatus]);

                    if ($newStatus === 'delivered') {
                        $amount = $shipment->order->total;
                        $shipment->order->update([
                            'status'         => 'delivered',
                            'paid_amount'    => $amount,
                            'due_amount'     => 0,
                            'payment_status' => 'paid',
                        ]);
                        $this->addIncomeTransaction($shipment, $amount);
                    }
                    $updated++;
                }
            }
        }

        return back()->with('success', "✅ Synced! {$updated} shipments updated.");
    }

    public function printLabel(Shipment $shipment)
    {
        abort_if($shipment->business_id !== auth()->user()->business_id, 403);
        $shipment->load(['order.items.product', 'order.items.variant', 'order.business']);
        $business = auth()->user()->business;
        return view('integrations.steadfast.label', compact('shipment', 'business'));
    }

    public function printLabels(Request $request)
    {
        $request->validate(['shipment_ids' => 'required|array']);
        $bizId     = auth()->user()->business_id;
        $shipments = Shipment::whereIn('id', $request->shipment_ids)
            ->where('business_id', $bizId)
            ->with(['order.items.product', 'order.items.variant', 'order.business'])
            ->get();
        $business = auth()->user()->business;
        return view('integrations.steadfast.labels', compact('shipments', 'business'));
    }

    public function printInvoice(Shipment $shipment)
    {
        abort_if($shipment->business_id !== auth()->user()->business_id, 403);
        $shipment->load(['order.items.product', 'order.business']);
        $business = auth()->user()->business;
        return view('integrations.steadfast.invoice', compact('shipment', 'business'));
    }

    public function webhook(Request $request)
    {
        // Shared-secret gate. Configure STEADFAST_WEBHOOK_SECRET in .env and append
        // ?secret=... (or send X-Webhook-Secret header) on the Steadfast dashboard URL.
        // If no secret is configured yet, we log a warning but still process so the
        // existing live integration is not broken mid-flight.
        $expected = env('STEADFAST_WEBHOOK_SECRET');
        if ($expected) {
            $provided = $request->header('X-Webhook-Secret') ?: $request->query('secret');
            if (!$provided || !hash_equals($expected, $provided)) {
                Log::warning('Steadfast webhook: invalid or missing secret');
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
        } else {
            Log::warning('Steadfast webhook processed WITHOUT a configured secret. Set STEADFAST_WEBHOOK_SECRET to secure this endpoint.');
        }

        $payload = $request->all();
        Log::info('Steadfast webhook received', $payload);

        if (!isset($payload['notification_type'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        if ($payload['notification_type'] === 'delivery_status') {
            if (!isset($payload['consignment_id'], $payload['status'])) {
                return response()->json(['status' => 'error', 'message' => 'Invalid consignment ID'], 400);
            }

            $shipment = Shipment::where('courier_name', 'Steadfast')
                ->where(fn($q) => $q->where('consignment_id', $payload['consignment_id'])
                    ->orWhere('notes', 'LIKE', '%' . $payload['consignment_id'] . '%'))
                ->with('order')
                ->first();

            if (!$shipment && isset($payload['invoice'])) {
                $order = Order::where('order_number', $payload['invoice'])->first();
                if ($order) {
                    $shipment = Shipment::where('order_id', $order->id)
                        ->where('courier_name', 'Steadfast')
                        ->with('order')
                        ->first();
                }
            }

            if ($shipment) {
                $newStatus = SteadfastService::mapStatus(strtolower($payload['status']));
                $shipment->update(['status' => $newStatus]);

                if (strtolower($payload['status']) === 'delivered') {
                    $shipment->update(['delivered_at' => now()]);
                    $codAmount = $payload['cod_amount'] ?? $shipment->order->total;
                    $shipment->order->update([
                        'status'         => 'delivered',
                        'paid_amount'    => $codAmount,
                        'due_amount'     => 0,
                        'payment_status' => 'paid',
                    ]);
                    $this->addIncomeTransaction($shipment, $codAmount);
                }

                if (in_array(strtolower($payload['status']), ['cancelled', 'unknown'])) {
                    // Restore stock
                    foreach ($shipment->order->items as $item) {
                        if ($item->product_id) {
                            \App\Models\Product::where('id', $item->product_id)
                                ->increment('stock', $item->quantity);
                        }
                    }
                    // Reverse income if already paid
                    if ($shipment->order->paid_amount > 0) {
                        Transaction::create([
                            'business_id'      => $shipment->business_id,
                            'created_by'       => auth()->id() ?? $shipment->order->created_by,
                            'order_id'         => $shipment->order_id,
                            'type'             => 'expense',
                            'title'            => 'Return/Cancel - Order #' . $shipment->order->order_number,
                            'amount'           => $shipment->order->paid_amount,
                            'payment_method'   => 'cod',
                            'transaction_date' => today(),
                        ]);
                    }
                    $shipment->order->update([
                        'status'         => 'returned',
                        'paid_amount'    => 0,
                        'due_amount'     => 0,
                        'payment_status' => 'refunded',
                    ]);
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook received.'], 200);
    }

    // =============================================
    // FRAUD CHECK — AJAX (called from order form)
    // =============================================
    public function checkPhone(Request $request)
    {
        $phone = preg_replace('/\D/', '', $request->input('phone', ''));

        if (strlen($phone) < 10) {
            return response()->json(['found' => false, 'error' => 'Invalid phone number'], 422);
        }

        $service = $this->getService();
        if (!$service) {
            return response()->json(['found' => false, 'error' => 'Steadfast not connected'], 503);
        }

        $result = $service->fraudCheck($phone);
        return response()->json($result);
    }

    public function getBalance()
    {
        $service = $this->getService();
        if (!$service) {
            return response()->json(['error' => 'Not connected'], 400);
        }
        return response()->json($service->getBalance());
    }
}