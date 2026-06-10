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

        $products        = $query->paginate(20);
        $categories      = Category::where('business_id', $bizId)->orderBy('name')->get();
        $brands          = Brand::where('business_id', $bizId)->orderBy('name')->get();
        $totalProducts   = Product::where('business_id', $bizId)->count();
        $lowStockCount   = Product::where('business_id', $bizId)->whereRaw('stock > 0 AND stock <= low_stock_alert')->count();
        $outOfStockCount = Product::where('business_id', $bizId)->where('stock', '<=', 0)->count();

        return view('products.index', compact('products', 'categories', 'brands', 'totalProducts', 'lowStockCount', 'outOfStockCount'));
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
        $product->load(['category', 'brand', 'variants', 'billOfMaterials']);
        return view('products.show', compact('product'));
    }

    public function quickEdit(Product $product)
    {
        $this->authorizeProduct($product);
        return response()->json([
            'id'              => $product->id,
            'name'            => $product->name,
            'description'     => $product->description,
            'sku'             => $product->sku,
            'barcode'         => $product->barcode,
            'price'           => $product->price,
            'cost_price'      => $product->cost_price,
            'sale_price'      => $product->sale_price,
            'stock'           => $product->stock,
            'low_stock_alert' => $product->low_stock_alert,
            'category_id'     => $product->category_id,
            'brand_id'        => $product->brand_id,
            'type'            => $product->type,
            'unit'            => $product->unit,
            'is_active'       => $product->is_active,
            'images'          => $product->images ?? [],
            'update_url'      => route('products.update', $product),
            'edit_url'        => route('products.edit', $product),
        ]);
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

        $bizId = auth()->user()->business_id;

        // Start with existing images — if existing_images not sent at all (e.g. quick-edit),
        // keep the product's current images instead of wiping them.
        if ($request->has('existing_images')) {
            $images = $request->existing_images ?? [];
        } else {
            $images = $product->images ?? [];
        }

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

        // AJAX (inline slide-over) save: return JSON so the page never navigates.
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Product '{$product->name}' updated successfully!",
            ]);
        }

        // Full-page save: return to the same list page (preserves pagination/filters)
        // instead of jumping back to page 1.
        return redirect()->back()
            ->with('success', "Product '{$product->name}' updated successfully!");
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);
        $name = $product->name;
        $this->logActivity('deleted', $product, [
            'name'  => $product->name,
            'sku'   => $product->sku,
            'stock' => $product->stock,
        ]);
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', "Product '{$name}' deleted.");
    }

    // ── Generate barcode for a single product ────────────────
    public function generateBarcode(Product $product)
    {
        $this->authorizeProduct($product);

        if (!$product->barcode) {
            // Generate unique CODE128-compatible barcode
            // Format: prefix (3 chars) + business_id (3 digits) + product_id (6 digits)
            $prefix   = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', auth()->user()->business->name ?? 'BIZ'), 0, 3));
            $barcode  = $prefix . str_pad($product->business_id, 3, '0', STR_PAD_LEFT) . str_pad($product->id, 6, '0', STR_PAD_LEFT);
            $product->update(['barcode' => $barcode]);
        }

        return response()->json([
            'success' => true,
            'barcode' => $product->barcode,
            'product_id' => $product->id,
        ]);
    }

    // ── Generate barcodes for ALL products without one ────────
    public function generateBarcodesBulk(Request $request)
    {
        $bizId    = auth()->user()->business_id;
        $business = auth()->user()->business;
        $prefix   = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $business->name ?? 'BIZ'), 0, 3));

        $products = Product::where('business_id', $bizId)
            ->whereNull('barcode')
            ->orWhere('barcode', '')
            ->get();

        $count = 0;
        foreach ($products as $p) {
            if (!$p->barcode) {
                $p->update(['barcode' => $prefix . str_pad($bizId, 3, '0', STR_PAD_LEFT) . str_pad($p->id, 6, '0', STR_PAD_LEFT)]);
                $count++;
            }
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function syncPage()
    {
        $bizId   = auth()->user()->business_id;
        $wooSettings = \App\Models\WoocommerceSetting::where('business_id', $bizId)->first();
        return view('products.sync', compact('wooSettings'));
    }

    public function doSync(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $wooSettings = \App\Models\WoocommerceSetting::where('business_id', $bizId)
            ->where('is_active', true)->first();

        if (!$wooSettings) {
            return redirect()->route('products.sync')
                ->with('error', 'WooCommerce not configured. Go to Integrations → WooCommerce to set it up.');
        }

        $type    = $request->type ?? 'products';
        $service = new \App\Services\WooCommerceService($wooSettings);

        if ($type === 'stock') {
            $result = $service->pushStockToWoo($bizId);
            $msg = "Stock pushed: {$result['updated']} products updated.";
        } else {
            $result = $service->syncProductsFromWoo($bizId);
            $msg = "Sync complete: {$result['imported']} imported, {$result['updated']} updated.";
        }

        if (!empty($result['errors'])) {
            $msg .= ' ' . count($result['errors']) . ' error(s) occurred.';
        }

        return redirect()->route('products.sync')->with('success', $msg);
    }

    public function export(Request $request)
    {
        $bizId    = auth()->user()->business_id;
        $products = Product::where('business_id', $bizId)
            ->with(['category', 'brand'])
            ->orderByDesc('created_at')
            ->get();

        $filename = 'products-' . date('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'SKU', 'Barcode', 'Category', 'Brand', 'Price', 'Cost Price', 'Sale Price', 'Stock', 'Status']);
            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->id, $p->name, $p->sku, $p->barcode,
                    $p->category?->name, $p->brand?->name,
                    $p->price, $p->cost_price, $p->sale_price ?? '',
                    $p->stock, $p->is_active ? 'Active' : 'Inactive',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
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
            ->orderBy('name')
            ->limit(50)
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

    // ── Import Page ───────────────────────────────────────────
    public function importPage()
    {
        return view('products.import');
    }

    // ── Download CSV Template ─────────────────────────────────
    public function downloadTemplate()
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="products-import-template.csv"'];
        $callback = function () {
            $h = fopen('php://output', 'w');
            // Headers
            fputcsv($h, ['name','sku','barcode','category','brand','price','cost_price','sale_price','stock','low_stock_alert','unit','description','is_active']);
            // Sample rows
            fputcsv($h, ['White Punjabi XL','SKU-001','','Punjabi','Eltiz',850,500,799,50,10,'pcs','Premium white punjabi size XL','1']);
            fputcsv($h, ['Black Punjabi M', 'SKU-002','','Punjabi','Eltiz',750,450,'',30,5,'pcs','','1']);
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ── Process CSV Import ────────────────────────────────────
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $bizId  = auth()->user()->business_id;
        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Read headers from first row
        $headers = fgetcsv($handle);
        if (!$headers) {
            return back()->with('error', 'Could not read CSV file.');
        }
        $headers = array_map('trim', $headers);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 1;

        // Category & Brand cache to avoid repeated DB hits
        $catCache   = [];
        $brandCache = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (count($data) < 2) continue;

            // Map columns by header name
            $col = [];
            foreach ($headers as $i => $h) {
                $col[strtolower(trim($h))] = trim($data[$i] ?? '');
            }

            $name = $col['name'] ?? '';
            if (!$name) {
                $errors[] = "Row {$row}: Name is required — skipped.";
                $skipped++;
                continue;
            }

            $price = (float) ($col['price'] ?? 0);
            if ($price <= 0) {
                $errors[] = "Row {$row}: '{$name}' — Price must be > 0, skipped.";
                $skipped++;
                continue;
            }

            // Resolve Category (create if not exists)
            $categoryId = null;
            if (!empty($col['category'])) {
                $catName = $col['category'];
                if (!isset($catCache[$catName])) {
                    $cat = Category::firstOrCreate(
                        ['business_id' => $bizId, 'name' => $catName],
                        ['slug' => Str::slug($catName) . '-' . $bizId]
                    );
                    $catCache[$catName] = $cat->id;
                }
                $categoryId = $catCache[$catName];
            }

            // Resolve Brand (create if not exists)
            $brandId = null;
            if (!empty($col['brand'])) {
                $brandName = $col['brand'];
                if (!isset($brandCache[$brandName])) {
                    $brand = Brand::firstOrCreate(
                        ['business_id' => $bizId, 'name' => $brandName],
                        ['slug' => Str::slug($brandName) . '-' . $bizId]
                    );
                    $brandCache[$brandName] = $brand->id;
                }
                $brandId = $brandCache[$brandName];
            }

            // Skip duplicate SKU if provided
            $sku = $col['sku'] ?? '';
            if ($sku && Product::where('business_id', $bizId)->where('sku', $sku)->exists()) {
                $errors[] = "Row {$row}: '{$name}' — SKU '{$sku}' already exists, skipped.";
                $skipped++;
                continue;
            }

            try {
                Product::create([
                    'business_id'     => $bizId,
                    'category_id'     => $categoryId,
                    'brand_id'        => $brandId,
                    'name'            => $name,
                    'slug'            => $this->uniqueSlug($name, $bizId),
                    'sku'             => $sku ?: 'SKU-' . strtoupper(Str::random(6)),
                    'barcode'         => $col['barcode'] ?: null,
                    'price'           => $price,
                    'cost_price'      => (float) ($col['cost_price'] ?? 0),
                    'sale_price'      => !empty($col['sale_price']) ? (float)$col['sale_price'] : null,
                    'stock'           => (int)   ($col['stock'] ?? 0),
                    'low_stock_alert' => (int)   ($col['low_stock_alert'] ?? 5),
                    'unit'            => $col['unit'] ?: 'pcs',
                    'description'     => $col['description'] ?? null,
                    'type'            => 'simple',
                    'is_active'       => ($col['is_active'] ?? '1') !== '0',
                    'track_stock'     => true,
                    'images'          => [],
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: '{$name}' — Import failed: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        $msg = "Import complete: {$imported} products imported, {$skipped} skipped.";
        return redirect()->route('products.import')
            ->with('import_success', $msg)
            ->with('import_errors', $errors)
            ->with('import_count', $imported);
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
