@extends('layouts.app')
@section('title', 'Steadfast Courier')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Steadfast Courier</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Manage your Steadfast shipments</p>
    </div>
    <div class="flex items-center gap-2">
        @if($isConnected)
        <form method="POST" action="{{ route('steadfast.sync-status') }}">
            @csrf
            <button type="submit" class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span class="hidden sm:inline">Sync</span>
            </button>
        </form>
        @endif
        <a href="{{ route('integrations.courier-providers') }}" class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
            ⚙️ <span class="hidden sm:inline">Settings</span>
        </a>
    </div>
</div>

@if(!$isConnected)
<div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-5 mb-4">
    <div class="flex items-start gap-3">
        <span class="text-2xl">⚠️</span>
        <div>
            <p class="font-semibold text-yellow-800 dark:text-yellow-400">Steadfast not connected!</p>
            <p class="text-sm text-yellow-700 dark:text-yellow-500 mt-1">API Key এবং Secret Key দিয়ে connect করুন।</p>
            <a href="{{ route('integrations.courier-providers') }}" class="mt-2 inline-block px-4 py-2 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700">Connect Now →</a>
        </div>
    </div>
</div>
@else

{{-- Stats Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-green-100 dark:border-green-900/30" x-data="{ balance: '{{ $balance }}' }">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs text-gray-500 dark:text-slate-400">Balance</p>
            <button onclick="refreshBalance()" class="p-1 text-gray-400 hover:text-blue-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
        </div>
        <p class="text-lg font-bold text-green-600 dark:text-green-400">৳<span id="balanceAmount">{{ number_format($balance ?? 0) }}</span></p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-blue-100 dark:border-blue-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Total Sent</p>
        <p class="text-lg font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $shipmentCounts[''] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-green-100 dark:border-green-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Delivered</p>
        <p class="text-lg font-bold text-green-600 dark:text-green-400 mt-1">{{ $shipmentCounts['delivered'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-yellow-100 dark:border-yellow-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Pending</p>
        <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $shipmentCounts['pending'] }}</p>
    </div>
</div>
@endif

{{-- Main Tabs --}}
<div class="flex gap-1 mb-3 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl" x-data="{ tab: '{{ $statusFilter || request()->has('shipment_status') ? 'sent' : 'ready' }}' }">
    <button @click="tab = 'ready'" class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
            :class="tab === 'ready' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
        📦 Ready to Send ({{ $readyOrders->total() }})
    </button>
    <button @click="tab = 'sent'" class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
            :class="tab === 'sent' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
        🚚 Sent ({{ $shipmentCounts[''] }})
    </button>
    <button onclick="document.getElementById('statusModal').classList.remove('hidden')"
            class="px-3 py-2 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-white dark:hover:bg-slate-800 rounded-lg transition-colors">
        🔍 Track
    </button>
</div>

{{-- Status Filter Tabs --}}
<div class="flex gap-1.5 mb-3 overflow-x-auto pb-1 scrollbar-hide">
    @foreach([
        [''                 , 'All',             '📦'],
        ['pending'          , 'Pending',          '⏳'],
        ['approval_pending' , 'Approval',         '🔍'],
        ['delivered'        , 'Delivered',        '✅'],
        ['partial_delivered', 'Partial',          '📬'],
        ['cancelled'        , 'Cancelled',        '❌'],
        ['in_review'        , 'In Review',        '🔎'],
    ] as [$key, $label, $icon])
    <a href="{{ route('steadfast.index', $key ? ['shipment_status' => $key] : []) }}"
       class="flex-shrink-0 flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
              {{ $statusFilter === $key ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-50' }}">
        {{ $icon }} {{ $label }}
        @if($shipmentCounts[$key] > 0)
        <span class="px-1.5 py-0.5 rounded-full text-xs {{ $statusFilter === $key ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700 text-gray-500' }}">
            {{ $shipmentCounts[$key] }}
        </span>
        @endif
    </a>
    @endforeach
</div>

{{-- Tab Content --}}
<div x-data="{ tab: '{{ $statusFilter || request()->has('shipment_status') ? 'sent' : 'ready' }}' }">

{{-- Ready to Send --}}
<div x-show="tab === 'ready'" x-data="{ selectedOrders: [] }">

    {{-- Bulk Send Bar --}}
    <div x-show="selectedOrders.length > 0" x-cloak class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 rounded-xl p-3 mb-3 flex items-center justify-between">
        <p class="text-sm font-medium text-blue-700"><span x-text="selectedOrders.length"></span> selected</p>
        <form method="POST" action="{{ route('steadfast.bulk-send') }}" id="bulkSendForm">
            @csrf
            <div id="bulkOrderIds"></div>
            <button type="button" @click="submitBulk()" class="px-3 py-2 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">🚀 Send All</button>
        </form>
    </div>

    @if($readyOrders->isEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
        <p class="text-4xl mb-3">📭</p>
        <p class="text-sm text-gray-500 dark:text-slate-400">No orders ready to send</p>
        <a href="{{ route('orders.index') }}" class="mt-2 inline-block text-xs text-blue-600 hover:underline">View Orders →</a>
    </div>
    @else

    {{-- Desktop Table --}}
    <div class="hidden md:block bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="px-4 py-3"><input type="checkbox" @change="toggleAll($event)" class="w-4 h-4 rounded"></th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Order</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Customer</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Address</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">COD</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Send</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($readyOrders as $order)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                    <td class="px-4 py-3"><input type="checkbox" :value="{{ $order->id }}" @change="toggleOrder({{ $order->id }})" class="w-4 h-4 rounded"></td>
                    <td class="px-4 py-3">
                        <a href="{{ route('orders.show', $order) }}" class="font-semibold text-blue-600 text-xs hover:underline">#{{ $order->order_number }}</a>
                        <p class="text-xs text-gray-400">{{ $order->created_at->format('d M') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</p>
                        <p class="text-xs text-gray-400">{{ $order->customer_phone ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3 max-w-40">
                        <p class="text-xs text-gray-600 dark:text-slate-300 truncate">{{ $order->shipping_address }}</p>
                        <p class="text-xs text-gray-400">{{ implode(', ', array_filter([$order->shipping_area, $order->shipping_district])) }}</p>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">৳{{ number_format($order->due_amount) }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($isConnected)
                        <form method="POST" action="{{ route('steadfast.send-order', $order) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">Send →</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($readyOrders->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $readyOrders->links() }}</div>
        @endif
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden space-y-2">
        @foreach($readyOrders as $order)
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-2">
                    <input type="checkbox" :value="{{ $order->id }}" @change="toggleOrder({{ $order->id }})" class="w-4 h-4 rounded mt-0.5">
                    <div>
                        <a href="{{ route('orders.show', $order) }}" class="font-semibold text-blue-600 text-sm">#{{ $order->order_number }}</a>
                        <p class="text-xs text-gray-400">{{ $order->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <span class="font-bold text-gray-900 dark:text-white">৳{{ number_format($order->due_amount) }}</span>
            </div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</p>
            <p class="text-xs text-gray-400 mb-2">{{ $order->customer_phone }} · {{ $order->shipping_district }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3 truncate">📍 {{ $order->shipping_address }}</p>
            @if($isConnected)
            <form method="POST" action="{{ route('steadfast.send-order', $order) }}">
                @csrf
                <button type="submit" class="w-full py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">🚀 Send to Steadfast</button>
            </form>
            @endif
        </div>
        @endforeach
        @if($readyOrders->hasPages())
        <div class="py-3">{{ $readyOrders->links() }}</div>
        @endif
    </div>
    @endif
</div>

{{-- Sent Shipments --}}
<div x-show="tab === 'sent'" x-cloak>

    @if($shipments->isEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
        <p class="text-4xl mb-3">🚚</p>
        <p class="text-sm text-gray-500 dark:text-slate-400">No shipments yet</p>
    </div>
    @else

    {{-- Desktop Table --}}
    <div class="hidden md:block bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Order</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Customer</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Consignment</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Tracking</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">COD</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($shipments as $shipment)
                @php
                $sc = match($shipment->status) {
                    'delivered'         => 'bg-green-100 text-green-700',
                    'cancelled'         => 'bg-red-100 text-red-700',
                    'partial_delivered' => 'bg-orange-100 text-orange-700',
                    'approval_pending'  => 'bg-purple-100 text-purple-700',
                    'in_review'         => 'bg-indigo-100 text-indigo-700',
                    default             => 'bg-yellow-100 text-yellow-700',
                };
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                    <td class="px-4 py-3">
                        <a href="{{ $shipment->order_id && $shipment->order ? route('orders.show', $shipment->order) : '#' }}" class="font-semibold text-blue-600 text-xs hover:underline">
                            #{{ $shipment->order->order_number ?? '—' }}
                        </a>
                        <p class="text-xs text-gray-400">{{ $shipment->created_at->format('d M') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $shipment->order->customer_name ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $shipment->order->customer_phone ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">{{ $shipment->consignment_id ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">{{ $shipment->tracking_id ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">
                            {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">৳{{ number_format($shipment->cod_amount) }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            @if($shipment->tracking_id && $isConnected)
                            <button onclick="checkStatus('{{ $shipment->tracking_id }}')" class="px-2 py-1.5 text-xs bg-gray-100 text-gray-600 rounded-lg hover:bg-blue-50 hover:text-blue-600">🔍</button>
                            @endif
                            <a href="{{ route('steadfast.label', $shipment) }}" target="_blank" class="px-2 py-1.5 text-xs bg-green-100 text-green-700 rounded-lg hover:bg-green-200">🏷️</a>
                            <a href="{{ route('steadfast.invoice', $shipment) }}" target="_blank" class="px-2 py-1.5 text-xs bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">📄</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($shipments->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $shipments->links() }}</div>
        @endif
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden space-y-2">
        @foreach($shipments as $shipment)
        @php
        $sc = match($shipment->status) {
            'delivered'         => 'bg-green-100 text-green-700',
            'cancelled'         => 'bg-red-100 text-red-700',
            'partial_delivered' => 'bg-orange-100 text-orange-700',
            'approval_pending'  => 'bg-purple-100 text-purple-700',
            'in_review'         => 'bg-indigo-100 text-indigo-700',
            default             => 'bg-yellow-100 text-yellow-700',
        };
        @endphp
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <a href="{{ $shipment->order_id && $shipment->order ? route('orders.show', $shipment->order) : '#' }}" class="font-semibold text-blue-600 text-sm">
                        #{{ $shipment->order->order_number ?? '—' }}
                    </a>
                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ $shipment->order->customer_name ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $shipment->order->customer_phone ?? '—' }}</p>
                </div>
                <div class="text-right">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">
                        {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                    </span>
                    <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">৳{{ number_format($shipment->cod_amount) }}</p>
                </div>
            </div>
            <div class="flex gap-1 mb-2 flex-wrap">
                @if($shipment->consignment_id)
                <span class="font-mono text-xs bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">CONS: {{ $shipment->consignment_id }}</span>
                @endif
                @if($shipment->tracking_id)
                <span class="font-mono text-xs bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">{{ $shipment->tracking_id }}</span>
                @endif
            </div>
            <div class="flex gap-2">
                @if($shipment->tracking_id && $isConnected)
                <button onclick="checkStatus('{{ $shipment->tracking_id }}')" class="flex-1 py-2 text-xs bg-gray-100 text-gray-600 rounded-lg hover:bg-blue-50 hover:text-blue-600 font-medium">🔍 Track</button>
                @endif
                <a href="{{ route('steadfast.label', $shipment) }}" target="_blank" class="flex-1 py-2 text-xs bg-green-100 text-green-700 rounded-lg hover:bg-green-200 font-medium text-center">🏷️ Label</a>
                <a href="{{ route('steadfast.invoice', $shipment) }}" target="_blank" class="flex-1 py-2 text-xs bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 font-medium text-center">📄 Invoice</a>
            </div>
        </div>
        @endforeach
        @if($shipments->hasPages())
        <div class="py-3">{{ $shipments->links() }}</div>
        @endif
    </div>
    @endif
</div>
</div>

{{-- Track Modal --}}
<div id="statusModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">🔍 Track Parcel</h3>
            <button onclick="document.getElementById('statusModal').classList.add('hidden')" class="text-gray-400 text-xl">×</button>
        </div>
        <div class="p-5 space-y-3">
            <select id="statusType" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="tracking">Tracking Code</option>
                <option value="consignment">Consignment ID</option>
                <option value="invoice">Order Number</option>
            </select>
            <input type="text" id="statusValue" placeholder="Enter value..."
                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
            <button onclick="doStatusCheck()" class="w-full py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Check Status</button>
            <div id="statusResult" class="hidden p-3 rounded-xl text-sm"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function checkStatus(code) {
    document.getElementById('statusValue').value = code;
    document.getElementById('statusModal').classList.remove('hidden');
    doStatusCheck();
}

async function doStatusCheck() {
    const type   = document.getElementById('statusType').value;
    const value  = document.getElementById('statusValue').value.trim();
    const result = document.getElementById('statusResult');
    if (!value) return;
    result.className = 'p-3 rounded-xl text-sm bg-gray-50 text-gray-600';
    result.classList.remove('hidden');
    result.innerHTML = '⏳ Checking...';
    try {
        const res  = await fetch('{{ route("steadfast.check-status") }}?type=' + type + '&value=' + encodeURIComponent(value), {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        const map  = {
            'delivered': '✅ Delivered', 'in_review': '🔍 In Review',
            'pending': '⏳ Pending', 'cancelled': '❌ Cancelled',
            'partial_delivered': '📦 Partially Delivered',
            'delivered_approval_pending': '✅ Delivered (Pending Approval)',
        };
        if (data.delivery_status) {
            result.className = 'p-3 rounded-xl text-sm bg-green-50 text-green-700 border border-green-200';
            result.innerHTML = `<strong>Status:</strong> ${map[data.delivery_status] || data.delivery_status}`;
        } else {
            result.className = 'p-3 rounded-xl text-sm bg-red-50 text-red-600 border border-red-200';
            result.innerHTML = `❌ ${data.error || 'Not found'}`;
        }
    } catch(e) { result.innerHTML = '❌ Network error'; }
}

async function refreshBalance() {
    try {
        const res  = await fetch('{{ route("steadfast.get-balance") }}');
        const data = await res.json();
        if (data.current_balance !== undefined) {
            document.getElementById('balanceAmount').textContent = parseFloat(data.current_balance).toLocaleString();
        }
    } catch(e) {}
}

function submitBulk() {
    const boxes = document.querySelectorAll('input[type=checkbox][value]:checked');
    const form  = document.getElementById('bulkSendForm');
    const cont  = document.getElementById('bulkOrderIds');
    cont.innerHTML = '';
    boxes.forEach(cb => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = 'order_ids[]'; i.value = cb.value;
        cont.appendChild(i);
    });
    if (cont.children.length > 0) form.submit();
}
</script>
@endpush