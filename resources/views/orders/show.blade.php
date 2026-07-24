@extends('layouts.app')
@section('title', 'Order #' . $order->order_number)

@section('content')

<div class="max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('orders.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Order #{{ $order->order_number }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Invoice dropdown --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                        class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden sm:inline">Invoice</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 mt-1 w-44 bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-xl shadow-xl z-20 overflow-hidden">
                    <a href="{{ route('orders.invoice', $order) }}" target="_blank"
                       class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        View Invoice
                    </a>
                    <a href="{{ route('orders.invoice.pdf', $order) }}"
                       class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF
                    </a>
                    <a href="{{ route('orders.receipt', $order) }}" target="_blank"
                       class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Thermal Receipt
                    </a>
                </div>
            </div>
            <a href="{{ route('orders.edit', $order) }}"
               class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
        </div>
    </div>

    {{-- Status Banner --}}
    @php
    $statusColors = [
        'pending'    => ['bg'=>'bg-yellow-50 dark:bg-yellow-900/20','border'=>'border-yellow-200 dark:border-yellow-700','text'=>'text-yellow-700 dark:text-yellow-400','badge'=>'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
        'confirmed'  => ['bg'=>'bg-primary-50 dark:bg-primary-900/20','border'=>'border-primary-200 dark:border-primary-700','text'=>'text-primary-700 dark:text-primary-400','badge'=>'bg-primary-100 text-primary-700'],
        'processing' => ['bg'=>'bg-primary-50 dark:bg-primary-900/20','border'=>'border-primary-200 dark:border-primary-700','text'=>'text-primary-700 dark:text-primary-400','badge'=>'bg-primary-100 text-primary-700'],
        'shipped'    => ['bg'=>'bg-purple-50 dark:bg-purple-900/20','border'=>'border-purple-200 dark:border-purple-700','text'=>'text-purple-700 dark:text-purple-400','badge'=>'bg-purple-100 text-purple-700'],
        'delivered'  => ['bg'=>'bg-green-50 dark:bg-green-900/20','border'=>'border-green-200 dark:border-green-700','text'=>'text-green-700 dark:text-green-400','badge'=>'bg-green-100 text-green-700'],
        'completed'  => ['bg'=>'bg-green-50 dark:bg-green-900/20','border'=>'border-green-200 dark:border-green-700','text'=>'text-green-700 dark:text-green-400','badge'=>'bg-green-100 text-green-700'],
        'cancelled'  => ['bg'=>'bg-red-50 dark:bg-red-900/20','border'=>'border-red-200 dark:border-red-700','text'=>'text-red-700 dark:text-red-400','badge'=>'bg-red-100 text-red-700'],
    ];
    $sc = $statusColors[$order->status] ?? $statusColors['pending'];
    @endphp

    <div class="{{ $sc['bg'] }} border {{ $sc['border'] }} rounded-xl p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $sc['badge'] }}">
                {{ ucfirst($order->status) }}
            </span>
            <span class="text-sm {{ $sc['text'] }}">
                @if($order->source === 'woocommerce') 📦 WooCommerce Order
                @elseif($order->source === 'pos') 🖥️ POS Sale
                @else Manual Order
                @endif
            </span>
        </div>

        {{-- Quick Status Update --}}
        <form method="POST" action="{{ route('orders.status', $order) }}" class="flex items-center gap-2">
            @csrf
            <select name="status"
                    class="text-xs px-2 py-1.5 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @foreach(['pending','confirmed','processing','shipped','delivered','completed','cancelled','returned'] as $s)
                    <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
                Update
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- LEFT: Items + Summary --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Order Items --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Order Items ({{ $order->items->count() }})
                    </h3>
                </div>

                <div class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($order->items as $item)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                            @if($item->product?->images)
                                <img src="{{ Storage::url($item->product->images[0]) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                                ৳{{ number_format($item->price) }} × {{ $item->quantity }}
                                @if($item->discount > 0)
                                    <span class="text-red-500">(-৳{{ number_format($item->discount) }})</span>
                                @endif
                            </p>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex-shrink-0">
                            ৳{{ number_format($item->total) }}
                        </span>
                    </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="px-4 py-3 bg-gray-50 dark:bg-slate-700/50 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-slate-300">
                        <span>Subtotal</span>
                        <span>৳{{ number_format($order->subtotal) }}</span>
                    </div>
                    @if($order->discount > 0)
                    <div class="flex justify-between text-sm text-red-600 dark:text-red-400">
                        <span>Discount</span>
                        <span>-৳{{ number_format($order->discount) }}</span>
                    </div>
                    @endif
                    @if($order->shipping_charge > 0)
                    <div class="flex justify-between text-sm text-gray-600 dark:text-slate-300">
                        <span>Shipping</span>
                        <span>৳{{ number_format($order->shipping_charge) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-base text-gray-900 dark:text-white border-t border-gray-200 dark:border-slate-600 pt-2">
                        <span>Total</span>
                        <span>৳{{ number_format($order->total) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                        <span>Paid</span>
                        <span>৳{{ number_format($order->paid_amount) }}</span>
                    </div>
                    @if($order->due_amount > 0)
                    <div class="flex justify-between text-sm font-semibold text-red-600 dark:text-red-400">
                        <span>Due</span>
                        <span>৳{{ number_format($order->due_amount) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Shipment Info --}}
            @if($order->shipments->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Shipment Details</h3>
                </div>
                @foreach($order->shipments as $shipment)
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $shipment->courier_name }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">
                            {{ ucfirst($shipment->status) }}
                        </span>
                    </div>
                    @if($shipment->tracking_id)
                        <p class="text-xs text-gray-500 dark:text-slate-400">Tracking: <span class="font-mono font-medium">{{ $shipment->tracking_id }}</span></p>
                    @endif
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Charge: ৳{{ number_format($shipment->charge) }}</p>
                </div>
                @endforeach
            </div>
            @elseif($isConnected ?? false)
            {{-- Send to Steadfast (any order that isn't shipped yet) --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Courier</h3>
                    <span class="text-xs text-gray-400 dark:text-slate-500">COD: ৳{{ number_format($order->due_amount) }}</span>
                </div>
                <form method="POST" action="{{ route('steadfast.send-order', $order) }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl active:scale-95 transition-all">
                        🚚 Send to Steadfast
                    </button>
                </form>
            </div>
            @endif

            {{-- Partial delivery: return the unaccepted items to stock --}}
            @if($order->payment_status === 'partial')
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-200 dark:border-orange-900/40 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Partial delivery — return to stock</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                    Courier collected ৳{{ number_format($order->paid_amount) }} of ৳{{ number_format($order->total) }}.
                    Enter how many of each item came back so stock is corrected.
                </p>
                <form method="POST" action="{{ route('orders.partial-return', $order) }}" class="space-y-2">
                    @csrf
                    @foreach($order->items as $item)
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-700 dark:text-slate-200 truncate">
                            {{ $item->product_name }}
                            <span class="text-gray-400">({{ $item->quantity }} sold)</span>
                        </span>
                        <input type="number" name="returns[{{ $item->id }}]" min="0" max="{{ $item->quantity }}" placeholder="0"
                               class="w-16 px-2 py-1 text-xs text-center border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                    </div>
                    @endforeach
                    <button type="submit"
                            class="w-full mt-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold rounded-xl active:scale-95 transition-all">
                        ↩️ Return to stock
                    </button>
                </form>
            </div>
            @endif

        </div>

        {{-- RIGHT: Customer + Payment --}}
        <div class="space-y-4">

            {{-- Customer --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Customer</h3>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ strtoupper(substr($order->customer_name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $order->customer_name }}</p>
                        @if($order->customer_phone)
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $order->customer_phone }}</p>
                        @endif
                    </div>
                </div>
                @if($order->shipping_address)
                    <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2.5">
                        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Shipping Address</p>
                        <p class="text-xs text-gray-700 dark:text-slate-300">{{ $order->shipping_address }}</p>
                        @if($order->shipping_area || $order->shipping_district)
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                                {{ implode(', ', array_filter([$order->shipping_area, $order->shipping_district])) }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Payment Info --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Payment</h3>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-slate-400">Method</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($order->payment_method) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-slate-400">Status</span>
                        <span class="font-medium
                            {{ $order->payment_status === 'paid' ? 'text-green-600 dark:text-green-400' : '' }}
                            {{ $order->payment_status === 'unpaid' ? 'text-red-600 dark:text-red-400' : '' }}
                            {{ $order->payment_status === 'partial' ? 'text-yellow-600 dark:text-yellow-400' : '' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-slate-400">Paid</span>
                        <span class="font-medium text-gray-900 dark:text-white">৳{{ number_format($order->paid_amount) }}</span>
                    </div>
                    @if($order->due_amount > 0)
                    <div class="flex justify-between text-xs font-semibold border-t border-gray-100 dark:border-slate-700 pt-2">
                        <span class="text-red-600 dark:text-red-400">Due</span>
                        <span class="text-red-600 dark:text-red-400">৳{{ number_format($order->due_amount) }}</span>
                    </div>

                    {{-- Collect payment --}}
                    <form method="POST" action="{{ route('orders.status', $order) }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="action" value="collect_payment">
                        <div class="flex gap-2">
                            <input type="number" name="collect_amount" placeholder="Amount" min="0"
                                   value="{{ $order->due_amount }}"
                                   class="flex-1 px-2.5 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                            <input type="date" name="collect_date" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                                   class="w-36 px-2.5 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                            <button type="submit" class="px-3 py-2 text-xs font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                Collect
                            </button>
                        </div>
                    </form>
                    @endif

                    {{-- bKash / Nagad Payment Buttons --}}
                    @if($order->due_amount > 0)
                    @php
                        $bkashGw = \App\Models\PaymentGatewaySetting::forBusiness($order->business_id, 'bkash');
                        $nagadGw = \App\Models\PaymentGatewaySetting::forBusiness($order->business_id, 'nagad');
                    @endphp
                    @if($bkashGw || $nagadGw)
                    <div class="border-t border-gray-100 dark:border-slate-700 pt-3 mt-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 mb-2">Online Payment</p>
                        <div class="space-y-2">
                        @if($bkashGw)
                        <form method="POST" action="{{ route('payment.bkash.create', $order) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold text-white transition-colors"
                                    style="background: #E2136E;">
                                <span class="text-base font-black">b</span>
                                Pay with bKash
                                <span class="text-xs font-normal opacity-80">৳{{ number_format($order->due_amount) }}</span>
                            </button>
                        </form>
                        @endif
                        @if($nagadGw)
                        <form method="POST" action="{{ route('payment.nagad.create', $order) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold text-white transition-colors"
                                    style="background: #F05825;">
                                <span class="text-base font-black">N</span>
                                Pay with Nagad
                                <span class="text-xs font-normal opacity-80">৳{{ number_format($order->due_amount) }}</span>
                            </button>
                        </form>
                        @endif
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            @if($order->notes)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Notes</h3>
                <p class="text-xs text-gray-600 dark:text-slate-300 leading-relaxed">{{ $order->notes }}</p>
            </div>
            @endif

            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('orders.invoice', $order) }}" target="_blank"
                       class="flex items-center gap-2 w-full px-3 py-2.5 text-xs font-medium text-gray-700 dark:text-slate-200 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        View & Print Invoice
                    </a>
                    <a href="{{ route('courier.create') }}?order_id={{ $order->id }}"
                       class="flex items-center gap-2 w-full px-3 py-2.5 text-xs font-medium text-gray-700 dark:text-slate-200 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/></svg>
                        Create Shipment
                    </a>
                    @if(in_array($order->status, ['delivered', 'completed']))
                    <button onclick="document.getElementById('returnModal_{{ $order->id }}').classList.remove('hidden')"
                            class="flex items-center gap-2 w-full px-3 py-2.5 text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3"/></svg>
                        Request Return
                    </button>
                    <div id="returnModal_{{ $order->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                        <div class="bg-white dark:bg-slate-800 rounded-xl w-full max-w-sm p-5 shadow-2xl">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Request Return for #{{ $order->order_number }}</h3>
                            <form method="POST" action="{{ route('returns.request', $order) }}">
                                @csrf
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Reason <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="3" required placeholder="Describe the reason for return..."
                                          class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white mb-3 focus:ring-2 focus:ring-orange-500 resize-none"></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="flex-1 py-2 bg-orange-600 text-white text-xs rounded-lg hover:bg-orange-700">Submit Request</button>
                                    <button type="button" onclick="document.getElementById('returnModal_{{ $order->id }}').classList.add('hidden')"
                                            class="flex-1 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs rounded-lg hover:bg-gray-200">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if(!in_array($order->status, ['cancelled','returned','return_requested','return_approved','return_received','refunded']))
                    <form method="POST" action="{{ route('orders.status', $order) }}" onsubmit="return confirm('Cancel this order?')">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit"
                                class="flex items-center gap-2 w-full px-3 py-2.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Cancel Order
                        </button>
                    </form>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
