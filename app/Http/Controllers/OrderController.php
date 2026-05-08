<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // =============================================
    // INDEX
    // =============================================
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $query = Order::where('business_id', $bizId)
            ->orderByDesc('created_at');

        // Status filter
        if ($status = $request->status) {
            $query->where('status', $status);
        }

        // Search
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Date filter
        if ($from = $request->date_from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->date_to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->paginate(20);

        // Status counts for tabs
        $statusCounts = [
            'all'        => Order::where('business_id', $bizId)->count(),
            'pending'    => Order::where('business_id', $bizId)->where('status', 'pending')->count(),
            'confirmed'  => Order::where('business_id', $bizId)->where('status', 'confirmed')->count(),
            'processing' => Order::where('business_id', $bizId)->where('status', 'processing')->count(),
            'shipped'    => Order::where('business_id', $bizId)->where('status', 'shipped')->count(),
            'delivered'  => Order::where('business_id', $bizId)->where('status', 'delivered')->count(),
            'cancelled'  => Order::where('business_id', $bizId)->where('status', 'cancelled')->count(),
        ];

        return view('orders.index', compact('orders', 'statusCounts'));
    }

    // =============================================
    // CREATE
    // =============================================
    public function create()
{
    $bizId     = auth()->user()->business_id;
    $products  = \App\Models\Product::where('business_id', $bizId)->where('is_active', true)->get();
    $customers = \App\Models\Customer::where('business_id', $bizId)->orderBy('name')->get();
    $cartItems = [];
    return view('orders.form', compact('products', 'customers', 'cartItems'));
}
    // =============================================
    // STORE
    // =============================================
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'items'         => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.total'        => 'required|numeric|min:0',
            'status'        => 'required|string',
            'payment_method'=> 'required|string',
            'paid_amount'   => 'nullable|numeric|min:0',
            'total'         => 'required|numeric|min:0',
        ]);

        $bizId = auth()->user()->business_id;

        DB::transaction(function () use ($request, $bizId) {

            $subtotal   = collect($request->items)->sum('total');
            $discount   = floatval($request->discount ?? 0);
            $shipping   = floatval($request->shipping_charge ?? 0);
            $total      = $subtotal - $discount + $shipping;
            $paid       = floatval($request->paid_amount ?? 0);
            $due        = max(0, $total - $paid);

            // Determine payment status
            $payStatus = 'unpaid';
            if ($paid >= $total) $payStatus = 'paid';
            elseif ($paid > 0)   $payStatus = 'partial';

            // Create order
            $order = Order::create([
                'business_id'      => $bizId,
                'customer_id'      => $request->customer_id ?: null,
                'created_by'       => auth()->id(),
                'order_number'     => 'ORD-' . strtoupper(Str::random(8)),
                'source'           => 'manual',
                'status'           => $request->status,
                'payment_status'   => $payStatus,
                'payment_method'   => $request->payment_method,
                'customer_name'    => $request->customer_name,
                'customer_phone'   => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_area'    => $request->shipping_area,
                'shipping_district'=> $request->shipping_district,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'shipping_charge'  => $shipping,
                'total'            => $total,
                'paid_amount'      => $paid,
                'due_amount'       => $due,
                'notes'            => $request->notes,
            ]);

            // Create order items + deduct stock
            foreach ($request->items as $item) {
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

                // Deduct stock
                Product::where('id', $item['product_id'])
                    ->where('track_stock', true)
                    ->decrement('stock', $item['quantity']);
            }

            // Auto-create income transaction if paid
            if ($paid > 0) {
                Transaction::create([
                    'business_id'      => $bizId,
                    'created_by'       => auth()->id(),
                    'order_id'         => $order->id,
                    'type'             => 'income',
                    'title'            => 'Payment for Order #' . $order->order_number,
                    'amount'           => $paid,
                    'payment_method'   => $request->payment_method,
                    'transaction_date' => today(),
                ]);
            }

            // Update customer totals
            if ($request->customer_id) {
                Customer::where('id', $request->customer_id)->increment('total_purchase', $total);
                if ($due > 0) {
                    Customer::where('id', $request->customer_id)->increment('due_amount', $due);
                }
            }

            return $order;
        });

        return redirect()->route('orders.index')
            ->with('success', 'Order placed successfully!');
    }

    // =============================================
    // SHOW
    // =============================================
    public function show(Order $order)
    {
        $this->authorize($order);
        $order->load(['items.product', 'shipments', 'customer']);
        $business = auth()->user()->business;
        return view('orders.show', compact('order', 'business'));
    }

    // =============================================
    // EDIT
    // =============================================
   public function edit(Order $order)
{
    abort_if($order->business_id !== auth()->user()->business_id, 403);
    $bizId     = auth()->user()->business_id;
    $products  = \App\Models\Product::where('business_id', $bizId)->where('is_active', true)->get();
    $customers = \App\Models\Customer::where('business_id', $bizId)->orderBy('name')->get();
    $cartItems = $order->items->map(function($i) {
        return [
            'product_id' => $i->product_id,
            'name'       => $i->product_name,
            'price'      => (float) $i->price,
            'cost_price' => (float) $i->cost_price,
            'qty'        => (int) $i->quantity,
            'unit'       => 'pcs',
            'discount'   => (float) $i->discount,
        ];
    })->values()->toArray();
    return view('orders.form', compact('order', 'products', 'customers', 'cartItems'));
}

    // =============================================
    // UPDATE
    // =============================================
    public function update(Request $request, Order $order)
    {
        $this->authorize($order);

        $request->validate([
            'customer_name' => 'required|string|max:100',
            'status'        => 'required|string',
            'payment_method'=> 'required|string',
        ]);

        DB::transaction(function () use ($request, $order) {
            $paid = floatval($request->paid_amount ?? 0);
            $total = floatval($request->total ?? $order->total);
            $due  = max(0, $total - $paid);

            $payStatus = 'unpaid';
            if ($paid >= $total) $payStatus = 'paid';
            elseif ($paid > 0)   $payStatus = 'partial';

            $order->update([
                'status'           => $request->status,
                'payment_status'   => $payStatus,
                'payment_method'   => $request->payment_method,
                'customer_name'    => $request->customer_name,
                'customer_phone'   => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_area'    => $request->shipping_area,
                'shipping_district'=> $request->shipping_district,
                'paid_amount'      => $paid,
                'due_amount'       => $due,
                'notes'            => $request->notes,
            ]);

            // Re-create items if provided
            if ($request->has('items')) {
                // Restore old stock
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)
                        ->where('track_stock', true)
                        ->increment('stock', $item->quantity);
                }

                $order->items()->delete();

                $subtotal = 0;
                foreach ($request->items as $item) {
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
                    $subtotal += $item['total'];

                    // Deduct new stock
                    Product::where('id', $item['product_id'])
                        ->where('track_stock', true)
                        ->decrement('stock', $item['quantity']);
                }

                $order->update(['subtotal' => $subtotal]);
            }
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order updated!');
    }

    // =============================================
    // UPDATE STATUS (quick update)
    // =============================================
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize($order);

        if ($request->action === 'collect_payment') {
            $amount = floatval($request->collect_amount ?? 0);
            $newPaid = min($order->total, $order->paid_amount + $amount);
            $newDue  = max(0, $order->total - $newPaid);

            $payStatus = $newDue <= 0 ? 'paid' : 'partial';

            $order->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => $newDue,
                'payment_status' => $payStatus,
            ]);

            // Record transaction
            Transaction::create([
                'business_id'      => auth()->user()->business_id,
                'created_by'       => auth()->id(),
                'order_id'         => $order->id,
                'type'             => 'income',
                'title'            => 'Payment collected for Order #' . $order->order_number,
                'amount'           => $amount,
                'payment_method'   => $order->payment_method,
                'transaction_date' => today(),
            ]);

            // Update customer due
            if ($order->customer_id) {
                Customer::where('id', $order->customer_id)->decrement('due_amount', $amount);
            }

            return back()->with('success', "৳{$amount} collected!");
        }

        if ($status = $request->status) {
            $order->update(['status' => $status]);

            // Auto-complete: mark payment as paid if delivered+COD
            if ($status === 'delivered' && $order->payment_method === 'cod') {
                $order->update([
                    'paid_amount'    => $order->total,
                    'due_amount'     => 0,
                    'payment_status' => 'paid',
                ]);
            }
        }

        return back()->with('success', 'Order status updated!');
    }

    // =============================================
    // INVOICE
    // =============================================
    public function invoice(Order $order)
    {
        $this->authorize($order);
        $order->load(['items.product', 'shipments']);
        $business = auth()->user()->business;
        return view('orders.invoice', compact('order', 'business'));
    }

    public function printInvoice(Order $order)
    {
        return $this->invoice($order);
    }

    // =============================================
    // DESTROY
    // =============================================
    public function destroy(Order $order)
    {
        $this->authorize($order);

        // Restore stock
        foreach ($order->items as $item) {
            Product::where('id', $item->product_id)
                ->where('track_stock', true)
                ->increment('stock', $item->quantity);
        }

        $order->delete();

        return redirect()->route('orders.index')
            ->with('success', 'Order deleted.');
    }

    // =============================================
    private function authorize(Order $order): void
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
    }
}
