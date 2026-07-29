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

        // Wholesale channel reuses this list, filtered to source=wholesale.
        $wholesale = $request->routeIs('wholesale.*');

        $query = Order::where('business_id', $bizId)
            ->when($wholesale, fn ($q) => $q->where('source', 'wholesale'))
            ->with(['shipments' => fn ($q) => $q->where('courier_name', 'Steadfast')])
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
            ->when($wholesale, fn ($q) => $q->where('source', 'wholesale'))
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

        // Steadfast connection — enables the inline "Send to Steadfast" button.
        $isConnected = \App\Models\CourierSetting::where('business_id', $bizId)
            ->where('provider', 'steadfast')->where('is_active', true)
            ->whereNotNull('api_key')->whereNotNull('api_secret')->exists();

        return view('orders.index', compact('orders', 'statusCounts', 'wholesale', 'isConnected'));
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
    $wholesale = request()->routeIs('wholesale.*');
    return view('orders.form', compact('products', 'customers', 'cartItems', 'wholesale'));
}
    // =============================================
    // STORE
    // =============================================
    public function store(StoreOrderRequest $request)
    {
        $bizId = auth()->user()->business_id;
        // Only manual and wholesale channels create orders through this form.
        $source = $request->input('source') === 'wholesale' ? 'wholesale' : 'manual';
        $order = $this->orderService->create($request->validated() + ['created_by' => auth()->id()], $bizId, $source);

        if ($request->status === 'confirmed') {
            $this->orderService->notifyConfirmed($order, auth()->user()->business->name);
        }

        $redirect = $source === 'wholesale' ? 'wholesale.index' : 'orders.index';
        return redirect()->route($redirect)->with('success', ucfirst($source) . ' order placed successfully!');
    }

    // =============================================
    // SHOW
    // =============================================
    public function show(Order $order)
    {
        $this->authorize($order);
        $order->load(['items.product', 'shipments', 'customer']);
        $business = auth()->user()->business;
        $isConnected = \App\Models\CourierSetting::where('business_id', $order->business_id)
            ->where('provider', 'steadfast')->where('is_active', true)
            ->whereNotNull('api_key')->whereNotNull('api_secret')->exists();
        return view('orders.show', compact('order', 'business', 'isConnected'));
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
    /**
     * Partial delivery: the courier tells us how much was collected but not WHICH
     * items came back, so the admin picks them here and we return that stock.
     */
    public function partialReturn(Request $request, Order $order)
    {
        $this->authorize($order);

        $request->validate([
            'returns'   => 'required|array',
            'returns.*' => 'nullable|integer|min:0',
        ]);

        $order->load('items');

        // Never accept more than what was ordered for a line.
        $lines = [];
        foreach ($order->items as $item) {
            if (!$item->product_id) continue;
            $qty = (int) ($request->input("returns.{$item->id}") ?? 0);
            if ($qty <= 0) continue;
            $lines[$item->product_id] = ($lines[$item->product_id] ?? 0) + min($qty, (int) $item->quantity);
        }

        if (empty($lines)) {
            return back()->with('error', 'Enter at least one returned quantity.');
        }

        $restored = app(\App\Services\StockService::class)->restorePartial($order, $lines);

        return back()->with('success', "✅ {$restored} item(s) returned to stock.");
    }

    /**
     * Wholesale exchange: return some items from a past order and buy new ones in one
     * go. Returned stock is restored (at the price it was originally sold), and a NEW
     * net order is created where the return value is applied as a credit (discount),
     * so the customer only pays (new items − returned value). Revenue stays correct
     * because the new order's total is already the net.
     */
    public function exchangeForm(Order $order)
    {
        $this->authorize($order);
        $order->load('items');
        $bizId    = $order->business_id;
        $products = \App\Models\Product::where('business_id', $bizId)->where('is_active', true)->get();
        return view('orders.exchange', compact('order', 'products'));
    }

    public function exchangeStore(Request $request, Order $order)
    {
        $this->authorize($order);

        $request->validate([
            'returns'              => 'array',
            'returns.*'            => 'nullable|integer|min:0',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.total'        => 'required|numeric|min:0',
            'paid_amount'          => 'nullable|numeric|min:0',
            'payment_method'       => 'required|string|max:50',
        ]);

        $order->load('items');
        $bizId = $order->business_id;

        // 1) Returned lines + credit at the ORIGINAL sold price.
        $returnLines  = [];   // product_id => qty
        $returnCredit = 0.0;
        $returnedQty  = 0;
        foreach ($order->items as $it) {
            if (!$it->product_id) continue;
            $q = (int) ($request->input("returns.{$it->id}") ?? 0);
            if ($q <= 0) continue;
            $q = min($q, (int) $it->quantity);
            $returnLines[$it->product_id] = ($returnLines[$it->product_id] ?? 0) + $q;
            $returnCredit += $q * (float) $it->price;
            $returnedQty  += $q;
        }

        $newSubtotal = collect($request->items)->sum('total');

        if ($returnCredit > $newSubtotal) {
            return back()->withInput()->with('error',
                'Return credit (৳' . number_format($returnCredit) . ') is more than the new purchase (৳'
                . number_format($newSubtotal) . '). Add more items or reduce returns.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $request, $bizId, $returnLines, $returnCredit, $returnedQty) {
            // Restock the returned items (warehouse-aware + logged).
            if (!empty($returnLines)) {
                app(\App\Services\StockService::class)->restorePartial($order, $returnLines);
            }

            // Create the net new order — return credit rides in as the discount.
            $data                 = $request->all();
            $data['discount']     = $returnCredit;
            $data['created_by']   = auth()->id();
            $data['customer_id']  = $order->customer_id;
            $data['customer_name']= $order->customer_name;
            $data['customer_phone']= $order->customer_phone;
            $data['status']       = $request->status ?: 'confirmed';
            $data['notes']        = 'Exchange for Order #' . $order->order_number
                                    . ' — returned ' . $returnedQty . ' item(s), credit ৳' . number_format($returnCredit)
                                    . ($request->notes ? "\n" . $request->notes : '');

            $this->orderService->create($data, $bizId, $order->source === 'wholesale' ? 'wholesale' : 'manual');
        });

        return redirect()->route('orders.index')
            ->with('success', 'Exchange done — returned items restocked, net order created.');
    }

    public function update(Request $request, Order $order)
    {
        $this->authorize($order);

        $request->validate([
            'customer_name' => 'required|string|max:100',
            'status'        => 'required|string',
            'payment_method'=> 'required|string',
            'order_date'    => 'nullable|date|before_or_equal:today',
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

            // Allow correcting the order (sale) date, keeping clock time on that day.
            if ($request->filled('order_date')) {
                $newDate = \Illuminate\Support\Carbon::parse($request->order_date);
                $order->created_at = $newDate->copy()->setTimeFrom($order->created_at ?? now());
                $order->save();

                // Keep the linked income transactions on the same day so dashboard,
                // Accounts > Income and P&L all reflect the corrected sale date.
                \App\Models\Transaction::where('order_id', $order->id)
                    ->update(['transaction_date' => $newDate->toDateString()]);
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
            $request->validate(['collect_date' => 'nullable|date|before_or_equal:today']);
            $this->orderService->collectPayment($order, $amount, $request->collect_date);
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

        $this->logActivity('deleted', $order, [
            'order_number' => $order->order_number,
            'total'        => $order->total,
            'status'       => $order->status,
        ]);

        DB::transaction(function () use ($order) {
            // Restore stock (scoped + logged) only for orders that still hold stock —
            // cancelled/returned orders already had their stock restored.
            if (!in_array($order->status, ['cancelled', 'returned', 'refunded'])) {
                app(\App\Services\StockService::class)->restoreForOrder($order);
            }

            // Remove this order's money from the books: drop its linked income/expense
            // transactions and roll back any due it was holding against the customer.
            if ($order->customer_id && $order->due_amount > 0) {
                Customer::where('id', $order->customer_id)->decrement('due_amount', $order->due_amount);
            }
            Transaction::where('order_id', $order->id)->delete();

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
    private function authorize(Order $order): void
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
    }
}
