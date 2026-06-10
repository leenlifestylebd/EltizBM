<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Jobs\SendSmsJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

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
    public function store(StoreOrderRequest $request)
    {
        $bizId = auth()->user()->business_id;
        $order = $this->orderService->create($request->validated() + ['created_by' => auth()->id()], $bizId, 'manual');

        if ($request->status === 'confirmed') {
            $this->orderService->notifyConfirmed($order, auth()->user()->business->name);
        }

        return redirect()->route('orders.index')->with('success', 'Order placed successfully!');
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

        $bizId = $order->business_id;
        $stock = app(\App\Services\StockService::class);

        DB::transaction(function () use ($request, $order, $bizId, $stock) {
            // Discount & shipping fall back to the order's existing values.
            $discount = floatval($request->discount ?? $order->discount);
            $shipping = floatval($request->shipping_charge ?? $order->shipping_charge);

            // Re-create items if provided, and derive subtotal from them.
            if ($request->has('items')) {
                // Restore stock for the OLD item set (scoped + logged).
                $stock->restoreForOrder($order);

                $order->items()->delete();

                $subtotal = 0;
                foreach ($request->items as $item) {
                    // Only link catalog products owned by this business.
                    $product = !empty($item['product_id'])
                        ? Product::where('id', $item['product_id'])->where('business_id', $bizId)->first()
                        : null;

                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $product?->id,
                        'product_name' => $item['product_name'],
                        'price'        => $item['price'],
                        'cost_price'   => $item['cost_price'] ?? 0,
                        'quantity'     => $item['quantity'],
                        'discount'     => $item['discount'] ?? 0,
                        'total'        => $item['total'],
                    ]);
                    $subtotal += floatval($item['total']);
                }

                $order->update(['subtotal' => $subtotal]);
                // Deduct stock for the NEW item set.
                $order->load('items');
                $stock->deductForOrder($order);
            }

            // Total is always derived server-side, never trusted from the client.
            // $order->subtotal reflects the new items (if provided) or the existing value.
            $total = max(0, $order->subtotal - $discount + $shipping);
            $paid     = floatval($request->paid_amount ?? $order->paid_amount);
            $due      = max(0, $total - $paid);

            $payStatus = 'unpaid';
            if ($paid >= $total && $total > 0) $payStatus = 'paid';
            elseif ($paid > 0)                 $payStatus = 'partial';

            $order->update([
                'status'           => $request->status,
                'payment_status'   => $payStatus,
                'payment_method'   => $request->payment_method,
                'customer_name'    => $request->customer_name,
                'customer_phone'   => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_area'    => $request->shipping_area,
                'shipping_district'=> $request->shipping_district,
                'discount'         => $discount,
                'shipping_charge'  => $shipping,
                'total'            => $total,
                'paid_amount'      => $paid,
                'due_amount'       => $due,
                'notes'            => $request->notes,
            ]);
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

        $bizId    = auth()->user()->business_id;
        $bizName  = auth()->user()->business->name;

        if ($request->action === 'collect_payment') {
            $amount = floatval($request->collect_amount ?? 0);
            if ($amount <= 0) {
                return back()->with('error', 'Enter a valid amount to collect.');
            }
            $amount = min($amount, (float) $order->due_amount);
            if ($amount <= 0) {
                return back()->with('error', 'This order has no outstanding due.');
            }
            $this->orderService->collectPayment($order, $amount);
            $this->orderService->notifyPaymentReceived($order, $amount, $bizName);
            return back()->with('success', "৳{$amount} collected!");
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,completed,cancelled,returned,refunded',
        ]);

        $status   = $request->status;
        $previous = $order->status;

        // No-op if the status hasn't actually changed — prevents duplicate income,
        // double stock restore, and repeat notifications from re-clicking a status.
        if ($status === $previous) {
            return back()->with('success', 'Order status unchanged.');
        }

        $cancelStates = ['cancelled', 'returned', 'refunded'];
        $order->update(['status' => $status]);

        if ($status === 'confirmed') {
            $this->orderService->notifyConfirmed($order, $bizName);
        }

        // COD auto-complete only when transitioning INTO delivered and not yet paid.
        if ($status === 'delivered' && $order->payment_method === 'cod' && $order->payment_status !== 'paid') {
            $this->orderService->completeCod($order);
        }

        // Cancel/return side effects only when moving from a non-cancelled state.
        if (in_array($status, $cancelStates) && !in_array($previous, $cancelStates)) {
            $this->orderService->cancel($order, $status);
        }

        return back()->with('success', 'Order status updated!');
    }

    // =============================================
    // INVOICE
    // =============================================
    public function invoice(Order $order)
    {
        $this->authorize($order);
        $order->load(['items.product', 'customer', 'shipments']);
        $business = auth()->user()->business;
        return view('orders.invoice', compact('order', 'business'));
    }

    public function downloadPdf(Order $order)
    {
        $this->authorize($order);
        $order->load(['items.product', 'customer', 'shipments']);
        $business = auth()->user()->business;

        $pdf = Pdf::loadView('orders.invoice-pdf', compact('order', 'business'))
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'sans-serif')
            ->setOption('isRemoteEnabled', true);

        return $pdf->download("Invoice-{$order->order_number}.pdf");
    }

    public function receipt(Order $order)
    {
        $this->authorize($order);
        $order->load(['items.product', 'customer']);
        $business = auth()->user()->business;
        return view('orders.receipt', compact('order', 'business'));
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
    // EXPORT CSV
    // =============================================
    public function export(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $query = Order::where('business_id', $bizId)->orderByDesc('created_at');
        if ($status = $request->status) $query->where('status', $status);
        if ($from = $request->date_from) $query->whereDate('created_at', '>=', $from);
        if ($to   = $request->date_to)   $query->whereDate('created_at', '<=', $to);

        $orders   = $query->get();
        $filename = 'orders-' . date('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order #', 'Date', 'Customer', 'Phone', 'Address', 'Status', 'Payment Status', 'Payment Method', 'Total', 'Paid', 'Due']);
            foreach ($orders as $o) {
                fputcsv($handle, [
                    $o->order_number,
                    $o->created_at->format('Y-m-d H:i'),
                    $o->customer_name,
                    $o->customer_phone,
                    trim($o->shipping_address.' '.$o->shipping_area.' '.$o->shipping_district),
                    $o->status,
                    $o->payment_status,
                    $o->payment_method,
                    $o->total,
                    $o->paid_amount,
                    $o->due_amount,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =============================================
    private function authorize(Order $order): void
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
    }
}
