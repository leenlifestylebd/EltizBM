@extends('layouts.app')
@section('title', 'Sales by Channel')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

@php
$channelColors = [
    'online'   => ['bg'=>'#3b82f6','light'=>'#eff6ff','text'=>'#1d4ed8'],
    'outlet'   => ['bg'=>'#8b5cf6','light'=>'#f5f3ff','text'=>'#6d28d9'],
    'direct'   => ['bg'=>'#10b981','light'=>'#ecfdf5','text'=>'#047857'],
    'facebook' => ['bg'=>'#3b5998','light'=>'#eff6ff','text'=>'#1e40af'],
    'whatsapp' => ['bg'=>'#25d366','light'=>'#f0fdf4','text'=>'#15803d'],
    'phone'    => ['bg'=>'#f59e0b','light'=>'#fffbeb','text'=>'#b45309'],
    'walk-in'  => ['bg'=>'#ec4899','light'=>'#fdf2f8','text'=>'#9d174d'],
    'other'    => ['bg'=>'#6b7280','light'=>'#f9fafb','text'=>'#374151'],
];
$fallbackColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ec4899','#ef4444','#14b8a6','#6366f1'];
@endphp

{{-- Breadcrumb + Export --}}
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('reports.all') }}" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">Reports</a>
        <span class="text-gray-300 dark:text-slate-600">/</span>
        <span class="text-xs font-medium text-gray-700 dark:text-slate-300">Sales by Channel</span>
    </div>
    <div class="flex items-center gap-2" x-data="{ open: false }" @click.outside="open = false">
        <button @click="open = !open" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 transition-colors shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
            <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition class="absolute right-4 mt-24 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
            @php $qs = http_build_query(['from'=>$from,'to'=>$to]); @endphp
            <a href="{{ route('reports.export','sales-channel') }}?format=csv&{{ $qs }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('reports.export','sales-channel') }}?format=pdf&{{ $qs }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
</div>

{{-- Date Presets --}}
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
        @php $active = $from === $p['from'] && $to === $p['to']; @endphp
        <a href="{{ route('reports.sales-channel', ['from'=>$p['from'],'to'=>$p['to']]) }}"
           class="px-3 py-1 text-xs font-medium rounded-lg border transition-colors
                  {{ $active ? 'bg-purple-600 text-white border-purple-600' : 'bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-100' }}">
            {{ $p['label'] }}
        </a>
        @endforeach
    </div>
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-xs text-gray-400 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-xs font-semibold rounded-lg hover:bg-purple-700 transition-colors">Apply</button>
    </form>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-900/10 rounded-xl p-4 border border-purple-100 dark:border-purple-900/40">
        <p class="text-xs text-purple-600 dark:text-purple-400 font-medium mb-1">Total Revenue</p>
        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">৳{{ number_format($totalRevenue) }}</p>
        <p class="text-xs text-purple-500 mt-1">completed orders</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Total Orders</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalOrders) }}</p>
        <p class="text-xs text-gray-400 mt-1">all statuses</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Channels</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $bySource->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">active sources</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Avg Order Value</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
            @php $completedTotal = $bySource->sum('completed_count'); @endphp
            ৳{{ $completedTotal > 0 ? number_format($totalRevenue / $completedTotal) : 0 }}
        </p>
        <p class="text-xs text-gray-400 mt-1">per completed order</p>
    </div>
</div>

@if($bySource->isNotEmpty())

{{-- Channel Cards + Chart --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

    {{-- Channel Cards --}}
    <div class="space-y-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white px-1">By Channel</h3>
        @php $maxRevenue = $bySource->max('completed_revenue') ?: 1; @endphp
        @foreach($bySource as $i => $ch)
        @php
            $src = $ch->source;
            $color = $channelColors[$src]['bg'] ?? $fallbackColors[$i % count($fallbackColors)];
            $completion = $ch->total_count > 0 ? round(($ch->completed_count / $ch->total_count) * 100) : 0;
        @endphp
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background: {{ $color }}"></span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white capitalize">{{ $src }}</span>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background:{{ $color }}22; color:{{ $color }}">
                    {{ $totalRevenue > 0 ? number_format(($ch->completed_revenue / $totalRevenue) * 100, 1) : 0 }}%
                </span>
            </div>
            <p class="text-xl font-bold text-gray-900 dark:text-white mb-1">৳{{ number_format($ch->completed_revenue) }}</p>
            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-slate-400 mb-2.5">
                <span>{{ $ch->total_count }} orders</span>
                <span>·</span>
                <span class="text-green-600 dark:text-green-400">{{ $completion }}% completed</span>
                @if($ch->cancelled_count > 0)
                <span>·</span>
                <span class="text-red-500">{{ $ch->cancelled_count }} cancelled</span>
                @endif
            </div>
            <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full transition-all duration-700"
                     style="width:{{ min(100, ($ch->completed_revenue/$maxRevenue)*100) }}%; background:{{ $color }}"></div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Revenue Trend Chart --}}
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Revenue Trend by Channel</h3>
        <div style="height: 260px;">
            <canvas id="channelChart"></canvas>
        </div>
    </div>
</div>

{{-- Top Products per Channel --}}
@if($topBySource->isNotEmpty())
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-4">
    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top Products per Channel</h3>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min($bySource->count(), 3) }} divide-y sm:divide-y-0 sm:divide-x divide-gray-50 dark:divide-slate-700">
        @foreach($bySource->take(3) as $i => $ch)
        @php
            $src = $ch->source;
            $color = $channelColors[$src]['bg'] ?? $fallbackColors[$i % count($fallbackColors)];
            $topProducts = $topBySource->get($src, collect())->take(5);
        @endphp
        <div class="p-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $color }}"></span>
                <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wide">{{ $src }}</h4>
            </div>
            @if($topProducts->isNotEmpty())
            <div class="space-y-2">
                @foreach($topProducts as $prod)
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-700 dark:text-slate-300 truncate flex-1 mr-2">{{ $prod->product_name }}</span>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span class="text-gray-400 dark:text-slate-500">{{ $prod->qty }} pcs</span>
                        <span class="font-bold text-gray-900 dark:text-white">৳{{ number_format($prod->revenue) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-xs text-gray-400 dark:text-slate-500">No data</p>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Summary Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Channel Summary</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Channel</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Orders</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Completed</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Cancelled</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Completion %</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Revenue</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Share</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($bySource as $i => $ch)
                @php
                    $src = $ch->source;
                    $color = $channelColors[$src]['bg'] ?? $fallbackColors[$i % count($fallbackColors)];
                    $completion = $ch->total_count > 0 ? round(($ch->completed_count / $ch->total_count) * 100) : 0;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $color }}"></span>
                            <span class="font-semibold text-gray-800 dark:text-white capitalize">{{ $src }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-slate-300">{{ $ch->total_count }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-medium text-green-600 dark:text-green-400">{{ $ch->completed_count }}</span>
                    </td>
                    <td class="px-4 py-3 text-center hidden sm:table-cell">
                        @if($ch->cancelled_count > 0)
                        <span class="text-red-500">{{ $ch->cancelled_count }}</span>
                        @else
                        <span class="text-gray-400">0</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center hidden sm:table-cell">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 bg-gray-200 dark:bg-slate-600 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-green-500" style="width:{{ $completion }}%"></div>
                            </div>
                            <span class="text-xs text-gray-600 dark:text-slate-300">{{ $completion }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-purple-600 dark:text-purple-400">
                        ৳{{ number_format($ch->completed_revenue) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                              style="background:{{ $color }}22; color:{{ $color }}">
                            {{ $totalRevenue > 0 ? number_format(($ch->completed_revenue/$totalRevenue)*100, 1) : 0 }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-slate-700/50 border-t-2 border-gray-200 dark:border-slate-600">
                <tr>
                    <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white text-sm">Total</td>
                    <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $totalOrders }}</td>
                    <td class="px-4 py-2.5 text-center font-bold text-green-600">{{ $bySource->sum('completed_count') }}</td>
                    <td class="hidden sm:table-cell px-4 py-2.5 text-center font-bold text-red-500">{{ $bySource->sum('cancelled_count') }}</td>
                    <td class="hidden sm:table-cell px-4 py-2.5"></td>
                    <td class="px-4 py-2.5 text-right font-bold text-purple-700 dark:text-purple-400 text-base">৳{{ number_format($totalRevenue) }}</td>
                    <td class="px-4 py-2.5 text-right font-bold text-gray-900 dark:text-white">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@else
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
    <svg class="w-10 h-10 text-gray-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
    <p class="text-sm text-gray-400 dark:text-slate-500">No orders for this period</p>
    <p class="text-xs text-gray-300 dark:text-slate-600 mt-1">Try selecting a different date range</p>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($bySource->isNotEmpty() && count($chartData) > 0)
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#6b7280';
    const gridColor = isDark ? '#334155' : '#f3f4f6';
    const sources = @json($sources);
    const chartData = @json($chartData);
    const channelColors = @json(array_values(array_merge(
        array_map(fn($c) => $c['bg'], $channelColors),
        $fallbackColors
    )));

    const colorMap = {};
    @foreach($bySource as $i => $ch)
    colorMap['{{ $ch->source }}'] = '{{ $channelColors[$ch->source]["bg"] ?? $fallbackColors[$i % count($fallbackColors)] }}';
    @endforeach

    const labels = chartData.map(d => d.label);
    const datasets = sources.map((src, i) => ({
        label: src.charAt(0).toUpperCase() + src.slice(1),
        data: chartData.map(d => d[src] || 0),
        borderColor: colorMap[src] || channelColors[i],
        backgroundColor: (colorMap[src] || channelColors[i]) + '20',
        fill: true,
        tension: 0.3,
        borderWidth: 2,
        pointRadius: chartData.length > 30 ? 0 : 3,
        pointHoverRadius: 5,
    }));

    new Chart(document.getElementById('channelChart'), {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: textColor, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': ৳' + Number(ctx.parsed.y).toLocaleString()
                    }
                }
            },
            scales: {
                x: { ticks: { color: textColor, font: { size: 10 }, maxTicksLimit: 15 }, grid: { color: gridColor } },
                y: { ticks: { color: textColor, font: { size: 11 }, callback: v => '৳' + v.toLocaleString() }, grid: { color: gridColor } }
            }
        }
    });
    @endif
});
</script>
@endpush

@endsection
