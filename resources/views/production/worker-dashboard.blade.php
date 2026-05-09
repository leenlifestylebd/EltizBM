@extends('layouts.app')
@section('title', 'My Dashboard')

@section('content')

<div class="max-w-3xl mx-auto" x-data="workerDash()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">👋 {{ $employee->name }}</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $employee->designation ?? 'Production Worker' }}</p>
        </div>
        <button @click="showEntry = !showEntry"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Entry
        </button>
    </div>

    {{-- Add Entry Form --}}
    <div x-show="showEntry" x-cloak
         class="bg-white dark:bg-slate-800 rounded-xl border border-blue-200 dark:border-blue-700 p-4 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">New Production Entry</h3>
        <form method="POST" action="{{ route('production.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Product/Rate <span class="text-red-500">*</span></label>
                    <select name="production_rate_id" required
                            @change="selectedRate = rates.find(r => r.id == $event.target.value); calcAmount()"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Product</option>
                        @foreach($rates as $rate)
                            <option value="{{ $rate->id }}">{{ $rate->full_name }} — ৳{{ number_format($rate->rate) }}/{{ $rate->unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="entry_date" value="{{ today()->toDateString() }}" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" x-model.number="quantity"
                           @input="calcAmount()" min="1" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <span class="text-xs text-gray-500 dark:text-slate-400">Amount:</span>
                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">৳<span x-text="amount.toLocaleString()">0</span></span>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                <input type="text" name="notes"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Optional...">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                    Save Entry
                </button>
                <button type="button" @click="showEntry = false"
                        class="px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    {{-- Date Range Filter --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-2">
            <div class="flex items-center gap-2 flex-1">
                <label class="text-xs font-medium text-gray-700 dark:text-slate-300 whitespace-nowrap">From:</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="flex-1 px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-center gap-2 flex-1">
                <label class="text-xs font-medium text-gray-700 dark:text-slate-300 whitespace-nowrap">To:</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="flex-1 px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Filter</button>
                <a href="{{ route('production.worker.dashboard') }}"
                   class="px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-sm rounded-lg hover:bg-gray-200">Reset</a>
            </div>
        </form>
        {{-- Quick filters --}}
        <div class="flex gap-2 mt-2 flex-wrap">
            @foreach([
                ['label'=>'Today',      'from'=>today()->toDateString(),                    'to'=>today()->toDateString()],
                ['label'=>'This Week',  'from'=>today()->startOfWeek()->toDateString(),     'to'=>today()->toDateString()],
                ['label'=>'This Month', 'from'=>today()->startOfMonth()->toDateString(),    'to'=>today()->toDateString()],
                ['label'=>'Last Month', 'from'=>today()->subMonth()->startOfMonth()->toDateString(), 'to'=>today()->subMonth()->endOfMonth()->toDateString()],
            ] as $q)
            <a href="{{ route('production.worker.dashboard', ['from'=>$q['from'], 'to'=>$q['to']]) }}"
               class="px-2.5 py-1 text-xs rounded-lg border transition-colors
                      {{ $from === $q['from'] && $to === $q['to'] ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-50' }}">
                {{ $q['label'] }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30 text-center">
            <p class="text-xl font-bold text-green-600 dark:text-green-400">৳{{ number_format($totalEarning) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total Earning</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30 text-center">
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $totalQty }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total Pieces</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-red-100 dark:border-red-900/30 text-center">
            <p class="text-xl font-bold text-red-600 dark:text-red-400">৳{{ number_format($totalAdvance) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Advance Taken</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-purple-100 dark:border-purple-900/30 text-center">
            <p class="text-xl font-bold {{ $netPayable >= 0 ? 'text-purple-600 dark:text-purple-400' : 'text-red-600' }}">৳{{ number_format(abs($netPayable)) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $netPayable >= 0 ? 'Net Payable' : 'Overpaid' }}</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-4 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl" x-data="{ tab: 'entries' }">
        <button @click="tab = 'entries'"
                class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
                :class="tab === 'entries' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
            📦 Production ({{ $entries->count() }})
        </button>
        <button @click="tab = 'payments'"
                class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
                :class="tab === 'payments' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
            💰 Payments ({{ $payments->count() }})
        </button>
        <button @click="tab = 'report'"
                class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
                :class="tab === 'report' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
            📊 Report
        </button>
    </div>

    {{-- TAB: Production Entries --}}
    <div x-data="{ tab: 'entries' }">
    <div x-show="tab === 'entries'">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Date</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Product</th>
                            <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Qty</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Rate</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @forelse($entries as $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry->productionRate->product_name }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ implode(' · ', array_filter([$entry->productionRate->variant_name, $entry->productionRate->color, $entry->productionRate->size])) }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">{{ $entry->quantity }}</td>
                            <td class="px-4 py-3 text-right text-xs text-gray-500">৳{{ number_format($entry->productionRate->rate) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-green-600 dark:text-green-400">৳{{ number_format($entry->amount) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No entries in this period</td></tr>
                        @endforelse
                    </tbody>
                    @if($entries->count() > 0)
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-slate-700/50 border-t border-gray-100 dark:border-slate-700">
                            <td colspan="2" class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-slate-300">Total</td>
                            <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">{{ $totalQty }}</td>
                            <td></td>
                            <td class="px-4 py-3 text-right font-bold text-green-600 dark:text-green-400">৳{{ number_format($totalEarning) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: Payments --}}
    <div x-show="tab === 'payments'" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($payments as $payment)
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($payment->type) }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500">
                            {{ $payment->payment_date->format('d M Y') }} · {{ ucfirst($payment->payment_method) }}
                        </p>
                        @if($payment->notes)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $payment->notes }}</p>
                        @endif
                    </div>
                    <p class="font-bold text-sm {{ in_array($payment->type, ['advance','deduction']) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                        {{ in_array($payment->type, ['advance','deduction']) ? '-' : '+' }}৳{{ number_format($payment->amount) }}
                    </p>
                </div>
                @empty
                <div class="px-4 py-12 text-center text-sm text-gray-400">No payments in this period</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- TAB: Report --}}
    <div x-show="tab === 'report'" x-cloak>
        <div class="space-y-3">
            {{-- Daily breakdown --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daily Breakdown</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Date</th>
                                <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Pieces</th>
                                <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Earning</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                            @foreach($dailyReport as $day)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($day->date)->format('d M Y, D') }}</td>
                                <td class="px-4 py-2.5 text-center font-semibold text-gray-900 dark:text-white">{{ $day->total_qty }}</td>
                                <td class="px-4 py-2.5 text-right font-medium text-green-600 dark:text-green-400">৳{{ number_format($day->total_amount) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Product breakdown --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Product Breakdown</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Product</th>
                                <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Pieces</th>
                                <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Earning</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                            @foreach($productReport as $prod)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-white">{{ $prod->product_name }}</td>
                                <td class="px-4 py-2.5 text-center font-semibold text-gray-900 dark:text-white">{{ $prod->total_qty }}</td>
                                <td class="px-4 py-2.5 text-right font-medium text-green-600 dark:text-green-400">৳{{ number_format($prod->total_amount) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function workerDash() {
    return {
        showEntry: false,
        selectedRate: null,
        quantity: 1,
        amount: 0,
        rates: @json($rates),

        calcAmount() {
            this.amount = this.selectedRate ? this.selectedRate.rate * this.quantity : 0;
        }
    }
}
</script>
@endpush