@extends('layouts.app')
@section('title', 'Returns & RMA')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Returns & Refunds</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">RMA workflow — request, approve, receive, refund</p>
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400">{{ session('error') }}</div>
@endif

{{-- Status Pipeline Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <a href="{{ route('returns.index', ['status' => 'return_requested']) }}"
       class="bg-white dark:bg-slate-800 rounded-xl border {{ $status === 'return_requested' ? 'border-yellow-400' : 'border-gray-100 dark:border-slate-700' }} p-4 hover:shadow-sm transition-shadow">
        <p class="text-xs text-yellow-600 dark:text-yellow-400 font-medium">Requested</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $counts['requested'] }}</p>
    </a>
    <a href="{{ route('returns.index', ['status' => 'return_approved']) }}"
       class="bg-white dark:bg-slate-800 rounded-xl border {{ $status === 'return_approved' ? 'border-blue-400' : 'border-gray-100 dark:border-slate-700' }} p-4 hover:shadow-sm transition-shadow">
        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Approved</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $counts['approved'] }}</p>
    </a>
    <a href="{{ route('returns.index', ['status' => 'return_received']) }}"
       class="bg-white dark:bg-slate-800 rounded-xl border {{ $status === 'return_received' ? 'border-indigo-400' : 'border-gray-100 dark:border-slate-700' }} p-4 hover:shadow-sm transition-shadow">
        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Received</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $counts['received'] }}</p>
    </a>
    <a href="{{ route('returns.index', ['status' => 'refunded']) }}"
       class="bg-white dark:bg-slate-800 rounded-xl border {{ $status === 'refunded' ? 'border-green-400' : 'border-gray-100 dark:border-slate-700' }} p-4 hover:shadow-sm transition-shadow">
        <p class="text-xs text-green-600 dark:text-green-400 font-medium">Refunded</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $counts['refunded'] }}</p>
    </a>
</div>

{{-- Returns Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    @if($returns->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Order</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Customer</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Amount</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Reason</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Status</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($returns as $order)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors" x-data="{ open: false }">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white text-xs">#{{ $order->order_number }}</p>
                        <p class="text-xs text-gray-400">{{ $order->created_at->format('d M Y') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-xs text-gray-700 dark:text-slate-200">{{ $order->customer_name }}</p>
                        <p class="text-xs text-gray-400">{{ $order->customer_phone }}</p>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <p class="font-medium text-gray-900 dark:text-white text-xs">৳{{ number_format($order->total, 2) }}</p>
                        @if($order->paid_amount > 0)
                        <p class="text-xs text-green-600">Paid: ৳{{ number_format($order->paid_amount, 2) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 max-w-xs">
                        <p class="text-xs text-gray-600 dark:text-slate-300 truncate">{{ $order->return_reason ?? '—' }}</p>
                        @if($order->return_condition)
                        <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium mt-0.5
                            {{ $order->return_condition === 'good' ? 'bg-green-100 text-green-700' : ($order->return_condition === 'damaged' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst($order->return_condition) }}
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $statusConfig = [
                                'return_requested' => ['label'=>'Requested', 'class'=>'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
                                'return_approved'  => ['label'=>'Approved',  'class'=>'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
                                'return_received'  => ['label'=>'Received',  'class'=>'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400'],
                                'refunded'         => ['label'=>'Refunded',  'class'=>'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                                'returned'         => ['label'=>'Returned',  'class'=>'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300'],
                            ];
                            $sc = $statusConfig[$order->status] ?? ['label'=>ucfirst($order->status), 'class'=>'bg-gray-100 text-gray-600'];
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sc['class'] }}">{{ $sc['label'] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1.5 flex-wrap">

                            {{-- APPROVE button (only for requested) --}}
                            @if($order->status === 'return_requested')
                            <button @click="open = 'approve_{{ $order->id }}'"
                                    class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                Approve
                            </button>
                            <button @click="open = 'reject_{{ $order->id }}'"
                                    class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded hover:bg-red-200 transition-colors">
                                Reject
                            </button>
                            @endif

                            {{-- RECEIVED button (only for approved) --}}
                            @if($order->status === 'return_approved')
                            <button @click="open = 'receive_{{ $order->id }}'"
                                    class="px-2 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 transition-colors">
                                Mark Received
                            </button>
                            @endif

                            {{-- REFUND button (only for received) --}}
                            @if($order->status === 'return_received')
                            <button @click="open = 'refund_{{ $order->id }}'"
                                    class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition-colors">
                                Process Refund
                            </button>
                            @endif

                            <a href="{{ route('orders.show', $order) }}"
                               class="px-2 py-1 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs rounded hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                                View
                            </a>
                        </div>

                        {{-- APPROVE MODAL --}}
                        <div x-show="open === 'approve_{{ $order->id }}'" x-cloak
                             class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                            <div class="bg-white dark:bg-slate-800 rounded-xl w-full max-w-sm p-5 shadow-2xl" @click.stop>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Approve Return Request</h3>
                                <p class="text-xs text-gray-500 mb-3">Order #{{ $order->order_number }} — <strong>{{ $order->customer_name }}</strong></p>
                                <form method="POST" action="{{ route('returns.approve', $order) }}">
                                    @csrf
                                    <textarea name="notes" rows="2" placeholder="Internal notes (optional)"
                                              class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white mb-3 focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">Approve</button>
                                        <button type="button" @click="open = false" class="flex-1 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs rounded-lg hover:bg-gray-200">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- REJECT MODAL --}}
                        <div x-show="open === 'reject_{{ $order->id }}'" x-cloak
                             class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                            <div class="bg-white dark:bg-slate-800 rounded-xl w-full max-w-sm p-5 shadow-2xl" @click.stop>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Reject Return</h3>
                                <form method="POST" action="{{ route('returns.reject', $order) }}">
                                    @csrf
                                    <textarea name="reason" rows="2" placeholder="Reason for rejection" required
                                              class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white mb-3 focus:ring-2 focus:ring-red-500 resize-none"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 py-2 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700">Reject</button>
                                        <button type="button" @click="open = false" class="flex-1 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs rounded-lg hover:bg-gray-200">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- RECEIVE MODAL --}}
                        <div x-show="open === 'receive_{{ $order->id }}'" x-cloak
                             class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                            <div class="bg-white dark:bg-slate-800 rounded-xl w-full max-w-sm p-5 shadow-2xl" @click.stop>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Mark Goods Received</h3>
                                <form method="POST" action="{{ route('returns.receive', $order) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Condition of Goods <span class="text-red-500">*</span></label>
                                        <select name="condition" required class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                                            <option value="good">Good — fully restockable</option>
                                            <option value="partial">Partial — some items ok</option>
                                            <option value="damaged">Damaged — cannot restock</option>
                                        </select>
                                    </div>
                                    <textarea name="notes" rows="2" placeholder="Inspection notes (optional)"
                                              class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white mb-3 focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 py-2 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700">Confirm Receipt</button>
                                        <button type="button" @click="open = false" class="flex-1 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs rounded-lg hover:bg-gray-200">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- REFUND MODAL --}}
                        <div x-show="open === 'refund_{{ $order->id }}'" x-cloak
                             class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                            <div class="bg-white dark:bg-slate-800 rounded-xl w-full max-w-sm p-5 shadow-2xl" @click.stop>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Process Refund</h3>
                                <p class="text-xs text-gray-500 mb-3">Max refundable: <strong class="text-gray-800 dark:text-white">৳{{ number_format($order->paid_amount, 2) }}</strong></p>
                                <form method="POST" action="{{ route('returns.refund', $order) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Refund Amount <span class="text-red-500">*</span></label>
                                        <input type="number" name="refund_amount" step="0.01" min="0.01" max="{{ $order->paid_amount }}"
                                               value="{{ $order->paid_amount }}" required
                                               class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Refund Method <span class="text-red-500">*</span></label>
                                        <select name="refund_method" required class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:ring-2 focus:ring-green-500">
                                            <option value="cash">Cash</option>
                                            <option value="bkash">bKash</option>
                                            <option value="nagad">Nagad</option>
                                            <option value="bank">Bank Transfer</option>
                                        </select>
                                    </div>
                                    <textarea name="notes" rows="2" placeholder="Refund notes (optional)"
                                              class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white mb-3 focus:ring-2 focus:ring-green-500 resize-none"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 py-2 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700">Confirm Refund</button>
                                        <button type="button" @click="open = false" class="flex-1 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs rounded-lg hover:bg-gray-200">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-50 dark:border-slate-700">
        {{ $returns->links() }}
    </div>
    @else
    <div class="flex flex-col items-center justify-center py-16">
        <svg class="w-12 h-12 text-gray-200 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
        <p class="text-sm text-gray-400 dark:text-slate-500">No return requests</p>
        @if($status)
        <a href="{{ route('returns.index') }}" class="mt-2 text-xs text-blue-600 hover:underline dark:text-blue-400">View all statuses</a>
        @endif
    </div>
    @endif
</div>

{{-- Pipeline Steps Info --}}
<div class="mt-5 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
    <h3 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-3">RMA Workflow</h3>
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
            <span class="text-yellow-700 dark:text-yellow-400">1. Customer Requests Return</span>
        </div>
        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            <span class="text-blue-700 dark:text-blue-400">2. Admin Approves</span>
        </div>
        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
            <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
            <span class="text-indigo-700 dark:text-indigo-400">3. Goods Inspected & Received</span>
        </div>
        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <span class="w-2 h-2 rounded-full bg-green-400"></span>
            <span class="text-green-700 dark:text-green-400">4. Refund Processed</span>
        </div>
    </div>
</div>

@endsection
