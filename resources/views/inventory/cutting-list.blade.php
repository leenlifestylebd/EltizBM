@extends('layouts.app')
@section('title', 'Cutting List')

@section('content')

<style>
    .print-only { display: none; }
    @media print {
        body * { visibility: hidden; }
        #cutting-print, #cutting-print * { visibility: visible; }
        #cutting-print { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        #cutting-print table { font-size: 12px; }
    }
</style>

{{-- Header --}}
<div class="flex items-center justify-between mb-4 no-print">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">✂️ Cutting List</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Products below minimum stock — produce these to refill</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('inventory.cutting-list.export', request()->only('search')) }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <button onclick="window.print()" type="button"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
    </div>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 gap-3 mb-4 no-print">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-gray-100 dark:border-slate-700 text-center">
        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $totalProducts }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Products below minimum</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-orange-100 dark:border-orange-900/30 text-center">
        <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($totalShortfall) }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total pieces to produce</p>
    </div>
</div>

{{-- Search --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4 no-print">
    <form method="GET" action="{{ route('inventory.cutting-list') }}" class="flex gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product or SKU..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm transition-colors">Search</button>
        @if(request('search'))
            <a href="{{ route('inventory.cutting-list') }}" class="px-3 py-2 text-sm text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Clear</a>
        @endif
    </form>
</div>

{{-- Printable area --}}
<div id="cutting-print" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">

    {{-- Print-only header --}}
    <div class="print-only px-4 pt-4 pb-2">
        <h1 style="font-size:18px;font-weight:bold;">Cutting / Production List — {{ $business->name ?? '' }}</h1>
        <p style="font-size:12px;">Generated: {{ now()->format('d M Y, h:i A') }} · {{ $totalProducts }} products · {{ number_format($totalShortfall) }} pieces to produce</p>
    </div>

    {{-- Desktop table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">#</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Product</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">SKU</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Category</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">In Stock</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Minimum</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">To Produce</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($products as $i => $product)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3 text-xs text-gray-400 dark:text-slate-500">{{ $i + 1 }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $product->name }}</td>
                    <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-slate-400">{{ $product->sku ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300">{{ $product->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold {{ $product->stock <= 0 ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">{{ $product->stock }}</span>
                        <span class="text-xs text-gray-400 dark:text-slate-500">{{ $product->unit }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-slate-300">{{ $product->low_stock_alert }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-sm font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">+{{ $product->shortfall }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                        🎉 All products are at or above their minimum stock — nothing to produce.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($products->count())
            <tfoot>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-t-2 border-gray-200 dark:border-slate-600 font-semibold">
                    <td colspan="6" class="px-4 py-3 text-right text-gray-700 dark:text-slate-200">Total pieces to produce</td>
                    <td class="px-4 py-3 text-center text-orange-600 dark:text-orange-400">{{ number_format($totalShortfall) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($products as $i => $product)
        <div class="p-4 flex items-center gap-3">
            <span class="text-xs text-gray-400 dark:text-slate-500 w-5 flex-shrink-0">{{ $i + 1 }}</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product->name }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                    {{ $product->sku ?? '—' }} · stock {{ $product->stock }} / min {{ $product->low_stock_alert }}
                </p>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="inline-flex px-2.5 py-1 rounded-lg text-sm font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">+{{ $product->shortfall }}</span>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">to produce</p>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-sm text-gray-400 dark:text-slate-500">
            🎉 All products at or above minimum stock.
        </div>
        @endforelse
    </div>
</div>

@endsection
