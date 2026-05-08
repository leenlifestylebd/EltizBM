@extends('layouts.app')
@section('title', 'Reports')

@section('content')

<div class="mb-5">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Reports & Analytics</h2>
    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Insights to grow your business</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    @php
    $reports = [
        [
            'route'  => 'reports.business-overview',
            'icon'   => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'label'  => 'Business Overview',
            'desc'   => 'Yearly performance summary with monthly P&L',
            'color'  => 'blue',
            'badge'  => 'Popular',
        ],
        [
            'route'  => 'reports.profit-loss',
            'icon'   => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'label'  => 'Profit & Loss',
            'desc'   => 'Revenue, COGS, expenses, gross & net profit',
            'color'  => 'green',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.order-analytics',
            'icon'   => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'label'  => 'Order Analytics',
            'desc'   => 'Order trends, sources, top products, completion rate',
            'color'  => 'indigo',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.customer-analytics',
            'icon'   => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            'label'  => 'Customer Analytics',
            'desc'   => 'Top customers, new registrations, due analysis',
            'color'  => 'purple',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.stock-inventory',
            'icon'   => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
            'label'  => 'Stock & Inventory',
            'desc'   => 'Stock value, low stock alerts, category breakdown',
            'color'  => 'orange',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.expense-category',
            'icon'   => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
            'label'  => 'Expense by Category',
            'desc'   => 'Where your money is going — by category',
            'color'  => 'red',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.income-source',
            'icon'   => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'label'  => 'Income Sources',
            'desc'   => 'Revenue by income category breakdown',
            'color'  => 'teal',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.account-balance',
            'icon'   => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'label'  => 'Account Balance',
            'desc'   => 'Cash, bKash, Nagad, Bank balance summary',
            'color'  => 'cyan',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.customer-due',
            'icon'   => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            'label'  => 'Customer Due',
            'desc'   => 'Customers with outstanding payment dues',
            'color'  => 'yellow',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.supplier-due',
            'icon'   => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'label'  => 'Supplier Due',
            'desc'   => 'Outstanding payments to vendors/suppliers',
            'color'  => 'slate',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.return-refund',
            'icon'   => 'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z',
            'label'  => 'Returns & Refunds',
            'desc'   => 'Returned shipments and refunded orders',
            'color'  => 'pink',
            'badge'  => null,
        ],
        [
            'route'  => 'reports.purchase-orders',
            'icon'   => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'label'  => 'Purchase Orders',
            'desc'   => 'Vendor purchase history and payment status',
            'color'  => 'violet',
            'badge'  => null,
        ],
    ];
    @endphp

    @foreach($reports as $report)
    <a href="{{ route($report['route']) }}"
       class="group bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 hover:shadow-md hover:border-{{ $report['color'] }}-200 dark:hover:border-{{ $report['color'] }}-700 transition-all">
        <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 bg-{{ $report['color'] }}-100 dark:bg-{{ $report['color'] }}-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-{{ $report['color'] }}-600 dark:text-{{ $report['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $report['icon'] }}"/>
                </svg>
            </div>
            @if($report['badge'])
                <span class="text-xs font-medium px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">{{ $report['badge'] }}</span>
            @endif
        </div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-{{ $report['color'] }}-600 dark:group-hover:text-{{ $report['color'] }}-400 transition-colors mb-1">
            {{ $report['label'] }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">{{ $report['desc'] }}</p>
        <p class="text-xs text-{{ $report['color'] }}-600 dark:text-{{ $report['color'] }}-400 mt-3 font-medium group-hover:underline">View Report →</p>
    </a>
    @endforeach

</div>

@endsection
