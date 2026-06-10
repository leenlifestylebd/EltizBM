@extends('layouts.app')
@section('title', 'Returns')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Returns</h2>
</div>
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($shipments as $shipment)
        <div class="flex items-center gap-3 px-4 py-3">
            <div class="flex-1">
                <a href="{{ route('courier.show', $shipment) }}" class="font-semibold text-sm text-primary-600 dark:text-primary-400">#{{ $shipment->order->order_number }}</a>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $shipment->order->customer_name }} · {{ $shipment->courier_name }}</p>
            </div>
            <span class="text-xs bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 px-2 py-0.5 rounded-full">Returned</span>
        </div>
        @empty
        <div class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No returns</div>
        @endforelse
    </div>
    @if($shipments->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $shipments->links() }}</div>
    @endif
</div>
@endsection
