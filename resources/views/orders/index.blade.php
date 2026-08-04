@extends('layouts.app')
@php $wholesale = $wholesale ?? false; $idxRoute = $wholesale ? 'wholesale.index' : 'orders.index'; @endphp
@section('title', $wholesale ? 'Wholesale' : 'Orders')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $wholesale ? 'Wholesale Orders' : 'Orders' }}</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $orders->total() }} total {{ $wholesale ? 'wholesale ' : '' }}orders</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route($wholesale ? 'wholesale.create' : 'orders.create') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New {{ $wholesale ? 'Wholesale' : 'Order' }}
        </a>
        <a href="{{ route('orders.manual-exchange') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 transition-colors" title="Return from a past invoice not in the app">
            ↔️ Manual Exchange
        </a>
        @unless($wholesale)
        <a href="{{ route('orders.export', request()->query()) }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        @endunless
    </div>
</div>

{{-- Status Tabs (scrollable on mobile) --}}
<div class="flex gap-1.5 mb-4 overflow-x-auto pb-1 scrollbar-hide">
    @php
    $tabs = [
        ['key' => '',           'label' => 'All',        'count' => $statusCounts['all']],
        ['key' => 'pending',    'label' => 'Pending',    'count' => $statusCounts['pending']],
        ['key' => 'confirmed',  'label' => 'Confirmed',  'count' => $statusCounts['confirmed']],
        ['key' => 'processing', 'label' => 'Processing', 'count' => $statusCounts['processing']],
        ['key' => 'shipped',    'label' => 'Shipped',    'count' => $statusCounts['shipped']],
        ['key' => 'delivered',  'label' => 'Delivered',  'count' => $statusCounts['delivered']],
        ['key' => 'cancelled',  'label' => 'Cancelled',  'count' => $statusCounts['cancelled']],
    ];
    $activeTab = request('status', '');
    @endphp

    @foreach($tabs as $tab)
    <a href="{{ route($idxRoute, array_merge(request()->except('status','page'), $tab['key'] ? ['status' => $tab['key']] : [])) }}"
       class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
              {{ $activeTab === $tab['key']
                  ? 'bg-primary-600 text-white'
                  : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700' }}">
        {{ $tab['label'] }}
        @if($tab['count'] > 0)
            <span class="px-1.5 py-0.5 rounded-full text-xs
                {{ $activeTab === $tab['key'] ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300' }}">
                {{ $tab['count'] }}
            </span>
        @endif
    </a>
    @endforeach
</div>

{{-- Search & Filter --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" action="{{ route($idxRoute) }}" class="flex flex-col sm:flex-row gap-2">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Order #, customer name, phone..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>

        <div class="flex gap-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="flex-1 sm:flex-none text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="flex-1 sm:flex-none text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
            </button>
        </div>
    </form>
</div>

{{-- Orders Table + bulk picking-list selection --}}
<div x-data="pickingSelect({{ \Illuminate\Support\Js::from($orders->pluck('id')->map(fn($i) => (string) $i)->values()) }})">

    {{-- Bulk action bar (appears when orders are ticked) --}}
    <div x-show="selected.length" x-cloak
         class="sticky top-2 z-30 mb-3 flex items-center gap-3 px-4 py-2.5 bg-primary-600 text-white rounded-xl shadow-lg">
        <span class="text-sm font-semibold"><span x-text="selected.length"></span> order(s) selected</span>
        <a :href="pdfUrl()" target="_blank"
           class="ml-auto inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-primary-700 text-sm font-semibold rounded-lg hover:bg-primary-50 active:scale-95 transition-all">
            📄 Picking List PDF
        </a>
        <button type="button" @click="selected = []"
           class="px-3 py-1.5 text-sm font-medium bg-primary-500/40 rounded-lg hover:bg-primary-500/60">Clear</button>
    </div>

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">

    {{-- Desktop Table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="px-4 py-3 w-8 text-center"><input type="checkbox" @change="toggleAll($event)" :checked="allChecked" class="w-4 h-4 rounded border-gray-300 cursor-pointer align-middle" title="Select all on this page"></th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Order</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Customer</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Source</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Total</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Payment</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Date</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group" :class="selected.includes('{{ $order->id }}') ? 'bg-primary-50/60 dark:bg-primary-900/10' : ''">
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" value="{{ $order->id }}" x-model="selected" class="w-4 h-4 rounded border-gray-300 cursor-pointer align-middle">
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('orders.show', $order) }}"
                           class="font-semibold text-primary-600 dark:text-primary-400 hover:underline text-xs">
                            #{{ $order->order_number }}
                        </a>
                        @if($order->source === 'woocommerce')
                            <span class="block text-xs text-purple-500 dark:text-purple-400">WooCommerce</span>
                        @elseif($order->source === 'pos')
                            <span class="block text-xs text-green-500 dark:text-green-400">POS</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white text-xs">{{ $order->customer_name }}</p>
                        @if($order->customer_phone)
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                        $sourceColors = [
                            'manual'      => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300',
                            'woocommerce' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                            'pos'         => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'web'         => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
                        ];
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $sourceColors[$order->source] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($order->source) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">৳{{ number_format($order->total) }}</p>
                        @if($order->due_amount > 0)
                            <p class="text-xs text-red-500">Due: ৳{{ number_format($order->due_amount) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                        $payColors = [
                            'paid'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'partial'  => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'unpaid'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            'refunded' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-400',
                        ];
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $payColors[$order->payment_status] ?? '' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                        $statusColors = [
                            'pending'    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'confirmed'  => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
                            'processing' => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
                            'shipped'    => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                            'delivered'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'completed'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'cancelled'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            'returned'   => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        ];
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? '' }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-xs text-gray-600 dark:text-slate-300">{{ $order->created_at->format('d M Y') }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500">{{ $order->created_at->format('h:i A') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1.5">
                            @php $sfShip = $order->shipments->first(); @endphp
                            @if($sfShip)
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg" title="{{ $sfShip->tracking_id }}">🚚 Sent</span>
                            @elseif($isConnected ?? false)
                                <form method="POST" action="{{ route('steadfast.send-order', $order) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-primary-600 hover:bg-primary-700 text-white rounded-lg active:scale-95 transition-all" title="Send to Steadfast">🚚 Send</button>
                                </form>
                            @endif
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('orders.show', $order) }}"
                               class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('orders.invoice', $order) }}"
                               class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg" title="Invoice" target="_blank">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </a>
                            <a href="{{ route('orders.edit', $order) }}"
                               class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @if(auth()->user()->role === 'owner' || auth()->user()->role === 'admin')
                            <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Delete order #{{ $order->order_number }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-200 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm text-gray-400 dark:text-slate-500">No orders found</p>
                        <a href="{{ route('orders.create') }}" class="mt-2 inline-block text-xs text-primary-600 hover:underline">Create your first order</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($orders as $order)
        <div class="p-4">
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-start gap-2">
                    <input type="checkbox" value="{{ $order->id }}" x-model="selected" class="mt-1 w-4 h-4 rounded border-gray-300 cursor-pointer">
                    <div>
                    <a href="{{ route('orders.show', $order) }}"
                       class="font-semibold text-primary-600 dark:text-primary-400 text-sm">
                        #{{ $order->order_number }}
                    </a>
                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ $order->customer_name }}</p>
                    @if($order->customer_phone)
                        <p class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone }}</p>
                    @endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-900 dark:text-white">৳{{ number_format($order->total) }}</p>
                    @if($order->due_amount > 0)
                        <p class="text-xs text-red-500">Due: ৳{{ number_format($order->due_amount) }}</p>
                    @endif
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $order->created_at->format('d M, h:i A') }}</p>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($order->status) }}
                    </span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $payColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
                <div class="flex items-center gap-1">
                    @php $sfShip = $order->shipments->first(); @endphp
                    @if($sfShip)
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg mr-auto">🚚 Sent</span>
                    @elseif($isConnected ?? false)
                        <form method="POST" action="{{ route('steadfast.send-order', $order) }}" class="mr-auto">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-primary-600 hover:bg-primary-700 text-white rounded-lg active:scale-95 transition-all">🚚 Send</button>
                        </form>
                    @endif
                    <a href="{{ route('orders.show', $order) }}"
                       class="p-1.5 text-gray-400 bg-gray-100 dark:bg-slate-700 rounded-lg hover:text-primary-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>
                    <a href="{{ route('orders.invoice', $order) }}" target="_blank"
                       class="p-1.5 text-gray-400 bg-gray-100 dark:bg-slate-700 rounded-lg hover:text-green-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>
                    <a href="{{ route('orders.edit', $order) }}"
                       class="p-1.5 text-gray-400 bg-gray-100 dark:bg-slate-700 rounded-lg hover:text-primary-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    @if(auth()->user()->role === 'owner' || auth()->user()->role === 'admin')
                    <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Delete?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-400 bg-gray-100 dark:bg-slate-700 rounded-lg hover:text-red-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <p class="text-sm text-gray-400 dark:text-slate-500">No orders found</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
        {{ $orders->withQueryString()->links() }}
    </div>
    @endif
</div>
</div>{{-- /x-data pickingSelect --}}

@push('scripts')
<script>
function pickingSelect(pageIds) {
    return {
        selected: [],
        pageIds: pageIds || [],
        get allChecked() {
            return this.pageIds.length && this.pageIds.every(id => this.selected.includes(id));
        },
        toggleAll(e) {
            this.selected = e.target.checked ? [...this.pageIds] : [];
        },
        pdfUrl() {
            return "{{ route('orders.picking-list') }}?ids=" + this.selected.join(',');
        },
    };
}
</script>
@endpush

@endsection
