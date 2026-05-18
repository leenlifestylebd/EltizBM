{{-- ===================================================
     FILE: resources/views/web-orders/index.blade.php
     =================================================== --}}
@extends('layouts.app')
@section('title', 'Web Orders')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Web Orders</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Orders from WooCommerce</p>
    </div>
    <div class="flex items-center gap-2">
        @if($wooSettings?->is_active)
        <form method="POST" action="{{ route('web-orders.sync') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sync Now
            </button>
        </form>
        @else
        <a href="{{ route('integrations.woocommerce') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-purple-600 border border-purple-200 dark:border-purple-700 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
            Connect WooCommerce
        </a>
        @endif
    </div>
</div>

{{-- Status Tabs --}}
<div class="flex gap-1.5 mb-4 overflow-x-auto pb-1">
    @php
    $tabs = [
        ['key' => '',           'label' => 'All'],
        ['key' => 'pending',    'label' => 'Pending'],
        ['key' => 'processing', 'label' => 'Processing'],
        ['key' => 'completed',  'label' => 'Completed'],
        ['key' => 'cancelled',  'label' => 'Cancelled'],
    ];
    @endphp
    @foreach($tabs as $tab)
    <a href="{{ route('web-orders.index', $tab['key'] ? ['status' => $tab['key']] : []) }}"
       class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
              {{ request('status', '') === $tab['key']
                  ? 'bg-purple-600 text-white'
                  : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-50' }}">
        {{ $tab['label'] }}
        <span class="px-1.5 py-0.5 rounded-full text-xs {{ request('status','') === $tab['key'] ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300' }}">
            {{ $statusCounts[$tab['key'] ?: 'all'] }}
        </span>
    </a>
    @endforeach
</div>

{{-- Not connected banner --}}
@if(!$wooSettings?->is_active)
<div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-xl p-5 mb-4 text-center">
    <svg class="w-10 h-10 text-purple-400 mx-auto mb-2" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
    </svg>
    <p class="text-sm font-semibold text-purple-700 dark:text-purple-400 mb-1">WooCommerce Not Connected</p>
    <p class="text-xs text-purple-600 dark:text-purple-500 mb-3">Connect your store to automatically import orders.</p>
    <a href="{{ route('integrations.woocommerce') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
        Connect Now
    </a>
</div>
@endif

{{-- Orders Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">

    {{-- Desktop --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">WOO Order</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Customer</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Total</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Date</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-4 py-3">
                        <a href="{{ route('web-orders.show', $order) }}" class="font-semibold text-purple-600 dark:text-purple-400 hover:underline text-xs">
                            #{{ $order->order_number }}
                        </a>
                        @if($order->woo_order_id)
                            <p class="text-xs text-gray-400 dark:text-slate-500">WOO #{{ $order->woo_order_id }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</p>
                        @if($order->customer_phone)
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white text-sm">৳{{ number_format($order->total) }}</td>
                    <td class="px-4 py-3 text-center">
                        @php $colors = ['pending'=>'yellow','processing'=>'blue','completed'=>'green','cancelled'=>'red','returned'=>'orange']; $c = $colors[$order->status] ?? 'gray'; @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700 dark:bg-{{ $c }}-900/30 dark:text-{{ $c }}-400">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-slate-400">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('web-orders.show', $order) }}" class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-16 text-center">
                        <p class="text-sm text-gray-400 dark:text-slate-500">No web orders yet. Sync from WooCommerce!</p>
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
            <div class="flex items-start justify-between mb-1.5">
                <div>
                    <a href="{{ route('web-orders.show', $order) }}" class="font-semibold text-purple-600 dark:text-purple-400 text-sm">
                        #{{ $order->order_number }}
                    </a>
                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ $order->customer_name }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-900 dark:text-white">৳{{ number_format($order->total) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y') }}</p>
                </div>
            </div>
            @php $c = $colors[$order->status] ?? 'gray'; @endphp
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700">{{ ucfirst($order->status) }}</span>
        </div>
        @empty
        <div class="p-12 text-center"><p class="text-sm text-gray-400">No web orders yet.</p></div>
        @endforelse
    </div>

    @if($orders->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $orders->withQueryString()->links() }}</div>
    @endif
</div>

@endsection
