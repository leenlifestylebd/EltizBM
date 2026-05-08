@extends('layouts.app')
@section('title', 'Stock & Inventory Report')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Stock & Inventory</h2>
    <a href="{{ route('inventory.overview') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Manage Inventory →</a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
    @foreach([
        ['label'=>'Total Products', 'value'=>$stats['total_products'],           'color'=>'gray'],
        ['label'=>'Total Stock',    'value'=>number_format($stats['total_stock']),'color'=>'blue'],
        ['label'=>'Stock Value',    'value'=>'৳'.number_format($stats['total_value']),'color'=>'green'],
        ['label'=>'Retail Value',   'value'=>'৳'.number_format($stats['total_retail']),'color'=>'indigo'],
        ['label'=>'Low Stock',      'value'=>$stats['low_stock'],                'color'=>'yellow'],
        ['label'=>'Out of Stock',   'value'=>$stats['out_of_stock'],             'color'=>'red'],
    ] as $c)
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-{{ $c['color'] }}-100 dark:border-{{ $c['color'] }}-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $c['label'] }}</p>
        <p class="text-base font-bold text-{{ $c['color'] }}-600 dark:text-{{ $c['color'] }}-400 mt-1">{{ $c['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- By Category --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Stock by Category</h3>
    @php $maxCat = $byCategory->max('value') ?: 1; @endphp
    <div class="space-y-3">
        @foreach($byCategory->sortByDesc('value') as $cat)
        <div>
            <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600 dark:text-slate-300">{{ $cat->category?->name ?? 'Uncategorized' }} ({{ $cat->count }} products)</span>
                <span class="font-semibold text-gray-900 dark:text-white">৳{{ number_format($cat->value) }}</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex-1 bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                    <div class="h-2 rounded-full bg-blue-400 dark:bg-blue-500" style="width:{{ min(100,($cat->value/$maxCat)*100) }}%"></div>
                </div>
                <span class="text-xs text-gray-400 dark:text-slate-500 w-16 text-right">{{ $cat->total_stock }} units</span>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Products Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">All Products Stock</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50">
                    <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Product</th>
                    <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Category</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Stock</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Cost</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Value</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($products as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white text-sm">{{ $p->name }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-slate-400 hidden sm:table-cell">{{ $p->category?->name ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-center font-bold {{ $p->stock<=0?'text-red-600 dark:text-red-400':$p->isLowStock()?'text-yellow-600 dark:text-yellow-400':'text-gray-900 dark:text-white' }}">{{ $p->stock }}</td>
                    <td class="px-4 py-2.5 text-right text-xs text-gray-500 dark:text-slate-400 hidden sm:table-cell">৳{{ number_format($p->cost_price) }}</td>
                    <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-900 dark:text-white">৳{{ number_format($p->stock * $p->cost_price) }}</td>
                    <td class="px-4 py-2.5 text-center">
                        @if($p->stock<=0) <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded-full">Out</span>
                        @elseif($p->isLowStock()) <span class="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-0.5 rounded-full">Low</span>
                        @else <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2 py-0.5 rounded-full">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $products->links() }}</div>
    @endif
</div>

@endsection


{{-- ============================================================
     FILE: resources/views/reports/customer-due.blade.php
     ============================================================ --}}