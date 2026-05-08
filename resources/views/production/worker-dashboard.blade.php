@extends('layouts.app')
@section('title', 'My Production')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">My Production</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $employee->name }}</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="month" value="{{ $month }}"
                   class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">View</button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30 text-center">
            <p class="text-xl font-bold text-green-600 dark:text-green-400">৳{{ number_format($totalEarning) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total Earning</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-red-100 dark:border-red-900/30 text-center">
            <p class="text-xl font-bold text-red-600 dark:text-red-400">৳{{ number_format($totalAdvance) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Advance Taken</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30 text-center">
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">৳{{ number_format($netPayable) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Net Payable</p>
        </div>
    </div>

    {{-- Production Entries --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-4">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Production History</h3>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($entries as $entry)
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry->productionRate->full_name }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $entry->entry_date->format('d M Y') }} · {{ $entry->quantity }} {{ $entry->productionRate->unit }}</p>
                </div>
                <p class="font-bold text-green-600 dark:text-green-400">৳{{ number_format($entry->amount) }}</p>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400">No entries this month</div>
            @endforelse
        </div>
    </div>

    {{-- Payments --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Payment History</h3>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($payments as $payment)
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($payment->type) }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $payment->payment_date->format('d M Y') }} · {{ $payment->payment_method }}</p>
                    @if($payment->notes)
                        <p class="text-xs text-gray-400">{{ $payment->notes }}</p>
                    @endif
                </div>
                <p class="font-bold {{ in_array($payment->type, ['advance','deduction']) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    {{ in_array($payment->type, ['advance','deduction']) ? '-' : '+' }}৳{{ number_format($payment->amount) }}
                </p>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400">No payments this month</div>
            @endforelse
        </div>
    </div>
</div>

@endsection