@extends('layouts.app')
@section('title', 'Production Entry')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Production Entry</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Daily production tracking</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('production.payroll') }}"
           class="px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            📊 Payroll
        </a>
        <button onclick="document.getElementById('addEntryModal').classList.remove('hidden')"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            + Add Entry
        </button>
    </div>
</div>

{{-- Date Filter --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" class="flex items-center gap-3">
        <label class="text-xs font-medium text-gray-700 dark:text-slate-300">Date:</label>
        <input type="date" name="date" value="{{ $date }}"
               class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Filter</button>
        <div class="ml-auto">
            <span class="text-xs text-gray-500 dark:text-slate-400">Total Today:</span>
            <span class="text-sm font-bold text-primary-600 dark:text-primary-400 ml-1">৳{{ number_format($totalToday) }}</span>
        </div>
    </form>
</div>

{{-- Entries Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Worker</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Product</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Qty</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Rate</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Amount</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($entries as $entry)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $entry->employee->name }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-slate-300">
                        {{ $entry->productionRate->full_name }}
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">{{ $entry->quantity }}</td>
                    <td class="px-4 py-3 text-right text-gray-600 dark:text-slate-300">৳{{ number_format($entry->productionRate->rate) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-green-600 dark:text-green-400">৳{{ number_format($entry->amount) }}</td>
                    <td class="px-4 py-3 text-center">
                        <form method="POST" action="{{ route('production.destroy', $entry) }}" onsubmit="return confirm('Delete?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg opacity-0 group-hover:opacity-100 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">No entries for this date</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Entry Modal --}}
<div id="addEntryModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     x-data="{ selectedRate: null, quantity: 1, amount: 0, rates: @json($rates) }">
    <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Add Production Entry</h3>
            <button onclick="document.getElementById('addEntryModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
        </div>
        <form method="POST" action="{{ route('production.store') }}" class="p-5 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Worker <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Worker</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Product/Rate <span class="text-red-500">*</span></label>
                <select name="production_rate_id" required
                        @change="selectedRate = rates.find(r => r.id == $event.target.value); amount = selectedRate ? selectedRate.rate * quantity : 0"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Product</option>
                    @foreach($rates as $rate)
                        <option value="{{ $rate->id }}">{{ $rate->full_name }} — ৳{{ number_format($rate->rate) }}/{{ $rate->unit }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="entry_date" value="{{ $date }}" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" x-model.number="quantity"
                           @input="amount = selectedRate ? selectedRate.rate * quantity : 0"
                           min="1" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <div class="p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-slate-400">Amount:</p>
                <p class="text-lg font-bold text-primary-600 dark:text-primary-400">৳<span x-text="amount.toLocaleString()">0</span></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                <input type="text" name="notes"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                       placeholder="Optional notes...">
            </div>
            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Save Entry
            </button>
        </form>
    </div>
</div>

@endsection