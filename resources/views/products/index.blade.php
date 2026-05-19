@extends('layouts.app')
@section('title', 'Products')

@section('content')
<div x-data="productSlideOver()" @keydown.escape.window="close()">

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Products</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $products->total() }} products in catalog</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('products.import') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/></svg>
            Import
        </a>
        <a href="{{ route('products.export') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
        </a>
        <a href="{{ route('products.sync') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Sync
        </a>
        <a href="{{ route('products.create') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Product
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" action="{{ route('products.index') }}" class="flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by name, SKU, barcode..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div class="flex gap-2">
            <select name="category" class="flex-1 sm:flex-none text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="stock" class="flex-1 sm:flex-none text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Stock</option>
                <option value="low"  {{ request('stock') === 'low'  ? 'selected' : '' }}>Low Stock</option>
                <option value="out"  {{ request('stock') === 'out'  ? 'selected' : '' }}>Out of Stock</option>
                <option value="in"   {{ request('stock') === 'in'   ? 'selected' : '' }}>In Stock</option>
            </select>
            <button type="submit" class="px-3 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
            </button>
        </div>
    </form>
</div>

{{-- Stats --}}
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
                        <input type="checkbox" class="rounded border-gray-300 text-primary-600" id="selectAll">
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
                @php
                    $imageUrl = null;
                    if ($product->images && count($product->images) > 0) {
                        $img = $product->images[0];
                        $imageUrl = str_starts_with($img, 'http') ? $img : Storage::url($img);
                    }
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="rounded border-gray-300 text-primary-600 product-checkbox" value="{{ $product->id }}">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
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
                                   class="font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate block max-w-48">
                                    {{ $product->name }}
                                </a>
                                @if($product->brand)
                                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $product->brand->name }}</p>
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
                            <button @click="openEdit('{{ route('products.quick-edit', $product) }}')"
                               class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <a href="{{ route('products.show', $product) }}"
                               class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
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
                        <a href="{{ route('products.create') }}" class="mt-2 inline-block text-xs text-primary-600 hover:underline">Add your first product</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($products as $product)
        @php
            $imageUrl = null;
            if ($product->images && count($product->images) > 0) {
                $img = $product->images[0];
                $imageUrl = str_starts_with($img, 'http') ? $img : Storage::url($img);
            }
        @endphp
        <div class="p-4 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
            <div class="flex items-start gap-3">
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
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
                        <div>
                            @if($product->isOutOfStock())
                                <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium">Out of Stock</span>
                            @elseif($product->isLowStock())
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">Low: {{ $product->stock }}</span>
                            @else
                                <span class="text-xs text-gray-500 dark:text-slate-400">Stock: {{ $product->stock }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="openEdit('{{ route('products.quick-edit', $product) }}')"
                               class="p-1.5 text-gray-400 hover:text-primary-600 bg-gray-100 dark:bg-slate-700 rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
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
        {{ $products->withQueryString()->links() }}
    </div>
    @endif
</div>

{{-- ── SLIDE-OVER PANEL ────────────────────────────────────────────── --}}

{{-- Backdrop --}}
<div x-show="open" x-cloak
     x-transition:enter="transition-opacity ease-out duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-150"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @click="close()"
     class="fixed inset-0 bg-black/40 z-40">
</div>

{{-- Panel --}}
<div x-show="open" x-cloak
     x-transition:enter="transition ease-out duration-250 transform"
     x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200 transform"
     x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
     class="fixed top-0 right-0 h-full w-full sm:w-[480px] bg-white dark:bg-slate-800 shadow-2xl z-50 flex flex-col">

    {{-- Panel Header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex-shrink-0">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Edit Product</h3>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5" x-text="product.name"></p>
        </div>
        <div class="flex items-center gap-2">
            <a :href="product.edit_url" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Full Edit →</a>
            <button @click="close()" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex-1 flex items-center justify-center">
        <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    {{-- Form --}}
    <form x-show="!loading" method="POST" :action="product.update_url" class="flex-1 overflow-y-auto flex flex-col" @submit="saving = true">
        <input type="hidden" name="_method" value="PUT">
        @csrf
        {{-- Preserve existing images so they don't get wiped on save --}}
        <template x-for="img in (product.images || [])" :key="img">
            <input type="hidden" name="existing_images[]" :value="img">
        </template>

        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

            {{-- Success message --}}
            @if(session('success'))
            <div class="px-3 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-xs text-green-700 dark:text-green-400">
                {{ session('success') }}
            </div>
            @endif

            {{-- Basic Info --}}
            <div>
                <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide mb-3">Basic Info</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Product Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" :value="product.name" required
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Description</label>
                        <textarea name="description" rows="2" x-text="product.description"
                                  class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">SKU</label>
                            <input type="text" name="sku" :value="product.sku"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Barcode</label>
                            <input type="text" name="barcode" :value="product.barcode"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none font-mono">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div>
                <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide mb-3">Pricing</h4>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Sell Price <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400">৳</span>
                            <input type="number" name="price" :value="product.price" step="0.01" min="0" required
                                   class="w-full pl-6 pr-2 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cost Price</label>
                        <div class="relative">
                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400">৳</span>
                            <input type="number" name="cost_price" :value="product.cost_price" step="0.01" min="0"
                                   class="w-full pl-6 pr-2 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Sale Price</label>
                        <div class="relative">
                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400">৳</span>
                            <input type="number" name="sale_price" :value="product.sale_price" step="0.01" min="0"
                                   class="w-full pl-6 pr-2 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stock --}}
            <div>
                <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide mb-3">Stock</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Current Stock</label>
                        <input type="number" name="stock" :value="product.stock" min="0"
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Low Stock Alert</label>
                        <input type="number" name="low_stock_alert" :value="product.low_stock_alert" min="0"
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            {{-- Category & Brand --}}
            <div>
                <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide mb-3">Category & Brand</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Category</label>
                        <select name="category_id"
                                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                            <option value="">— None —</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" :selected="product.category_id == {{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Brand</label>
                        <select name="brand_id"
                                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                            <option value="">— None —</option>
                            @foreach($brands as $b)
                            <option value="{{ $b->id }}" :selected="product.brand_id == {{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Type, Unit, Status --}}
            <div>
                <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide mb-3">Settings</h4>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Type</label>
                        <select name="type"
                                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                            <option value="simple"    :selected="product.type == 'simple'">Simple</option>
                            <option value="variable"  :selected="product.type == 'variable'">Variable</option>
                            <option value="composite" :selected="product.type == 'composite'">Composite</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Unit</label>
                        <select name="unit"
                                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                            @foreach(['pcs','kg','litre','meter','box','dozen','set'] as $u)
                            <option value="{{ $u }}" :selected="product.unit == '{{ $u }}'">{{ ucfirst($u) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Status</label>
                        <select name="is_active"
                                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                            <option value="1" :selected="product.is_active == 1">Active</option>
                            <option value="0" :selected="product.is_active == 0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer Buttons --}}
        <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 dark:border-slate-700 flex items-center gap-3">
            <button type="submit" :disabled="saving"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 disabled:opacity-60 transition-colors">
                <span x-show="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
            </button>
            <button type="button" @click="close()"
                    class="px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                Cancel
            </button>
        </div>
    </form>
</div>

</div>{{-- end x-data --}}

@endsection

@push('scripts')
<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = this.checked);
});

function productSlideOver() {
    return {
        open: false,
        loading: false,
        saving: false,
        product: {},

        openEdit(url) {
            this.open = true;
            this.loading = true;
            this.saving = false;
            this.product = {};
            document.body.style.overflow = 'hidden';

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                this.product = data;
                this.loading = false;
                this.$nextTick(() => {
                    // sync select values after Alpine renders
                    const form = this.$el.querySelector('form');
                    if (!form) return;
                    form.querySelector('[name=category_id]').value = data.category_id ?? '';
                    form.querySelector('[name=brand_id]').value    = data.brand_id ?? '';
                    form.querySelector('[name=type]').value        = data.type ?? 'simple';
                    form.querySelector('[name=unit]').value        = data.unit ?? 'pcs';
                    form.querySelector('[name=is_active]').value   = data.is_active ? '1' : '0';
                });
            })
            .catch(() => { this.open = false; this.loading = false; });
        },

        close() {
            this.open = false;
            document.body.style.overflow = '';
        }
    }
}
</script>
@endpush
