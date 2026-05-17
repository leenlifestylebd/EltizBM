<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

            DB::transaction(function () use ($po, $bizId) {
                $po->update(['status' => 'received']);

                // Increase stock for each item
                foreach ($po->items as $item) {
                    Product::where('id', $item->product_id)
                        ->where('business_id', $bizId)
                        ->increment('stock', $item->quantity);

                    StockAdjustment::create([
                        'business_id'     => $bizId,
                        'product_id'      => $item->product_id,
                        'created_by'      => auth()->id(),
                        'type'            => 'increase',
                        'quantity'        => $item->quantity,
                        'quantity_before' => Product::where('id', $item->product_id)->value('stock') - $item->quantity,
                        'quantity_after'  => Product::where('id', $item->product_id)->value('stock'),
                        'reason'          => 'Purchase Order: ' . $po->po_number,
                    ]);
                }
            });

            return back()->with('success', 'Purchase order marked as received. Stock updated!');
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

                // Update product cost price
                Product::where('id', $item['product_id'])
                    ->where('business_id', $bizId)
                    ->update(['cost_price' => $item['cost_price']]);
            }
        });

        return back()->with('success', 'Purchase order created!');
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
            'type'       => 'required|in:increase,decrease,damage,return,transfer',
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
    // STOCK TRANSFERS
    // =============================================
    public function transfers()
    {
        $bizId = auth()->user()->business_id;

        $products   = Product::where('business_id', $bizId)->orderBy('name')->get();
        $warehouses = Warehouse::where('business_id', $bizId)->orderBy('name')->get();
        $transfers  = StockAdjustment::where('business_id', $bizId)
            ->where('type', 'transfer')
            ->with('product')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('inventory.transfers', compact('products', 'warehouses', 'transfers'));
    }

    public function storeTransfer(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:1',
        ]);

        $bizId   = auth()->user()->business_id;
        $product = Product::where('id', $request->product_id)
            ->where('business_id', $bizId)
            ->firstOrFail();

        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Insufficient stock for transfer.');
        }

        $before = $product->stock;
        $after  = $before - $request->quantity;

        $product->update(['stock' => $after]);

        StockAdjustment::create([
            'business_id'     => $bizId,
            'product_id'      => $product->id,
            'warehouse_id'    => $request->from_warehouse_id ?: null,
            'created_by'      => auth()->id(),
            'type'            => 'transfer',
            'quantity'        => $request->quantity,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'reason'          => $request->reason ?? 'Warehouse transfer',
        ]);

        return back()->with('success', 'Transfer completed!');
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
