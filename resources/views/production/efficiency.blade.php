@extends('layouts.app')
@section('title', 'Worker Efficiency Report')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Worker Efficiency</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Pieces per day, trends and product breakdown</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-5">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1.5">
            <label class="text-xs text-gray-500 dark:text-slate-400">From</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="px-2.5 py-1.5 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
        </div>
        <div class="flex items-center gap-1.5">
            <label class="text-xs text-gray-500 dark:text-slate-400">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="px-2.5 py-1.5 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
        </div>
        <div class="flex items-center gap-1.5">
            <label class="text-xs text-gray-500 dark:text-slate-400">Worker</label>
            <select name="employee"
                    class="px-2.5 py-1.5 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:ring-2 focus:ring-primary-500">
                <option value="">All Workers</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ $empId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-3 py-1.5 bg-primary-600 text-white text-xs rounded-lg hover:bg-primary-700 transition-colors">Apply</button>
    </form>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <p class="text-xs text-gray-500 dark:text-slate-400">Total Pieces</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalQty) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <p class="text-xs text-gray-500 dark:text-slate-400">Total Wages</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">৳{{ number_format($totalAmount) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <p class="text-xs text-gray-500 dark:text-slate-400">Active Workers</p>
        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">{{ $workerStats->count() }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <p class="text-xs text-gray-500 dark:text-slate-400">Avg per Worker/Day</p>
        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">
            {{ $workerStats->count() > 0 ? number_format($workerStats->avg('avg_per_day'), 1) : '0' }}
        </p>
    </div>
</div>

{{-- Daily Output Chart --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-5">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Daily Output Trend</h3>
    <div class="h-48">
        <canvas id="efficiencyChart"></canvas>
    </div>
</div>

{{-- Worker Performance Table --}}
@if($workerStats->count() > 0)
<div class="space-y-4">
    @foreach($workerStats as $stat)
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden"
         x-data="{ open: false }">
        <div class="flex items-center gap-4 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors"
             @click="open = !open">
            {{-- Avatar --}}
            <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                <span class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ mb_substr($stat['employee']->name, 0, 1) }}</span>
            </div>

            {{-- Name + stats --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $stat['employee']->name }}</p>
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $stat['working_days'] }} days</span>
                </div>
                {{-- Progress bar (relative to best worker) --}}
                @php $maxQty = $workerStats->max('total_qty') ?: 1; $pct = round($stat['total_qty'] / $maxQty * 100); @endphp
                <div class="mt-1 w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5">
                    <div class="bg-primary-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>

            {{-- Key metrics --}}
            <div class="hidden sm:flex items-center gap-6 text-center flex-shrink-0">
                <div>
                    <p class="text-xs text-gray-400 dark:text-slate-500">Total Pieces</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($stat['total_qty']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 dark:text-slate-500">Avg/Day</p>
                    <p class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ $stat['avg_per_day'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 dark:text-slate-500">Best Day</p>
                    <p class="text-sm font-bold text-green-600 dark:text-green-400">{{ $stat['max_day'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 dark:text-slate-500">Wages</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">৳{{ number_format($stat['total_amount']) }}</p>
                </div>
            </div>

            {{-- Chevron --}}
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        {{-- Expanded detail --}}
        <div x-show="open" x-cloak class="border-t border-gray-50 dark:border-slate-700">
            <div class="p-4 grid grid-cols-2 gap-4">
                {{-- Mobile metrics --}}
                <div class="sm:hidden col-span-2 grid grid-cols-4 gap-2 mb-3">
                    <div class="text-center p-2 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs text-gray-400">Pieces</p>
                        <p class="font-bold text-gray-900 dark:text-white">{{ number_format($stat['total_qty']) }}</p>
                    </div>
                    <div class="text-center p-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                        <p class="text-xs text-primary-400">Avg/Day</p>
                        <p class="font-bold text-primary-600 dark:text-primary-400">{{ $stat['avg_per_day'] }}</p>
                    </div>
                    <div class="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-xs text-green-400">Best Day</p>
                        <p class="font-bold text-green-600 dark:text-green-400">{{ $stat['max_day'] }}</p>
                    </div>
                    <div class="text-center p-2 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs text-gray-400">Wages</p>
                        <p class="font-bold text-gray-900 dark:text-white text-xs">৳{{ number_format($stat['total_amount']) }}</p>
                    </div>
                </div>

                {{-- Product breakdown --}}
                <div class="col-span-2 sm:col-span-1">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 mb-2">Product Breakdown</h4>
                    <div class="space-y-1.5">
                        @foreach($stat['products'] as $prod)
                        @php $prodPct = $stat['total_qty'] > 0 ? round($prod['qty'] / $stat['total_qty'] * 100) : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-0.5">
                                <span class="text-gray-700 dark:text-slate-300 truncate max-w-[160px]">{{ $prod['name'] }}</span>
                                <span class="text-gray-500 dark:text-slate-400 flex-shrink-0 ml-2">{{ number_format($prod['qty']) }} pcs ({{ $prodPct }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1">
                                <div class="bg-primary-400 h-1 rounded-full" style="width:{{ $prodPct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Daily mini heatmap --}}
                <div class="col-span-2 sm:col-span-1">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 mb-2">Daily Output</h4>
                    <div class="flex flex-wrap gap-1">
                        @php $maxDayQty = max($stat['daily_pieces']->values()->toArray() ?: [1]); @endphp
                        @foreach($stat['daily_pieces'] as $date => $qty)
                        @php
                            $intensity = $maxDayQty > 0 ? ($qty / $maxDayQty) : 0;
                            $bg = $intensity > 0.75 ? 'bg-primary-600' : ($intensity > 0.5 ? 'bg-primary-400' : ($intensity > 0.25 ? 'bg-primary-200 dark:bg-primary-800' : 'bg-primary-100 dark:bg-primary-900/30'));
                        @endphp
                        <div title="{{ \Carbon\Carbon::parse($date)->format('d M') }}: {{ $qty }} pcs"
                             class="w-5 h-5 rounded {{ $bg }} cursor-help flex items-center justify-center">
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-1 text-xs text-gray-400 dark:text-slate-500 flex items-center gap-1">
                        <span>Less</span>
                        <div class="w-3 h-3 rounded bg-primary-100 dark:bg-primary-900/30"></div>
                        <div class="w-3 h-3 rounded bg-primary-200 dark:bg-primary-800"></div>
                        <div class="w-3 h-3 rounded bg-primary-400"></div>
                        <div class="w-3 h-3 rounded bg-primary-600"></div>
                        <span>More</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 flex flex-col items-center justify-center py-16">
    <svg class="w-12 h-12 text-gray-200 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
    <p class="text-sm text-gray-400 dark:text-slate-500">No production entries for this period</p>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('efficiencyChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Pieces Produced',
                data: @json($chartData),
                backgroundColor: 'rgba(59,130,246,0.7)',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, maxRotation: 0 } },
                y: { beginAtZero: true, ticks: { font: { size: 11 } } }
            }
        }
    });
}
</script>
@endpush
