<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockAdjustment;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Employee;
use App\Models\ProductionTask;
use App\Models\ProductionRate;
use App\Models\EmployeeNotification;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    // =============================================
    // OVERVIEW
    // =============================================
    public function overview()
    {
        $bizId = auth()->user()->business_id;

        $stats = [
            'total'       => Product::where('business_id', $bizId)->count(),
            'low_stock'   => Product::where('business_id', $bizId)->whereRaw('stock > 0 AND stock <= low_stock_alert')->count(),
            'out_of_stock'=> Product::where('business_id', $bizId)->where('stock', '<=', 0)->count(),
            'total_value' => Product::where('business_id', $bizId)->selectRaw('SUM(stock * cost_price) as val')->value('val') ?? 0,
        ];

        $lowStockProducts = Product::where('business_id', $bizId)
            ->whereRaw('stock <= low_stock_alert')
            ->orderBy('stock')
            ->limit(10)
            ->get();

        $recentAdjustments = StockAdjustment::where('business_id', $bizId)
            ->with('product')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('inventory.overview', compact('stats', 'lowStockProducts', 'recentAdjustments'));
    }

    // =============================================
    // STOCK LEVELS
    // =============================================
    public function stockLevels(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $query = Product::where('business_id', $bizId)->with('category')->orderBy('name');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        match ($request->filter) {
            'low'  => $query->whereRaw('stock > 0 AND stock <= low_stock_alert'),
            'out'  => $query->where('stock', '<=', 0),
            'in'   => $query->whereRaw('stock > low_stock_alert'),
            default=> null,
        };

        $products = $query->paginate(30);

        return view('inventory.stock-levels', compact('products'));
    }

    // =============================================
    // CUTTING LIST — production requirement to reach minimum stock
    // =============================================
    private function shortfallProducts(int $bizId, ?string $search = null)
    {
        $query = Product::where('business_id', $bizId)
            ->where('low_stock_alert', '>', 0)
            ->whereColumn('stock', '<', 'low_stock_alert')
            ->with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->get()
            ->map(function ($p) {
                // How many more pieces must be produced to reach the minimum.
                $p->shortfall = max(0, (int) $p->low_stock_alert - (int) $p->stock);
                return $p;
            })
            ->sortByDesc('shortfall')
            ->values();
    }

    public function cuttingList(Request $request)
    {
        $bizId    = auth()->user()->business_id;
        $products = $this->shortfallProducts($bizId, $request->search);

        $totalShortfall = $products->sum('shortfall');
        $totalProducts  = $products->count();

        // For the "Send to Cutting" assign modal
        $cuttingWorkers = Employee::where('business_id', $bizId)
            ->where('status', 'active')->orderBy('name')->get();
        $cuttingRates = ProductionRate::where('business_id', $bizId)
            ->where('stage', 'cutting')->where('is_active', true)
            ->orderBy('product_name')->get();

        return view('inventory.cutting-list', compact(
            'products', 'totalShortfall', 'totalProducts', 'cuttingWorkers', 'cuttingRates'
        ));
    }

    /**
     * Create cutting-stage production tasks for the selected low-stock products,
     * assigned to one cutting worker. Quantity per product = the shortfall
     * (minimum − current stock), recomputed server-side. Entry point of the
     * Cutting → Sewing → Iron pipeline.
     */
    public function sendToCutting(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $request->validate([
            'product_ids'        => 'required|array|min:1',
            'product_ids.*'      => 'integer',
            'employee_id'        => ['required', Rule::exists('employees', 'id')->where('business_id', $bizId)],
            'production_rate_id' => ['nullable', Rule::exists('production_rates', 'id')->where('business_id', $bizId)->where('stage', 'cutting')->where('is_active', true)],
        ]);

        $worker = Employee::where('business_id', $bizId)->findOrFail($request->employee_id);

        $products = Product::where('business_id', $bizId)
            ->whereIn('id', $request->product_ids)
            ->where('low_stock_alert', '>', 0)
            ->get();

        $created = 0;
        foreach ($products as $p) {
            $qty = max(0, (int) $p->low_stock_alert - (int) $p->stock);
            if ($qty <= 0) continue;

            ProductionTask::assignWithCurrentRate([
                'business_id'        => $bizId,
                'employee_id'        => $worker->id,
                'assigned_by'        => auth()->id(),
                'production_rate_id' => $request->production_rate_id ?: null,
                'stage'              => 'cutting',
                'product_name'       => $p->name,
                'assigned_quantity'  => $qty,
                'completed_quantity' => 0,
                'status'             => 'assigned',
                'assigned_at'        => now(),
                'notes'              => 'From cutting list — SKU: ' . ($p->sku ?? '—'),
            ]);
            $created++;
        }

        if ($created === 0) {
            return back()->with('error', 'No cutting tasks created — selected products are already at their minimum stock.');
        }

        EmployeeNotification::notify(
            $bizId, $worker->id, 'task',
            'নতুন কাটিং কাজ পেয়েছেন',
            $created . 'টি product কাটিং-এর জন্য assign করা হয়েছে',
            route('production.worker.dashboard')
        );

        return redirect()->route('production.pipeline')
            ->with('success', "{$created} cutting task(s) assigned to {$worker->name}.");
    }

    public function exportCuttingList(Request $request)
    {
        $bizId    = auth()->user()->business_id;
        $products = $this->shortfallProducts($bizId, $request->search);

        $filename = 'cutting-list-' . date('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Product', 'Category', 'Current Stock', 'Minimum Qty', 'To Produce']);
            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->sku, $p->name, $p->category?->name,
                    $p->stock, $p->low_stock_alert, $p->shortfall,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =============================================
    // PURCHASE ORDERS
    // =============================================
    public function purchaseOrders(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $purchaseOrders = PurchaseOrder::where('business_id', $bizId)
            ->with('vendor')
            ->orderByDesc('created_at')
            ->paginate(20);

        $vendors     = Vendor::where('business_id', $bizId)->orderBy('name')->get();
        $allProducts = Product::where('business_id', $bizId)->orderBy('name')->get();

        return view('inventory.purchase-orders', compact('purchaseOrders', 'vendors', 'allProducts'));
    }

    public function storePurchaseOrder(Request $request)
    {
        $bizId = auth()->user()->business_id;

        // Mark as received
        if ($request->action === 'mark_received' && $request->po_id) {
            $po = PurchaseOrder::where('id', $request->po_id)
                ->where('business_id', $bizId)
                ->firstOrFail();

            // Idempotency guard — receiving an already-received PO would double the
            // stock. Only a PO that has not yet been received can add stock.
            if ($po->status === 'received') {
                return back()->with('error', 'This purchase order is already received.');
            }

            $stock = app(StockService::class);

            DB::transaction(function () use ($po, $bizId, $stock) {
                $po->update(['status' => 'received']);

                foreach ($po->items as $item) {
                    $stock->addStock(
                        bizId:    $bizId,
                        productId: $item->product_id,
                        qty:      $item->quantity,
                        reason:   'Purchase Order: ' . $po->po_number,
                    );
                    $item->update(['received_quantity' => $item->quantity]);

                    // Recognise cost only when goods are actually received, scoped to
                    // this tenant's product (a draft PO must not revalue inventory).
                    Product::where('id', $item->product_id)
                        ->where('business_id', $bizId)
                        ->update(['cost_price' => $item->cost_price]);
                }
            });

            return back()->with('success', 'Purchase order received. Stock added!');
        }

        // Create new PO
        $request->validate([
            'items'         => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $bizId) {
            $total = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['cost_price']);

            $po = PurchaseOrder::create([
                'business_id' => $bizId,
                'vendor_id'   => $request->vendor_id ?: null,
                'created_by'  => auth()->id(),
                'po_number'   => 'PO-' . strtoupper(Str::random(8)),
                'status'      => $request->status ?? 'draft',
                'total'       => $total,
                'notes'       => $request->notes,
                'expected_date' => $request->expected_date,
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'cost_price'        => $item['cost_price'],
                    'total'             => $item['quantity'] * $item['cost_price'],
                ]);
            }
        });

        return back()->with('success', 'Purchase order created!');
    }

    // =============================================
    // VENDOR PAYMENT (against a purchase order)
    // =============================================
    public function payPurchaseOrder(Request $request, PurchaseOrder $po)
    {
        abort_if($po->business_id !== auth()->user()->business_id, 403);

        $outstanding = max(0, (float) $po->total - (float) $po->paid_amount);

        $request->validate([
            'amount'         => "required|numeric|min:0.01|max:{$outstanding}",
            'payment_method' => 'required|string|max:30',
            'payment_date'   => 'required|date',
        ]);

        if ($outstanding <= 0) {
            return back()->with('error', 'This purchase order is already fully paid.');
        }

        $bizId  = auth()->user()->business_id;
        $amount = (float) $request->amount;

        DB::transaction(function () use ($po, $bizId, $amount, $request) {
            $newPaid   = (float) $po->paid_amount + $amount;
            $payStatus = $newPaid >= (float) $po->total ? 'paid' : 'partial';

            $po->update([
                'paid_amount'    => $newPaid,
                'payment_status' => $payStatus,
            ]);

            // Record the cash going out so it shows in expenses / account balance.
            \App\Models\Transaction::create([
                'business_id'      => $bizId,
                'created_by'       => auth()->id(),
                'type'             => 'expense',
                'title'            => 'Purchase payment — ' . $po->po_number . ($po->vendor ? ' (' . $po->vendor->name . ')' : ''),
                'amount'           => $amount,
                'payment_method'   => $request->payment_method,
                'reference'        => 'PO-PAY-' . $po->id,
                'transaction_date' => $request->payment_date,
            ]);
        });

        return back()->with('success', '৳' . number_format($amount) . ' paid against ' . $po->po_number . '.');
    }

    // =============================================
    // STOCK ADJUSTMENTS
    // =============================================
    public function adjustments()
    {
        $bizId = auth()->user()->business_id;

        $products = Product::where('business_id', $bizId)
            ->orderBy('name')
            ->get();

        $adjustments = StockAdjustment::where('business_id', $bizId)
            ->with('product')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('inventory.adjustments', compact('products', 'adjustments'));
    }

    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'type'       => 'required|in:increase,decrease,damage,return',
            'quantity'   => 'required|integer|min:1',
        ]);

        $bizId   = auth()->user()->business_id;
        $product = Product::where('id', $request->product_id)
            ->where('business_id', $bizId)
            ->firstOrFail();

        $before = $product->stock;

        // Handle "new_stock" direct set (from stock levels inline edit)
        if ($request->has('new_stock') && $request->has('source') && $request->source === 'stock_levels') {
            $newStock = max(0, intval($request->new_stock));
            $diff     = $newStock - $before;
            $type     = $diff >= 0 ? 'increase' : 'decrease';
            $qty      = abs($diff);

            if ($qty === 0) return back()->with('success', 'No change needed.');

            $product->update(['stock' => $newStock]);

            StockAdjustment::create([
                'business_id'    => $bizId,
                'product_id'     => $product->id,
                'created_by'     => auth()->id(),
                'type'           => $type,
                'quantity'       => $qty,
                'quantity_before'=> $before,
                'quantity_after' => $newStock,
                'reason'         => $request->reason ?? 'Stock level correction',
            ]);

            return back()->with('success', "Stock updated to {$newStock}.");
        }

        $qty   = intval($request->quantity);
        $after = match($request->type) {
            'increase', 'return' => $before + $qty,
            'decrease', 'damage' => max(0, $before - $qty),
            'transfer'           => max(0, $before - $qty),
            default              => $before,
        };

        $product->update(['stock' => $after]);

        StockAdjustment::create([
            'business_id'    => $bizId,
            'product_id'     => $product->id,
            'created_by'     => auth()->id(),
            'type'           => $request->type,
            'quantity'       => $qty,
            'quantity_before'=> $before,
            'quantity_after' => $after,
            'reason'         => $request->reason,
        ]);

        return back()->with('success', 'Stock adjusted successfully!');
    }

    /**
     * Quick +/- stock adjust from the stock-levels list. Applies a delta, logs a
     * StockAdjustment row, and returns JSON so the row updates without a reload —
     * lets the user run down the whole list without opening each product.
     */
    public function quickAdjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'delta'      => 'required|integer|not_in:0',
        ]);

        $bizId   = auth()->user()->business_id;
        $product = Product::where('id', $request->product_id)
            ->where('business_id', $bizId)
            ->firstOrFail();

        $before = (int) $product->stock;
        $after  = max(0, $before + (int) $request->delta);
        $diff   = $after - $before; // real change after clamping at 0

        if ($diff !== 0) {
            $product->update(['stock' => $after]);

            StockAdjustment::create([
                'business_id'     => $bizId,
                'product_id'      => $product->id,
                'created_by'      => auth()->id(),
                'type'            => $diff > 0 ? 'increase' : 'decrease',
                'quantity'        => abs($diff),
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'reason'          => $request->reason ?: 'Quick adjust',
            ]);
        }

        return response()->json([
            'stock'   => $after,
            'changed' => $diff !== 0,
            'low'     => $product->isLowStock(),
            'out'     => $after <= 0,
        ]);
    }

    // =============================================
    // BARCODE PRINT
    // =============================================
    public function barcode()
    {
        $bizId    = auth()->user()->business_id;
        $products = Product::where('business_id', $bizId)
            ->orderBy('name')
            ->select('id', 'name', 'sku', 'barcode', 'price', 'images')
            ->get();

        return view('inventory.barcode', compact('products'));
    }

    public function printBarcode(Request $request)
    {
        // Handled on frontend via JS barcode library
        return response()->json(['success' => true]);
    }

    // =============================================
    // EXPORT CSV
    // =============================================
    public function exportStock()
    {
        $bizId    = auth()->user()->business_id;
        $products = Product::where('business_id', $bizId)
            ->with(['category', 'brand'])
            ->orderBy('name')
            ->get();

        $filename = 'stock-' . date('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Name', 'Category', 'Brand', 'Stock', 'Unit', 'Cost Price', 'Stock Value', 'Low Stock Alert', 'Status']);
            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->sku, $p->name,
                    $p->category?->name, $p->brand?->name,
                    $p->stock, $p->unit,
                    $p->cost_price,
                    number_format($p->stock * $p->cost_price, 2),
                    $p->low_stock_alert,
                    $p->isOutOfStock() ? 'Out of Stock' : ($p->isLowStock() ? 'Low Stock' : 'In Stock'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
