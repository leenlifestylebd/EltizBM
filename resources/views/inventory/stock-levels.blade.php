@extends('layouts.app')
@section('title', 'Stock Levels')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Stock Levels</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $products->total() }} products</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('inventory.export-stock') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <a href="{{ route('inventory.adjustments') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Adjust Stock
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" action="{{ route('inventory.stock-levels') }}" class="flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search product..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div class="flex gap-2">
            <select name="filter" class="flex-1 sm:flex-none text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Stock</option>
                <option value="low"  {{ request('filter')==='low'  ? 'selected':'' }}>Low Stock</option>
                <option value="out"  {{ request('filter')==='out'  ? 'selected':'' }}>Out of Stock</option>
                <option value="in"   {{ request('filter')==='in'   ? 'selected':'' }}>In Stock</option>
            </select>
            <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm transition-colors">Filter</button>
        </div>
    </form>
</div>

<div x-data="{ step: 1 }">

{{-- Quick-adjust step selector --}}
<div class="flex items-center gap-2 mb-3 flex-wrap">
    <span class="text-xs text-gray-500 dark:text-slate-400">Quick adjust step:</span>
    <template x-for="s in [1, 5, 10, 25]" :key="s">
        <button type="button" @click="step = s"
                :class="step === s ? 'bg-primary-600 text-white border-primary-600' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600'"
                class="px-3 py-1 text-xs font-semibold rounded-lg border transition-colors"
                x-text="'±' + s"></button>
    </template>
    <span class="text-xs text-gray-400 dark:text-slate-500 ml-1">— each <span class="font-semibold text-green-600">+</span>/<span class="font-semibold text-red-600">−</span> changes stock by this amount</span>
</div>

{{-- Desktop Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Product</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">SKU</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Category</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Stock</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Low Alert</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Cost Value</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Status</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($products as $product)
                @php
                    $stockColor = $product->stock <= 0
                        ? 'text-red-600 dark:text-red-400'
                        : ($product->isLowStock()
                            ? 'text-yellow-600 dark:text-yellow-400'
                            : 'text-gray-900 dark:text-white');
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors" x-data="{ editing: false, newQty: {{ $product->stock }}, stock: {{ $product->stock }}, saving: false, low: {{ $product->low_stock_alert }} }">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                                @if($product->images)
                                    <img src="{{ Storage::url($product->images[0]) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                @endif
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white truncate max-w-44">{{ $product->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-slate-400">{{ $product->sku ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300">{{ $product->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div x-show="!editing" class="flex items-center justify-center gap-1.5">
                            <button type="button" :disabled="saving || stock <= 0"
                                    @click="saving = true; quickAdjustStock({{ $product->id }}, -step).then(d => { if (d.stock !== null) stock = d.stock; saving = false; })"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 disabled:opacity-30 disabled:cursor-not-allowed transition-colors text-lg font-bold leading-none">−</button>
                            <span class="min-w-[2.75rem] text-center text-base font-bold"
                                  :class="stock <= 0 ? 'text-red-600 dark:text-red-400' : (stock <= low ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white')"
                                  :style="saving ? 'opacity:0.4' : ''"
                                  x-text="stock"></span>
                            <button type="button" :disabled="saving"
                                    @click="saving = true; quickAdjustStock({{ $product->id }}, step).then(d => { if (d.stock !== null) stock = d.stock; saving = false; })"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/40 disabled:opacity-30 transition-colors text-lg font-bold leading-none">+</button>
                        </div>
                        <div x-show="editing" x-cloak>
                            <input type="number" x-model.number="newQty" min="0"
                                   class="w-20 px-2 py-1 text-sm text-center border border-primary-300 dark:border-primary-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-slate-400">{{ $product->low_stock_alert }}</td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">
                        <span x-text="'৳' + Math.round(stock * {{ $product->cost_price }}).toLocaleString()"></span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span x-show="stock <= 0" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Out</span>
                        <span x-show="stock > 0 && stock <= low" x-cloak class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Low</span>
                        <span x-show="stock > low" x-cloak class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">OK</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div x-show="!editing">
                            <button @click="editing = true; newQty = stock"
                                    class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors" title="Set exact stock">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                        </div>
                        <div x-show="editing" x-cloak class="flex items-center gap-1 justify-center">
                            <form method="POST" action="{{ route('inventory.adjustments.store') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="new_stock" :value="newQty">
                                <input type="hidden" name="current_stock" value="{{ $product->stock }}">
                                <input type="hidden" name="reason" value="Manual stock correction">
                                <input type="hidden" name="source" value="stock_levels">
                                <button type="submit" class="px-2 py-1 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700">✓</button>
                            </form>
                            <button @click="editing = false" class="px-2 py-1 text-xs bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-500">✕</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No products found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($products as $product)
        @php
            $mobileColor = $product->stock <= 0
                ? 'text-red-600 dark:text-red-400'
                : ($product->isLowStock()
                    ? 'text-yellow-600 dark:text-yellow-400'
                    : 'text-gray-900 dark:text-white');
        @endphp
        <div class="p-4 flex items-center gap-3" x-data="{ stock: {{ $product->stock }}, saving: false, low: {{ $product->low_stock_alert }} }">
            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                @if($product->images)
                    <img src="{{ Storage::url($product->images[0]) }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product->name }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $product->sku ?? '—' }} · {{ $product->category?->name ?? '—' }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button type="button" :disabled="saving || stock <= 0"
                        @click="saving = true; quickAdjustStock({{ $product->id }}, -step).then(d => { if (d.stock !== null) stock = d.stock; saving = false; })"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 disabled:opacity-30 text-xl font-bold leading-none">−</button>
                <span class="min-w-[2.25rem] text-center text-lg font-bold"
                      :class="stock <= 0 ? 'text-red-600 dark:text-red-400' : (stock <= low ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white')"
                      :style="saving ? 'opacity:0.4' : ''"
                      x-text="stock"></span>
                <button type="button" :disabled="saving"
                        @click="saving = true; quickAdjustStock({{ $product->id }}, step).then(d => { if (d.stock !== null) stock = d.stock; saving = false; })"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 disabled:opacity-30 text-xl font-bold leading-none">+</button>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-sm text-gray-400">No products found</div>
        @endforelse
    </div>

    @if($products->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $products->withQueryString()->links() }}</div>
    @endif
</div>

</div>{{-- /x-data step wrapper --}}

<script>
    // Shared by every +/- button: POST a delta, return the new stock from the server.
    // Stock lives in the DB, so the displayed number always matches what was saved.
    window.quickAdjustStock = function (productId, delta) {
        return fetch('{{ route('inventory.quick-adjust') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ product_id: productId, delta: delta }),
        })
        .then(r => r.json())
        .catch(() => { alert('Could not update stock. Check your connection and try again.'); return { stock: null }; });
    };
</script>

@endsection
