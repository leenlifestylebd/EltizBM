@extends('layouts.app')
@php $isWholesale = $wholesale ?? (isset($order) && $order->source === 'wholesale'); @endphp
@section('title', isset($order) ? 'Edit Order' : ($isWholesale ? 'New Wholesale Order' : 'New Order'))

@section('content')

<div class="max-w-5xl mx-auto" x-data="orderForm()">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route($isWholesale ? 'wholesale.index' : 'orders.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
            @if(isset($order))
                Edit Order #{{ $order->order_number }}
            @else
                Create New {{ $isWholesale ? 'Wholesale ' : '' }}Order
            @endif
        </h2>
        @if($isWholesale)
        <span class="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">Wholesale</span>
        @endif
    </div>

    <form method="POST"
          action="{{ isset($order) ? route('orders.update', $order) : route('orders.store') }}"
          id="orderForm">
        @csrf
        @if(isset($order)) @method('PUT') @endif
        @if($isWholesale && !isset($order)) <input type="hidden" name="source" value="wholesale"> @endif

        @if($errors->any())
            <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- LEFT: Products + Customer --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Customer Info --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Customer Information</h3>

                    <div class="relative mb-3">
                        <input type="text" id="customerSearch"
                               placeholder="Search customer by name or phone..."
                               autocomplete="off"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
                               @input="searchCustomer($event.target.value)">
                        <div x-show="customerResults.length > 0" x-cloak
                             class="absolute z-20 top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-lg overflow-hidden">
                            <template x-for="c in customerResults" :key="c.id">
                                <div @click="selectCustomer(c)"
                                     class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer">
                                    <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400" x-text="c.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="c.name"></p>
                                        <p class="text-xs text-gray-400 dark:text-slate-500" x-text="c.phone"></p>
                                    </div>
                                    <span x-show="c.due_amount > 0" class="ml-auto text-xs text-red-500" x-text="'Due: ৳' + c.due_amount"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Order Date</label>
                        <input type="date" name="order_date"
                               value="{{ old('order_date', isset($order) ? optional($order->created_at)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                               max="{{ now()->format('Y-m-d') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">আগের কোনো দিনের বিক্রি হলে সেই তারিখ দিন — না দিলে আজকের তারিখ ধরা হবে।</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_name" id="customerName"
                                   value="{{ old('customer_name', isset($order) ? $order->customer_name : '') }}" required
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   placeholder="Customer name">
                            <input type="hidden" name="customer_id" id="customerId" value="{{ old('customer_id', isset($order) ? $order->customer_id : '') }}">
                        </div>
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                                Phone
                                <span x-show="fraudLoading" class="ml-1 text-primary-500 animate-pulse">checking...</span>
                            </label>
                            <input type="text" name="customer_phone" id="customerPhone"
                                   value="{{ old('customer_phone', isset($order) ? $order->customer_phone : '') }}"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   placeholder="01XXXXXXXXX"
                                   @input.debounce.700ms="checkFraud($event.target.value)">

                            {{-- Fraud Result Card --}}
                            <div x-show="fraudResult" x-cloak x-transition class="mt-2 rounded-xl border p-3 text-xs"
                                 :class="{
                                     'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800': fraudResult?.risk === 'low',
                                     'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800': fraudResult?.risk === 'medium',
                                     'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800': fraudResult?.risk === 'high',
                                     'bg-gray-50 border-gray-200 dark:bg-slate-700 dark:border-slate-600': fraudResult?.risk === 'new',
                                 }">
                                {{-- Header --}}
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold">
                                        <span x-show="fraudResult?.risk === 'low'"    class="text-green-600 dark:text-green-400">✅ Trusted Customer</span>
                                        <span x-show="fraudResult?.risk === 'medium'" class="text-yellow-600 dark:text-yellow-400">⚠️ Moderate Risk</span>
                                        <span x-show="fraudResult?.risk === 'high'"   class="text-red-600 dark:text-red-400">🚨 High Risk / Fraud Alert</span>
                                        <span x-show="fraudResult?.risk === 'new'"    class="text-gray-600 dark:text-slate-300">🆕 New Customer</span>
                                    </div>
                                    <span class="font-bold px-2 py-0.5 rounded-full"
                                          :class="{
                                              'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': fraudResult?.risk === 'low',
                                              'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400': fraudResult?.risk === 'medium',
                                              'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400': fraudResult?.risk === 'high',
                                              'bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-slate-300': fraudResult?.risk === 'new',
                                          }"
                                          x-text="fraudResult?.success_rate != null ? fraudResult.success_rate + '% success' : 'No history'">
                                    </span>
                                </div>
                                {{-- Stats --}}
                                <div x-show="fraudResult?.total > 0" class="grid grid-cols-4 gap-1.5">
                                    <div class="text-center bg-white/60 dark:bg-slate-800/60 rounded-lg p-1.5">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm" x-text="fraudResult?.total"></div>
                                        <div class="text-gray-400" style="font-size:10px">Total</div>
                                    </div>
                                    <div class="text-center bg-white/60 dark:bg-slate-800/60 rounded-lg p-1.5">
                                        <div class="font-bold text-green-600 dark:text-green-400 text-sm" x-text="fraudResult?.delivered"></div>
                                        <div class="text-gray-400" style="font-size:10px">Delivered</div>
                                    </div>
                                    <div class="text-center bg-white/60 dark:bg-slate-800/60 rounded-lg p-1.5">
                                        <div class="font-bold text-orange-500 text-sm" x-text="fraudResult?.returned"></div>
                                        <div class="text-gray-400" style="font-size:10px">Returned</div>
                                    </div>
                                    <div class="text-center bg-white/60 dark:bg-slate-800/60 rounded-lg p-1.5">
                                        <div class="font-bold text-red-500 text-sm" x-text="fraudResult?.cancelled"></div>
                                        <div class="text-gray-400" style="font-size:10px">Cancelled</div>
                                    </div>
                                </div>
                                {{-- Progress Bar --}}
                                <div x-show="fraudResult?.total > 0" class="mt-2">
                                    <div class="flex justify-between text-gray-400 mb-1" style="font-size:10px">
                                        <span>Success Rate</span>
                                        <span x-text="fraudResult?.success_rate + '%'"></span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-gray-200 dark:bg-slate-600">
                                        <div class="h-1.5 rounded-full transition-all duration-500"
                                             :class="{ 'bg-green-500': fraudResult?.risk==='low', 'bg-yellow-400': fraudResult?.risk==='medium', 'bg-red-500': fraudResult?.risk==='high' }"
                                             :style="'width:' + (fraudResult?.success_rate ?? 0) + '%'"></div>
                                    </div>
                                </div>
                                <div class="mt-2 text-right text-gray-400 dark:text-slate-500" style="font-size:10px">via Steadfast</div>
                            </div>
                            {{-- Not connected / rate limited --}}
                            <div x-show="fraudNotConnected" x-cloak class="mt-1.5 text-xs text-gray-400 dark:text-slate-500" x-text="fraudNotConnectedMsg"></div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Shipping Address</label>
                            <textarea name="shipping_address" rows="2"
                                      class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                                      placeholder="Full address...">{{ old('shipping_address', isset($order) ? $order->shipping_address : '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Area</label>
                            <input type="text" name="shipping_area"
                                   value="{{ old('shipping_area', isset($order) ? $order->shipping_area : '') }}"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   placeholder="Mirpur, Gulshan...">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">District</label>
                            <input type="text" name="shipping_district"
                                   value="{{ old('shipping_district', isset($order) ? $order->shipping_district : '') }}"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   placeholder="Dhaka, Chittagong...">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <label for="warehouse_id" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Stock from / পণ্য যাবে যেখান থেকে</label>
                    <select id="warehouse_id" name="warehouse_id" x-model="warehouseId"
                            @disabled($locationLocked) @required(!isset($order) && $warehouses->isNotEmpty())
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                        <option value="">{{ isset($order) ? 'Legacy order — location not recorded' : 'Select warehouse / outlet' }}</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500 dark:text-slate-300">
                        @if($locationLocked)
                            পুরোনো unassigned order অথবা shipment/closed order-এর stock location বদলানো যাবে না।
                        @elseif($warehouses->isEmpty())
                            Warehouse / outlet যোগ করুন Settings → Warehouses থেকে।
                        @else
                            এই অর্ডারের সব পণ্য নির্বাচিত warehouse / outlet-এর stock থেকে যাবে।
                        @endif
                    </p>
                    <p x-show="stockProblem" x-cloak role="alert" class="mt-2 text-sm text-red-600" x-text="stockProblem"></p>
                </div>

                {{-- Product Search + Cart --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Order Items</h3>
                        <button type="button" @click="bulkOpen = !bulkOpen"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                :class="bulkOpen ? 'bg-primary-600 text-white' : 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 hover:bg-primary-100'">
                            ⚡ Bulk Add
                        </button>
                    </div>

                    {{-- Bulk Add Grid (fast entry: type qty down the list, add all at once) --}}
                    <div x-show="bulkOpen" x-cloak class="mb-4 border border-primary-200 dark:border-primary-800 rounded-xl overflow-hidden">
                        <div class="p-2.5 bg-primary-50 dark:bg-slate-700/40 border-b border-primary-100 dark:border-slate-600">
                            <input type="text" x-model="bulkSearch" placeholder="Filter products by name / SKU..."
                                   class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        </div>
                        <div class="max-h-72 overflow-y-auto divide-y divide-gray-50 dark:divide-slate-700">
                            <template x-for="p in bulkFiltered" :key="p.id">
                                <div class="flex items-center gap-2 px-3 py-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 dark:text-white truncate" x-text="p.name"></p>
                                        <p class="text-xs" :class="p.stock <= 0 ? 'text-red-500' : 'text-gray-400 dark:text-slate-500'"
                                           x-text="'৳' + p.price + ' · ' + (p.sku ? p.sku + ' · ' : '') + (p.stock <= 0 ? 'Out of stock' : 'Stock: ' + p.stock)"></p>
                                    </div>
                                    <input type="number" min="0" x-model.number="p.qty" placeholder="0"
                                           class="w-16 px-2 py-1.5 text-sm text-center border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                                </div>
                            </template>
                            <div x-show="bulkFiltered.length === 0" class="px-3 py-6 text-center text-xs text-gray-400">No products match</div>
                        </div>
                        <div class="p-2.5 bg-gray-50 dark:bg-slate-700/40 border-t border-gray-100 dark:border-slate-600 flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-slate-400"><span x-text="bulkSelectedCount"></span> item(s) with qty</span>
                            <div class="flex gap-2">
                                <button type="button" @click="bulkOpen = false" class="px-3 py-1.5 text-xs bg-white dark:bg-slate-600 border border-gray-200 dark:border-slate-500 text-gray-600 dark:text-slate-200 rounded-lg">Close</button>
                                <button type="button" @click="addBulk()" :disabled="bulkSelectedCount === 0"
                                        class="px-3 py-1.5 text-xs font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                    Add <span x-text="bulkSelectedCount"></span> to order
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="relative mb-4">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input type="text" id="productSearch"
                               placeholder="Search product by name, SKU or barcode..."
                               autocomplete="off"
                               class="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
                               @input="searchProduct($event.target.value)">

                        <div x-show="productResults.length > 0" x-cloak
                             class="absolute z-20 top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-lg overflow-hidden max-h-64 overflow-y-auto">
                            <template x-for="p in productResults" :key="p.id">
                                <div @click="addToCart(p)"
                                     class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer border-b border-gray-50 dark:border-slate-700 last:border-0">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-slate-700 flex-shrink-0 overflow-hidden">
                                        <img x-show="p.image" :src="p.image" class="w-full h-full object-cover">
                                        <div x-show="!p.image" class="w-full h-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="p.name"></p>
                                        <p class="text-xs" :class="p.stock <= 0 ? 'text-red-500' : 'text-gray-400 dark:text-slate-500'"
                                           x-text="'SKU: ' + (p.sku || 'N/A') + ' · ' + (p.stock <= 0 ? 'Out of stock' : 'Stock: ' + p.stock)"></p>
                                    </div>
                                    <template x-if="cart.some(i => i.product_id === p.id)">
                                        <span class="text-xs font-medium text-green-600 dark:text-green-400 flex-shrink-0"
                                              x-text="'✓ ' + cart.find(i => i.product_id === p.id).qty"></span>
                                    </template>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white flex-shrink-0" x-text="'৳' + p.price"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Cart Items --}}
                    <div class="space-y-2 mb-4" x-show="cart.length > 0">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                                <div class="w-full sm:w-auto sm:flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="item.name"></p>
                                    <div class="flex items-center gap-1 mt-1">
                                        <span class="text-xs text-gray-400 dark:text-slate-500">৳</span>
                                        <input type="number" min="0" step="0.01" x-model.number="item.price" @input="calcTotals()"
                                               :name="'items[' + index + '][price]'"
                                               class="w-20 px-1.5 py-0.5 text-xs border border-gray-200 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                                        <span class="text-xs text-gray-400 dark:text-slate-500" x-text="'/ ' + item.unit"></span>
                                    </div>
                                    <p class="text-xs" :class="isShort(item) ? 'text-red-600' : 'text-gray-500 dark:text-slate-300'" x-text="'Available for this order: ' + stockLabel(item.product_id)"></p>
                                    <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id">
                                    <input type="hidden" :name="'items[' + index + '][product_name]'" :value="item.name">
                                    <input type="hidden" :name="'items[' + index + '][cost_price]'" :value="item.cost_price || 0">
                                    <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.qty">
                                    <input type="hidden" :name="'items[' + index + '][discount]'" :value="item.discount || 0">
                                    <input type="hidden" :name="'items[' + index + '][total]'" :value="(item.price * item.qty) - (item.discount || 0)">
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button type="button" @click="decreaseQty(index)"
                                            class="w-7 h-7 bg-white dark:bg-slate-600 border border-gray-200 dark:border-slate-500 rounded-lg flex items-center justify-center text-gray-600 dark:text-slate-200 hover:bg-gray-100 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    </button>
                                    <span class="w-8 text-center text-sm font-semibold text-gray-900 dark:text-white" x-text="item.qty"></span>
                                    <button type="button" @click="increaseQty(index)"
                                            class="w-7 h-7 bg-white dark:bg-slate-600 border border-gray-200 dark:border-slate-500 rounded-lg flex items-center justify-center text-gray-600 dark:text-slate-200 hover:bg-gray-100 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>

                                <span class="w-20 text-right text-sm font-semibold text-gray-900 dark:text-white flex-shrink-0"
                                      x-text="'৳' + (item.price * item.qty).toLocaleString()"></span>

                                <button type="button" @click="removeItem(index)"
                                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div x-show="cart.length === 0" class="text-center py-8 text-gray-400 dark:text-slate-500">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-200 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-sm">Search and add products above</p>
                    </div>

                    <div class="mt-3">
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Order Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                                  placeholder="Internal notes...">{{ old('notes', isset($order) ? $order->notes : '') }}</textarea>
                    </div>
                </div>

            </div>

            {{-- RIGHT: Summary + Payment --}}
            <div class="space-y-4">

                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Order Summary</h3>
                    <div class="space-y-2.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">Subtotal</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="'৳' + subtotal.toLocaleString()"></span>
                            <input type="hidden" name="subtotal" :value="subtotal">
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">Discount</span>
                            <div class="relative w-28">
                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">৳</span>
                                <input type="number" name="discount" x-model.number="discount"
                                       @input="calcTotals()" min="0" step="0.01"
                                       class="w-full pl-5 pr-2 py-1 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 text-right">
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">Shipping</span>
                            <div class="relative w-28">
                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">৳</span>
                                <input type="number" name="shipping_charge" x-model.number="shippingCharge"
                                       @input="calcTotals()" min="0" step="0.01"
                                       class="w-full pl-5 pr-2 py-1 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 text-right">
                            </div>
                        </div>
                        <div class="border-t border-gray-100 dark:border-slate-700 pt-2.5 flex justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white text-sm">Total</span>
                            <span class="font-bold text-lg text-gray-900 dark:text-white" x-text="'৳' + total.toLocaleString()"></span>
                            <input type="hidden" name="total" :value="total">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Payment</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Payment Method</label>
                            <select name="payment_method"
                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                @php $currentMethod = old('payment_method', isset($order) ? $order->payment_method : 'cash'); @endphp
                                @foreach(['cash','bkash','nagad','rocket','card','bank','cod','other'] as $method)
                                    <option value="{{ $method }}" {{ $currentMethod === $method ? 'selected' : '' }}>
                                        {{ ucfirst($method) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Paid Amount</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-slate-400 text-sm">৳</span>
                                <input type="number" name="paid_amount" x-model.number="paidAmount"
                                       @input="calcDue()" min="0" step="0.01"
                                       class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>
                        </div>
                        <div class="flex justify-between items-center p-2.5 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                            <span class="text-xs text-gray-500 dark:text-slate-400">Due Amount</span>
                            <span class="text-sm font-semibold"
                                  :class="dueAmount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                  x-text="'৳' + Math.max(0, dueAmount).toLocaleString()"></span>
                            <input type="hidden" name="due_amount" :value="Math.max(0, dueAmount)">
                        </div>
                        <button type="button" @click="payFull()"
                                class="w-full text-xs text-primary-600 dark:text-primary-400 py-1.5 border border-primary-200 dark:border-primary-700 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                            Pay Full Amount
                        </button>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Order Status</h3>
                    @php $currentStatus = old('status', isset($order) ? $order->status : 'pending'); @endphp
                    <select name="status"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @foreach(['pending','confirmed','processing','shipped','delivered','completed','cancelled'] as $s)
                            <option value="{{ $s }}" {{ $currentStatus === $s ? 'selected' : '' }}>
                                {{ ucfirst($s) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" :disabled="!!stockProblem"
                            class="w-full mt-3 bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 rounded-lg text-sm transition-colors">
                        {{ isset($order) ? 'Update Order' : 'Place Order' }}
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
function orderForm() {
    return {
        cart: {{ \Illuminate\Support\Js::from(old('items') !== null ? collect(old('items'))->map(fn ($item) => [
            'product_id' => (int) ($item['product_id'] ?? 0),
            'name' => $item['product_name'] ?? '',
            'price' => (float) ($item['price'] ?? 0),
            'cost_price' => (float) ($item['cost_price'] ?? 0),
            'qty' => (int) ($item['quantity'] ?? 1),
            'discount' => (float) ($item['discount'] ?? 0),
            'unit' => 'pcs',
        ])->values() : ($cartItems ?? [])) }},
        warehouseId: {{ \Illuminate\Support\Js::from((string) old('warehouse_id', $order->warehouse_id ?? '')) }},
        locationStock: {{ \Illuminate\Support\Js::from($locationStock) }},
        stockProducts: {{ \Illuminate\Support\Js::from($stockProducts) }},
        heldStock: {{ \Illuminate\Support\Js::from($heldStock) }},
        originalWarehouseId: {{ \Illuminate\Support\Js::from((string) ($originalWarehouseId ?? '')) }},
        hasLocations: @js($warehouses->isNotEmpty()),
        isNewOrder: @js(!isset($order)),
        holdsStock: @js(!isset($order) || $order->holdsStock()),

        available(productId) {
            const product = this.stockProducts[productId];
            if (!product || !product.track_stock) return null;
            const held = Number(this.heldStock[productId] || 0);
            if (!this.warehouseId) return Number(product.stock) + held;
            const local = Number(this.locationStock[this.warehouseId]?.[productId] || 0);
            const sameSource = this.warehouseId === this.originalWarehouseId;
            return Math.min(local + (sameSource ? held : 0), Number(product.stock) + held);
        },
        stockLabel(productId) {
            if (!this.warehouseId && this.hasLocations && this.isNewOrder) return 'Select location';
            const available = this.available(productId);
            return available === null ? 'Not tracked' : available + ' pcs';
        },
        isShort(item) {
            const available = this.available(item.product_id);
            const requested = this.cart.filter(i => i.product_id === item.product_id).reduce((sum, i) => sum + i.qty, 0);
            return this.holdsStock && available !== null && requested > available;
        },
        get stockProblem() {
            if (this.isNewOrder && this.hasLocations && !this.warehouseId) return 'Warehouse / outlet নির্বাচন করুন।';
            const item = this.cart.find(i => this.isShort(i));
            return item ? item.name + ': নির্বাচিত জায়গায় পর্যাপ্ত stock নেই। Quantity কমান অথবা অন্য location বেছে নিন।' : '';
        },
        productResults: [],
        customerResults: [],

        // --- Bulk add grid (fast wholesale entry) ---
        bulkOpen: false,
        bulkSearch: '',
        bulkProducts: [
            @foreach($products as $p)
            { id: {{ $p->id }}, name: @js($p->name), sku: @js($p->sku ?? ''), price: {{ (float) $p->current_price }}, cost_price: {{ (float) ($p->cost_price ?? 0) }}, stock: {{ (float) $p->stock }}, unit: @js($p->unit ?? 'pcs'), qty: null },
            @endforeach
        ],
        discount: {{ (float) old('discount', isset($order) ? $order->discount : 0) }},
        shippingCharge: {{ (float) old('shipping_charge', isset($order) ? $order->shipping_charge : 0) }},
        paidAmount: {{ (float) old('paid_amount', isset($order) ? $order->paid_amount : 0) }},
        subtotal: 0,
        total: 0,
        dueAmount: 0,
        searchTimeout: null,

        // --- Fraud Checker ---
        fraudLoading: false,
        fraudResult: null,
        fraudNotConnected: false,
        fraudNotConnectedMsg: '⚠️ Steadfast not connected — fraud check unavailable',

        checkFraud(phone) {
            const cleaned = phone.replace(/\D/g, '');
            if (cleaned.length < 10) {
                this.fraudResult = null;
                this.fraudNotConnected = false;
                return;
            }
            this.fraudLoading = true;
            this.fraudResult = null;
            this.fraudNotConnected = false;

            fetch(`{{ route('steadfast.check-phone') }}?phone=${cleaned}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                this.fraudLoading = false;
                if (data.error && data.error.includes('not connected')) {
                    this.fraudNotConnected = true;
                    return;
                }
                if (data.rate_limited) {
                    this.fraudNotConnected = true;
                    this.fraudNotConnectedMsg = '⏳ Daily fraud check limit reached (' + data.limit + '/day). Try tomorrow.';
                    return;
                }
                this.fraudResult = data;
            })
            .catch(() => { this.fraudLoading = false; });
        },

        init() { this.calcTotals(); },

        searchProduct(q) {
            clearTimeout(this.searchTimeout);
            if (q.length < 2) { this.productResults = []; return; }
            this.searchTimeout = setTimeout(async () => {
                const res = await fetch(`/api/products/search?q=${encodeURIComponent(q)}`);
                this.productResults = await res.json();
            }, 300);
        },

        addToCart(product) {
            const existing = this.cart.find(i => i.product_id === product.id);
            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({
                    product_id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    cost_price: parseFloat(product.cost_price || 0),
                    qty: 1,
                    unit: product.unit || 'pcs',
                    discount: 0,
                });
            }
            // Keep the search results open so multiple variants (e.g. same size,
            // different colour) can be added without re-typing. Clear the box to close.
            this.calcTotals();
        },

        // --- Bulk add grid ---
        get bulkFiltered() {
            const q = this.bulkSearch.trim().toLowerCase();
            if (!q) return this.bulkProducts;
            return this.bulkProducts.filter(p =>
                p.name.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q));
        },
        get bulkSelectedCount() {
            return this.bulkProducts.filter(p => (parseInt(p.qty) || 0) > 0).length;
        },
        addBulk() {
            this.bulkProducts.forEach(p => {
                const q = parseInt(p.qty) || 0;
                if (q <= 0) return;
                const ex = this.cart.find(i => i.product_id === p.id);
                if (ex) { ex.qty += q; }
                else {
                    this.cart.push({
                        product_id: p.id, name: p.name, price: parseFloat(p.price),
                        cost_price: parseFloat(p.cost_price || 0), qty: q,
                        unit: p.unit || 'pcs', discount: 0,
                    });
                }
                p.qty = null;
            });
            this.bulkOpen = false;
            this.bulkSearch = '';
            this.calcTotals();
        },

        increaseQty(i) { this.cart[i].qty++; this.calcTotals(); },
        decreaseQty(i) {
            if (this.cart[i].qty > 1) { this.cart[i].qty--; }
            else { this.cart.splice(i, 1); }
            this.calcTotals();
        },
        removeItem(i) { this.cart.splice(i, 1); this.calcTotals(); },

        calcTotals() {
            this.subtotal = this.cart.reduce((s, i) => s + (i.price * i.qty), 0);
            this.total    = Math.max(0, this.subtotal - this.discount + this.shippingCharge);
            this.calcDue();
        },
        calcDue() { this.dueAmount = this.total - this.paidAmount; },
        payFull() { this.paidAmount = this.total; this.calcDue(); },

        async searchCustomer(q) {
            if (q.length < 2) { this.customerResults = []; return; }
            const res = await fetch(`/api/customers/search?q=${encodeURIComponent(q)}`);
            this.customerResults = await res.json();
        },

        selectCustomer(c) {
            document.getElementById('customerId').value = c.id;
            document.getElementById('customerName').value = c.name;
            document.getElementById('customerPhone').value = c.phone || '';
            document.getElementById('customerSearch').value = '';
            this.customerResults = [];
            if (c.phone && c.phone.length >= 10) {
                this.checkFraud(c.phone);
            }
        },
    }
}
</script>
@endpush
