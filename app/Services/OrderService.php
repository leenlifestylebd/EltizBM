<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Mail\OrderConfirmed;
use App\Mail\PaymentReceived;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Centralised business logic for Order create / cancel / payment workflows.
 * Delegates from OrderController, PosController, SteadfastController
 * so each controller becomes a thin HTTP adapter.
 */
class OrderService
{
    // =============================================
    // CREATE
    // =============================================
    /**
     * Create a new order from a validated request (manual or POS).
     *
     * @param  array  $data  Validated order data (items, totals, customer info…)
     * @param  int    $bizId
     * @param  string $source  'manual' | 'pos' | 'woocommerce' etc.
     * @return Order
     */
    public function create(array $data, int $bizId, string $source = 'manual'): Order
    {
        return DB::transaction(function () use ($data, $bizId, $source) {
            $items    = $this->resolveItems($data['items'], $bizId);
            $subtotal = collect($items)->sum('total');
            $discount = (float) ($data['discount'] ?? 0);
            $shipping = (float) ($data['shipping_charge'] ?? $data['shipping_cost'] ?? 0);
            $total    = max(0, $subtotal - min($discount, $subtotal) + $shipping);

            // paid_amount may be omitted (POS defaults to paid-in-full) or supplied.
            $paidInput = $data['paid_amount'] ?? null;
            if ($paidInput === null) {
                $paidInput = !empty($data['pay_in_full']) ? $total : 0;
            }
            $paid = min($total, max(0, (float) $paidInput));
            $due  = max(0, $total - $paid);

            $payStatus = 'unpaid';
            if ($total > 0 && $paid >= $total) $payStatus = 'paid';
            elseif ($paid > 0)                 $payStatus = 'partial';

            $order = Order::create([
                'business_id'       => $bizId,
                'customer_id'       => $data['customer_id'] ?? null,
                'created_by'        => $data['created_by'] ?? auth()->id(),
                'order_number'      => $data['order_number'] ?? ('ORD-' . strtoupper(Str::random(8))),
                'source'            => $source,
                'status'            => $data['status'] ?? 'pending',
                'payment_status'    => $payStatus,
                'payment_method'    => $data['payment_method'] ?? 'cash',
                'customer_name'     => $data['customer_name'] ?? '',
                'customer_phone'    => $data['customer_phone'] ?? null,
                'shipping_address'  => $data['shipping_address'] ?? null,
                'shipping_area'     => $data['shipping_area'] ?? null,
                'shipping_district' => $data['shipping_district'] ?? null,
                'subtotal'          => $subtotal,
                'discount'          => $discount,
                'shipping_charge'   => $shipping,
                'total'             => $total,
                'paid_amount'       => $paid,
                'due_amount'        => $due,
                'notes'             => $data['notes'] ?? null,
            ]);

            $this->syncItems($order, $items);
            if ($order->holdsStock()) {
                app(StockService::class)->deductForOrder($order->load('items'));
            }

            // An order created already terminal (cancelled/returned/refunded) must not
            // book money or touch the customer ledger.
            if ($paid > 0 && $order->holdsStock()) {
                Transaction::create([
                    'business_id'      => $bizId,
                    'created_by'       => $data['created_by'] ?? auth()->id(),
                    'order_id'         => $order->id,
                    'type'             => 'income',
                    'title'            => 'Payment for Order #' . $order->order_number,
                    'amount'           => $paid,
                    'payment_method'   => $order->payment_method,
                    'transaction_date' => today(),
                ]);
            }

            if ($order->holdsStock()) {
                $this->updateCustomerTotals($order, $total, $due);
            }

            return $order;
        });
    }

    // =============================================
    // COLLECT PAYMENT
    // =============================================
    /**
     * Record a partial or full payment against an existing order.
     */
    public function collectPayment(Order $order, float $amount): void
    {
        DB::transaction(function () use ($order, $amount) {
            // Only the outstanding due can be collected; never over-collect or
            // over-decrement the customer ledger.
            $applied = min(max(0, $amount), (float) $order->due_amount);
            if ($applied <= 0) {
                return;
            }

            $newPaid = min((float) $order->total, (float) $order->paid_amount + $applied);
            $newDue  = max(0, (float) $order->total - $newPaid);
            $payStatus = $newDue <= 0 ? 'paid' : 'partial';

            $order->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => $newDue,
                'payment_status' => $payStatus,
            ]);

            Transaction::create([
                'business_id'      => $order->business_id,
                'created_by'       => auth()->id(),
                'order_id'         => $order->id,
                'type'             => 'income',
                'title'            => 'Payment collected for Order #' . $order->order_number,
                'amount'           => $applied,
                'payment_method'   => $order->payment_method,
                'transaction_date' => today(),
            ]);

            if ($order->customer_id) {
                $customer = Customer::where('business_id', $order->business_id)->find($order->customer_id);
                if ($customer && (float) $customer->due_amount > 0) {
                    $customer->decrement('due_amount', min($applied, (float) $customer->due_amount));
                }
            }
        });
    }

    // =============================================
    // COD AUTO-COMPLETE (on delivery)
    // =============================================
    /**
     * When a COD order is marked delivered, auto-mark payment as paid.
     */
    public function completeCod(Order $order): void
    {
        // Book only the amount still outstanding; any earlier partial payment already
        // created its own income entry, so using the full total would double-count.
        $outstanding = max(0, (float) $order->due_amount);
        if ($outstanding <= 0) {
            return;
        }

        $order->update([
            'paid_amount'    => $order->total,
            'due_amount'     => 0,
            'payment_status' => 'paid',
        ]);

        Transaction::create([
            'business_id'      => $order->business_id,
            'created_by'       => auth()->id(),
            'order_id'         => $order->id,
            'type'             => 'income',
            'title'            => 'COD collected for Order #' . $order->order_number,
            'amount'           => $outstanding,
            'payment_method'   => 'cod',
            'transaction_date' => today(),
        ]);

        // The customer's due for this order is now settled.
        if ($order->customer_id) {
            $customer = Customer::where('business_id', $order->business_id)->find($order->customer_id);
            if ($customer && (float) $customer->due_amount > 0) {
                $customer->decrement('due_amount', min($outstanding, (float) $customer->due_amount));
            }
        }
    }

    // =============================================
    // CANCEL / RETURN / REFUND
    // =============================================
    /**
     * Cancel/return an order: restore stock, record refund transaction,
     * update customer due.
     */
    public function cancel(Order $order, string $status = 'cancelled'): void
    {
        // Only restore stock the order still holds — an RMA that already put the goods
        // back (return_received) must not be restored a second time.
        if ($order->holdsStock()) {
            app(StockService::class)->restoreForOrder($order->load('items'));
        }

        // Refund transaction if already paid
        if ($order->paid_amount > 0) {
            Transaction::create([
                'business_id'      => $order->business_id,
                'created_by'       => auth()->id(),
                'order_id'         => $order->id,
                'type'             => 'expense',
                'title'            => 'Refund for Order #' . $order->order_number,
                'amount'           => $order->paid_amount,
                'payment_method'   => $order->payment_method,
                'transaction_date' => today(),
            ]);
        }

        // Clear customer due
        if ($order->customer_id && $order->due_amount > 0) {
            Customer::where('id', $order->customer_id)
                ->decrement('due_amount', $order->due_amount);
        }

        $order->update([
            'status'         => $status,
            'paid_amount'    => 0,
            'due_amount'     => 0,
            'payment_status' => 'refunded',
        ]);
    }

    // =============================================
    // SEND SMS NOTIFICATIONS
    // =============================================
    public function notifyConfirmed(Order $order, string $bizName): void
    {
        // SMS
        SendSmsJob::dispatch($order->business_id, 'order_confirmation', [
            'phone'         => $order->customer_phone,
            'customer_name' => $order->customer_name,
            'order_number'  => $order->order_number,
            'total'         => number_format($order->total),
            'business_name' => $bizName,
        ]);

        // Email (if customer has one)
        $this->sendOrderEmail($order, new OrderConfirmed($order));
    }

    public function notifyPaymentReceived(Order $order, float $amount, string $bizName): void
    {
        // SMS
        SendSmsJob::dispatch($order->business_id, 'payment_received', [
            'phone'         => $order->customer_phone,
            'order_number'  => $order->order_number,
            'amount'        => number_format($amount),
            'business_name' => $bizName,
        ]);

        // Email
        $this->sendOrderEmail($order, new PaymentReceived($order, $amount));
    }

    private function sendOrderEmail(Order $order, \Illuminate\Mail\Mailable $mailable): void
    {
        $email = $order->customer?->email;
        if (!$email) return;

        try {
            Mail::to($email)->queue($mailable);
        } catch (\Throwable $e) {
            // Never let email failure break the order flow
            Log::warning('Order email failed: ' . $e->getMessage(), ['order_id' => $order->id]);
        }
    }

    // =============================================
    // HELPERS
    // =============================================
    /**
     * Replace an order's lines using authoritative catalog values.
     * Must be called inside a database transaction.
     */
    public function replaceItems(Order $order, array $items): float
    {
        // Lock every affected product in a stable order before restoring/deducting
        // so concurrent edits cannot overwrite each other's stock calculation.
        $productIds = $order->items->pluck('product_id')
            ->merge(collect($items)->pluck('product_id'))
            ->filter()->unique()->sort()->values();
        Product::where('business_id', $order->business_id)
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($order->holdsStock()) {
            app(StockService::class)->restoreForOrder($order->load('items'));
        }
        $order->items()->delete();

        $resolved = $this->resolveItems($items, $order->business_id, $order->holdsStock());
        $this->syncItems($order, $resolved);
        if ($order->holdsStock()) {
            app(StockService::class)->deductForOrder($order->load('items'));
        }

        return (float) collect($resolved)->sum('total');
    }

    /** Resolve names, prices and costs from this tenant's catalog and lock stock rows. */
    private function resolveItems(array $items, int $businessId, bool $checkStock = true): array
    {
        // Aggregate demand per product first: two lines of the same product must be
        // validated against the combined total, otherwise the order oversells.
        $demand = collect($items)
            ->filter(fn ($item) => (int) ($item['product_id'] ?? 0) > 0)
            ->groupBy(fn ($item) => (int) $item['product_id'])
            ->map(fn ($rows) => (int) $rows->sum(fn ($item) => (int) ($item['quantity'] ?? 0)));

        return collect($items)->map(function (array $item, int $index) use ($businessId, $checkStock, $demand) {
            $product = Product::where('business_id', $businessId)
                ->whereKey($item['product_id'] ?? 0)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'The selected product is unavailable for this business.',
                ]);
            }

            $quantity = (int) ($item['quantity'] ?? 0);
            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Quantity must be at least 1.',
                ]);
            }

            $required = $demand[$product->id] ?? $quantity;
            if ($checkStock && $product->track_stock && (float) $product->stock < $required) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => "Only {$product->stock} unit(s) of {$product->name} are in stock.",
                ]);
            }

            $price    = (float) $product->current_price;
            $gross    = $price * $quantity;
            $discount = min(max(0, (float) ($item['discount'] ?? 0)), $gross);

            return [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'price'        => $price,
                'cost_price'   => (float) $product->cost_price,
                'quantity'     => $quantity,
                'discount'     => $discount,
                'total'        => $gross - $discount,
            ];
        })->all();
    }

    private function syncItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $item['product_id'],
                'product_name' => $item['product_name'],
                'price'        => $item['price'],
                'cost_price'   => $item['cost_price'] ?? 0,
                'quantity'     => $item['quantity'],
                'discount'     => $item['discount'] ?? 0,
                'total'        => $item['total'],
            ]);

        }
    }

    private function updateCustomerTotals(Order $order, float $total, float $due): void
    {
        if (!$order->customer_id) return;

        // Scope by business so a foreign customer_id can never touch another tenant.
        $customer = Customer::where('business_id', $order->business_id)->find($order->customer_id);
        if (!$customer) return;

        $customer->increment('total_purchase', $total);

        if ($due > 0) {
            $customer->increment('due_amount', $due);
        }
    }
}
