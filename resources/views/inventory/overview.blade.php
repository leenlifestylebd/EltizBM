@extends('layouts.app')
@section('title', 'Inventory Overview')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Inventory</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Stock overview & management</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('inventory.adjustments') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Adjust
        </a>
        <a href="{{ route('inventory.purchase-orders') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="hidden xs:inline">Purchase Order</span>
            <span class="xs:hidden">PO</span>
        </a>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400">Total Products</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">in catalog</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-yellow-100 dark:border-yellow-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Low Stock</p>
        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['low_stock'] }}</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">need reorder</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-red-100 dark:border-red-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Out of Stock</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['out_of_stock'] }}</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">unavailable</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Stock Value</p>
        <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">৳{{ number_format($stats['total_value']) }}</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">at cost price</p>
    </div>
</div>

{{-- Quick Nav --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
    @php
    $navItems = [
        ['route'=>'inventory.overview',        'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','label'=>'Overview','color'=>'primary','active'=>true],
        ['route'=>'inventory.stock-levels',    'icon'=>'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4','label'=>'Stock Levels','color'=>'violet','active'=>false],
        ['route'=>'inventory.purchase-orders', 'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','label'=>'Purchase Orders','color'=>'green','active'=>false],
        ['route'=>'inventory.adjustments',     'icon'=>'M12 6v6m0 0v6m0-6h6m-6 0H6','label'=>'Adjustments','color'=>'orange','active'=>false],
        ['route'=>'inventory.barcode',         'icon'=>'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z','label'=>'Barcode Print','color'=>'purple','active'=>false],
    ];
    @endphp
    @foreach($navItems as $nav)
    <a href="{{ route($nav['route']) }}"
       class="flex flex-col items-center gap-2 p-3 rounded-xl border transition-all hover:shadow-sm
              {{ $nav['active'] ? 'bg-'.$nav['color'].'-50 dark:bg-'.$nav['color'].'-900/20 border-'.$nav['color'].'-200 dark:border-'.$nav['color'].'-700' : 'bg-white dark:bg-slate-800 border-gray-100 dark:border-slate-700 hover:border-'.$nav['color'].'-200' }}">
        <div class="w-9 h-9 bg-{{ $nav['color'] }}-100 dark:bg-{{ $nav['color'] }}-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-{{ $nav['color'] }}-600 dark:text-{{ $nav['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $nav['icon'] }}"/>
            </svg>
        </div>
        <span class="text-xs font-medium text-center text-gray-700 dark:text-slate-300 leading-tight">{{ $nav['label'] }}</span>
    </a>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- Low Stock Alert --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Low Stock Alert</h3>
            </div>
            <a href="{{ route('inventory.stock-levels') }}?filter=low" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">View all</a>
        </div>
        @forelse($lowStockProducts as $product)
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 dark:border-slate-700 last:border-0">
            <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                @if($product->images)
                    <img src="{{ Storage::url($product->images[0]) }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product->name }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $product->sku }}</p>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold
                    {{ $product->stock <= 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                    {{ $product->stock <= 0 ? 'Out' : $product->stock . ' left' }}
                </span>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">min: {{ $product->low_stock_alert }}</p>
            </div>
        </div>
        @empty
        <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">
            ✅ All products are well stocked!
        </div>
        @endforelse
    </div>

    {{-- Recent Adjustments --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Adjustments</h3>
            <a href="{{ route('inventory.adjustments') }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">View all</a>
        </div>
        @forelse($recentAdjustments as $adj)
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 dark:border-slate-700 last:border-0">
            <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0
                {{ in_array($adj->type, ['increase','return']) ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                <svg class="w-3.5 h-3.5 {{ in_array($adj->type, ['increase','return']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if(in_array($adj->type, ['increase','return']))
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    @endif
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $adj->product->name ?? 'N/A' }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">{{ ucfirst($adj->type) }} · {{ $adj->created_at->diffForHumans() }}</p>
            </div>
            <span class="text-sm font-bold flex-shrink-0 {{ in_array($adj->type, ['increase','return']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ in_array($adj->type, ['increase','return']) ? '+' : '-' }}{{ $adj->quantity }}
            </span>
        </div>
        @empty
        <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No adjustments yet</div>
        @endforelse
    </div>

</div>

@endsection
