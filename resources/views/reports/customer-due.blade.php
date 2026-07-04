@extends('layouts.app')
@section('title', 'Customer Due Report')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Customer Due Report</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total outstanding: <strong class="text-red-600 dark:text-red-400">৳{{ number_format($totalDue) }}</strong></p>
    </div>
    <div x-data="{ open: false }" @click.outside="open = false" class="relative">
        <button @click="open = !open" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 text-gray-700 dark:text-slate-300 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
        </button>
        <div x-show="open" x-cloak x-transition class="absolute right-0 top-10 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
            <a href="{{ route('reports.export','customer-due') }}?format=csv" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('reports.export','customer-due') }}?format=pdf" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
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
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Action</th>
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
                    <td class="px-4 py-3 text-center">
                        <button type="button"
                                @click="$dispatch('open-collect-due', { id: {{ $c->id }}, name: @js($c->name), due: {{ $c->due_amount }} })"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                            Collect
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No customers with due amounts 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $customers->links() }}</div>
    @endif
</div>

{{-- Collect Due Modal --}}
<div x-data="{ open: false, c: { id: null, name: '', due: 0 } }"
     @open-collect-due.window="c = $event.detail; open = true">
    <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
        <div @click.outside="open = false" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-sm">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Collect from <span x-text="c.name"></span></h3>
                <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <form method="POST" :action="`{{ url('people/customers') }}/${c.id}/collect-due`" class="p-5 space-y-4">
                @csrf
                <p class="text-xs text-gray-500 dark:text-slate-400">Outstanding due: <span class="font-semibold text-red-500">৳<span x-text="Number(c.due).toLocaleString()"></span></span></p>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Amount</label>
                    <input type="number" step="0.01" min="0.01" :max="c.due" name="amount" :value="c.due" required
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Method</label>
                    <select name="payment_method"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="cash">Cash</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                        <option value="bank">Bank</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date</label>
                    <input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <p class="text-xs text-gray-400 dark:text-slate-500">Applied oldest-first to this customer's unpaid orders. আগের দিনের হলে তারিখ বদলে দিন।</p>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">
                    Collect payment
                </button>
            </form>
        </div>
    </div>
</div>

@endsection


{{-- ============================================================
     FILE: resources/views/reports/account-balance.blade.php
     ============================================================ --}}