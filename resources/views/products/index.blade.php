@extends('layouts.app')
@section('title', 'Products')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Products</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $products->total() }} products in catalog</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('products.sync') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Sync
        </a>
        <a href="{{ route('products.create') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="hidden xs:inline">Add Product</span>
            <span class="xs:hidden">Add</span>
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" action="{{ route('products.index') }}" class="flex flex-col sm:flex-row gap-2">
        {{-- Search --}}
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by name, SKU, barcode..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex gap-2">
            {{-- Category filter --}}
            <select name="category" class="flex-1 sm:flex-none text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            {{-- Stock filter --}}
            <select name="stock" class="flex-1 sm:flex-none text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Stock</option>
                <option value="low"  {{ request('stock') === 'low'  ? 'selected' : '' }}>Low Stock</option>
                <option value="out"  {{ request('stock') === 'out'  ? 'selected' : '' }}>Out of Stock</option>
                <option value="in"   {{ request('stock') === 'in'   ? 'selected' : '' }}>In Stock</option>
            </select>

            <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
            </button>
        </div>
    </form>
</div>

{{-- Stats bar --}}
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-gray-100 dark:border-slate-700 text-center">
        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $totalProducts }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400">Total</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-yellow-100 dark:border-yellow-900/30 text-center">
        <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ $lowStockCount }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400">Low Stock</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-red-100 dark:border-red-900/30 text-center">
        <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ $outOfStockCount }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400">Out of Stock</p>
    </div>
</div>

{{-- Products Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">

    {{-- Desktop Table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600" id="selectAll">
                    </th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Product</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">SKU</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Category</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Price</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Stock</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Status</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 product-checkbox" value="{{ $product->id }}">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            {{-- Product image --}}
                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                                @if($product->images && count($product->images) > 0)
                                    <img src="{{ Storage::url($product->images[0]) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('products.show', $product) }}"
                                   class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate block max-w-48">
                                    {{ $product->name }}
                                </a>
                                @if($product->brand)
                                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $product->brand->name }}</p>
                                @endif
                                @if($product->woo_product_id)
                                    <span class="inline-flex items-center gap-1 text-xs text-purple-600 dark:text-purple-400">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
                                        WooCommerce
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-slate-400 font-mono">{{ $product->sku ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300">{{ $product->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="font-medium text-gray-900 dark:text-white text-sm">৳{{ number_format($product->current_price) }}</span>
                        @if($product->sale_price)
                            <p class="text-xs text-gray-400 line-through">৳{{ number_format($product->price) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($product->isOutOfStock())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Out</span>
                        @elseif($product->isLowStock())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">{{ $product->stock }}</span>
                        @else
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->stock }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $product->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-slate-400' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('products.edit', $product) }}"
                               class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <a href="{{ route('products.show', $product) }}"
                               class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-200 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-400 dark:text-slate-500">No products found</p>
                        <a href="{{ route('products.create') }}" class="mt-2 inline-block text-xs text-blue-600 hover:underline">Add your first product</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($products as $product)
        <div class="p-4 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
            <div class="flex items-start gap-3">
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                    @if($product->images && count($product->images) > 0)
                        <img src="{{ Storage::url($product->images[0]) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ route('products.show', $product) }}"
                           class="font-medium text-gray-900 dark:text-white text-sm truncate">
                            {{ $product->name }}
                        </a>
                        <span class="text-sm font-bold text-gray-900 dark:text-white flex-shrink-0">৳{{ number_format($product->current_price) }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        @if($product->sku)
                            <span class="text-xs text-gray-400 dark:text-slate-500 font-mono">{{ $product->sku }}</span>
                        @endif
                        @if($product->category)
                            <span class="text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 px-1.5 py-0.5 rounded">{{ $product->category->name }}</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-2">
                            @if($product->isOutOfStock())
                                <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded-full font-medium">Out of Stock</span>
                            @elseif($product->isLowStock())
                                <span class="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-0.5 rounded-full font-medium">Low: {{ $product->stock }}</span>
                            @else
                                <span class="text-xs text-gray-500 dark:text-slate-400">Stock: {{ $product->stock }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('products.edit', $product) }}"
                               class="p-1.5 text-gray-400 hover:text-blue-600 bg-gray-100 dark:bg-slate-700 rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 bg-gray-100 dark:bg-slate-700 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <p class="text-sm text-gray-400 dark:text-slate-500">No products found</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
        {{ $products->withQueryString()->links('vendor.pagination.simple-tailwind') }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
