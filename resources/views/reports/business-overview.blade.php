@extends('layouts.app')
@section('title', 'Business Overview')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Business Overview</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Full year performance — {{ $year }}</p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET" class="flex items-center gap-2">
            <select name="year" class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                @for($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected':'' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm transition-colors">View</button>
        </form>
        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
            <button @click="open = !open" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 text-gray-700 dark:text-slate-300 shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </button>
            <div x-show="open" x-cloak x-transition class="absolute right-0 top-10 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
                <a href="{{ route('reports.export','business-overview') }}?format=csv&year={{ $year }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export CSV
                </a>
                <a href="{{ route('reports.export','business-overview') }}?format=pdf&year={{ $year }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Export PDF
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Year Summary --}}
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-5">
    @foreach([
        ['label'=>'Revenue',   'value'=>'৳'.number_format($yearStats['revenue']),  'color'=>'primary'],
        ['label'=>'Expenses',  'value'=>'৳'.number_format($yearStats['expense']),  'color'=>'red'],
        ['label'=>'Net Profit','value'=>'৳'.number_format($yearStats['profit']),   'color'=>$yearStats['profit']>=0?'green':'orange'],
        ['label'=>'Orders',    'value'=>number_format($yearStats['orders']),        'color'=>'violet'],
        ['label'=>'Products',  'value'=>$yearStats['products'],                    'color'=>'purple'],
        ['label'=>'Customers', 'value'=>$yearStats['customers'],                   'color'=>'teal'],
        ['label'=>'Employees', 'value'=>$yearStats['employees'],                   'color'=>'pink'],
    ] as $card)
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-{{ $card['color'] }}-100 dark:border-{{ $card['color'] }}-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $card['label'] }}</p>
        <p class="text-sm font-bold text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-0.5">{{ $card['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- Best Month --}}
@if($bestMonth)
<div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-4 mb-5 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-primary-100 text-xs">🏆 Best Month of {{ $year }}</p>
            <p class="text-2xl font-bold mt-1">{{ $bestMonth['month'] }}</p>
            <p class="text-primary-100 text-sm mt-0.5">৳{{ number_format($bestMonth['revenue']) }} revenue · {{ $bestMonth['orders'] }} orders</p>
        </div>
        <div class="text-right">
            <p class="text-primary-100 text-xs">Profit</p>
            <p class="text-xl font-bold">৳{{ number_format($bestMonth['profit']) }}</p>
        </div>
    </div>
</div>
@endif

{{-- Monthly Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-5">
    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Monthly Breakdown — {{ $year }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Month</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Revenue</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Expenses</th>
                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Net Profit</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Orders</th>
                    <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden md:table-cell">Trend</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @php $maxRev = collect($months)->max('revenue') ?: 1; @endphp
                @foreach($months as $m)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors {{ $m['month'] === now()->format('M') && $year == now()->year ? 'bg-primary-50 dark:bg-primary-900/10' : '' }}">
                    <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white text-sm">
                        {{ $m['month'] }}
                        @if($m['month'] === now()->format('M') && $year == now()->year)
                            <span class="ml-1 text-xs text-primary-600 dark:text-primary-400">(current)</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-right text-sm text-gray-900 dark:text-white">{{ $m['revenue']>0?'৳'.number_format($m['revenue']):'—' }}</td>
                    <td class="px-4 py-2.5 text-right text-sm text-red-600 dark:text-red-400">{{ $m['expense']>0?'৳'.number_format($m['expense']):'—' }}</td>
                    <td class="px-4 py-2.5 text-right font-bold text-sm {{ $m['profit']>=0?'text-green-600 dark:text-green-400':'text-red-600 dark:text-red-400' }}">
                        {{ $m['profit']!=0 ? ($m['profit']>0?'':'- ').'৳'.number_format(abs($m['profit'])) : '—' }}
                    </td>
                    <td class="px-4 py-2.5 text-center text-sm text-gray-600 dark:text-slate-300">{{ $m['orders']>0?$m['orders']:'—' }}</td>
                    <td class="px-4 py-2.5 hidden md:table-cell">
                        <div class="flex items-center gap-1">
                            <div class="bg-gray-100 dark:bg-slate-700 rounded-full h-1.5 w-full">
                                <div class="h-1.5 rounded-full bg-primary-400 dark:bg-primary-500" style="width:{{ $maxRev>0?min(100,($m['revenue']/$maxRev)*100):0 }}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-slate-700/50 border-t-2 border-gray-200 dark:border-slate-600">
                <tr>
                    <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white">Total</td>
                    <td class="px-4 py-2.5 text-right font-bold text-primary-600 dark:text-primary-400">৳{{ number_format($yearStats['revenue']) }}</td>
                    <td class="px-4 py-2.5 text-right font-bold text-red-600 dark:text-red-400">৳{{ number_format($yearStats['expense']) }}</td>
                    <td class="px-4 py-2.5 text-right font-bold text-lg {{ $yearStats['profit']>=0?'text-green-600 dark:text-green-400':'text-red-600 dark:text-red-400' }}">৳{{ number_format(abs($yearStats['profit'])) }}</td>
                    <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ number_format($yearStats['orders']) }}</td>
                    <td class="hidden md:table-cell"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection


{{-- ============================================================
     FILE: resources/views/reports/customer-analytics.blade.php
     ============================================================ --}}