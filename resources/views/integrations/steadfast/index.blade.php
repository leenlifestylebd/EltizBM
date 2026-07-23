@extends('layouts.app')
@section('title', 'Shipments')

@section('content')

{{-- ── HEADER ─────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
        {{-- Courier badge (future: dropdown for multi-courier) --}}
        <div class="w-9 h-9 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Shipments</h2>
            <div class="flex items-center gap-1.5 mt-0.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                    {{ $isConnected ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isConnected ? 'bg-green-500' : 'bg-red-500' }} animate-pulse"></span>
                    Steadfast {{ $isConnected ? 'Connected' : 'Disconnected' }}
                </span>
                {{-- Future: + Add Courier button here --}}
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        @if($isConnected)
        <form method="POST" action="{{ route('steadfast.sync-status') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span class="hidden sm:inline">Sync All</span>
            </button>
        </form>
        @endif
        <a href="{{ route('integrations.courier-providers') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="hidden sm:inline">Settings</span>
        </a>
    </div>
</div>

{{-- ── NOT CONNECTED BANNER ───────────────────────────────── --}}
@if(!$isConnected)
<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-2xl p-5 mb-4">
    <div class="flex items-start gap-4">
        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-400">Steadfast API সংযুক্ত নেই</p>
            <p class="text-xs text-amber-700 dark:text-amber-500 mt-1">API Key এবং Secret Key দিয়ে connect করুন।</p>
            <a href="{{ route('integrations.courier-providers') }}"
               class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 text-white text-xs font-semibold rounded-lg hover:bg-amber-700 transition-colors">
                Connect Now →
            </a>
        </div>
    </div>
</div>
@endif

{{-- ── MAIN ALPINE WRAPPER ─────────────────────────────────── --}}
<div x-data="{
    tab: '{{ request()->has('shipment_status') || $statusFilter ? 'sent' : 'ready' }}',
    statusFilter: '{{ $statusFilter }}',
    selectedOrders: [],
    selectedShipments: [],
    trackModal: false,
    trackValue: '',
    trackType: 'tracking',
    trackResult: null,
    trackLoading: false,

    toggleOrder(id) {
        const idx = this.selectedOrders.indexOf(id);
        idx === -1 ? this.selectedOrders.push(id) : this.selectedOrders.splice(idx, 1);
    },
    toggleAllOrders(checked, ids) {
        this.selectedOrders = checked ? [...ids] : [];
    },
    toggleShipment(id) {
        const idx = this.selectedShipments.indexOf(id);
        idx === -1 ? this.selectedShipments.push(id) : this.selectedShipments.splice(idx, 1);
    },
    toggleAllShipments(checked, ids) {
        this.selectedShipments = checked ? [...ids] : [];
    },
    submitBulkSend() {
        const form = document.getElementById('bulkSendForm');
        const cont = document.getElementById('bulkSendIds');
        cont.innerHTML = '';
        this.selectedOrders.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'order_ids[]'; inp.value = id;
            cont.appendChild(inp);
        });
        form.submit();
    },
    submitBulkLabel() {
        const form = document.getElementById('bulkLabelForm');
        const cont = document.getElementById('bulkLabelIds');
        cont.innerHTML = '';
        this.selectedShipments.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'shipment_ids[]'; inp.value = id;
            cont.appendChild(inp);
        });
        form.submit();
    },
    openTrack(code) {
        this.trackValue = code || '';
        this.trackResult = null;
        this.trackModal = true;
        if (code) this.$nextTick(() => this.doTrack());
    },
    async doTrack() {
        if (!this.trackValue.trim()) return;
        this.trackLoading = true;
        this.trackResult = null;
        try {
            const res  = await fetch('{{ route('steadfast.check-status') }}?type=' + this.trackType + '&value=' + encodeURIComponent(this.trackValue), {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            });
            const data = await res.json();
            this.trackResult = data;
        } catch(e) { this.trackResult = { error: 'Network error' }; }
        this.trackLoading = false;
    },
    statusLabel(s) {
        const m = { delivered:'Delivered', pending:'Pending', in_review:'In Review',
                    partial_delivered:'Partial', cancelled:'Cancelled', approval_pending:'Approval Pending',
                    delivered_approval_pending:'Delivered (Pending)' };
        return m[s] || s;
    }
}" @keydown.escape.window="trackModal = false">

{{-- ── STATS ROW ───────────────────────────────────────────── --}}
@if($isConnected)
<div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-4">
    @php
    $statsData = [
        ['label'=>'Balance',  'value'=>'৳'.number_format($balance ?? 0), 'color'=>'green',  'refresh'=>true],
        ['label'=>'Total',    'value'=>$shipmentCounts[''],               'color'=>'primary'],
        ['label'=>'Pending',  'value'=>$shipmentCounts['pending'],        'color'=>'yellow'],
        ['label'=>'Delivered','value'=>$shipmentCounts['delivered'],      'color'=>'green'],
        ['label'=>'Partial',  'value'=>$shipmentCounts['partial_delivered'],'color'=>'orange'],
        ['label'=>'Cancelled','value'=>$shipmentCounts['cancelled'],      'color'=>'red'],
    ];
    @endphp
    @foreach($statsData as $s)
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-gray-100 dark:border-slate-700 text-center">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1 truncate">{{ $s['label'] }}</p>
        <p class="text-base font-bold text-{{ $s['color'] }}-600 dark:text-{{ $s['color'] }}-400 leading-none" @if($s['label']==='Balance') id="balanceAmount" @endif>
            {{ $s['value'] }}
        </p>
        @if($s['label']==='Balance')
        <button onclick="refreshBalance()" class="mt-1 text-xs text-gray-400 hover:text-primary-500">↻</button>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- Orders are sent to Steadfast from the Web Orders page. This panel only shows
     the shipments already sent, with live status / tracking. --}}
<div class="flex items-center justify-between mb-3">
    <a href="{{ route('web-orders.index') }}" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline">← Send new orders from Web Orders</a>
    <button @click="openTrack('')"
            class="px-4 py-2 text-xs font-semibold text-violet-600 dark:text-violet-400 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:bg-violet-50 dark:hover:bg-slate-700 transition-all">
        🔍 Track a Parcel
    </button>
</div>

@if(false)
{{-- (Removed) ready-to-send list — sending moved to the Web Orders page. --}}
<div x-show="tab === 'ready'" x-cloak>

    {{-- Bulk Send Bar --}}
    <div x-show="selectedOrders.length > 0" x-cloak
         class="sticky top-16 z-20 bg-primary-600 text-white rounded-xl px-4 py-3 mb-3 flex items-center justify-between shadow-lg">
        <span class="text-sm font-semibold"><span x-text="selectedOrders.length"></span> orders selected</span>
        <div class="flex gap-2">
            <button @click="selectedOrders = []" class="px-3 py-1.5 text-xs bg-white/20 rounded-lg hover:bg-white/30">Clear</button>
            <form id="bulkSendForm" method="POST" action="{{ route('steadfast.bulk-send') }}">
                @csrf
                <div id="bulkSendIds"></div>
                <button type="button" @click="submitBulkSend()"
                        class="px-3 py-1.5 text-xs bg-white text-primary-700 font-semibold rounded-lg hover:bg-primary-50">
                    🚀 Send All
                </button>
            </form>
        </div>
    </div>

    @if($readyOrders->isEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 py-16 text-center">
        <p class="text-5xl mb-3">📭</p>
        <p class="text-sm font-medium text-gray-600 dark:text-slate-300">No orders ready to send</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Confirmed/Processing orders will appear here</p>
        <a href="{{ route('orders.index') }}" class="mt-3 inline-block text-xs text-primary-600 hover:underline">View all orders →</a>
    </div>
    @else

    @php $allOrderIds = $readyOrders->pluck('id')->toArray(); @endphp

    {{-- Select All Bar --}}
    <div class="flex items-center justify-between px-1 mb-2">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox"
                   @change="toggleAllOrders($event.target.checked, {{ json_encode($allOrderIds) }})"
                   :checked="selectedOrders.length === {{ count($allOrderIds) }} && {{ count($allOrderIds) }} > 0"
                   class="w-4 h-4 rounded text-primary-600">
            <span class="text-xs text-gray-500 dark:text-slate-400">Select all {{ $readyOrders->count() }}</span>
        </label>
        <span class="text-xs text-gray-400 dark:text-slate-500">{{ $readyOrders->total() }} total</span>
    </div>

    <div class="space-y-2">
        @foreach($readyOrders as $order)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-4 transition-all"
             :class="selectedOrders.includes({{ $order->id }}) ? 'ring-2 ring-primary-500 border-primary-300' : ''">

            <div class="flex items-start gap-3">
                <input type="checkbox"
                       @change="toggleOrder({{ $order->id }})"
                       :checked="selectedOrders.includes({{ $order->id }})"
                       class="w-4 h-4 rounded text-primary-600 mt-1 flex-shrink-0">

                <div class="flex-1 min-w-0">
                    {{-- Row 1: Order # + COD --}}
                    <div class="flex items-center justify-between mb-1">
                        <a href="{{ route('orders.show', $order) }}"
                           class="text-sm font-bold text-primary-600 hover:underline">#{{ $order->order_number }}</a>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">৳{{ number_format($order->due_amount) }}</span>
                    </div>

                    {{-- Row 2: Customer --}}
                    <p class="text-sm font-medium text-gray-800 dark:text-slate-200 truncate">{{ $order->customer_name }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone }}</p>

                    {{-- Row 3: Address --}}
                    <div class="flex items-start gap-1 mt-1.5">
                        <svg class="w-3 h-3 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <p class="text-xs text-gray-500 dark:text-slate-400 leading-snug">
                            {{ implode(', ', array_filter([$order->shipping_address, $order->shipping_area, $order->shipping_district])) ?: '—' }}
                        </p>
                    </div>

                    {{-- Row 4: Date + Send button --}}
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs text-gray-400">{{ $order->created_at->format('d M, H:i') }}</span>
                        @if($isConnected)
                        <form method="POST" action="{{ route('steadfast.send-order', $order) }}">
                            @csrf
                            <button type="submit"
                                    class="flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-xl transition-colors active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Send
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-gray-400 italic">Not connected</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($readyOrders->hasPages())
    <div class="mt-4">{{ $readyOrders->links() }}</div>
    @endif
    @endif
</div>

@endif

{{-- SENT SHIPMENTS — live status / tracking via the Steadfast API --}}
<div>

    {{-- Status Filter Pills --}}
    <div class="flex gap-1.5 mb-3 overflow-x-auto pb-1 scrollbar-hide">
        @php
        $statusPills = [
            [''                 , 'All',            'gray'],
            ['pending'          , 'Pending',        'yellow'],
            ['approval_pending' , 'Approval',       'purple'],
            ['in_review'        , 'In Review',      'indigo'],
            ['delivered'        , 'Delivered',      'green'],
            ['partial_delivered', 'Partial',        'orange'],
            ['cancelled'        , 'Cancelled',      'red'],
        ];
        @endphp
        @foreach($statusPills as [$key, $label, $color])
        @php $active = $statusFilter === $key; @endphp
        <a href="{{ route('steadfast.index', $key ? ['shipment_status' => $key] : []) }}"
           class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap
                  {{ $active
                    ? "bg-{$color}-600 text-white shadow-sm"
                    : "bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700" }}">
            {{ $label }}
            @if($shipmentCounts[$key] > 0)
            <span class="px-1.5 py-0.5 rounded-full text-xs font-bold
                {{ $active ? 'bg-white/25 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400' }}">
                {{ $shipmentCounts[$key] }}
            </span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Bulk Print Bar --}}
    <div x-show="selectedShipments.length > 0" x-cloak
         class="sticky top-16 z-20 bg-green-600 text-white rounded-xl px-4 py-3 mb-3 flex items-center justify-between shadow-lg">
        <span class="text-sm font-semibold"><span x-text="selectedShipments.length"></span> selected</span>
        <div class="flex gap-2">
            <button @click="selectedShipments = []" class="px-3 py-1.5 text-xs bg-white/20 rounded-lg hover:bg-white/30">Clear</button>
            <form id="bulkLabelForm" method="POST" action="{{ route('steadfast.labels') }}" target="_blank">
                @csrf
                <div id="bulkLabelIds"></div>
                <button type="button" @click="submitBulkLabel()"
                        class="px-3 py-1.5 text-xs bg-white text-green-700 font-semibold rounded-lg hover:bg-green-50">
                    🏷️ Print Labels
                </button>
            </form>
        </div>
    </div>

    @if($shipments->isEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 py-16 text-center">
        <p class="text-5xl mb-3">🚚</p>
        <p class="text-sm font-medium text-gray-600 dark:text-slate-300">
            {{ $statusFilter ? 'No ' . str_replace('_', ' ', $statusFilter) . ' shipments' : 'No shipments yet' }}
        </p>
    </div>
    @else

    @php $allShipmentIds = $shipments->pluck('id')->toArray(); @endphp

    {{-- Select All --}}
    <div class="flex items-center justify-between px-1 mb-2">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox"
                   @change="toggleAllShipments($event.target.checked, {{ json_encode($allShipmentIds) }})"
                   :checked="selectedShipments.length === {{ count($allShipmentIds) }} && {{ count($allShipmentIds) }} > 0"
                   class="w-4 h-4 rounded text-green-600">
            <span class="text-xs text-gray-500 dark:text-slate-400">Select all {{ $shipments->count() }}</span>
        </label>
        <span class="text-xs text-gray-400 dark:text-slate-500">{{ $shipments->total() }} total</span>
    </div>

    <div class="space-y-2">
        @foreach($shipments as $shipment)
        @php
        $sConfig = match($shipment->status) {
            'delivered'         => ['color' => 'green',  'icon' => '✅', 'label' => 'Delivered'],
            'partial_delivered' => ['color' => 'orange', 'icon' => '📬', 'label' => 'Partial'],
            'cancelled'         => ['color' => 'red',    'icon' => '❌', 'label' => 'Cancelled'],
            'approval_pending'  => ['color' => 'purple', 'icon' => '🔍', 'label' => 'Approval'],
            'in_review'         => ['color'=>'violet', 'icon' => '🔎', 'label' => 'In Review'],
            default             => ['color' => 'yellow', 'icon' => '⏳', 'label' => 'Pending'],
        };
        @endphp

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-4 transition-all"
             :class="selectedShipments.includes({{ $shipment->id }}) ? 'ring-2 ring-green-500 border-green-300' : ''">

            <div class="flex items-start gap-3">
                <input type="checkbox"
                       @change="toggleShipment({{ $shipment->id }})"
                       :checked="selectedShipments.includes({{ $shipment->id }})"
                       class="w-4 h-4 rounded text-green-600 mt-1 flex-shrink-0">

                <div class="flex-1 min-w-0">
                    {{-- Row 1: Order # + Status badge --}}
                    <div class="flex items-center justify-between mb-1">
                        <a href="{{ $shipment->order ? route('orders.show', $shipment->order) : '#' }}"
                           class="text-sm font-bold text-primary-600 hover:underline">
                            #{{ $shipment->order->order_number ?? '—' }}
                        </a>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                            bg-{{ $sConfig['color'] }}-100 text-{{ $sConfig['color'] }}-700 dark:bg-{{ $sConfig['color'] }}-900/30 dark:text-{{ $sConfig['color'] }}-400">
                            {{ $sConfig['icon'] }} {{ $sConfig['label'] }}
                        </span>
                    </div>

                    {{-- Row 2: Customer + COD --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-slate-200">{{ $shipment->order->customer_name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $shipment->order->customer_phone ?? '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">৳{{ number_format($shipment->cod_amount) }}</p>
                            <p class="text-xs text-gray-400">COD</p>
                        </div>
                    </div>

                    {{-- Row 3: Tracking codes --}}
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @if($shipment->tracking_id)
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg text-xs font-mono">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/></svg>
                            {{ $shipment->tracking_id }}
                        </span>
                        @endif
                        @if($shipment->consignment_id)
                        <span class="px-2 py-1 bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 rounded-lg text-xs font-mono">
                            {{ $shipment->consignment_id }}
                        </span>
                        @endif
                        <span class="px-2 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-lg text-xs font-medium">
                            Steadfast
                        </span>
                    </div>

                    {{-- Row 4: Date + Actions --}}
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-50 dark:border-slate-700">
                        <span class="text-xs text-gray-400">{{ $shipment->created_at->format('d M, H:i') }}</span>
                        <div class="flex gap-1.5">
                            @if($shipment->tracking_id && $isConnected)
                            <button @click="openTrack('{{ $shipment->tracking_id }}')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 rounded-lg hover:bg-violet-100 transition-colors active:scale-95">
                                🔍 Track
                            </button>
                            @endif
                            <a href="{{ route('steadfast.label', $shipment) }}" target="_blank"
                               class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-100 transition-colors active:scale-95">
                                🏷️ Label
                            </a>
                            <a href="{{ route('steadfast.invoice', $shipment) }}" target="_blank"
                               class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 rounded-lg hover:bg-primary-100 transition-colors active:scale-95">
                                📄 Invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($shipments->hasPages())
    <div class="mt-4">{{ $shipments->links() }}</div>
    @endif
    @endif
</div>

{{-- ── TRACK MODAL ─────────────────────────────────────────── --}}
<div x-show="trackModal" x-cloak
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="trackModal = false"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden">

        {{-- Handle (mobile) --}}
        <div class="flex justify-center pt-3 pb-1 sm:hidden">
            <div class="w-10 h-1 bg-gray-200 dark:bg-slate-600 rounded-full"></div>
        </div>

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">🔍 Track Parcel</h3>
            <button @click="trackModal = false" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">✕</button>
        </div>

        <div class="p-5 space-y-3">
            <div class="flex gap-2">
                <select x-model="trackType"
                        class="flex-1 px-3 py-2.5 text-xs border border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="tracking">Tracking Code</option>
                    <option value="consignment">Consignment ID</option>
                    <option value="invoice">Order Number</option>
                </select>
            </div>

            <div class="flex gap-2">
                <input type="text" x-model="trackValue" placeholder="Enter value..."
                       @keydown.enter="doTrack()"
                       class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-400 font-mono focus:outline-none focus:ring-2 focus:ring-primary-500">
                <button @click="doTrack()"
                        :disabled="trackLoading"
                        class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white text-sm font-semibold rounded-xl transition-colors">
                    <span x-show="!trackLoading">Go</span>
                    <span x-show="trackLoading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                </button>
            </div>

            {{-- Result --}}
            <div x-show="trackResult !== null" x-cloak>
                <template x-if="trackResult && trackResult.delivery_status">
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl">
                        <p class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">Status</p>
                        <p class="text-sm font-bold text-green-800 dark:text-green-300" x-text="statusLabel(trackResult.delivery_status)"></p>
                    </div>
                </template>
                <template x-if="trackResult && !trackResult.delivery_status">
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl">
                        <p class="text-sm text-red-600 dark:text-red-400" x-text="trackResult.error || 'Parcel not found'"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

</div>{{-- /x-data --}}

@endsection

@push('scripts')
<script>
async function refreshBalance() {
    try {
        const res  = await fetch('{{ route("steadfast.get-balance") }}');
        const data = await res.json();
        if (data.current_balance !== undefined) {
            document.getElementById('balanceAmount').textContent = '৳' + parseFloat(data.current_balance).toLocaleString('en-BD');
        }
    } catch(e) {}
}
</script>
@endpush
