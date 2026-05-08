@extends('layouts.app')
@section('title', 'Customer Analytics')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Customer Analytics</h2>
    <form method="GET" class="flex items-center gap-2">
        <input type="date" name="from" value="{{ $from }}" class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="date" name="to" value="{{ $to }}" class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm transition-colors">Apply</button>
    </form>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Total Customers</p>
        <p class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($stats['total']) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">New (Period)</p>
        <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['new'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-red-100 dark:border-red-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">With Due</p>
        <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['with_due'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-orange-100 dark:border-orange-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Total Due</p>
        <p class="text-xl font-bold text-orange-600 dark:text-orange-400 mt-1">৳{{ number_format($stats['total_due']) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    {{-- Top Customers --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top Customers by Purchase</h3>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($topCustomers as $i => $c)
            <div class="flex items-center gap-3 px-4 py-3">
                <span class="w-6 h-6 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $c->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $c->phone ?? '—' }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">৳{{ number_format($c->total_purchase) }}</p>
                    @if($c->due_amount > 0)
                        <p class="text-xs text-red-500">Due: ৳{{ number_format($c->due_amount) }}</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400">No data</div>
            @endforelse
        </div>
    </div>

    {{-- Due Customers --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Highest Due Customers</h3>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($dueCustomers as $i => $c)
            <div class="flex items-center gap-3 px-4 py-3">
                <span class="w-6 h-6 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $c->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $c->phone ?? '—' }}</p>
                </div>
                <span class="text-sm font-bold text-red-600 dark:text-red-400 flex-shrink-0">৳{{ number_format($c->due_amount) }}</span>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400">No dues! 🎉</div>
            @endforelse
        </div>
    </div>
</div>

@endsection