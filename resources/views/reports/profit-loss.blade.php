@extends('layouts.app')
@section('title', 'Profit & Loss')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('reports.all') }}" class="text-xs text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300">Reports</a>
        <span class="text-gray-300 dark:text-slate-600">/</span>
        <span class="text-xs font-medium text-gray-700 dark:text-slate-300">Profit & Loss</span>
    </div>
    {{-- Export Buttons --}}
    <div class="flex items-center gap-2" x-data="{ open: false }" @click.outside="open = false">
        <button @click="open = !open"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 transition-colors shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
            <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition
             class="absolute right-4 mt-24 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
            @php $qs = http_build_query(['from'=>$from,'to'=>$to,'group_by'=>$groupBy]); @endphp
            <a href="{{ route('reports.export','profit-loss') }}?format=csv&{{ $qs }}"
               class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('reports.export','profit-loss') }}?format=pdf&{{ $qs }}"
               class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
</div>

{{-- Quick Presets + Date Form --}}
@php
$presets = [
    ['label'=>'Today',         'from'=>today()->toDateString(),                                              'to'=>today()->toDateString(),                                               'gb'=>'day'],
    ['label'=>'This Week',     'from'=>now()->startOfWeek()->toDateString(),                                 'to'=>now()->endOfWeek()->toDateString(),                                     'gb'=>'day'],
    ['label'=>'This Month',    'from'=>now()->startOfMonth()->toDateString(),                                'to'=>now()->endOfMonth()->toDateString(),                                    'gb'=>'day'],
    ['label'=>'Last Month',    'from'=>now()->subMonthNoOverflow()->startOfMonth()->toDateString(),          'to'=>now()->subMonthNoOverflow()->endOfMonth()->toDateString(),              'gb'=>'day'],
    ['label'=>'Last 3 Months', 'from'=>now()->subMonths(3)->startOfMonth()->toDateString(),                 'to'=>now()->endOfMonth()->toDateString(),                                    'gb'=>'month'],
    ['label'=>'This Year',     'from'=>now()->startOfYear()->toDateString(),                                 'to'=>now()->endOfYear()->toDateString(),                                     'gb'=>'month'],
];
@endphp

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
    {{-- Preset Tabs --}}
    <div class="flex flex-wrap gap-1.5 mb-3">
        @foreach($presets as $p)
        @php $active = $from === $p['from'] && $to === $p['to']; @endphp
        <a href="{{ route('reports.profit-loss', ['from'=>$p['from'],'to'=>$p['to'],'group_by'=>$p['gb']]) }}"
           class="px-3 py-1 text-xs font-medium rounded-lg border transition-colors
                  {{ $active ? 'bg-primary-600 text-white border-primary-600' : 'bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-100' }}">
            {{ $p['label'] }}
        </a>
        @endforeach
    </div>

    {{-- Custom Date Picker --}}
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-xs text-gray-400 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Group by</label>
            <select name="group_by" class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="day"   {{ $groupBy==='day'   ? 'selected':'' }}>Daily</option>
                <option value="month" {{ $groupBy==='month' ? 'selected':'' }}>Monthly</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition-colors">Apply</button>
    </form>

    <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">
        Showing: <span class="font-medium text-gray-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</span>
        to <span class="font-medium text-gray-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</span>
    </p>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
    @php
    $cards = [
        ['label'=>'Revenue',          'value'=>$revenue,          'color'=>'primary',                                      'prefix'=>'৳', 'sub'=>'from sales'],
        ['label'=>'Cost (COGS)',       'value'=>$cogs,             'color'=>'orange',                                    'prefix'=>'৳', 'sub'=>'cost of goods'],
        ['label'=>'Gross Profit',      'value'=>$grossProfit,      'color'=>$grossProfit>=0?'green':'rose',               'prefix'=>'৳', 'sub'=>$grossMargin.'% margin'],
        ['label'=>'Operating Exp.',    'value'=>$expenses,         'color'=>'red',                                       'prefix'=>'৳', 'sub'=>'transactions'],
        ['label'=>'Production Wages',  'value'=>$productionWages,  'color'=>'purple',                                    'prefix'=>'৳', 'sub'=>'worker payments'],
        ['label'=>'Net Profit',        'value'=>$netProfit,        'color'=>$netProfit>=0?'emerald':'rose',              'prefix'=>'৳', 'sub'=>$netMargin.'% margin'],
    ];
    @endphp
    @foreach($cards as $c)
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 border border-{{ $c['color'] }}-100 dark:border-{{ $c['color'] }}-900/40 stat-card">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">{{ $c['label'] }}</p>
        <p class="text-base font-bold text-{{ $c['color'] }}-600 dark:text-{{ $c['color'] }}-400">
            {{ $c['value'] >= 0 ? $c['prefix'] : '-'.$c['prefix'] }}{{ number_format(abs($c['value'])) }}
        </p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $c['sub'] }}</p>
    </div>
    @endforeach
</div>

{{-- Chart + Expense Breakdown --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

    {{-- Revenue vs Expense Chart --}}
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Revenue vs Expenses Trend</h3>
            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-primary-500 rounded-sm inline-block"></span>Revenue</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-red-400 rounded-sm inline-block"></span>Expense</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-green-400 rounded-sm inline-block"></span>Profit</span>
            </div>
        </div>
        <div class="relative" style="height:220px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    {{-- Expense Category Donut --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Expense Categories</h3>
        <p class="text-xs text-gray-400 dark:text-slate-500 mb-3">Total: ৳{{ number_format($expenses) }}</p>
        @if($expenseBreakdown->isNotEmpty())
        <div class="relative" style="height:140px;">
            <canvas id="expenseDonut"></canvas>
        </div>
        <div class="mt-3 space-y-1.5">
            @php
            $colors = ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#6366f1'];
            @endphp
            @foreach($expenseBreakdown->take(6) as $i => $e)
            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $colors[$i % count($colors)] }}"></span>
                    <span class="text-gray-600 dark:text-slate-300 truncate max-w-24">{{ $e->category?->name ?? 'Uncategorized' }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-gray-900 dark:text-white">৳{{ number_format($e->total) }}</span>
                    <span class="text-gray-400 dark:text-slate-500">({{ $expenses > 0 ? round(($e->total/$expenses)*100) : 0 }}%)</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="flex items-center justify-center h-32 text-xs text-gray-400 dark:text-slate-500">No expense data</div>
        @endif
    </div>
</div>

{{-- Expense Category Breakdown Bars --}}
@if($expenseBreakdown->isNotEmpty())
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-5">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Expense Category Breakdown</h3>
    @php $maxExp = $expenseBreakdown->max('total') ?: 1; $totalExp = $expenseBreakdown->sum('total'); @endphp
    <div class="space-y-3">
        @foreach($expenseBreakdown as $i => $e)
        <div>
            <div class="flex justify-between text-xs mb-1">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $colors[$i % count($colors)] }}"></span>
                    <span class="font-medium text-gray-700 dark:text-slate-300">{{ $e->category?->name ?? 'Uncategorized' }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-gray-400 dark:text-slate-500">{{ $totalExp > 0 ? number_format(($e->total/$totalExp)*100, 1) : 0 }}%</span>
                    <span class="font-bold text-gray-900 dark:text-white w-20 text-right">৳{{ number_format($e->total) }}</span>
                </div>
            </div>
            <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                <div class="h-2 rounded-full transition-all duration-500"
                     style="width:{{ min(100,($e->total/$maxExp)*100) }}%; background:{{ $colors[$i % count($colors)] }}"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- P&L Statement --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Profit & Loss Statement</h3>
        <span class="text-xs text-gray-400 dark:text-slate-500">{{ \Carbon\Carbon::parse($from)->format('d M') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</span>
    </div>
    <table class="w-full text-sm">
        <tbody>
            @php
            $stmt = [
                ['label'=>'Revenue from Sales',         'amount'=>$revenue,          'indent'=>0, 'color'=>'text-gray-900 dark:text-white',         'bold'=>true,  'section'=>'INCOME'],
                ['label'=>'Cost of Goods Sold (COGS)',  'amount'=>-$cogs,            'indent'=>1, 'color'=>'text-red-600 dark:text-red-400',         'bold'=>false, 'section'=>null],
                ['label'=>'Gross Profit',               'amount'=>$grossProfit,      'indent'=>0, 'color'=>$grossProfit>=0?'text-green-600 dark:text-green-400':'text-red-600 dark:text-red-400', 'bold'=>true, 'border'=>true, 'section'=>null],
                ['label'=>'Other Income',               'amount'=>$otherIncome,      'indent'=>1, 'color'=>'text-teal-600 dark:text-teal-400',       'bold'=>false, 'section'=>'DEDUCTIONS'],
                ['label'=>'Operating Expenses',         'amount'=>-$expenses,        'indent'=>1, 'color'=>'text-red-600 dark:text-red-400',         'bold'=>false, 'section'=>null],
                ['label'=>'Production Wages',           'amount'=>-$productionWages, 'indent'=>1, 'color'=>'text-purple-600 dark:text-purple-400',   'bold'=>false, 'section'=>null],
                ['label'=>'Net Profit / Loss',          'amount'=>$netProfit,        'indent'=>0, 'color'=>$netProfit>=0?'text-primary-600 dark:text-primary-400':'text-red-600 dark:text-red-400', 'bold'=>true, 'border'=>true, 'section'=>null],
            ];
            @endphp
            @foreach($stmt as $row)
            @if(isset($row['section']))
            <tr class="bg-gray-50 dark:bg-slate-700/50">
                <td colspan="2" class="px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ $row['section'] }}</td>
            </tr>
            @endif
            <tr class="{{ isset($row['border']) ? 'border-t-2 border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700/50' : 'border-b border-gray-50 dark:border-slate-700' }} hover:bg-gray-50/50 dark:hover:bg-slate-700/20 transition-colors">
                <td class="py-3 text-gray-700 dark:text-slate-300 {{ $row['bold'] ? 'font-bold' : '' }}"
                    style="padding-left: {{ 16 + ($row['indent'] * 20) }}px">
                    {{ $row['label'] }}
                </td>
                <td class="px-4 py-3 text-right {{ $row['bold'] ? 'font-bold text-base' : '' }} {{ $row['color'] }}">
                    {{ $row['amount'] >= 0 ? '৳' : '-৳' }}{{ number_format(abs($row['amount'])) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(0,0,0,0.06)';
    const textColor = isDark ? '#94a3b8' : '#6b7280';

    const chartData = @json($chartData);
    const labels  = chartData.map(d => d.label);
    const revenue = chartData.map(d => d.revenue);
    const expense = chartData.map(d => d.expense);
    const profit  = chartData.map(d => d.profit);

    // Trend Chart
    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: revenue,
                    backgroundColor: 'rgba(59,130,246,0.75)',
                    borderRadius: 4,
                    order: 2,
                },
                {
                    label: 'Expense',
                    data: expense,
                    backgroundColor: 'rgba(239,68,68,0.65)',
                    borderRadius: 4,
                    order: 2,
                },
                {
                    label: 'Profit',
                    data: profit,
                    type: 'line',
                    borderColor: 'rgba(34,197,94,0.9)',
                    backgroundColor: 'rgba(34,197,94,0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#22c55e',
                    fill: false,
                    tension: 0.4,
                    order: 1,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': ৳' + Number(ctx.parsed.y).toLocaleString()
                    }
                }
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 }, maxRotation: 45 } },
                y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 }, callback: v => '৳' + Number(v).toLocaleString() } }
            }
        }
    });

    @if($expenseBreakdown->isNotEmpty())
    // Expense Donut
    new Chart(document.getElementById('expenseDonut'), {
        type: 'doughnut',
        data: {
            labels: @json($expenseBreakdown->map(fn($e) => $e->category?->name ?? 'Uncategorized')),
            datasets: [{
                data: @json($expenseBreakdown->pluck('total')),
                backgroundColor: ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#6366f1'],
                borderWidth: 2,
                borderColor: isDark ? '#1e293b' : '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.label + ': ৳' + Number(ctx.parsed).toLocaleString()
                    }
                }
            }
        }
    });
    @endif
});
</script>
@endpush

@endsection
