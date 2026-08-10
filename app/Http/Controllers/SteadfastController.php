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

    /**
     * Build the courier note from the order's item SKUs (e.g. "ABC123×2, DEF456×1").
     * The SKU goes into the Steadfast note field for picking/verification.
     */
    private function skuNote(Order $order): string
    {
        $order->loadMissing('items.product', 'items.variant');
        $parts = [];
        foreach ($order->items as $item) {
            $sku = $item->sku ?: (optional($item->variant)->sku ?: optional($item->product)->sku);
            if (!$sku) continue;
            $parts[] = $sku . '×' . $item->quantity;
        }
        return implode(', ', $parts);
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

    /**
     * Parcel delivered — COD collected: mark the order paid and book the income.
     * Idempotent, so webhook + manual sync can both call it safely.
     */
    private function applyDelivered(Shipment $shipment, ?float $codAmount = null, bool $partial = false): void
    {
        $order = $shipment->order;
        if (!$order || $order->status === 'delivered') return;

        // Partial delivery: only the courier knows how much was actually collected.
        // Without that figure, book nothing rather than assume the full total.
        if ($partial && $codAmount === null) return;

        $amount = $codAmount !== null ? (float) $codAmount : (float) $order->total;

        $order->update([
            'status'         => 'delivered',
            'paid_amount'    => $amount,
            'due_amount'     => 0,
            'payment_status' => $partial ? 'partial' : 'paid',
        ]);

        $this->addIncomeTransaction($shipment, $amount);
    }

    /**
     * Store the courier's ACTUAL delivery charge (it varies by weight/distance) and,
     * once delivered, book it as an expense so profit reflects the real courier cost.
     * Customer-paid shipping stays in the COD income; this is the cost side.
     * Idempotent — safe for repeated webhook calls.
     */
    private function recordCourierCharge(Shipment $shipment, $charge, bool $bookExpense = false, string $label = 'Courier charge'): void
    {
        $amount = (float) $charge;
        if ($amount <= 0) return;

        if ((float) $shipment->charge !== $amount) {
            $shipment->update(['charge' => $amount]);
        }

        if (!$bookExpense) return;

        $reference = 'COURIER-' . $shipment->id;
        if (Transaction::where('business_id', $shipment->business_id)->where('reference', $reference)->exists()) {
            return;
        }

        Transaction::create([
            'business_id'      => $shipment->business_id,
            'created_by'       => auth()->id() ?? optional($shipment->order)->created_by,
            'order_id'         => $shipment->order_id,
            'type'             => 'expense',
            'title'            => $label . ' - Order #' . (optional($shipment->order)->order_number ?? $shipment->id),
            'amount'           => $amount,
            'payment_method'   => 'cod',
            'reference'        => $reference,
            'transaction_date' => today(),
        ]);
    }

    /**
     * Courier's COD service fee — a % the courier keeps out of the remittance
     * (charged on the collected COD after the delivery charge). The rate is set
     * per business in courier settings (config.cod_fee_percent). Idempotent.
     * Runs from the webhook too, so the business is taken from the shipment.
     */
    private function recordCodFee(Shipment $shipment, float $codCollected, float $deliveryCharge): void
    {
        $setting = CourierSetting::where('business_id', $shipment->business_id)
            ->where('provider', 'steadfast')->first();

        $pct = (float) ($setting->config['cod_fee_percent'] ?? 0);
        if ($pct <= 0 || $codCollected <= 0) return;

        $fee = round(max(0, $codCollected - $deliveryCharge) * $pct / 100, 2);
        if ($fee <= 0) return;

        $reference = 'CODFEE-' . $shipment->id;
        if (Transaction::where('business_id', $shipment->business_id)->where('reference', $reference)->exists()) {
            return;
        }

        Transaction::create([
            'business_id'      => $shipment->business_id,
            'created_by'       => auth()->id() ?? optional($shipment->order)->created_by,
            'order_id'         => $shipment->order_id,
            'type'             => 'expense',
            'title'            => 'COD fee (' . rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.') . '%) - Order #'
                                   . (optional($shipment->order)->order_number ?? $shipment->id),
            'amount'           => $fee,
            'payment_method'   => 'cod',
            'reference'        => $reference,
            'transaction_date' => today(),
        ]);
    }

    /**
     * Parcel cancelled/returned — put the stock back (warehouse-aware + logged)
     * and reverse any COD income already booked. Idempotent.
     */
    private function applyCancelled(Shipment $shipment): void
    {
        $order = $shipment->order;
        if (!$order || $order->status === 'returned') return;

        app(\App\Services\StockService::class)->restoreForOrder($order->load('items'));

        if ((float) $order->paid_amount > 0) {
            Transaction::create([
                'business_id'      => $shipment->business_id,
                'created_by'       => auth()->id() ?? $order->created_by,
                'order_id'         => $order->id,
                'type'             => 'expense',
                'title'            => 'Return/Cancel - Order #' . $order->order_number,
                'amount'           => $order->paid_amount,
                'payment_method'   => 'cod',
                'transaction_date' => today(),
            ]);
        }

        $order->update([
            'status'         => 'returned',
            'paid_amount'    => 0,
            'due_amount'     => 0,
            'payment_status' => 'refunded',
        ]);
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

        // Sending now happens from the Web Orders page; this panel only shows the
        // post-send shipments and their live status/tracking.
        return view('integrations.steadfast.index', compact(
            'isConnected', 'balance', 'shipments', 'shipmentCounts', 'statusFilter'
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
            'note'              => $this->skuNote($order),
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
            'note'              => $this->skuNote($order),
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
                $raw       = strtolower($result['delivery_status']);
                $newStatus = SteadfastService::mapStatus($raw);
                $shipment->update(['status' => $newStatus]);

                if (in_array($newStatus, ['delivered', 'partial_delivered'])) {
                    // Only the collected amount is booked for a partial delivery.
                    $this->applyDelivered($shipment, $result['cod_amount'] ?? null, $newStatus === 'partial_delivered');
                } elseif ($newStatus === 'cancelled') {
                    $this->applyCancelled($shipment);
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
            ->whereNotIn('status', ['delivered', 'partial_delivered', 'cancelled'])
            ->whereNotNull('tracking_id')
            ->with('order')
            ->get();

        $updated = 0;

        $failed = 0;

        foreach ($shipments as $shipment) {
            // One bad parcel (API hiccup, deleted order…) must not break the whole sync.
            try {
                $result = $service->statusByTrackingCode($shipment->tracking_id);

                if (!isset($result['delivery_status'])) {
                    continue;
                }

                $raw       = strtolower($result['delivery_status']);
                $newStatus = SteadfastService::mapStatus($raw);

                if ($newStatus !== $shipment->status) {
                    $shipment->update(['status' => $newStatus]);

                    if (in_array($newStatus, ['delivered', 'partial_delivered'])) {
                        // Only the collected amount is booked for a partial delivery.
                        $this->applyDelivered($shipment, $result['cod_amount'] ?? null, $newStatus === 'partial_delivered');
                    } elseif ($newStatus === 'cancelled') {
                        $this->applyCancelled($shipment);
                    }
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Steadfast sync failed for shipment ' . $shipment->id . ': ' . $e->getMessage());
            }
        }

        $msg = "✅ Synced! {$updated} shipments updated.";
        if ($failed) $msg .= " ({$failed} could not be checked.)";
        return back()->with('success', $msg);
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
        // Shared-secret gate. Set STEADFAST_WEBHOOK_SECRET in .env and append
        // ?secret=... (or send an X-Webhook-Secret header) on the Steadfast dashboard
        // URL. Read via config() so it survives config:cache (env() would return null
        // there and silently disable the check). When a secret IS configured it is
        // enforced; if it is not set we still process (opt-in), but log a warning so
        // the endpoint isn't left spoofable unknowingly.
        // Fail CLOSED: with no secret configured this endpoint is fully spoofable —
        // anyone could post a fake delivery_status and move stock, COD income and
        // courier expenses. Refuse to process until STEADFAST_WEBHOOK_SECRET is set.
        $expected = (string) config('services.steadfast.webhook_secret', '');
        if ($expected === '') {
            Log::error('Steadfast webhook REJECTED — STEADFAST_WEBHOOK_SECRET is not configured.');
            return response()->json(['status' => 'error', 'message' => 'Webhook not configured'], 503);
        }

        $provided = (string) ($request->header('X-Webhook-Secret') ?: $request->query('secret', ''));
        if ($provided === '' || !hash_equals($expected, $provided)) {
            Log::warning('Steadfast webhook: invalid or missing secret', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
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
                $newStatus   = SteadfastService::mapStatus(strtolower($payload['status']));
                $rawStatus   = strtolower($payload['status']);
                $isDelivered = $rawStatus === 'delivered';
                $isPartial   = $rawStatus === 'partial_delivered';
                $isCancelled = in_array($rawStatus, ['cancelled', 'unknown']);
                $shipment->update(['status' => $newStatus]);

                // Courier's real charge (weight/distance based). The courier bills it
                // for returns too, so book the expense on delivery AND on cancel.
                if (isset($payload['delivery_charge'])) {
                    $this->recordCourierCharge(
                        $shipment,
                        $payload['delivery_charge'],
                        $isDelivered || $isPartial || $isCancelled,
                        $isCancelled ? 'Courier return charge' : 'Courier charge'
                    );
                }

                // Partial delivery: cod_amount is the amount actually collected after
                // the customer kept only part of the order — book exactly that.
                if ($isDelivered || $isPartial) {
                    $shipment->update(['delivered_at' => now()]);
                    $this->applyDelivered($shipment, $payload['cod_amount'] ?? null, $isPartial);
                    $this->recordCodFee(
                        $shipment,
                        (float) ($payload['cod_amount'] ?? 0),
                        (float) ($payload['delivery_charge'] ?? 0)
                    );
                }

                if ($isCancelled) {
                    $this->applyCancelled($shipment);
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