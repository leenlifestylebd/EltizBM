@extends('layouts.app')
@section('title', $product->name)

@section('content')

<div class="max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('products.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $product->name }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">SKU: {{ $product->sku }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('products.edit', $product) }}"
               class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Images + Status --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Images --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                @if($product->images && count($product->images) > 0)
                    <div x-data="{ active: 0 }">
                        <div class="aspect-square bg-gray-100 dark:bg-slate-700">
                            @foreach($product->images as $i => $img)
                            <img src="{{ Str::startsWith($img, 'http') ? $img : Storage::url($img) }}"
                                 class="w-full h-full object-cover" x-show="active === {{ $i }}">
                            @endforeach
                        </div>
                        @if(count($product->images) > 1)
                        <div class="flex gap-1.5 p-2">
                            @foreach($product->images as $i => $img)
                            <button @click="active = {{ $i }}"
                                    class="w-10 h-10 rounded-lg overflow-hidden border-2 transition-colors"
                                    :class="active === {{ $i }} ? 'border-primary-500' : 'border-transparent'">
                                <img src="{{ Str::startsWith($img, 'http') ? $img : Storage::url($img) }}" class="w-full h-full object-cover">
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                @else
                    <div class="aspect-square bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif
            </div>

            {{-- Status --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-slate-400">Status</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-slate-400">Type</span>
                        <span class="text-xs font-medium text-gray-700 dark:text-slate-200 capitalize">{{ $product->type }}</span>
                    </div>
                    @if($product->woo_product_id)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-slate-400">WooCommerce ID</span>
                        <span class="text-xs font-medium text-purple-600">#{{ $product->woo_product_id }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Pricing --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Pricing</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                        <p class="text-xs text-primary-600 dark:text-primary-400 mb-1">Selling Price</p>
                        <p class="text-lg font-bold text-primary-700 dark:text-primary-300">৳{{ number_format($product->price, 2) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Cost Price</p>
                        <p class="text-lg font-bold text-gray-700 dark:text-slate-200">৳{{ number_format($product->cost_price, 2) }}</p>
                    </div>
                    @if($product->sale_price)
                    <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <p class="text-xs text-orange-600 dark:text-orange-400 mb-1">Sale Price</p>
                        <p class="text-lg font-bold text-orange-700 dark:text-orange-300">৳{{ number_format($product->sale_price, 2) }}</p>
                    </div>
                    @else
                    <div class="text-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs text-gray-400 dark:text-slate-500 mb-1">Margin</p>
                        @php $margin = $product->cost_price > 0 ? (($product->price - $product->cost_price) / $product->price * 100) : 0; @endphp
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($margin, 1) }}%</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Inventory --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Inventory</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="text-center p-3 rounded-lg {{ $product->isOutOfStock() ? 'bg-red-50 dark:bg-red-900/20' : ($product->isLowStock() ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-green-50 dark:bg-green-900/20') }}">
                        <p class="text-xs {{ $product->isOutOfStock() ? 'text-red-500' : ($product->isLowStock() ? 'text-yellow-600' : 'text-green-600') }} mb-1">Stock</p>
                        <p class="text-xl font-bold {{ $product->isOutOfStock() ? 'text-red-700 dark:text-red-300' : ($product->isLowStock() ? 'text-yellow-700 dark:text-yellow-300' : 'text-green-700 dark:text-green-300') }}">
                            {{ $product->stock }}
                        </p>
                        <p class="text-xs {{ $product->isOutOfStock() ? 'text-red-400' : ($product->isLowStock() ? 'text-yellow-500' : 'text-green-500') }}">{{ $product->unit }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs text-gray-400 mb-1">Alert At</p>
                        <p class="text-xl font-bold text-gray-600 dark:text-slate-300">{{ $product->low_stock_alert }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs text-gray-400 mb-1">Stock Value</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-slate-200">৳{{ number_format($product->stock * $product->cost_price) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs text-gray-400 mb-1">Barcode</p>
                        <p class="text-xs font-mono font-bold text-gray-700 dark:text-slate-200">{{ $product->barcode ?: '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Organization --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Organization</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Category</p>
                        <p class="text-sm text-gray-700 dark:text-slate-200">{{ $product->category?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Brand</p>
                        <p class="text-sm text-gray-700 dark:text-slate-200">{{ $product->brand?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Unit</p>
                        <p class="text-sm text-gray-700 dark:text-slate-200">{{ $product->unit }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Weight</p>
                        <p class="text-sm text-gray-700 dark:text-slate-200">{{ $product->weight ? $product->weight . ' kg' : '—' }}</p>
                    </div>
                </div>
                @if($product->description)
                <div class="mt-3 pt-3 border-t border-gray-50 dark:border-slate-700">
                    <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Description</p>
                    <p class="text-sm text-gray-700 dark:text-slate-300">{{ $product->description }}</p>
                </div>
                @endif
            </div>

            {{-- Variants --}}
            @if($product->variants->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Variants ({{ $product->variants->count() }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-700/50">
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Variant</th>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">SKU</th>
                                <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Price</th>
                                <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                            @foreach($product->variants as $variant)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2.5 text-sm text-gray-700 dark:text-slate-200">{{ $variant->name }}</td>
                                <td class="px-4 py-2.5 text-xs font-mono text-gray-500 dark:text-slate-400">{{ $variant->sku ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-right text-sm text-gray-700 dark:text-slate-200">৳{{ number_format($variant->price, 2) }}</td>
                                <td class="px-4 py-2.5 text-right text-sm font-medium {{ $variant->stock <= 0 ? 'text-red-500' : 'text-green-600' }}">{{ $variant->stock }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Bill of Materials --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden"
                 x-data="{ addOpen: false }">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Bill of Materials</h3>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Raw materials required per piece</p>
                    </div>
                    <button @click="addOpen = !addOpen"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Material
                    </button>
                </div>

                {{-- Add Form --}}
                <div x-show="addOpen" x-cloak class="px-4 py-3 bg-primary-50/50 dark:bg-primary-900/10 border-b border-primary-100 dark:border-primary-900/30">
                    <form method="POST" action="{{ route('products.bom.store', $product) }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Material Name <span class="text-red-500">*</span></label>
                                <input type="text" name="material_name" placeholder="e.g. Cotton Fabric" required
                                       class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Category</label>
                                <input type="text" name="category" placeholder="e.g. Fabric"
                                       class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Color</label>
                                <input type="text" name="color" placeholder="e.g. White"
                                       class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Qty per Piece <span class="text-red-500">*</span></label>
                                <input type="number" name="quantity_per_piece" step="0.001" min="0.001" placeholder="2.5" required
                                       class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Unit <span class="text-red-500">*</span></label>
                                <input type="text" name="unit" placeholder="yard / meter / pcs" required
                                       class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Wastage %</label>
                                <input type="number" name="wastage_percent" step="0.1" min="0" max="100" placeholder="5"
                                       class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Notes</label>
                            <input type="text" name="notes" placeholder="Optional notes"
                                   class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button type="button" @click="addOpen = false"
                                    class="px-3 py-1.5 text-xs text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                                Add Material
                            </button>
                        </div>
                    </form>
                </div>

                {{-- BOM Table --}}
                @if($product->billOfMaterials->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-700/50">
                                <th class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Material</th>
                                <th class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Color</th>
                                <th class="text-right px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Qty/Piece</th>
                                <th class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Unit</th>
                                <th class="text-right px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Wastage %</th>
                                <th class="text-right px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Effective</th>
                                <th class="text-center px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Status</th>
                                <th class="text-right px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                            @foreach($product->billOfMaterials as $bom)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 {{ !$bom->is_active ? 'opacity-50' : '' }}">
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-gray-700 dark:text-slate-200">{{ $bom->material_name }}</div>
                                    @if($bom->category)
                                    <div class="text-gray-400 dark:text-slate-500">{{ $bom->category }}</div>
                                    @endif
                                    @if($bom->notes)
                                    <div class="text-gray-400 dark:text-slate-500 italic">{{ $bom->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-slate-300">{{ $bom->color ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-700 dark:text-slate-200">{{ $bom->quantity_per_piece }}</td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-slate-300">{{ $bom->unit }}</td>
                                <td class="px-4 py-2.5 text-right text-gray-600 dark:text-slate-300">{{ $bom->wastage_percent > 0 ? $bom->wastage_percent.'%' : '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-mono font-medium text-primary-600 dark:text-primary-400">{{ number_format($bom->effective_qty, 3) }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <form method="POST" action="{{ route('products.bom.toggle', [$product, $bom]) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium transition-colors
                                            {{ $bom->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-400' }}">
                                            {{ $bom->is_active ? 'Active' : 'Off' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <form method="POST" action="{{ route('products.bom.destroy', [$product, $bom]) }}"
                                          onsubmit="return confirm('Remove this material?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="text-red-400 hover:text-red-600 dark:hover:text-red-300 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50/60 dark:bg-primary-900/10">
                                <td colspan="5" class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400 font-medium">Total for 1 piece (active only)</td>
                                <td class="px-4 py-2 text-right text-xs font-bold text-primary-700 dark:text-primary-300">
                                    @php
                                        $activeByUnit = $product->billOfMaterials->where('is_active', true)->groupBy('unit');
                                    @endphp
                                    @foreach($activeByUnit as $unit => $items)
                                        {{ number_format($items->sum('effective_qty'), 3) }} {{ $unit }}<br>
                                    @endforeach
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <svg class="w-10 h-10 text-gray-200 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    <p class="text-sm text-gray-400 dark:text-slate-500">No materials added yet</p>
                    <button @click="addOpen = true" class="mt-2 text-xs text-primary-600 hover:underline dark:text-primary-400">+ Add first material</button>
                </div>
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="flex gap-2">
                <a href="{{ route('products.edit', $product) }}"
                   class="flex-1 py-2.5 text-center text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                    Edit Product
                </a>
                <a href="{{ route('inventory.adjustments', ['product' => $product->id]) }}"
                   class="flex-1 py-2.5 text-center text-sm font-medium bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                    Adjust Stock
                </a>
                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

