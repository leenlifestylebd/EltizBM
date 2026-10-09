<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    // =============================================
    // INDEX — list all return requests
    // =============================================
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $status = $request->status ?? '';

        $query = Order::where('business_id', $bizId)
            ->whereIn('status', ['return_requested', 'return_approved', 'return_received', 'refunded', 'returned'])
            ->with(['customer', 'items'])
            ->orderByDesc('updated_at');

        if ($status) {
            $query->where('status', $status);
        }

        $returns = $query->paginate(20);

        $counts = [
            'requested' => Order::where('business_id', $bizId)->where('status', 'return_requested')->count(),
            'approved'  => Order::where('business_id', $bizId)->where('status', 'return_approved')->count(),
            'received'  => Order::where('business_id', $bizId)->where('status', 'return_received')->count(),
            'refunded'  => Order::where('business_id', $bizId)->where('status', 'refunded')->count(),
        ];

        return view('returns.index', compact('returns', 'counts', 'status'));
    }

    // =============================================
    // REQUEST RETURN — customer/admin raises RMA
    // =============================================
    public function requestReturn(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        abort_if(in_array($order->status, ['return_requested', 'return_approved', 'return_received', 'refunded']), 422, 'Return already in progress.');

        $order->update([
            'status'       => 'return_requested',
            'return_reason'=> $request->reason,
        ]);

        return back()->with('success', 'Return request submitted for Order #' . $order->order_number);
    }

    // =============================================
    // APPROVE RETURN
    // =============================================
    public function approve(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
        abort_if($order->status !== 'return_requested', 422, 'Order is not in return_requested state.');

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $order->update([
            'status'       => 'return_approved',
            'return_notes' => $request->notes,
        ]);

        return back()->with('success', 'Return approved. Waiting for customer to ship back.');
    }

    // =============================================
    // MARK RECEIVED (physical goods returned)
    // =============================================
    public function receive(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
        abort_if($order->status !== 'return_approved', 422, 'Order must be approved before receiving.');

        $request->validate([
            'condition' => 'required|in:good,damaged,partial',
            'notes'     => 'nullable|string|max:500',
        ]);

        // Restore stock for good/partial returns. Quantity is aggregated per product
        // and applied through StockService so it is business-scoped, row-locked and
        // written to the stock_adjustments audit trail (the old raw increment was
        // none of those, and re-ran on every receive).
        $quantities = [];
        if (in_array($request->condition, ['good', 'partial'], true)) {
            foreach ($order->items as $item) {
                if (!$item->product_id) continue;
                $qty = $request->condition === 'partial'
                    ? (int) ceil($item->quantity / 2)
                    : (int) $item->quantity;
                $quantities[$item->product_id] = ($quantities[$item->product_id] ?? 0) + $qty;
            }
        }

        DB::transaction(function () use ($order, $request, $quantities) {
            $order->update([
                'status'            => 'return_received',
                'return_condition'  => $request->condition,
                'return_notes'      => ($order->return_notes ? $order->return_notes . "\n" : '') . 'Received: ' . $request->notes,
            ]);

            app(StockService::class)->restoreReturned(
                $order,
                $quantities,
                'Return received - Order #' . $order->order_number,
            );
        });

        return back()->with('success', 'Goods received. Stock restored. Ready to process refund.');
    }

    // =============================================
    // PROCESS REFUND
    // =============================================
    public function refund(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
        abort_if($order->status !== 'return_received', 422, 'Goods must be received before refunding.');

        $request->validate([
            'refund_amount'  => 'required|numeric|min:0.01|max:' . $order->paid_amount,
            'refund_method'  => 'required|string|max:50',
            'notes'          => 'nullable|string|max:500',
        ]);

        $orderDue = (float) $order->due_amount;
        $refund   = min((float) $request->refund_amount, (float) $order->paid_amount);

        DB::transaction(function () use ($request, $order, $refund, $orderDue) {
            // Record refund as expense transaction
            Transaction::create([
                'business_id'      => $order->business_id,
                'created_by'       => auth()->id(),
                'order_id'         => $order->id,
                'type'             => 'expense',
                'title'            => 'Refund for Order #' . $order->order_number,
                'amount'           => $refund,
                'payment_method'   => $request->refund_method,
                'transaction_date' => today(),
                'notes'            => $request->notes,
            ]);

            // A refunded order is closed: paid drops by the refund, and any remaining
            // due is cancelled (it is no longer collectible).
            $order->update([
                'status'         => 'refunded',
                'paid_amount'    => max(0, (float) $order->paid_amount - $refund),
                'due_amount'     => 0,
                'payment_status' => 'refunded',
                'return_notes'   => ($order->return_notes ? $order->return_notes . "\n" : '') . 'Refunded: ৳' . $refund . ' via ' . $request->refund_method,
            ]);

            // Clear the outstanding due this order was holding against the customer.
            if ($order->customer_id && $orderDue > 0) {
                $customer = \App\Models\Customer::where('business_id', $order->business_id)->find($order->customer_id);
                if ($customer && (float) $customer->due_amount > 0) {
                    $customer->decrement('due_amount', min($orderDue, (float) $customer->due_amount));
                }
            }
        });

        return back()->with('success', '৳' . number_format($refund, 2) . ' refund processed for Order #' . $order->order_number);
    }

    // =============================================
    // REJECT RETURN
    // =============================================
    public function reject(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Revert back to previous order status (completed/delivered)
        $order->update([
            'status'       => 'delivered',
            'return_notes' => 'Return rejected: ' . $request->reason,
        ]);

        return back()->with('success', 'Return request rejected.');
    }
}
