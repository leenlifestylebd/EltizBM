@extends('layouts.app')
@section('title', 'Steadfast Courier')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Steadfast Courier</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Manage your Steadfast shipments</p>
    </div>
    <div class="flex items-center gap-2">
        @if($isConnected)
        <form method="POST" action="{{ route('steadfast.sync-status') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Sync Status
            </button>
        </form>
        @endif
        <a href="{{ route('integrations.courier-providers') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
            ⚙️ Settings
        </a>
    </div>
</div>

@if(!$isConnected)
{{-- Not Connected --}}
<div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-5 mb-4">
    <div class="flex items-start gap-3">
        <span class="text-2xl">⚠️</span>
        <div>
            <p class="font-semibold text-yellow-800 dark:text-yellow-400">Steadfast not connected!</p>
            <p class="text-sm text-yellow-700 dark:text-yellow-500 mt-1">API Key এবং Secret Key দিয়ে connect করুন।</p>
            <a href="{{ route('integrations.courier-providers') }}"
               class="mt-2 inline-block px-4 py-2 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700 transition-colors">
                Connect Now →
            </a>
        </div>
    </div>
</div>
@else

{{-- Balance Card --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30 col-span-2 sm:col-span-1"
         x-data="{ balance: '{{ $balance }}', loading: false }"
         id="balance-card">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs text-gray-500 dark:text-slate-400">Steadfast Balance</p>
            <button @click="refreshBalance()"
                    class="p-1 text-gray-400 hover:text-blue-600 transition-colors" title="Refresh">
                <svg class="w-3.5 h-3.5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
        <p class="text-xl font-bold text-green-600 dark:text-green-400">
            ৳<span x-text="balance !== null ? parseFloat(balance).toLocaleString() : '—'"></span>
        </p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Total Shipments</p>
        <p class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $shipments->total() }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-yellow-100 dark:border-yellow-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400">Ready to Send</p>
        <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $readyOrders->total() }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400">Status Check</p>
        <button onclick="document.getElementById('statusModal').classList.remove('hidden')"
                class="mt-1 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
            Track Parcel →
        </button>
    </div>
</div>

@endif

{{-- Tabs --}}
<div class="flex gap-1 mb-4 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl" x-data="{ tab: 'ready' }">
    <button @click="tab = 'ready'"
            class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
            :class="tab === 'ready' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
        📦 Ready to Send ({{ $readyOrders->total() }})
    </button>
    <button @click="tab = 'sent'"
            class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
            :class="tab === 'sent' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
        🚚 Sent Shipments ({{ $shipments->total() }})
    </button>
</div>

{{-- TAB: Ready to Send --}}
<div x-data="{ tab: 'ready' }">
<div x-show="tab === 'ready'" x-data="{ selectedOrders: [], selectAll: false }">

    {{-- Bulk Send Bar --}}
    <div x-show="selectedOrders.length > 0" x-cloak
         class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-3 mb-3 flex items-center justify-between">
        <p class="text-sm font-medium text-blue-700 dark:text-blue-400">
            <span x-text="selectedOrders.length"></span> orders selected
        </p>
        <form method="POST" action="{{ route('steadfast.bulk-send') }}" id="bulkSendForm">
            @csrf
            <div id="bulkOrderIds"></div>
            <button type="button" @click="submitBulk()"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                🚀 Send to Steadfast
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" @change="toggleAll($event)"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        </th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Order</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Customer</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Address</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">COD</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @forelse($readyOrders as $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-4 py-3">
                            <input type="checkbox"
                                   :value="{{ $order->id }}"
                                   @change="toggleOrder({{ $order->id }})"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('orders.show', $order) }}" class="font-semibold text-blue-600 dark:text-blue-400 text-xs hover:underline">
                                #{{ $order->order_number }}
                            </a>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $order->created_at->format('d M') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300 max-w-48">
                            <p class="truncate">{{ $order->shipping_address }}</p>
                            <p class="text-gray-400 dark:text-slate-500">{{ implode(', ', array_filter([$order->shipping_area, $order->shipping_district])) }}</p>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                            ৳{{ number_format($order->due_amount) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($isConnected)
                            <form method="POST" action="{{ route('steadfast.send-order', $order) }}">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    Send →
                                </button>
                            </form>
                            @else
                            <span class="text-xs text-gray-400">Not connected</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                            No orders ready to send
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($readyOrders as $order)
            <div class="p-4">
                <div class="flex items-start gap-3 mb-3">
                    <input type="checkbox" :value="{{ $order->id }}" @change="toggleOrder({{ $order->id }})"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded mt-0.5 flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('orders.show', $order) }}" class="font-semibold text-blue-600 text-sm">#{{ $order->order_number }}</a>
                            <span class="font-bold text-gray-900 dark:text-white">৳{{ number_format($order->due_amount) }}</span>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ $order->customer_name }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone }} · {{ $order->shipping_district }}</p>
                    </div>
                </div>
                @if($isConnected)
                <form method="POST" action="{{ route('steadfast.send-order', $order) }}">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        🚀 Send to Steadfast
                    </button>
                </form>
                @endif
            </div>
            @empty
            <div class="p-12 text-center text-sm text-gray-400">No orders ready</div>
            @endforelse
        </div>

        @if($readyOrders->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $readyOrders->links() }}</div>
        @endif
    </div>
</div>

{{-- TAB: Sent Shipments --}}
<div x-show="tab === 'sent'" x-cloak>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Order</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Customer</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Tracking</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">COD</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Track</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @forelse($shipments as $shipment)
                    @php
                        $statusColor = match($shipment->status) {
                            'delivered'   => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'in_transit'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                            'cancelled'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            'picked'      => 'bg-indigo-100 text-indigo-700',
                            default       => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('orders.show', $shipment->order) }}" class="font-semibold text-blue-600 dark:text-blue-400 text-xs hover:underline">
                                #{{ $shipment->order->order_number }}
                            </a>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $shipment->created_at->format('d M') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $shipment->order->customer_name }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $shipment->order->customer_phone }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">
                                {{ $shipment->tracking_id ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                            ৳{{ number_format($shipment->cod_amount) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($shipment->tracking_id && $isConnected)
                            <button onclick="checkStatus('{{ $shipment->tracking_id }}')"
                                    class="px-2.5 py-1.5 text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                🔍 Track
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                            No shipments yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($shipments->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $shipments->links() }}</div>
        @endif
    </div>
</div>
</div>

{{-- Status Check Modal --}}
<div id="statusModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">🔍 Track Parcel</h3>
            <button onclick="document.getElementById('statusModal').classList.add('hidden')"
                    class="p-1 text-gray-400 hover:text-gray-600 text-xl">×</button>
        </div>
        <div class="p-5 space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Search By</label>
                <select id="statusType" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="tracking">Tracking Code</option>
                    <option value="invoice">Invoice / Order Number</option>
                    <option value="consignment">Consignment ID</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Value</label>
                <input type="text" id="statusValue" placeholder="Enter tracking code..."
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
            </div>
            <button onclick="doStatusCheck()"
                    class="w-full py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Check Status
            </button>
            <div id="statusResult" class="hidden p-3 rounded-xl text-sm"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Toggle individual order selection
function toggleOrder(id) {
    // handled by Alpine
}

// Check status
function checkStatus(trackingCode) {
    document.getElementById('statusValue').value = trackingCode;
    document.getElementById('statusModal').classList.remove('hidden');
    doStatusCheck();
}

async function doStatusCheck() {
    const type  = document.getElementById('statusType').value;
    const value = document.getElementById('statusValue').value.trim();
    const result = document.getElementById('statusResult');

    if (!value) return;

    result.className = 'p-3 rounded-xl text-sm bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-slate-300';
    result.classList.remove('hidden');
    result.innerHTML = '⏳ Checking...';

    try {
        const res  = await fetch('{{ route("steadfast.check-status") }}?type=' + type + '&value=' + encodeURIComponent(value), {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();

        const statusMap = {
            'delivered': '✅ Delivered',
            'in_review': '🔍 In Review',
            'pending': '⏳ Pending',
            'cancelled': '❌ Cancelled',
            'hold': '⏸️ On Hold',
            'delivered_approval_pending': '✅ Delivered (Pending Approval)',
            'partial_delivered': '📦 Partially Delivered',
        };

        if (data.delivery_status) {
            const label = statusMap[data.delivery_status] || data.delivery_status;
            result.className = 'p-3 rounded-xl text-sm bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200';
            result.innerHTML = `<strong>Status:</strong> ${label}`;
        } else if (data.error) {
            result.className = 'p-3 rounded-xl text-sm bg-red-50 dark:bg-red-900/20 text-red-600 border border-red-200';
            result.innerHTML = `❌ ${data.error}`;
        } else {
            result.className = 'p-3 rounded-xl text-sm bg-red-50 text-red-600 border border-red-200';
            result.innerHTML = '❌ Not found or API error';
        }
    } catch(e) {
        result.innerHTML = '❌ Network error';
    }
}

// Refresh balance
async function refreshBalance() {
    try {
        const res  = await fetch('{{ route("steadfast.get-balance") }}');
        const data = await res.json();
        if (data.current_balance !== undefined) {
            document.querySelector('[x-text]').textContent = parseFloat(data.current_balance).toLocaleString();
        }
    } catch(e) {}
}

// Bulk send
function submitBulk() {
    const checkboxes = document.querySelectorAll('input[type=checkbox][value]:checked');
    const form = document.getElementById('bulkSendForm');
    const container = document.getElementById('bulkOrderIds');
    container.innerHTML = '';

    checkboxes.forEach(cb => {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'order_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    if (container.children.length > 0) form.submit();
}
</script>
@endpush
