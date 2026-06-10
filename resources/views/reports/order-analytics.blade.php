{{-- ============================================================
     FILE: resources/views/reports/order-analytics.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Order Analytics')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Order Analytics</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($from)->format('d M') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <input type="date" name="from" value="{{ $from }}" class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
        <input type="date" name="to"   value="{{ $to }}"   class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm transition-colors">Apply</button>
    </form>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
    @foreach([
        ['label'=>'Total Orders',      'value'=>$stats['total'],           'color'=>'gray',   'suffix'=>''],
        ['label'=>'Completed',         'value'=>$stats['completed'],       'color'=>'green',  'suffix'=>''],
        ['label'=>'Cancelled',         'value'=>$stats['cancelled'],       'color'=>'red',    'suffix'=>''],
        ['label'=>'Revenue',           'value'=>'৳'.number_format($stats['revenue']), 'color'=>'primary',  'raw'=>true],
        ['label'=>'Avg Order Value',   'value'=>'৳'.number_format($stats['avg_value']), 'color'=>'violet','raw'=>true],
        ['label'=>'Completion Rate',   'value'=>$stats['completion_rate'], 'color'=>'teal',   'suffix'=>'%'],
    ] as $card)
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-{{ $card['color'] }}-100 dark:border-{{ $card['color'] }}-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $card['label'] }}</p>
        <p class="text-base font-bold text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-1">
            @if(isset($card['raw']) && $card['raw']){{ $card['value'] }}@else{{ $card['value'] }}{{ $card['suffix'] ?? '' }}@endif
        </p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">

    {{-- Daily Orders Chart --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Daily Orders Trend</h3>
        @php $maxCount = collect($chartData)->max('count') ?: 1; @endphp
        <div class="overflow-x-auto">
            <div class="flex items-end gap-1 h-28 min-w-max">
                @foreach($chartData as $d)
                <div class="flex flex-col items-center gap-0.5 min-w-6">
                    <div class="w-full bg-primary-400 dark:bg-primary-500 rounded-t min-h-0.5"
                         style="height:{{ max(2, ($d['count']/$maxCount)*88) }}px"
                         title="{{ $d['label'] }}: {{ $d['count'] }} orders"></div>
                    @if(count($chartData) <= 14)
                        <p class="text-gray-400" style="font-size:8px;white-space:nowrap">{{ $d['label'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Orders by Source --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Orders by Source</h3>
        @php $totalOrders = $bySource->sum('count') ?: 1;
        $sourceColors = ['manual'=>'blue','woocommerce'=>'purple','pos'=>'green','web'=>'orange']; @endphp
        <div class="space-y-3">
            @foreach($bySource as $s)
            @php $c = $sourceColors[$s->source] ?? 'gray'; $pct = round(($s->count/$totalOrders)*100); @endphp
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-600 dark:text-slate-300 font-medium capitalize">{{ $s->source }}</span>
                    <span class="text-gray-500 dark:text-slate-400">{{ $s->count }} orders ({{ $pct }}%)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                        <div class="h-2 rounded-full bg-{{ $c }}-400 dark:bg-{{ $c }}-500" style="width:{{ $pct }}%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700 dark:text-slate-300 w-16 text-right">৳{{ number_format($s->revenue) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- Top Products --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top Products by Revenue</h3>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($topProducts as $i => $p)
            <div class="flex items-center gap-3 px-4 py-3">
                <span class="w-6 h-6 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $p->product_name }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $p->qty_sold }} units sold</p>
                </div>
                <span class="text-sm font-bold text-gray-900 dark:text-white flex-shrink-0">৳{{ number_format($p->revenue) }}</span>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400">No data</div>
            @endforelse
        </div>
    </div>

    {{-- Payment Methods + Order Status --}}
    <div class="space-y-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Payment Methods</h3>
            <div class="space-y-2">
                @foreach($byPayment as $p)
                @php $payPct = round(($p->count/max($stats['total'],1))*100); @endphp
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-600 dark:text-slate-300 w-16 capitalize">{{ $p->payment_method }}</span>
                    <div class="flex-1 bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                        <div class="h-2 rounded-full bg-green-400 dark:bg-green-500" style="width:{{ $payPct }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400 w-12 text-right">{{ $p->count }} ({{ $payPct }}%)</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Order Status Distribution</h3>
            <div class="space-y-2">
                @php $statusColors=['pending'=>'yellow','confirmed'=>'blue','processing'=>'indigo','shipped'=>'purple','delivered'=>'green','completed'=>'green','cancelled'=>'red','returned'=>'orange']; @endphp
                @foreach($byStatus as $s)
                @php $pct = round(($s->count/max($stats['total'],1))*100); $c = $statusColors[$s->status]??'gray'; @endphp
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-600 dark:text-slate-300 w-20 capitalize">{{ $s->status }}</span>
                    <div class="flex-1 bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                        <div class="h-2 rounded-full bg-{{ $c }}-400 dark:bg-{{ $c }}-500" style="width:{{ $pct }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400 w-12 text-right">{{ $s->count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
