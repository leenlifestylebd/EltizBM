{{-- ============================================================
     FILE: resources/views/courier/create.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'New Shipment')

@section('content')

<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('courier.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">New Shipment</h2>
    </div>

    <form method="POST" action="{{ route('courier.store') }}" class="space-y-4">
        @csrf

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Order Details</h3>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Select Order <span class="text-red-500">*</span></label>
                <select name="order_id" required id="orderSelect"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="fillOrderDetails(this)">
                    <option value="">Search & select order...</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}"
                                data-name="{{ $order->customer_name }}"
                                data-phone="{{ $order->customer_phone }}"
                                data-address="{{ $order->shipping_address }}"
                                data-area="{{ $order->shipping_area }}"
                                data-district="{{ $order->shipping_district }}"
                                data-total="{{ $order->total }}"
                                {{ request('order_id') == $order->id ? 'selected' : '' }}>
                            #{{ $order->order_number }} — {{ $order->customer_name }} (৳{{ number_format($order->total) }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Order info preview --}}
            <div id="orderInfo" class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hidden">
                <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1">Order Details</p>
                <p class="text-xs text-blue-600 dark:text-blue-400" id="orderInfoText"></p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Courier Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Courier Provider <span class="text-red-500">*</span></label>
                    <select name="courier_name" required
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select courier</option>
                        @foreach(['Pathao','Steadfast','RedX','Paperfly','Sundarban','SA Paribahan','Janani','Custom'] as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Tracking ID</label>
                    <input type="text" name="tracking_id"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Enter tracking number">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Delivery Charge (৳)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">৳</span>
                        <input type="number" name="charge" min="0" step="0.01" value="{{ $defaultCharge ?? 60 }}"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">COD Amount (৳)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">৳</span>
                        <input type="number" name="cod_amount" id="codAmount" min="0" step="0.01"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="0">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Status</label>
                    <select name="status"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="pending">Pending</option>
                        <option value="picked">Picked Up</option>
                        <option value="in_transit">In Transit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Ship Date</label>
                    <input type="date" name="shipped_at" value="{{ today()->format('Y-m-d') }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                    <input type="text" name="notes"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Optional delivery instructions...">
                </div>
            </div>
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-xl text-sm transition-colors">
            Create Shipment
        </button>
    </form>
</div>

@endsection

@push('scripts')
<script>
function fillOrderDetails(sel) {
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('orderInfo');
    const txt  = document.getElementById('orderInfoText');
    const cod  = document.getElementById('codAmount');

    if (opt.value) {
        txt.textContent = `${opt.dataset.name} · ${opt.dataset.phone || 'No phone'} · ${opt.dataset.area || ''}, ${opt.dataset.district || ''}`;
        cod.value = opt.dataset.total || '';
        info.classList.remove('hidden');
    } else {
        info.classList.add('hidden');
    }
}
// Pre-fill if order_id in URL
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('orderSelect');
    if (sel && sel.value) fillOrderDetails(sel);
});
</script>
@endpush
