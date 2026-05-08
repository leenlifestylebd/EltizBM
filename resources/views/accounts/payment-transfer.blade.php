@extends('layouts.app')
@section('title', 'Payment Transfer')

@section('content')

<div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Payment Transfers</h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">New Transfer</h3>
        <form method="POST" action="{{ route('accounts.payment-transfer.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Transfer description...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">৳</span>
                    <input type="number" name="amount" required min="0" step="0.01"
                           class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">From Account</label>
                <select name="from_method" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(['cash','bkash','nagad','bank'] as $m)
                        <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">To Account</label>
                <select name="to_method" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(['cash','bkash','nagad','bank'] as $m)
                        <option value="{{ $m }}" {{ $m === 'bank' ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                <input type="date" name="transaction_date" required value="{{ today()->format('Y-m-d') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Record Transfer
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Transfer History</h3>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($transfers as $txn)
            <div class="flex items-center gap-3 px-4 py-3">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $txn->title }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                        {{ ucfirst($txn->payment_method) }} · {{ $txn->transaction_date->format('d M Y') }}
                    </p>
                </div>
                <span class="text-sm font-bold text-purple-600 dark:text-purple-400 flex-shrink-0">৳{{ number_format($txn->amount) }}</span>
            </div>
            @empty
            <div class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No transfers yet</div>
            @endforelse
        </div>
    </div>
</div>

@endsection