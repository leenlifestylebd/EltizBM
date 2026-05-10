{{-- ============================================================
     FILE: resources/views/courier/index.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Courier & Shipments')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Courier & Shipments</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $shipments->total() }} total shipments</p>
    </div>
    <a href="{{ route('courier.create') }}"
       class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Shipment
    </a>
</div>

{{-- Status Tabs --}}
<div class="flex gap-1.5 mb-4 overflow-x-auto pb-1 scrollbar-hide">
    @php $tabs = [''=>'All','pending'=>'Pending','picked'=>'Picked','in_transit'=>'In Transit','delivered'=>'Delivered','returned'=>'Returned','cancelled'=>'Cancelled']; @endphp
    @foreach($tabs as $key => $label)
    <a href="{{ route('courier.index', $key ? ['status'=>$key] : []) }}"
       class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
              {{ request('status','')===$key ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700' }}">
        {{ $label }}
        @if(isset($statusCounts[$key]) && $statusCounts[$key] > 0)
            <span class="px-1.5 py-0.5 rounded-full text-xs {{ request('status','')===$key ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300' }}">
                {{ $statusCounts[$key] }}
            </span>
        @endif
    </a>
    @endforeach
</div>

{{-- Shipments --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">

    {{-- Desktop Table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Order</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Customer</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Courier</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Tracking</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Charge</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @php $sc=['pending'=>'yellow','picked'=>'blue','in_transit'=>'indigo','delivered'=>'green','returned'=>'orange','cancelled'=>'red']; @endphp
                @forelse($shipments as $shipment)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-4 py-3">
                        <a href="{{ $shipment->order_id && $shipment->order ? route('orders.show', $shipment->order) : '#' }}"
                           class="font-semibold text-blue-600 dark:text-blue-400 hover:underline text-xs">
                            #{{ $shipment->order->order_number ?? '—' }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $shipment->order->customer_name ?? '—' }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500 truncate max-w-32">{{ $shipment->order->shipping_area ?? '' }}{{ $shipment->order->shipping_district ? ', '.$shipment->order->shipping_district : '' }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-300">{{ $shipment->courier_name }}</td>
                    <td class="px-4 py-3">
                        @if($shipment->tracking_id)
                            <span class="text-xs font-mono text-gray-600 dark:text-slate-300">{{ $shipment->tracking_id }}</span>
                        @else
                            <span class="text-xs text-gray-400 dark:text-slate-500">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">৳{{ number_format($shipment->charge) }}</td>
                    <td class="px-4 py-3 text-center">
                        @php $c=$sc[$shipment->status]??'gray'; @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700 dark:bg-{{ $c }}-900/30 dark:text-{{ $c }}-400">
                            {{ ucfirst(str_replace('_',' ',$shipment->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-slate-400">{{ $shipment->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('courier.show', $shipment) }}"
                               class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('courier.update', $shipment) }}" x-data>
                                @csrf @method('PUT')
                                <select name="status" onchange="this.form.submit()"
                                        class="text-xs px-1.5 py-1 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                                    @foreach(['pending','picked','in_transit','delivered','returned','cancelled'] as $s)
                                        <option value="{{ $s }}" {{ $shipment->status === $s ? 'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No shipments found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @php $sc=['pending'=>'yellow','picked'=>'blue','in_transit'=>'indigo','delivered'=>'green','returned'=>'orange','cancelled'=>'red']; @endphp
        @forelse($shipments as $shipment)
        <div class="p-4">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <a href="{{ route('courier.show', $shipment) }}" class="font-semibold text-blue-600 dark:text-blue-400 text-sm">
                        #{{ $shipment->order->order_number ?? '—' }}
                    </a>
                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ $shipment->order->customer_name ?? '—' }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $shipment->courier_name }}</p>
                </div>
                <div class="text-right">
                    @php $c=$sc[$shipment->status]??'gray'; @endphp
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700 dark:bg-{{ $c }}-900/30 dark:text-{{ $c }}-400">
                        {{ ucfirst(str_replace('_',' ',$shipment->status)) }}
                    </span>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">৳{{ number_format($shipment->charge) }}</p>
                </div>
            </div>
            @if($shipment->tracking_id)
            <p class="text-xs font-mono text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-700/50 px-2 py-1 rounded-lg">
                📦 {{ $shipment->tracking_id }}
            </p>
            @endif
        </div>
        @empty
        <div class="p-12 text-center text-sm text-gray-400">No shipments found</div>
        @endforelse
    </div>

    @if($shipments->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $shipments->withQueryString()->links() }}</div>
    @endif
</div>

@endsection