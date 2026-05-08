@extends('layouts.app')
@section('title', 'Customer Due Report')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Customer Due Report</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total outstanding: <strong class="text-red-600 dark:text-red-400">৳{{ number_format($totalDue) }}</strong></p>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">#</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Customer</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Phone</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Total Purchase</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400 text-red-500">Due Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($customers as $i => $c)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-slate-400">{{ ($customers->currentPage()-1)*$customers->perPage()+$i+1 }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $c->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $c->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-slate-300">৳{{ number_format($c->total_purchase) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">৳{{ number_format($c->due_amount) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No customers with due amounts 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $customers->links() }}</div>
    @endif
</div>

@endsection


{{-- ============================================================
     FILE: resources/views/reports/account-balance.blade.php
     ============================================================ --}}