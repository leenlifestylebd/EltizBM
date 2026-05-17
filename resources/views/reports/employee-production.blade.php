@extends('layouts.app')
@section('title', 'Employee Production Report')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- Breadcrumb + Export --}}
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('reports.all') }}" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">Reports</a>
        <span class="text-gray-300 dark:text-slate-600">/</span>
        <span class="text-xs font-medium text-gray-700 dark:text-slate-300">Employee Production</span>
    </div>
    <div class="flex items-center gap-2" x-data="{ open: false }" @click.outside="open = false">
        <button @click="open = !open" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 transition-colors shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
            <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition class="absolute right-4 mt-24 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
            @php $qs = http_build_query(array_filter(['from'=>$from,'to'=>$to,'employee'=>$empId])); @endphp
            <a href="{{ route('reports.export','employee-production') }}?format=csv&{{ $qs }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('reports.export','employee-production') }}?format=pdf&{{ $qs }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
</div>

{{-- Filters --}}
@php
$presets = [
    ['label'=>'This Month',    'from'=>now()->startOfMonth()->toDateString(),                       'to'=>now()->endOfMonth()->toDateString()],
    ['label'=>'Last Month',    'from'=>now()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to'=>now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
    ['label'=>'Last 3 Months', 'from'=>now()->subMonths(3)->startOfMonth()->toDateString(),         'to'=>now()->endOfMonth()->toDateString()],
    ['label'=>'This Year',     'from'=>now()->startOfYear()->toDateString(),                        'to'=>now()->endOfYear()->toDateString()],
];
@endphp

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
    <div class="flex flex-wrap gap-1.5 mb-3">
        @foreach($presets as $p)
        @php $active = $from === $p['from'] && $to === $p['to'] && !$empId; @endphp
        <a href="{{ route('reports.employee-production', ['from'=>$p['from'],'to'=>$p['to']]) }}"
           class="px-3 py-1 text-xs font-medium rounded-lg border transition-colors
                  {{ $active ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-100' }}">
            {{ $p['label'] }}
        </a>
        @endforeach
    </div>
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-xs text-gray-400 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Employee</label>
            <select name="employee"
                    class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ $empId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors">Apply</button>
        @if($empId)
        <a href="{{ route('reports.employee-production', ['from'=>$from,'to'=>$to]) }}"
           class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors">
            Clear Filter
        </a>
        @endif
    </form>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-900/10 rounded-xl p-4 border border-blue-100 dark:border-blue-900/40">
        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Total Wages</p>
        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">৳{{ number_format($totalAmount) }}</p>
        <p class="text-xs text-blue-500 mt-1">{{ \Carbon\Carbon::parse($from)->format('d M') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Total Pieces</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalQty) }}</p>
        <p class="text-xs text-gray-400 mt-1">pcs produced</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Employees</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summaryByEmployee->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">with production</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Avg Rate/pc</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
            ৳{{ $totalQty > 0 ? number_format($totalAmount / $totalQty, 2) : '0.00' }}
        </p>
        <p class="text-xs text-gray-400 mt-1">per piece</p>
    </div>
</div>

@if($summaryByEmployee->isNotEmpty())

{{-- Employee Summary Chart --}}
@if($summaryByEmployee->count() > 1)
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Production Comparison</h3>
    <div style="height: 200px;">
        <canvas id="empChart"></canvas>
    </div>
</div>
@endif

{{-- Per-Employee Detail Blocks --}}
<div class="space-y-4">
    @foreach($summaryByEmployee as $summary)
    @php $emp = $summary['employee']; @endphp
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden"
         x-data="{ tab: 'summary' }">

        {{-- Employee Header --}}
        <div class="px-4 py-4 border-b border-gray-50 dark:border-slate-700 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ strtoupper(substr($emp?->name ?? 'U', 0, 1)) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $emp?->name ?? 'Unknown Employee' }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $emp?->designation ?? $emp?->department ?? 'Worker' }}</p>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">৳{{ number_format($summary['total_amount']) }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">{{ number_format($summary['total_qty']) }} pcs · {{ $summary['entry_count'] }} entries</p>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex border-b border-gray-50 dark:border-slate-700">
            <button @click="tab='summary'"
                    :class="tab==='summary' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-slate-400'"
                    class="px-4 py-2.5 text-xs font-medium transition-colors">
                Product Summary
            </button>
            <button @click="tab='entries'"
                    :class="tab==='entries' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-slate-400'"
                    class="px-4 py-2.5 text-xs font-medium transition-colors">
                All Entries ({{ $summary['entry_count'] }})
            </button>
        </div>

        {{-- Summary Tab: Grouped by product/color/size --}}
        <div x-show="tab==='summary'" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Product</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Color</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Size</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Qty</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Entries</th>
                            <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Earnings</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($summary['products'] as $prod)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800 dark:text-white text-sm">{{ $prod['product_name'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($prod['color'] !== '—')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300">{{ $prod['color'] }}</span>
                                @else
                                <span class="text-gray-400 dark:text-slate-500 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($prod['size'] !== '—')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-medium">{{ $prod['size'] }}</span>
                                @else
                                <span class="text-gray-400 dark:text-slate-500 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">
                                {{ number_format($prod['quantity']) }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500 dark:text-slate-400 hidden sm:table-cell">{{ $prod['entry_count'] }}</td>
                            <td class="px-4 py-3 text-right font-bold text-blue-600 dark:text-blue-400">
                                ৳{{ number_format($prod['amount']) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-blue-50 dark:bg-blue-900/10 border-t-2 border-blue-100 dark:border-blue-900/30">
                        <tr>
                            <td colspan="3" class="px-4 py-2.5 font-bold text-gray-900 dark:text-white text-xs">Total</td>
                            <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_qty']) }}</td>
                            <td class="hidden sm:table-cell px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $summary['entry_count'] }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-blue-700 dark:text-blue-400">৳{{ number_format($summary['total_amount']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Entries Tab: All individual entries --}}
        <div x-show="tab==='entries'" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Product</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Color</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Size</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Qty</th>
                            <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($summary['entries'] as $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $entry->entry_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-2.5">
                                <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $entry->productionRate?->product_name ?? 'Manual Entry' }}</p>
                                @if($entry->notes)
                                <p class="text-xs text-gray-400 dark:text-slate-500 truncate">{{ $entry->notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center hidden sm:table-cell">
                                @if($entry->productionRate?->color)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300">{{ $entry->productionRate->color }}</span>
                                @else
                                <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center hidden sm:table-cell">
                                @if($entry->productionRate?->size)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">{{ $entry->productionRate->size }}</span>
                                @else
                                <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $entry->quantity }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-blue-600 dark:text-blue-400">৳{{ number_format($entry->amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    @endforeach
</div>

@else
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
    <svg class="w-10 h-10 text-gray-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <p class="text-sm text-gray-400 dark:text-slate-500">No production data for this period</p>
    <p class="text-xs text-gray-300 dark:text-slate-600 mt-1">Try selecting a different date range or employee</p>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($summaryByEmployee->count() > 1)
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#6b7280';
    const gridColor = isDark ? '#334155' : '#f3f4f6';

    new Chart(document.getElementById('empChart'), {
        type: 'bar',
        data: {
            labels: @json($summaryByEmployee->pluck('employee')->map(fn($e) => $e?->name ?? 'Unknown')),
            datasets: [
                {
                    label: 'Earnings (৳)',
                    data: @json($summaryByEmployee->pluck('total_amount')),
                    backgroundColor: 'rgba(59,130,246,0.7)',
                    borderRadius: 6,
                    yAxisID: 'y',
                },
                {
                    label: 'Pieces',
                    data: @json($summaryByEmployee->pluck('total_qty')),
                    backgroundColor: 'rgba(16,185,129,0.7)',
                    borderRadius: 6,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: textColor, font: { size: 11 } } } },
            scales: {
                x: { ticks: { color: textColor, font: { size: 11 } }, grid: { color: gridColor } },
                y: {
                    position: 'left',
                    ticks: { color: textColor, font: { size: 11 }, callback: v => '৳' + v.toLocaleString() },
                    grid: { color: gridColor }
                },
                y1: {
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#10b981', font: { size: 11 }, callback: v => v + ' pcs' }
                }
            }
        }
    });
    @endif
});
</script>
@endpush

@endsection
