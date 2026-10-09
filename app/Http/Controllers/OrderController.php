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
use Illuminate\Validation\ValidationException;
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

        // Status counts for tabs — one grouped query instead of seven COUNTs.
        $counts = Order::where('business_id', $bizId)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $statusCounts = [
            'all'        => (int) $counts->sum(),
            'pending'    => (int) ($counts['pending']    ?? 0),
            'confirmed'  => (int) ($counts['confirmed']  ?? 0),
            'processing' => (int) ($counts['processing'] ?? 0),
            'shipped'    => (int) ($counts['shipped']    ?? 0),
            'delivered'  => (int) ($counts['delivered']  ?? 0),
            'cancelled'  => (int) ($counts['cancelled']  ?? 0),
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
return view('orders.form', compact('products', 'customers', 'cartItems') + $this->stockData($bizId));
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
        $order->load(['items.product', 'shipments', 'customer', 'warehouse']);
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
return view('orders.form', compact('order', 'products', 'customers', 'cartItems') + $this->stockData($bizId, $order));
    }

    // =============================================
    // UPDATE
    // =============================================
    public function update(Request $request, Order $order)
    {
        $this->authorize($order);

        $request->validate([
            'customer_name' => 'required|string|max:100',
            'status'        => 'required|in:pending,confirmed,processing,shipped,delivered,completed,cancelled,returned,refunded',
            'payment_method'=> 'required|in:cash,card,bkash,nagad,rocket,bank,cod,other',
            'paid_amount'   => 'nullable|numeric|min:0',
            'discount'      => 'nullable|numeric|min:0',
            'shipping_charge' => 'nullable|numeric|min:0',
            'items'                  => 'sometimes|array|min:1',
            'items.*.product_id'     => 'required_with:items|integer',
            'items.*.quantity'       => 'required_with:items|integer|min:1',
            'items.*.discount'       => 'nullable|numeric|min:0',
        ]);

        $bizId = $order->business_id;
        DB::transaction(function () use ($request, $order, $bizId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $oldTotal = (float) $order->total;
            $oldDue   = (float) $order->due_amount;
            // Discount & shipping fall back to the order's existing values.
            $discount = floatval($request->discount ?? $order->discount);
            $shipping = floatval($request->shipping_charge ?? $order->shipping_charge);

            // Re-create items from authoritative tenant catalog values if provided.
            if ($request->has('items')) {
                $subtotal = $this->orderService->replaceItems($order, $request->items);
                $order->update(['subtotal' => $subtotal]);
            }

            // Total is always derived server-side, never trusted from the client.
            // $order->subtotal reflects the new items (if provided) or the existing value.
            $discount = min($discount, (float) $order->subtotal);
            $total = max(0, $order->subtotal - $discount + $shipping);
            // Payments are changed only through collect/refund flows so ledger
            // transactions and customer balances cannot silently diverge.
            $paid = (float) $order->paid_amount;
            if ($paid > $total) {
                throw ValidationException::withMessages([
                    'total' => 'The revised total cannot be lower than the amount already paid. Refund the difference first.',
                ]);
            }
            $due      = max(0, $total - $paid);

            $payStatus = 'unpaid';
            if ($paid >= $total && $total > 0) $payStatus = 'paid';
            elseif ($paid > 0)                 $payStatus = 'partial';

            $order->update([
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

            if ($order->customer_id) {
                $customer = Customer::where('business_id', $bizId)->find($order->customer_id);
                if ($customer) {
                    $totalDelta = $total - $oldTotal;
                    $dueDelta   = $due - $oldDue;
                    if ($totalDelta > 0) $customer->increment('total_purchase', $totalDelta);
                    elseif ($totalDelta < 0) $customer->decrement('total_purchase', abs($totalDelta));
                    if ($dueDelta > 0) $customer->increment('due_amount', $dueDelta);
                    elseif ($dueDelta < 0) $customer->decrement('due_amount', abs($dueDelta));
                }
            }

            $this->applyStatusTransition($order, $request->status);
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

        DB::transaction(function () use ($order, $status) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->applyStatusTransition($locked, $status);
        });

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

        // Never erase an order that already has money booked against it — deleting it
        // would silently remove revenue/expense from the books with no offsetting entry
        // and leave the customer ledger inconsistent. Such orders must be cancelled or
        // refunded instead, which keeps the financial trail intact.
        if (Transaction::withTrashed()->where('order_id', $order->id)->exists()) {
            return back()->with('error', 'This order has financial transactions. Cancel or refund it instead of deleting, so the books stay balanced.');
        }

        $this->logActivity('deleted', $order, [
            'order_number' => $order->order_number,
            'total'        => $order->total,
            'status'       => $order->status,
        ]);

        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Return stock to the shelf only for orders that still hold it and whose
            // goods have not already shipped — a delivered/completed order's stock is
            // gone and must not reappear.
            if ($order->holdsStock() && !in_array($order->status, ['shipped', 'delivered', 'completed'], true)) {
                app(\App\Services\StockService::class)->restoreForOrder($order);
            }

            // Roll back everything this order added to the customer ledger (create()
            // incremented both total_purchase and due_amount).
            if ($order->customer_id) {
                $customer = Customer::where('business_id', $order->business_id)->find($order->customer_id);
                if ($customer) {
                    if ($order->due_amount > 0) {
                        $customer->decrement('due_amount', min((float) $order->due_amount, (float) $customer->due_amount));
                    }
                    if ($order->total > 0) {
                        $customer->decrement('total_purchase', min((float) $order->total, (float) $customer->total_purchase));
                    }
                }
            }

            $order->delete();
        });

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
    private function stockData(int $bizId, ?Order $order = null): array
    {
        $stockProducts = Product::where('business_id', $bizId)->get(['id', 'track_stock', 'stock'])->keyBy('id');
        $heldStock = $order && $order->holdsStock()
            ? $order->items->groupBy('product_id')->map(fn ($items) => (int) $items->sum('quantity'))
            : collect();

        return compact('stockProducts', 'heldStock');
    }

    private function authorize(Order $order): void
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
    }

    private function applyStatusTransition(Order $order, string $status): void
    {
        $previous = $order->status;
        if ($status === $previous) return;

        $terminal = ['cancelled', 'returned', 'refunded'];
        if (in_array($previous, $terminal, true) && !in_array($status, $terminal, true)) {
            throw ValidationException::withMessages([
                'status' => 'A cancelled, returned, or refunded order cannot be reopened automatically.',
            ]);
        }

        if (in_array($status, $terminal, true) && !in_array($previous, $terminal, true)) {
            $this->orderService->cancel($order, $status);
            return;
        }
        $order->update(['status' => $status]);

        if ($status === 'confirmed') {
            $this->orderService->notifyConfirmed($order, auth()->user()->business->name);
        }
        if ($status === 'delivered' && $order->payment_method === 'cod' && $order->payment_status !== 'paid') {
            $this->orderService->completeCod($order);
        }
    }
}
