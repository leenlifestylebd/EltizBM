<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Read-broad / write-narrow API consumed by the SufiAI n8n agent.
 * Auth + business scoping handled by BotTokenMiddleware (business_id attribute).
 * ERP is the source of truth: prices and stock are always resolved server-side.
 */
class BotApiController extends Controller
{
    private function bizId(Request $request): int
    {
        return (int) $request->attributes->get('business_id');
    }

    public function health(Request $request)
    {
        return response()->json(['ok' => true, 'business_id' => $this->bizId($request), 'time' => now()->toIso8601String()]);
    }

    // ---------------- Catalog (read) ----------------

    public function products(Request $request)
    {
        $bizId = $this->bizId($request);

        $query = Product::with(['variants', 'category', 'brand'])
            ->where('business_id', $bizId)
            ->where('is_active', true);

        if ($s = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            });
        }
        if ($cat = trim((string) $request->query('category'))) {
            $query->whereHas('category', fn ($c) => $c->where('name', 'like', "%{$cat}%"));
        }

        $limit = min(max((int) $request->query('limit', 10), 1), 30);
        $products = $query->orderByDesc('id')->limit($limit)->get();

        return response()->json(['data' => $products->map(fn ($p) => $this->productPayload($p))->values()]);
    }

    public function product(Request $request, int $id)
    {
        $bizId = $this->bizId($request);
        $product = Product::with(['variants', 'category', 'brand'])
            ->where('business_id', $bizId)->find($id);

        if (! $product) {
            return response()->json(['error' => ['code' => 'not_found', 'message' => 'Product not found.']], 404);
        }

        return response()->json($this->productPayload($product));
    }

    public function stock(Request $request)
    {
        $bizId = $this->bizId($request);
        $productId = (int) $request->query('product_id');
        $variantId = (int) $request->query('variant_id');

        if ($variantId) {
            $variant = ProductVariant::whereHas('product', fn ($p) => $p->where('business_id', $bizId))->find($variantId);
            if (! $variant) {
                return response()->json(['error' => ['code' => 'not_found', 'message' => 'Variant not found.']], 404);
            }
            return response()->json([
                'variant_id' => $variant->id,
                'name'       => $variant->name,
                'attributes' => $variant->attributes,
                'stock'      => (int) $variant->stock,
                'in_stock'   => (int) $variant->stock > 0,
                'price'      => (float) ($variant->price ?: optional($variant->product)->current_price),
            ]);
        }

        $product = Product::where('business_id', $bizId)->find($productId);
        if (! $product) {
            return response()->json(['error' => ['code' => 'not_found', 'message' => 'Product not found.']], 404);
        }

        return response()->json([
            'product_id'  => $product->id,
            'name'        => $product->name,
            'track_stock' => (bool) $product->track_stock,
            'stock'       => (int) $product->stock,
            'in_stock'    => ! $product->track_stock || (int) $product->stock > 0,
            'price'       => (float) $product->current_price,
        ]);
    }

    // ---------------- Orders ----------------

    public function orderStatus(Request $request, string $orderNumber)
    {
        $bizId = $this->bizId($request);
        $order = Order::with('items')->where('business_id', $bizId)
            ->where('order_number', $orderNumber)->first();

        if (! $order) {
            return response()->json(['error' => ['code' => 'not_found', 'message' => 'Order not found.']], 404);
        }

        return response()->json($this->orderPayload($order));
    }

    public function storeOrder(Request $request)
    {
        $bizId = $this->bizId($request);

        $data = $request->validate([
            'customer.name'      => 'required|string|max:120',
            'customer.phone'     => 'required|string|max:20',
            'customer.address'   => 'nullable|string|max:255',
            'customer.area'      => 'nullable|string|max:120',
            'customer.district'  => 'nullable|string|max:120',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity'   => 'required|integer|min:1',
            'payment_method'     => 'nullable|string|max:30',
            'shipping_charge'    => 'nullable|numeric|min:0',
            'paid_amount'        => 'nullable|numeric|min:0',
            'source'             => 'nullable|string|max:30',
            'notes'              => 'nullable|string|max:1000',
        ]);

        // Resolve each line against ERP (authoritative price + stock).
        $lines = [];
        foreach ($data['items'] as $it) {
            $product = Product::where('business_id', $bizId)->find($it['product_id']);
            if (! $product) {
                return response()->json(['error' => ['code' => 'product_not_found', 'message' => "Product {$it['product_id']} not found."]], 422);
            }

            $variant = null;
            $price = (float) $product->current_price;
            $available = (int) $product->stock;

            if (! empty($it['variant_id'])) {
                $variant = ProductVariant::where('product_id', $product->id)->find($it['variant_id']);
                if (! $variant) {
                    return response()->json(['error' => ['code' => 'variant_not_found', 'message' => "Variant {$it['variant_id']} not found."]], 422);
                }
                if ($variant->price) {
                    $price = (float) $variant->price;
                }
                $available = (int) $variant->stock;
            }

            if ($product->track_stock && $available < (int) $it['quantity']) {
                return response()->json([
                    'error' => ['code' => 'out_of_stock', 'message' => "{$product->name}: not enough stock.", 'available' => $available],
                ], 409);
            }

            $lines[] = ['product' => $product, 'variant' => $variant, 'price' => $price, 'quantity' => (int) $it['quantity']];
        }

        $shipping = (float) ($data['shipping_charge'] ?? 0);
        $paid     = (float) ($data['paid_amount'] ?? 0);
        $source   = $data['source'] ?? 'messenger';
        $payMethod = $data['payment_method'] ?? 'cod';
        $createdBy = User::where('business_id', $bizId)->value('id'); // system attribution fallback

        $order = DB::transaction(function () use ($bizId, $data, $lines, $shipping, $paid, $source, $payMethod, $createdBy) {
            $customer = Customer::firstOrCreate(
                ['business_id' => $bizId, 'phone' => $data['customer']['phone']],
                [
                    'name'     => $data['customer']['name'],
                    'address'  => $data['customer']['address'] ?? null,
                    'area'     => $data['customer']['area'] ?? null,
                    'district' => $data['customer']['district'] ?? null,
                ]
            );

            $subtotal = collect($lines)->sum(fn ($l) => $l['price'] * $l['quantity']);
            $total    = $subtotal + $shipping;
            $due      = max(0, $total - $paid);
            $payStatus = ($total > 0 && $paid >= $total) ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            $order = Order::create([
                'business_id'      => $bizId,
                'customer_id'      => $customer->id,
                'created_by'       => $createdBy,
                'order_number'     => 'ORD-' . strtoupper(Str::random(8)),
                'source'           => $source,
                'status'           => 'pending',
                'payment_status'   => $payStatus,
                'payment_method'   => $payMethod,
                'customer_name'    => $data['customer']['name'],
                'customer_phone'   => $data['customer']['phone'],
                'shipping_address' => $data['customer']['address'] ?? null,
                'shipping_area'    => $data['customer']['area'] ?? null,
                'shipping_district' => $data['customer']['district'] ?? null,
                'subtotal'         => $subtotal,
                'discount'         => 0,
                'shipping_charge'  => $shipping,
                'total'            => $total,
                'paid_amount'      => $paid,
                'due_amount'       => $due,
                'notes'            => $data['notes'] ?? null,
            ]);

            foreach ($lines as $l) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $l['product']->id,
                    'variant_id'   => $l['variant']?->id,
                    'product_name' => $l['product']->name . ($l['variant'] ? ' - ' . $l['variant']->name : ''),
                    'sku'          => $l['variant']->sku ?? $l['product']->sku,
                    'price'        => $l['price'],
                    'cost_price'   => $l['product']->cost_price ?? 0,
                    'quantity'     => $l['quantity'],
                    'discount'     => 0,
                    'total'        => $l['price'] * $l['quantity'],
                ]);

                if ($l['product']->track_stock) {
                    if ($l['variant']) {
                        ProductVariant::where('id', $l['variant']->id)->decrement('stock', $l['quantity']);
                    } else {
                        Product::where('id', $l['product']->id)->decrement('stock', $l['quantity']);
                    }
                }
            }

            if ($paid > 0) {
                Transaction::create([
                    'business_id'      => $bizId,
                    'created_by'       => $createdBy,
                    'order_id'         => $order->id,
                    'type'             => 'income',
                    'title'            => 'Payment for Order #' . $order->order_number,
                    'amount'           => $paid,
                    'payment_method'   => $payMethod,
                    'transaction_date' => today(),
                ]);
            }

            Customer::where('id', $customer->id)->increment('total_purchase', $total);
            if ($due > 0) {
                Customer::where('id', $customer->id)->increment('due_amount', $due);
            }

            return $order;
        });

        $order->load('items');

        return response()->json($this->orderPayload($order), 201);
    }

    // ---------------- Reports ----------------

    public function reportsSummary(Request $request)
    {
        $bizId = $this->bizId($request);
        $base  = Order::where('business_id', $bizId)->whereDate('created_at', today());

        return response()->json([
            'range'  => 'today',
            'orders' => [
                'count'     => (clone $base)->count(),
                'pending'   => (clone $base)->where('status', 'pending')->count(),
                'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
                'delivered' => (clone $base)->where('status', 'delivered')->count(),
            ],
            'sales' => [
                'total' => (float) (clone $base)->sum('total'),
                'paid'  => (float) (clone $base)->sum('paid_amount'),
                'due'   => (float) (clone $base)->sum('due_amount'),
            ],
            'low_stock_count' => Product::where('business_id', $bizId)
                ->where('track_stock', true)
                ->whereColumn('stock', '<=', 'low_stock_alert')->count(),
        ]);
    }

    // ---------------- Helpers ----------------

    private function imageUrls(?array $images): array
    {
        return collect($images ?? [])
            ->map(fn ($p) => Str::startsWith($p, ['http://', 'https://']) ? $p : url(Storage::url($p)))
            ->values()->all();
    }

    private function productPayload(Product $p): array
    {
        return [
            'id'            => $p->id,
            'name'          => $p->name,
            'sku'           => $p->sku,
            'type'          => $p->type,
            'price'         => (float) $p->price,
            'sale_price'    => $p->sale_price !== null ? (float) $p->sale_price : null,
            'current_price' => (float) $p->current_price,
            'track_stock'   => (bool) $p->track_stock,
            'stock'         => (int) $p->stock,
            'in_stock'      => ! $p->track_stock || (int) $p->stock > 0,
            'is_active'     => (bool) $p->is_active,
            'category'      => optional($p->category)->name,
            'brand'         => optional($p->brand)->name,
            'description'   => $p->description,
            'images'        => $this->imageUrls($p->images),
            'variants'      => $p->variants->map(fn ($v) => [
                'id'         => $v->id,
                'name'       => $v->name,
                'sku'        => $v->sku,
                'attributes' => $v->attributes,
                'price'      => (float) ($v->price ?: $p->current_price),
                'stock'      => (int) $v->stock,
                'in_stock'   => (int) $v->stock > 0,
            ])->values(),
        ];
    }

    private function orderPayload(Order $order): array
    {
        return [
            'order_number'   => $order->order_number,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'source'         => $order->source,
            'customer_name'  => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'subtotal'       => (float) $order->subtotal,
            'shipping_charge' => (float) $order->shipping_charge,
            'total'          => (float) $order->total,
            'paid_amount'    => (float) $order->paid_amount,
            'due_amount'     => (float) $order->due_amount,
            'created_at'     => optional($order->created_at)->toIso8601String(),
            'items'          => $order->items->map(fn ($i) => [
                'product_name' => $i->product_name,
                'sku'          => $i->sku,
                'quantity'     => (int) $i->quantity,
                'price'        => (float) $i->price,
                'total'        => (float) $i->total,
            ])->values(),
        ];
    }
}
