{{-- ============================================================
     FILE: resources/views/inventory/transfers.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Stock Transfers')

@section('content')

<div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Stock Transfers</h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- New Transfer Form --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">New Transfer</h3>
        <form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Product <span class="text-red-500">*</span></label>
                <select name="product_id" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select product</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->stock }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">From Warehouse</label>
                    <select name="from_warehouse_id" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">To Warehouse</label>
                    <select name="to_warehouse_id" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Quantity <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" required min="1"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                <input type="text" name="reason" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Reason for transfer...">
            </div>
            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Create Transfer
            </button>
        </form>
    </div>

    {{-- Transfer History --}}
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Transfer History</h3>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($transfers as $transfer)
            <div class="flex items-center gap-3 px-4 py-3">
                <div class="w-8 h-8 bg-teal-100 dark:bg-teal-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $transfer->product->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                        Transfer · {{ $transfer->reason ?? '' }} · {{ $transfer->created_at->format('d M, h:i A') }}
                    </p>
                </div>
                <span class="text-sm font-bold text-teal-600 dark:text-teal-400 flex-shrink-0">{{ $transfer->quantity }}</span>
            </div>
            @empty
            <div class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No transfers yet</div>
            @endforelse
        </div>
    </div>

</div>

@endsection
