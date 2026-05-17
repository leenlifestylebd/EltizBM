@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

{{-- Welcome Banner --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
            Welcome, {{ auth()->user()->name ?? 'User' }}! 👋
        </h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Today's Update — {{ now()->format('d M Y') }}
        </p>
    </div>
</div>

{{-- Stats Grid — 2 col mobile, 3 col md, 6 col xl --}}
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 mb-5">

    {{-- Total Orders --}}
    <div class="stat-card bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">Total Orders</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total_orders'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">today</p>
            </div>
            <div class="w-9 h-9 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Revenue --}}
    <div class="stat-card bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">Revenue</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">৳{{ number_format($stats['revenue'] ?? 0) }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">today</p>
            </div>
            <div class="w-9 h-9 bg-amber-50 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Income --}}
    <div class="stat-card bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">Income</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">৳{{ number_format($stats['income'] ?? 0) }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">today</p>
            </div>
            <div class="w-9 h-9 bg-green-50 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Expenses --}}
    <div class="stat-card bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">Expenses</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">৳{{ number_format($stats['expenses'] ?? 0) }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">today</p>
            </div>
            <div class="w-9 h-9 bg-red-50 dark:bg-red-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Products --}}
    <div class="stat-card bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">Products</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['products'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">in catalog</p>
            </div>
            <div class="w-9 h-9 bg-purple-50 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Shipments --}}
    <div class="stat-card bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">Shipments</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['shipments'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">today</p>
            </div>
            <div class="w-9 h-9 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                </svg>
            </div>
        </div>
    </div>

</div>

{{-- Net Profit/Loss Banner --}}
<div class="bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-700 rounded-xl p-4 mb-5 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-slate-400">Net Profit/Loss</p>
            @php $profit = ($stats['income'] ?? 0) - ($stats['expenses'] ?? 0); @endphp
            <p class="text-sm font-bold {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                ৳{{ number_format($profit) }} {{ $profit >= 0 ? 'profit' : 'loss' }}
            </p>
        </div>
    </div>
    <a href="{{ route('reports.profit-loss') }}"
       class="text-xs font-medium bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 px-3 py-2 rounded-lg border border-gray-200 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
        View Reports
    </a>
</div>

{{-- Two column on md+: Courier + Recent Orders --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">

    {{-- Today's Courier Details --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Today's Courier Details</h3>
            <span class="text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 px-2 py-1 rounded-full font-medium">
                {{ $stats['shipments'] ?? 0 }} shipments
            </span>
        </div>
        @if(($stats['shipments'] ?? 0) === 0)
        <div class="flex flex-col items-center justify-center py-10">
            <svg class="w-10 h-10 text-gray-200 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/>
            </svg>
            <p class="text-sm font-medium text-gray-400 dark:text-slate-500">No shipments today</p>
            <p class="text-xs text-gray-300 dark:text-slate-600 mt-1">Shipments created today will appear here</p>
        </div>
        @else
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @foreach($todayShipments ?? [] as $shipment)
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $shipment->tracking_id }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $shipment->courier_name }}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-medium
                    {{ $shipment->status === 'delivered' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $shipment->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                    {{ $shipment->status === 'in_transit' ? 'bg-blue-100 text-blue-700' : '' }}">
                    {{ ucfirst($shipment->status) }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Orders</h3>
            <a href="{{ route('orders.index') }}" class="text-xs text-primary-600 dark:text-blue-400 font-medium hover:underline">View All</a>
        </div>
        @if(($recentOrders ?? collect())->isEmpty())
        <div class="flex flex-col items-center justify-center py-10">
            <svg class="w-10 h-10 text-gray-200 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm font-medium text-gray-400 dark:text-slate-500">No orders yet</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-700/50">
                        <th class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Order</th>
                        <th class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Customer</th>
                        <th class="text-right px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Amount</th>
                        <th class="text-center px-4 py-2.5 font-medium text-gray-500 dark:text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($recentOrders as $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-2.5 font-medium text-primary-600 dark:text-blue-400">
                            <a href="{{ route('orders.show', $order) }}">#{{ $order->order_number }}</a>
                        </td>
                        <td class="px-4 py-2.5 text-gray-700 dark:text-slate-300 truncate max-w-24">{{ $order->customer_name }}</td>
                        <td class="px-4 py-2.5 text-right font-medium text-gray-900 dark:text-white">৳{{ number_format($order->total) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $order->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $order->status === 'processing' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $order->status === 'cancelled' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>


@endsection
