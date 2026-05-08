<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $query = Product::where('business_id', $bizId)
            ->with(['category', 'brand'])
            ->orderByDesc('created_at');

        // Search
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($cat = $request->category) {
            $query->where('category_id', $cat);
        }

        // Stock filter
        match ($request->stock) {
            'low'  => $query->whereRaw('stock > 0 AND stock <= low_stock_alert'),
            'out'  => $query->where('stock', '<=', 0),
            'in'   => $query->whereRaw('stock > low_stock_alert'),
            default => null,
        };

        $products       = $query->paginate(20);
        $categories     = Category::where('business_id', $bizId)->orderBy('name')->get();
        $totalProducts  = Product::where('business_id', $bizId)->count();
        $lowStockCount  = Product::where('business_id', $bizId)->whereRaw('stock > 0 AND stock <= low_stock_alert')->count();
        $outOfStockCount= Product::where('business_id', $bizId)->where('stock', '<=', 0)->count();

        return view('products.index', compact('products', 'categories', 'totalProducts', 'lowStockCount', 'outOfStockCount'));
    }

    public function create()
    {
        $bizId      = auth()->user()->business_id;
        $categories = Category::where('business_id', $bizId)->orderBy('name')->get();
        $brands     = Brand::where('business_id', $bizId)->orderBy('name')->get();
        $attributes = Attribute::where('business_id', $bizId)->with('values')->get();

        return view('products.form', compact('categories', 'brands', 'attributes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'price'           => 'required|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
            'sale_price'      => 'nullable|numeric|min:0',
            'stock'           => 'nullable|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'sku'             => 'nullable|string|max:100',
            'barcode'         => 'nullable|string|max:100',
            'images.*'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $bizId  = auth()->user()->business_id;
        $images = [];

        // Upload images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store("businesses/{$bizId}/products", 'public');
                $images[] = $path;
            }
        }

        // Keep existing images if editing (won't happen on create, but safe)
        if ($request->existing_images) {
            $images = array_merge($request->existing_images, $images);
        }

        $product = Product::create([
            'business_id'     => $bizId,
            'category_id'     => $request->category_id ?: null,
            'brand_id'        => $request->brand_id ?: null,
            'name'            => $request->name,
            'slug'            => $this->uniqueSlug($request->name, $bizId),
            'sku'             => $request->sku ?: 'SKU-' . strtoupper(Str::random(6)),
            'barcode'         => $request->barcode,
            'description'     => $request->description,
            'price'           => $request->price,
            'cost_price'      => $request->cost_price ?? 0,
            'sale_price'      => $request->sale_price ?: null,
            'stock'           => $request->stock ?? 0,
            'low_stock_alert' => $request->low_stock_alert ?? 5,
            'type'            => $request->type ?? 'simple',
            'unit'            => $request->unit ?? 'pcs',
            'weight'          => $request->weight,
            'images'          => $images,
            'is_active'       => $request->boolean('is_active', true),
            'track_stock'     => $request->boolean('track_stock', true),
        ]);

        return redirect()->route('products.index')
            ->with('success', "Product '{$product->name}' created successfully!");
    }

    public function show(Product $product)
    {
        $this->authorizeProduct($product);
        $product->load(['category', 'brand', 'variants']);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);
        $bizId      = auth()->user()->business_id;
        $categories = Category::where('business_id', $bizId)->orderBy('name')->get();
        $brands     = Brand::where('business_id', $bizId)->orderBy('name')->get();
        $attributes = Attribute::where('business_id', $bizId)->with('values')->get();

        return view('products.form', compact('product', 'categories', 'brands', 'attributes'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeProduct($product);

        $request->validate([
            'name'       => 'required|string|max:255',
            'price'      => 'required|numeric|min:0',
            'images.*'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $bizId  = auth()->user()->business_id;
        $images = $request->existing_images ?? [];

        // Upload new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store("businesses/{$bizId}/products", 'public');
                $images[] = $path;
            }
        }

        $product->update([
            'category_id'     => $request->category_id ?: null,
            'brand_id'        => $request->brand_id ?: null,
            'name'            => $request->name,
            'sku'             => $request->sku,
            'barcode'         => $request->barcode,
            'description'     => $request->description,
            'price'           => $request->price,
            'cost_price'      => $request->cost_price ?? 0,
            'sale_price'      => $request->sale_price ?: null,
            'stock'           => $request->stock ?? 0,
            'low_stock_alert' => $request->low_stock_alert ?? 5,
            'type'            => $request->type ?? 'simple',
            'unit'            => $request->unit ?? 'pcs',
            'weight'          => $request->weight,
            'images'          => $images,
            'is_active'       => $request->boolean('is_active', true),
            'track_stock'     => $request->boolean('track_stock', true),
        ]);

        return redirect()->route('products.index')
            ->with('success', "Product '{$product->name}' updated successfully!");
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);
        $name = $product->name;
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', "Product '{$name}' deleted.");
    }

    public function syncPage()
    {
        $bizId   = auth()->user()->business_id;
        $wooSettings = \App\Models\WoocommerceSetting::where('business_id', $bizId)->first();
        return view('products.sync', compact('wooSettings'));
    }

    public function doSync(Request $request)
    {
        // Handled in Phase 4 (WooCommerce Integration)
        return redirect()->route('products.sync')
            ->with('success', 'Sync initiated! Check back shortly.');
    }

    // AJAX search for POS / order form
    public function apiSearch(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $term  = $request->q;

        $products = Product::where('business_id', $bizId)
            ->where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%")
                  ->orWhere('barcode', $term);
            })
            ->select('id', 'name', 'sku', 'barcode', 'price', 'sale_price', 'stock', 'images', 'unit')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'sku'      => $p->sku,
                'barcode'  => $p->barcode,
                'price'    => $p->current_price,
                'stock'    => $p->stock,
                'unit'     => $p->unit,
                'image'    => $p->images ? Storage::url($p->images[0]) : null,
            ]);

        return response()->json($products);
    }

    // =============================================
    private function authorizeProduct(Product $product): void
    {
        abort_if($product->business_id !== auth()->user()->business_id, 403);
    }

    private function uniqueSlug(string $name, int $bizId): string
    {
        $slug = Str::slug($name);
        $count = Product::where('business_id', $bizId)->where('slug', 'like', "{$slug}%")->count();
        return $count > 0 ? "{$slug}-{$count}" : $slug;
    }
}
