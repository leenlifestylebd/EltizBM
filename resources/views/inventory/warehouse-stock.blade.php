@extends('layouts.app')
@section('title', 'Stock by Warehouse')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Stock by Warehouse</h2>
    <a href="{{ route('inventory.transfers') }}"
       class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
        Transfer Stock
    </a>
</div>

{{-- Warehouse tabs --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach($warehouses as $wh)
    <a href="{{ route('inventory.warehouse-stock', ['warehouse_id' => $wh->id] + request()->except('warehouse_id', 'page')) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors
              {{ $whId === $wh->id
                  ? 'bg-primary-600 text-white border-primary-600'
                  : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-700 hover:border-primary-400' }}">
        {{ $wh->name }}
        @if($wh->is_default)
            <span class="ml-1 text-xs opacity-70">(default)</span>
        @endif
    </a>
    @endforeach
    @if($warehouses->isEmpty())
    <p class="text-sm text-gray-500 dark:text-slate-400">
        No warehouses set up yet.
        <a href="{{ route('settings.warehouses') }}" class="text-primary-600 hover:underline">Add warehouses →</a>
    </p>
    @endif
</div>

{{-- Search --}}
<form method="GET" class="mb-4">
    <input type="hidden" name="warehouse_id" value="{{ $whId }}">
    <div class="relative max-w-xs">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product…"
               class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
    </div>
</form>

@if($stocks->isEmpty())
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-10 text-center">
    <svg class="w-12 h-12 text-gray-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
    </svg>
    <p class="text-sm text-gray-500 dark:text-slate-400">No stock in this warehouse yet.</p>
    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Receive a purchase order or transfer stock here.</p>
</div>
@else

{{-- Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Product</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Category</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">In Warehouse</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Stock</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                @foreach($stocks as $ws)
                @php $p = $ws->product; @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('products.show', $p->id) }}" class="font-medium text-gray-900 dark:text-white hover:text-primary-600">
                            {{ $p->name }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-500 dark:text-slate-400 font-mono text-xs">{{ $p->sku ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-slate-400">{{ $p->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @php $pct = $p->stock > 0 ? min(100, round($ws->quantity / $p->stock * 100)) : 0; @endphp
                        <span class="font-bold text-base {{ $ws->quantity <= ($p->low_stock_alert ?? 5) ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ number_format($ws->quantity) }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-slate-500 ml-1">{{ $p->unit }}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-500 dark:text-slate-400">{{ number_format($p->stock) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-gray-700 dark:text-slate-300">
                        ৳{{ number_format($ws->quantity * $p->cost_price) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-t border-gray-100 dark:border-slate-700">
                    <td colspan="3" class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">
                        {{ $stocks->total() }} products (this page: {{ $stocks->count() }})
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                        {{ number_format($stocks->sum('quantity')) }}
                    </td>
                    <td colspan="2" class="px-4 py-3 text-right font-bold text-primary-600">
                        ৳{{ number_format($stocks->sum(fn($ws) => $ws->quantity * $ws->product->cost_price)) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $stocks->links() }}
</div>

@endif

@endsection
