<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Jobs\SyncWooCommerceStock;
use App\Models\WoocommerceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosController extends Controller
{
    // =============================================
    // POS PAGE
    // =============================================
    public function index()
    {
        $bizId = auth()->user()->business_id;

        // Load all active products with necessary fields for POS
        $products = Product::where('business_id', $bizId)
            ->where('is_active', true)
            ->where('type', 'simple') // POS handles simple products only
            ->select('id','name','sku','barcode','price','sale_price','cost_price','stock','images','unit','category_id')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'sku'         => $p->sku,
                'barcode'     => $p->barcode,
                'price'       => floatval($p->sale_price ?? $p->price),
                'cost_price'  => floatval($p->cost_price),
                'stock'       => $p->stock,
                'unit'        => $p->unit,
                'category_id' => $p->category_id,
                'image'       => $p->images ? \Illuminate\Support\Facades\Storage::url($p->images[0]) : null,
            ]);

        $categories = Category::where('business_id', $bizId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pos.index', compact('products', 'categories'));
    }

    // =============================================
    // CHECKOUT (AJAX)
    // =============================================
    public function checkout(Request $request)
    {
        // Only product/quantity come from the client; prices, totals, stock checks and
        // the customer ledger are all resolved server-side by OrderService.
        $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.discount'   => 'nullable|numeric|min:0',
            'payment_method'   => 'required|string',
            'paid_amount'      => 'nullable|numeric|min:0',
        ]);

        $bizId = auth()->user()->business_id;

        try {
            $order = app(\App\Services\OrderService::class)->create([
                'customer_id'     => $request->customer_id ?: null,
                'created_by'      => auth()->id(),
                'order_number'    => 'POS-' . strtoupper(Str::random(7)),
                'status'          => 'completed',
                'payment_method'  => $request->payment_method,
                'customer_name'   => $request->customer_name ?? 'Walk-in Customer',
                'customer_phone'  => $request->customer_phone,
                'discount'        => $request->discount ?? 0,
                'shipping_charge' => $request->shipping_charge ?? 0,
                'paid_amount'     => $request->filled('paid_amount') ? $request->paid_amount : null,
                'pay_in_full'     => !$request->filled('paid_amount'),
                'items'           => $request->items,
            ], $bizId, 'pos');

            // Push stock to WooCommerce in background if connected
            $wooSettings = WoocommerceSetting::where('business_id', $bizId)
                ->where('is_active', true)
                ->where('sync_stock', true)
                ->first();

            if ($wooSettings) {
                SyncWooCommerceStock::dispatch($bizId)->onQueue('low');
            }

            return response()->json([
                'success'      => true,
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'message'      => 'Sale completed!',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // =============================================
    // RECEIPT
    // =============================================
    public function receipt(Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
        $order->load('items');
        $business = auth()->user()->business;
        return view('pos.receipt', compact('order', 'business'));
    }

    // =============================================
    // BARCODE SCAN (AJAX)
    // =============================================
    public function scanBarcode(Request $request)
    {
        $bizId   = auth()->user()->business_id;
        $barcode = $request->barcode;

        $product = Product::where('business_id', $bizId)
            ->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)
                  ->orWhere('sku', $barcode);
            })
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json(['product' => null, 'message' => 'Product not found']);
        }

        return response()->json([
            'product' => [
                'id'          => $product->id,
                'name'        => $product->name,
                'sku'         => $product->sku,
                'barcode'     => $product->barcode,
                'price'       => floatval($product->sale_price ?? $product->price),
                'cost_price'  => floatval($product->cost_price),
                'stock'       => $product->stock,
                'unit'        => $product->unit,
                'category_id' => $product->category_id,
                'image'       => $product->images ? \Illuminate\Support\Facades\Storage::url($product->images[0]) : null,
            ]
        ]);
    }
}
