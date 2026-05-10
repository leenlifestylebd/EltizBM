@extends('layouts.app')
@section('title', 'Worker Dashboard')

@section('content')
<div x-data="workerDash()" class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                {{ strtoupper(substr($employee->name, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">{{ $employee->name }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $employee->designation ?? 'Production Worker' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if(in_array(auth()->user()->role, ['admin', 'owner']) && $allEmployees->count() > 1)
            <select onchange="window.location.href='{{ route('production.worker.dashboard') }}?employee_id='+this.value+'&from={{ $from }}&to={{ $to }}'"
                    class="px-2 py-1.5 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                @foreach($allEmployees as $emp)
                <option value="{{ $emp->id }}" {{ $emp->id === $employee->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
            @endif
            <button @click="showEntry = !showEntry"
                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add
            </button>
        </div>
    </div>

    {{-- Add Entry Form --}}
    <div x-show="showEntry" x-cloak class="bg-white dark:bg-slate-800 rounded-xl border border-blue-200 p-4 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">New Production Entry</h3>
        <form method="POST" action="{{ route('production.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Product/Rate *</label>
                <select name="production_rate_id" required
                        @change="selectedRate = rates.find(r => r.id == $event.target.value); calcAmount()"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Product</option>
                    @foreach($rates as $rate)
                    <option value="{{ $rate->id }}">
                        {{ $rate->product_name }}{{ $rate->size ? ' ['.$rate->size.']' : '' }}{{ $rate->color ? ' ('.$rate->color.')' : '' }} — ৳{{ number_format($rate->rate) }}/{{ $rate->unit }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Size & Color (auto-filled) --}}
            <div class="grid grid-cols-2 gap-3" x-show="selectedRate && (selectedRate.size || selectedRate.color)" x-cloak>
                <div x-show="selectedRate && selectedRate.size">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Size</label>
                    <input type="text" readonly :value="selectedRate ? selectedRate.size : ''"
                           class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 dark:bg-slate-700 text-gray-700">
                </div>
                <div x-show="selectedRate && selectedRate.color">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Color</label>
                    <input type="text" readonly :value="selectedRate ? selectedRate.color : ''"
                           class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 dark:bg-slate-700 text-gray-700">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Date *</label>
                    <input type="date" name="entry_date" value="{{ today()->toDateString() }}" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Quantity *</label>
                    <input type="number" name="quantity" x-model.number="quantity" @input="calcAmount()" min="1" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <span class="text-xs text-gray-500">Amount:</span>
                <span class="text-lg font-bold text-blue-600">৳<span x-text="amount.toLocaleString()">0</span></span>
            </div>

            <input type="text" name="notes" placeholder="Notes (optional)"
                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white font-medium py-2.5 text-sm rounded-lg hover:bg-blue-700">Save Entry</button>
                <button type="button" @click="showEntry = false" class="px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-600 text-sm rounded-lg">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Date Filter --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
        <form method="GET" class="space-y-2">
            @if(in_array(auth()->user()->role, ['admin', 'owner']))
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            @endif
            <div class="flex gap-2">
                <input type="date" name="from" value="{{ $from }}"
                       class="flex-1 px-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="date" name="to" value="{{ $to }}"
                       class="flex-1 px-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-xs rounded-lg">Filter</button>
            </div>
            <div class="flex gap-1.5 flex-wrap">
                @foreach([
                    ['label'=>'Today',      'from'=>today()->toDateString(),                    'to'=>today()->toDateString()],
                    ['label'=>'This Week',  'from'=>today()->startOfWeek()->toDateString(),     'to'=>today()->toDateString()],
                    ['label'=>'This Month', 'from'=>today()->startOfMonth()->toDateString(),    'to'=>today()->toDateString()],
                    ['label'=>'Last Month', 'from'=>today()->subMonth()->startOfMonth()->toDateString(), 'to'=>today()->subMonth()->endOfMonth()->toDateString()],
                ] as $q)
                <a href="{{ route('production.worker.dashboard', array_merge(['from'=>$q['from'], 'to'=>$q['to']], in_array(auth()->user()->role, ['admin','owner']) ? ['employee_id'=>$employee->id] : [])) }}"
                   class="px-2.5 py-1 text-xs rounded-lg border transition-colors
                          {{ $from === $q['from'] && $to === $q['to'] ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 hover:bg-gray-50' }}">
                    {{ $q['label'] }}
                </a>
                @endforeach
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-green-100 dark:border-green-900/30">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Total Earning</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">৳{{ number_format($totalEarning) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-blue-100 dark:border-blue-900/30">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Total Pieces</p>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalQty) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-red-100 dark:border-red-900/30">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Advance Taken</p>
            <p class="text-xl font-bold text-red-600 dark:text-red-400">৳{{ number_format($totalAdvance) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border {{ $netPayable >= 0 ? 'border-purple-100' : 'border-orange-100' }}">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">{{ $netPayable >= 0 ? 'Net Payable' : 'Overpaid' }}</p>
            <p class="text-xl font-bold {{ $netPayable >= 0 ? 'text-purple-600 dark:text-purple-400' : 'text-orange-600' }}">৳{{ number_format(abs($netPayable)) }}</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'production' }">
        <div class="flex gap-1 mb-4 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl">
            <button @click="tab = 'production'" class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
                    :class="tab === 'production' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                📦 Production ({{ $entries->count() }})
            </button>
            <button @click="tab = 'payments'" class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
                    :class="tab === 'payments' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                💰 Payments ({{ $payments->count() }})
            </button>
            <button @click="tab = 'report'" class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
                    :class="tab === 'report' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                📊 Report
            </button>
        </div>

        {{-- Production Tab --}}
        <div x-show="tab === 'production'">
            {{-- Mobile Cards --}}
            <div class="md:hidden space-y-2">
                @forelse($entries as $entry)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry->productionRate->product_name }}</p>
                            @if($entry->productionRate->size || $entry->productionRate->color)
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ implode(' · ', array_filter([$entry->productionRate->size, $entry->productionRate->color])) }}
                            </p>
                            @endif
                            <p class="text-xs text-gray-400 mt-0.5">{{ $entry->entry_date->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-600">৳{{ number_format($entry->amount) }}</p>
                            <p class="text-xs text-gray-500">{{ $entry->quantity }} × ৳{{ number_format($entry->productionRate->rate) }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 py-12 text-center">
                    <p class="text-sm text-gray-400">No entries in this period</p>
                </div>
                @endforelse
                @if($entries->count() > 0)
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 flex justify-between">
                    <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">Total: {{ $totalQty }} pcs</span>
                    <span class="text-sm font-bold text-green-600">৳{{ number_format($totalEarning) }}</span>
                </div>
                @endif
            </div>

            {{-- Desktop Table --}}
            <div class="hidden md:block bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
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
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry->productionRate->product_name }}</p>
                                @if($entry->productionRate->size || $entry->productionRate->color)
                                <p class="text-xs text-gray-400">{{ implode(' · ', array_filter([$entry->productionRate->size, $entry->productionRate->color])) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-semibold">{{ $entry->quantity }}</td>
                            <td class="px-4 py-3 text-right text-xs text-gray-500">৳{{ number_format($entry->productionRate->rate) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-green-600">৳{{ number_format($entry->amount) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No entries in this period</td></tr>
                        @endforelse
                    </tbody>
                    @if($entries->count() > 0)
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-slate-700/50 border-t border-gray-100">
                            <td colspan="2" class="px-4 py-3 text-xs font-semibold text-gray-600">Total</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $totalQty }}</td>
                            <td></td>
                            <td class="px-4 py-3 text-right font-bold text-green-600">৳{{ number_format($totalEarning) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Payments Tab --}}
        <div x-show="tab === 'payments'" x-cloak>
            <div class="space-y-2">
                @forelse($payments as $payment)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($payment->type) }}</p>
                        <p class="text-xs text-gray-400">{{ $payment->payment_date->format('d M Y') }} · {{ ucfirst($payment->payment_method) }}</p>
                        @if($payment->notes)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $payment->notes }}</p>
                        @endif
                    </div>
                    <p class="font-bold text-sm {{ in_array($payment->type, ['advance','deduction']) ? 'text-red-600' : 'text-green-600' }}">
                        {{ in_array($payment->type, ['advance','deduction']) ? '-' : '+' }}৳{{ number_format($payment->amount) }}
                    </p>
                </div>
                @empty
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 py-12 text-center">
                    <p class="text-sm text-gray-400">No payments in this period</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Report Tab --}}
        <div x-show="tab === 'report'" x-cloak>
            <div class="space-y-4">

                {{-- Chart --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">📈 Daily Earning Chart</h3>
                    <div class="relative h-40">
                        <canvas id="earningChart"></canvas>
                    </div>
                </div>

                {{-- Daily Breakdown --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daily Breakdown</h3>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($dailyReport as $day)
                        <div class="px-4 py-2.5 flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($day->date)->format('d M Y, D') }}</span>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-gray-500">{{ $day->total_qty }} pcs</span>
                                <span class="text-sm font-medium text-green-600">৳{{ number_format($day->total_amount) }}</span>
                            </div>
                        </div>
                        @endforeach
                        @if($dailyReport->isEmpty())
                        <div class="px-4 py-8 text-center text-sm text-gray-400">No data</div>
                        @endif
                    </div>
                </div>

                {{-- Product Breakdown --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Product Breakdown</h3>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($productReport as $prod)
                        <div class="px-4 py-2.5 flex items-center justify-between">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $prod->product_name }}</span>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-gray-500">{{ $prod->total_qty }} pcs</span>
                                <span class="text-sm font-medium text-green-600">৳{{ number_format($prod->total_amount) }}</span>
                            </div>
                        </div>
                        @endforeach
                        @if($productReport->isEmpty())
                        <div class="px-4 py-8 text-center text-sm text-gray-400">No data</div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

// Chart
const dailyData = @json($dailyReport);
const labels = dailyData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('en-BD', { day: 'numeric', month: 'short' });
});
const amounts = dailyData.map(d => parseFloat(d.total_amount));

const ctx = document.getElementById('earningChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['No data'],
            datasets: [{
                label: 'Earning (৳)',
                data: amounts.length ? amounts : [0],
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => '৳' + ctx.raw.toLocaleString()
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: val => '৳' + val.toLocaleString(),
                        font: { size: 10 }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { font: { size: 10 } },
                    grid: { display: false }
                }
            }
        }
    });
}
</script>
@endpush