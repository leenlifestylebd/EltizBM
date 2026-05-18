@extends('layouts.app')
@section('title', 'Reports & Analytics')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-base font-bold text-gray-900 dark:text-white">📊 Reports & Analytics</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ now()->format('F Y') }} — Live snapshot</p>
    </div>
    <a href="{{ route('reports.profit-loss') }}" class="px-3 py-2 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700">Full P&amp;L →</a>
</div>

{{-- This Month Live KPIs --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    @php
    $kpis = [
        ['label'=>'Revenue',      'value'=>'৳'.number_format($thisMonth['revenue']),  'sub'=>'sales income',     'color'=>'primary',   'icon'=>'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
        ['label'=>'Expenses',     'value'=>'৳'.number_format($thisMonth['expenses']), 'sub'=>'incl. wages',      'color'=>'red',    'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2'],
        ['label'=>'Net Profit',   'value'=>'৳'.number_format(abs($thisMonth['profit'])), 'sub'=>$thisMonth['profit']>=0?'profit':'loss', 'color'=>$thisMonth['profit']>=0?'green':'orange', 'icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2'],
        ['label'=>'Orders',       'value'=>number_format($thisMonth['orders']),        'sub'=>'this month',       'color'=>'violet', 'icon'=>'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17'],
        ['label'=>'Customers',    'value'=>number_format($thisMonth['customers']),     'sub'=>'total',            'color'=>'purple', 'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0'],
        ['label'=>'Customer Due', 'value'=>'৳'.number_format($thisMonth['total_due']), 'sub'=>'outstanding',     'color'=>'yellow', 'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3.5 stat-card">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs text-gray-500 dark:text-slate-400 font-medium">{{ $kpi['label'] }}</p>
            <div class="w-7 h-7 bg-{{ $kpi['color'] }}-100 dark:bg-{{ $kpi['color'] }}-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-{{ $kpi['color'] }}-600 dark:text-{{ $kpi['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kpi['icon'] }}"/>
                </svg>
            </div>
        </div>
        <p class="text-lg font-bold text-{{ $kpi['color'] }}-600 dark:text-{{ $kpi['color'] }}-400">{{ $kpi['value'] }}</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $kpi['sub'] }}</p>
    </div>
    @endforeach
</div>

{{-- Quick Links to Date-Filtered Reports --}}
<div class="flex flex-wrap gap-2 mb-5">
    @php
    $qs = http_build_query(['from' => $thisMonth['from'], 'to' => $thisMonth['to']]);
    @endphp
    <a href="{{ route('reports.profit-loss') }}?{{ $qs }}" class="px-3 py-1.5 text-xs font-medium bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border border-primary-200 dark:border-primary-800 rounded-lg hover:bg-primary-100 transition-colors">This Month P&amp;L</a>
    <a href="{{ route('reports.expense-category') }}?{{ $qs }}" class="px-3 py-1.5 text-xs font-medium bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 transition-colors">Expense Breakdown</a>
    <a href="{{ route('reports.income-source') }}?{{ $qs }}" class="px-3 py-1.5 text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-100 transition-colors">Income Sources</a>
    <a href="{{ route('reports.business-overview') }}" class="px-3 py-1.5 text-xs font-medium bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border border-primary-200 dark:border-primary-800 rounded-lg hover:bg-primary-100 transition-colors">{{ now()->year }} Overview</a>
    <a href="{{ route('reports.employee-production') }}?{{ $qs }}" class="px-3 py-1.5 text-xs font-medium bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border border-primary-200 dark:border-primary-800 rounded-lg hover:bg-primary-100 transition-colors">Employee Production</a>
    <a href="{{ route('reports.sales-channel') }}?{{ $qs }}" class="px-3 py-1.5 text-xs font-medium bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800 rounded-lg hover:bg-purple-100 transition-colors">Sales by Channel</a>
</div>

{{-- Report Cards Grid --}}
<p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-3">All Reports</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @php
    $reports = [
        ['route'=>'reports.business-overview', 'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'label'=>'Business Overview', 'desc'=>'Full year monthly P&L, orders and performance summary', 'color'=>'primary', 'badge'=>'Popular'],
        ['route'=>'reports.profit-loss',       'icon'=>'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'label'=>'Profit & Loss', 'desc'=>'Revenue, COGS, expenses, production wages, gross & net profit with chart', 'color'=>'green', 'badge'=>null],
        ['route'=>'reports.expense-category',  'icon'=>'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', 'label'=>'Expense by Category', 'desc'=>'Where money goes — category breakdown with donut chart & percentages', 'color'=>'red', 'badge'=>null],
        ['route'=>'reports.income-source',     'icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label'=>'Income Sources', 'desc'=>'Revenue by income category with trend analysis', 'color'=>'teal', 'badge'=>null],
        ['route'=>'reports.order-analytics',   'icon'=>'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'label'=>'Order Analytics', 'desc'=>'Order trends, sources, top products, completion & cancellation rates', 'color'=>'violet', 'badge'=>null],
        ['route'=>'reports.customer-analytics','icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'label'=>'Customer Analytics', 'desc'=>'Top customers, new registrations, payment due analysis', 'color'=>'purple', 'badge'=>null],
        ['route'=>'reports.stock-inventory',   'icon'=>'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'label'=>'Stock & Inventory', 'desc'=>'Stock value, low stock alerts, category breakdown', 'color'=>'orange', 'badge'=>null],
        ['route'=>'reports.account-balance',   'icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label'=>'Account Balance', 'desc'=>'Cash, bKash, Nagad, Bank balance by payment method', 'color'=>'cyan', 'badge'=>null],
        ['route'=>'reports.customer-due',      'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'label'=>'Customer Due', 'desc'=>'Customers with outstanding payment dues ranked by amount', 'color'=>'yellow', 'badge'=>null],
        ['route'=>'reports.supplier-due',      'icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label'=>'Supplier Due', 'desc'=>'Outstanding vendor/supplier payments and purchase dues', 'color'=>'slate', 'badge'=>null],
        ['route'=>'reports.return-refund',     'icon'=>'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z', 'label'=>'Returns & Refunds', 'desc'=>'Returned shipments and refunded orders with values', 'color'=>'pink', 'badge'=>null],
        ['route'=>'reports.purchase-orders',     'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label'=>'Purchase Orders', 'desc'=>'Vendor purchase history, amounts paid and payment status', 'color'=>'violet', 'badge'=>null],
        ['route'=>'reports.employee-production', 'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'label'=>'Employee Production', 'desc'=>'Per-employee breakdown by product, color, size — quantities & wages with export', 'color'=>'primary', 'badge'=>'New'],
        ['route'=>'reports.sales-channel',       'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'label'=>'Sales by Channel', 'desc'=>'Online vs outlet vs direct — revenue, completion rate and top products per channel', 'color'=>'purple', 'badge'=>'New'],
    ];
    @endphp

    @foreach($reports as $r)
    <a href="{{ route($r['route']) }}"
       class="group relative bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 hover:shadow-lg hover:border-{{ $r['color'] }}-200 dark:hover:border-{{ $r['color'] }}-700 transition-all duration-200">
        <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 bg-{{ $r['color'] }}-100 dark:bg-{{ $r['color'] }}-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-{{ $r['color'] }}-600 dark:text-{{ $r['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $r['icon'] }}"/>
                </svg>
            </div>
            @if($r['badge'])
            <span class="text-xs font-medium px-2 py-0.5 bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 rounded-full">{{ $r['badge'] }}</span>
            @endif
        </div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-{{ $r['color'] }}-600 dark:group-hover:text-{{ $r['color'] }}-400 transition-colors">{{ $r['label'] }}</h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">{{ $r['desc'] }}</p>
        <div class="flex items-center gap-1 mt-3">
            <span class="text-xs text-{{ $r['color'] }}-600 dark:text-{{ $r['color'] }}-400 font-medium">View Report</span>
            <svg class="w-3 h-3 text-{{ $r['color'] }}-500 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </a>
    @endforeach
</div>

@endsection
