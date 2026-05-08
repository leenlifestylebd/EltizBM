@extends('layouts.app')
@section('title', isset($product) ? 'Edit Product' : 'Add Product')

@section('content')

<div class="max-w-4xl mx-auto" x-data="productForm()">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('products.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ isset($product) ? 'Edit Product' : 'Add New Product' }}
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Fill in product details below</p>
        </div>
    </div>

    <form method="POST"
          action="{{ isset($product) ? route('products.update', $product) : route('products.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if(isset($product)) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- LEFT: Main Info --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Basic Info --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                                Product Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g. Samsung Galaxy S24">
                            @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Description</label>
                            <textarea name="description" rows="3"
                                      class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                      placeholder="Product description...">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">SKU</label>
                                <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                                       placeholder="SKU-001">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Barcode</label>
                                <input type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                                       placeholder="8901234567890">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Pricing</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                                Selling Price <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-slate-400 text-sm">৳</span>
                                <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" required
                                       step="0.01" min="0"
                                       class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="0.00">
                            </div>
                            @error('price')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Cost Price</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-slate-400 text-sm">৳</span>
                                <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price ?? '') }}"
                                       step="0.01" min="0"
                                       class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Sale Price</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-slate-400 text-sm">৳</span>
                                <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? '') }}"
                                       step="0.01" min="0"
                                       class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Optional">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Inventory --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Inventory</h3>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Stock Quantity</label>
                            <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" min="0"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Low Stock Alert</label>
                            <input type="number" name="low_stock_alert" value="{{ old('low_stock_alert', $product->low_stock_alert ?? 5) }}" min="0"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Unit</label>
                            <select name="unit" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach(['pcs','kg','g','litre','ml','box','pack','dozen','meter','yard'] as $unit)
                                    <option value="{{ $unit }}" {{ old('unit', $product->unit ?? 'pcs') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Weight (kg)</label>
                            <input type="number" name="weight" value="{{ old('weight', $product->weight ?? '') }}"
                                   step="0.01" min="0"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <input type="hidden" name="track_stock" value="0">
                        <input type="checkbox" name="track_stock" id="track_stock" value="1"
                               {{ old('track_stock', $product->track_stock ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        <label for="track_stock" class="text-xs text-gray-600 dark:text-slate-300">Track stock quantity</label>
                    </div>
                </div>

                {{-- Product Images --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Product Images</h3>

                    {{-- Image preview area --}}
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 mb-3" id="imagePreviewGrid">
                        @if(isset($product) && $product->images)
                            @foreach($product->images as $idx => $img)
                            <div class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-slate-700">
                                <img src="{{ Storage::url($img) }}" class="w-full h-full object-cover">
                                <button type="button" onclick="removeExistingImage({{ $idx }})"
                                        class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                                <input type="hidden" name="existing_images[]" value="{{ $img }}" id="existing_{{ $idx }}">
                            </div>
                            @endforeach
                        @endif
                    </div>

                    <label for="images"
                           class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-200 dark:border-slate-600 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors">
                        <svg class="w-8 h-8 text-gray-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-xs text-gray-500 dark:text-slate-400">Click to upload images</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">PNG, JPG up to 2MB each</p>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" class="hidden" onchange="previewImages(this)">
                    </label>
                </div>

            </div>

            {{-- RIGHT: Sidebar Options --}}
            <div class="space-y-4">

                {{-- Publish --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Publish</h3>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs text-gray-600 dark:text-slate-300">Active Status</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                                   {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors">
                        {{ isset($product) ? 'Update Product' : 'Save Product' }}
                    </button>
                    <a href="{{ route('products.index') }}"
                       class="w-full mt-2 flex items-center justify-center text-xs text-gray-500 dark:text-slate-400 hover:text-gray-700 py-2">
                        Cancel
                    </a>
                </div>

                {{-- Organization --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Organization</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Category</label>
                            <select name="category_id" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Brand</label>
                            <select name="brand_id" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id ?? '') == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Product Type</label>
                            <select name="type" x-model="productType" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="simple"   {{ old('type', $product->type ?? 'simple') === 'simple' ? 'selected' : '' }}>Simple Product</option>
                                <option value="variable" {{ old('type', $product->type ?? '') === 'variable' ? 'selected' : '' }}>Variable Product</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- WooCommerce Info (if synced) --}}
                @if(isset($product) && $product->woo_product_id)
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
                        <span class="text-xs font-semibold text-purple-700 dark:text-purple-400">WooCommerce Synced</span>
                    </div>
                    <p class="text-xs text-purple-600 dark:text-purple-400">Product ID: #{{ $product->woo_product_id }}</p>
                    @if($product->woo_synced_at)
                        <p class="text-xs text-purple-500 dark:text-purple-500 mt-0.5">Last sync: {{ $product->woo_synced_at->diffForHumans() }}</p>
                    @endif
                </div>
                @endif

            </div>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
function productForm() {
    return {
        productType: '{{ old("type", $product->type ?? "simple") }}',
    }
}

function previewImages(input) {
    const grid = document.getElementById('imagePreviewGrid');
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'relative aspect-square rounded-lg overflow-hidden bg-gray-100';
            div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">
                <span class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded">New</span>`;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

function removeExistingImage(idx) {
    document.getElementById('existing_' + idx)?.remove();
    event.target.closest('.relative').remove();
}
</script>
@endpush
