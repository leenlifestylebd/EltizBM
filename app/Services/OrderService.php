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
            $subtotal = collect($data['items'])->sum('total');
            $discount = (float) ($data['discount'] ?? 0);
            $shipping = (float) ($data['shipping_charge'] ?? $data['shipping_cost'] ?? 0);
            $total    = $subtotal - $discount + $shipping;
            $paid     = (float) ($data['paid_amount'] ?? 0);
            $due      = max(0, $total - $paid);

            $payStatus = 'unpaid';
            if ($paid >= $total) $payStatus = 'paid';
            elseif ($paid > 0)   $payStatus = 'partial';

            $order = Order::create([
                'business_id'       => $bizId,
                'customer_id'       => $data['customer_id'] ?? null,
                'created_by'        => $data['created_by'] ?? auth()->id(),
                'order_number'      => 'ORD-' . strtoupper(Str::random(8)),
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

            $this->syncItems($order, $data['items'], deductStock: true);

            if ($paid > 0) {
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

            $this->updateCustomerTotals($order, $total, $due);

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
        $newPaid = min($order->total, $order->paid_amount + $amount);
        $newDue  = max(0, $order->total - $newPaid);
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
            'amount'           => $amount,
            'payment_method'   => $order->payment_method,
            'transaction_date' => today(),
        ]);

        if ($order->customer_id) {
            Customer::where('id', $order->customer_id)->decrement('due_amount', $amount);
        }
    }

    // =============================================
    // COD AUTO-COMPLETE (on delivery)
    // =============================================
    /**
     * When a COD order is marked delivered, auto-mark payment as paid.
     */
    public function completeCod(Order $order): void
    {
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
            'amount'           => $order->total,
            'payment_method'   => 'cod',
            'transaction_date' => today(),
        ]);
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
        // Restore stock
        foreach ($order->items as $item) {
            if ($item->product_id) {
                Product::where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
            }
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
    private function syncItems(Order $order, array $items, bool $deductStock = true): void
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

            if ($deductStock) {
                Product::where('id', $item['product_id'])
                    ->where('track_stock', true)
                    ->decrement('stock', $item['quantity']);
            }
        }
    }

    private function updateCustomerTotals(Order $order, float $total, float $due): void
    {
        if (!$order->customer_id) return;

        Customer::where('id', $order->customer_id)->increment('total_purchase', $total);

        if ($due > 0) {
            Customer::where('id', $order->customer_id)->increment('due_amount', $due);
        }
    }
}
