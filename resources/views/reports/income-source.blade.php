@extends('layouts.app')
@section('title', 'Income Sources')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- Breadcrumb + Export --}}
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('reports.all') }}" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">Reports</a>
        <span class="text-gray-300 dark:text-slate-600">/</span>
        <span class="text-xs font-medium text-gray-700 dark:text-slate-300">Income Sources</span>
    </div>
    <div class="flex items-center gap-2" x-data="{ open: false }" @click.outside="open = false">
        <button @click="open = !open" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 transition-colors shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
            <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition class="absolute right-4 mt-24 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
            @php $qs = http_build_query(['from'=>$from,'to'=>$to]); @endphp
            <a href="{{ route('reports.export','income-source') }}?format=csv&{{ $qs }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('reports.export','income-source') }}?format=pdf&{{ $qs }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
</div>

{{-- Date Filter --}}
@php
$presets = [
    ['label'=>'This Month',    'from'=>now()->startOfMonth()->toDateString(),                        'to'=>now()->endOfMonth()->toDateString()],
    ['label'=>'Last Month',    'from'=>now()->subMonthNoOverflow()->startOfMonth()->toDateString(),  'to'=>now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
    ['label'=>'Last 3 Months', 'from'=>now()->subMonths(3)->startOfMonth()->toDateString(),          'to'=>now()->endOfMonth()->toDateString()],
    ['label'=>'This Year',     'from'=>now()->startOfYear()->toDateString(),                         'to'=>now()->endOfYear()->toDateString()],
];
@endphp

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
    <div class="flex flex-wrap gap-1.5 mb-3">
        @foreach($presets as $p)
        @php $active = $from === $p['from'] && $to === $p['to']; @endphp
        <a href="{{ route('reports.income-source', ['from'=>$p['from'],'to'=>$p['to']]) }}"
           class="px-3 py-1 text-xs font-medium rounded-lg border transition-colors
                  {{ $active ? 'bg-teal-600 text-white border-teal-600' : 'bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-100' }}">
            {{ $p['label'] }}
        </a>
        @endforeach
    </div>
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-xs text-gray-400 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-teal-600 text-white text-xs font-semibold rounded-lg hover:bg-teal-700 transition-colors">Apply</button>
    </form>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-900/30 dark:to-teal-900/10 rounded-xl p-4 border border-teal-100 dark:border-teal-900/40">
        <p class="text-xs text-teal-600 dark:text-teal-400 font-medium mb-1">Total Other Income</p>
        <p class="text-2xl font-bold text-teal-700 dark:text-teal-300">৳{{ number_format($totalIncome) }}</p>
        <p class="text-xs text-teal-500 mt-1">{{ \Carbon\Carbon::parse($from)->format('d M') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Sources</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $breakdown->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">income categories</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Transactions</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $breakdown->sum('count') }}</p>
        <p class="text-xs text-gray-400 mt-1">income entries</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Avg per Entry</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
            ৳{{ $breakdown->sum('count') > 0 ? number_format($totalIncome / $breakdown->sum('count')) : 0 }}
        </p>
        <p class="text-xs text-gray-400 mt-1">average amount</p>
    </div>
</div>

@php $colors = ['#14b8a6','#22c55e','#3b82f6','#8b5cf6','#f97316','#ec4899','#eab308','#ef4444','#6366f1','#f59e0b']; @endphp

@if($breakdown->isNotEmpty())
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">

    {{-- Donut Chart --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Income Distribution</h3>
        <div class="flex flex-col sm:flex-row items-center gap-4">
            <div class="relative flex-shrink-0" style="width:180px; height:180px;">
                <canvas id="incomeDonut"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <p class="text-xs text-gray-400 dark:text-slate-500">Total</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">৳{{ number_format($totalIncome) }}</p>
                </div>
            </div>
            <div class="flex-1 space-y-2 w-full">
                @foreach($breakdown->take(8) as $i => $e)
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $colors[$i % count($colors)] }}"></span>
                        <span class="text-gray-600 dark:text-slate-300 truncate">{{ $e->category?->name ?? 'Uncategorized' }}</span>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white ml-2 flex-shrink-0">{{ $totalIncome > 0 ? round(($e->total/$totalIncome)*100) : 0 }}%</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Breakdown Bars --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Source Breakdown</h3>
        @php $maxInc = $breakdown->max('total') ?: 1; @endphp
        <div class="space-y-3.5">
            @foreach($breakdown as $i => $e)
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $colors[$i % count($colors)] }}"></span>
                        <span class="font-medium text-gray-700 dark:text-slate-300 truncate">{{ $e->category?->name ?? 'Uncategorized' }}</span>
                        <span class="text-gray-400 dark:text-slate-500 flex-shrink-0">({{ $e->count }})</span>
                    </div>
                    <div class="flex items-center gap-2 ml-2 flex-shrink-0">
                        <span class="text-gray-400 dark:text-slate-500">{{ $totalIncome > 0 ? number_format(($e->total/$totalIncome)*100, 1) : 0 }}%</span>
                        <span class="font-bold text-gray-900 dark:text-white w-20 text-right">৳{{ number_format($e->total) }}</span>
                    </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-700"
                         style="width:{{ min(100,($e->total/$maxInc)*100) }}%; background:{{ $colors[$i % count($colors)] }}"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Income Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Income Summary Table</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">#</th>
                    <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Source</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Transactions</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Amount</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">% Share</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Avg/tx</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($breakdown as $i => $e)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3 text-xs text-gray-400 dark:text-slate-500">{{ $i+1 }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $colors[$i % count($colors)] }}"></span>
                            <span class="font-medium text-gray-800 dark:text-white">{{ $e->category?->name ?? 'Uncategorized' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-slate-300">{{ $e->count }}</td>
                    <td class="px-4 py-3 text-right font-bold text-teal-600 dark:text-teal-400">৳{{ number_format($e->total) }}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                              style="background:{{ $colors[$i % count($colors)] }}22; color:{{ $colors[$i % count($colors)] }}">
                            {{ $totalIncome > 0 ? number_format(($e->total/$totalIncome)*100, 1) : 0 }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-600 dark:text-slate-300 hidden sm:table-cell">
                        ৳{{ $e->count > 0 ? number_format($e->total / $e->count) : 0 }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-slate-700/50 border-t-2 border-gray-200 dark:border-slate-600">
                <tr>
                    <td colspan="2" class="px-4 py-2.5 font-bold text-gray-900 dark:text-white text-sm">Total</td>
                    <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $breakdown->sum('count') }}</td>
                    <td class="px-4 py-2.5 text-right font-bold text-teal-600 dark:text-teal-400 text-base">৳{{ number_format($totalIncome) }}</td>
                    <td class="px-4 py-2.5 text-right font-bold text-gray-900 dark:text-white">100%</td>
                    <td class="px-4 py-2.5 hidden sm:table-cell"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@else
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
    <svg class="w-10 h-10 text-gray-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-sm text-gray-400 dark:text-slate-500">No income data for this period</p>
    <p class="text-xs text-gray-300 dark:text-slate-600 mt-1">Try selecting a different date range</p>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($breakdown->isNotEmpty())
    const isDark = document.documentElement.classList.contains('dark');
    new Chart(document.getElementById('incomeDonut'), {
        type: 'doughnut',
        data: {
            labels: @json($breakdown->map(fn($e) => $e->category?->name ?? 'Uncategorized')),
            datasets: [{
                data: @json($breakdown->pluck('total')),
                backgroundColor: ['#14b8a6','#22c55e','#3b82f6','#8b5cf6','#f97316','#ec4899','#eab308','#ef4444','#6366f1','#f59e0b'],
                borderWidth: 2,
                borderColor: isDark ? '#1e293b' : '#fff',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: false,
            cutout: '68%',
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
