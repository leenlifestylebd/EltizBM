@extends('layouts.app')
@section('title', 'Stock Adjustments')

@section('content')

<div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Stock Adjustments</h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- New Adjustment Form --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">New Adjustment</h3>

        <form method="POST" action="{{ route('inventory.adjustments.store') }}" class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Product <span class="text-red-500">*</span></label>
                <select name="product_id" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select product</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-stock="{{ $p->stock }}">{{ $p->name }} (Stock: {{ $p->stock }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Adjustment Type <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="increase">➕ Increase Stock</option>
                    <option value="decrease">➖ Decrease Stock</option>
                    <option value="damage">💥 Damage / Loss</option>
                    <option value="return">↩️ Return / Restock</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Quantity <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" required min="1"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                       placeholder="Enter quantity">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Reason</label>
                <input type="text" name="reason"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                       placeholder="e.g. Found extra stock, damaged in warehouse...">
            </div>

            <button type="submit"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Apply Adjustment
            </button>
        </form>
    </div>

    {{-- Adjustment History --}}
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Adjustment History</h3>
        </div>

        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($adjustments as $adj)
            <div class="flex items-center gap-3 px-4 py-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                    {{ in_array($adj->type, ['increase','return']) ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                    <svg class="w-4 h-4 {{ in_array($adj->type, ['increase','return']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="{{ in_array($adj->type, ['increase','return']) ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' }}"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $adj->product->name ?? 'N/A' }}</p>
                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                        <span class="text-xs text-gray-400 dark:text-slate-500">{{ ucfirst($adj->type) }}</span>
                        @if($adj->reason)
                        <span class="text-xs text-gray-400 dark:text-slate-500">· {{ $adj->reason }}</span>
                        @endif
                        <span class="text-xs text-gray-400 dark:text-slate-500">· {{ $adj->created_at->format('d M, h:i A') }}</span>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <span class="text-sm font-bold {{ in_array($adj->type, ['increase','return']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ in_array($adj->type, ['increase','return']) ? '+' : '-' }}{{ $adj->quantity }}
                    </span>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $adj->quantity_before }} → {{ $adj->quantity_after }}</p>
                </div>
            </div>
            @empty
            <div class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No adjustments yet</div>
            @endforelse
        </div>

        @if($adjustments->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $adjustments->links() }}</div>
        @endif
    </div>

</div>

@endsection


{{-- ============================================================
     FILE: resources/views/inventory/barcode.blade.php
     ============================================================ --}}