@extends('layouts.app')
@section('title', 'Profit & Loss')

@section('content')

{{-- Header + Date Filter --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Profit & Loss</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    </div>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="date" name="from" value="{{ $from }}"
               class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="date" name="to" value="{{ $to }}"
               class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <select name="group_by" class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="day"   {{ $groupBy==='day'   ? 'selected':'' }}>Daily</option>
            <option value="month" {{ $groupBy==='month' ? 'selected':'' }}>Monthly</option>
        </select>
        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm transition-colors">Apply</button>
    </form>
</div>

{{-- P&L Summary Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
    @foreach([
        ['label'=>'Revenue',      'value'=>$revenue,     'color'=>'blue',   'prefix'=>'৳'],
        ['label'=>'Cost (COGS)',   'value'=>$cogs,        'color'=>'orange', 'prefix'=>'৳'],
        ['label'=>'Gross Profit', 'value'=>$grossProfit, 'color'=>'green',  'prefix'=>'৳'],
        ['label'=>'Gross Margin', 'value'=>$grossMargin, 'color'=>'teal',   'prefix'=>'', 'suffix'=>'%'],
        ['label'=>'Expenses',     'value'=>$expenses,    'color'=>'red',    'prefix'=>'৳'],
        ['label'=>'Net Profit',   'value'=>$netProfit,   'color'=>$netProfit>=0?'emerald':'rose', 'prefix'=>'৳'],
    ] as $card)
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-{{ $card['color'] }}-100 dark:border-{{ $card['color'] }}-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $card['label'] }}</p>
        <p class="text-base font-bold text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-1">
            {{ $card['prefix'] ?? '' }}{{ number_format(abs($card['value'])) }}{{ $card['suffix'] ?? '' }}
            @if(isset($card['prefix']) && $card['prefix']==='৳' && $card['value'] < 0)
                <span class="text-xs text-red-500 ml-1">(Loss)</span>
            @endif
        </p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

    {{-- Chart --}}
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Revenue vs Expense Trend</h3>
        @php $maxVal = collect($chartData)->max(fn($x) => max($x['revenue'], $x['expense'])) ?: 1; @endphp
        <div class="overflow-x-auto">
            <div class="flex items-end gap-1.5 h-36 min-w-max">
                @foreach($chartData as $d)
                <div class="flex flex-col items-center gap-0.5 min-w-8">
                    <div class="flex flex-col gap-0.5 justify-end" style="height:112px;">
                        <div class="w-full rounded-t bg-blue-400 dark:bg-blue-500 min-h-0.5"
                             style="height:{{ max(2, ($d['revenue']/$maxVal)*100) }}px"
                             title="Revenue: ৳{{ number_format($d['revenue']) }}"></div>
                        <div class="w-full rounded-t bg-red-400 dark:bg-red-500 min-h-0.5"
                             style="height:{{ max(2, ($d['expense']/$maxVal)*100) }}px"
                             title="Expense: ৳{{ number_format($d['expense']) }}"></div>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-slate-500 whitespace-nowrap" style="font-size:9px">{{ $d['label'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        <div class="flex gap-4 mt-3">
            <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-3 h-3 bg-blue-400 rounded"></span>Revenue</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-3 h-3 bg-red-400 rounded"></span>Expense</span>
        </div>
    </div>

    {{-- Expense Breakdown --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Expense Breakdown</h3>
        @php $maxExp = $expenseBreakdown->max('total') ?: 1; @endphp
        <div class="space-y-3">
            @forelse($expenseBreakdown->take(8) as $exp)
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-600 dark:text-slate-300 truncate max-w-32">{{ $exp->category?->name ?? 'Uncategorized' }}</span>
                    <span class="font-semibold text-gray-900 dark:text-white ml-2">৳{{ number_format($exp->total) }}</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full bg-red-400 dark:bg-red-500"
                         style="width:{{ min(100, ($exp->total/$maxExp)*100) }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 dark:text-slate-500 text-center py-4">No expenses</p>
            @endforelse
        </div>
    </div>
</div>

{{-- P&L Statement Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Profit & Loss Statement</h3>
    </div>
    <table class="w-full text-sm">
        <tbody>
            @php
            $items = [
                ['label'=>'Revenue from Sales',        'amount'=>$revenue,     'indent'=>0, 'color'=>'text-gray-900 dark:text-white',        'bold'=>false],
                ['label'=>'Cost of Goods Sold (COGS)', 'amount'=>-$cogs,       'indent'=>1, 'color'=>'text-red-600 dark:text-red-400',        'bold'=>false],
                ['label'=>'Gross Profit',              'amount'=>$grossProfit, 'indent'=>0, 'color'=>'text-green-600 dark:text-green-400',    'bold'=>true, 'border'=>true],
                ['label'=>'Other Income',              'amount'=>$otherIncome, 'indent'=>1, 'color'=>'text-gray-900 dark:text-white',         'bold'=>false],
                ['label'=>'Total Operating Expenses',  'amount'=>-$expenses,   'indent'=>1, 'color'=>'text-red-600 dark:text-red-400',        'bold'=>false],
                ['label'=>'Net Profit / Loss',         'amount'=>$netProfit,   'indent'=>0, 'color'=>$netProfit>=0?'text-blue-600 dark:text-blue-400':'text-red-600 dark:text-red-400', 'bold'=>true, 'border'=>true],
            ];
            @endphp
            @foreach($items as $item)
            <tr class="{{ isset($item['border']) ? 'border-t-2 border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700/50' : 'border-b border-gray-50 dark:border-slate-700' }}">
                <td class="px-4 py-3 {{ $item['bold'] ? 'font-bold' : '' }} text-gray-700 dark:text-slate-300"
                    style="padding-left: {{ 16 + $item['indent'] * 16 }}px">
                    {{ $item['label'] }}
                </td>
                <td class="px-4 py-3 text-right {{ $item['bold'] ? 'font-bold text-base' : '' }} {{ $item['color'] }}">
                    {{ $item['amount'] >= 0 ? '৳' : '-৳' }}{{ number_format(abs($item['amount'])) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
