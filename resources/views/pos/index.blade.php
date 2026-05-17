@extends('layouts.app')
@section('title', 'POS')

@push('styles')
<style>
    nav.lg\:hidden { display: none !important; }
    main { padding-bottom: 0 !important; }
    .product-card { transition: transform 0.1s ease, box-shadow 0.1s ease; cursor: pointer; }
    .product-card:active { transform: scale(0.96); }
    .cart-item { animation: slideIn 0.15s ease; }
    @keyframes slideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .scrollbar-hide { -ms-overflow-style:none; scrollbar-width:none; }
    .scrollbar-hide::-webkit-scrollbar { display:none; }
    @keyframes scanMove {
        0%   { top: 10%; }
        50%  { top: 85%; }
        100% { top: 10%; }
    }
    .scan-line { animation: scanMove 2s ease-in-out infinite; }
</style>
@endpush

@section('content')
<div class="h-[calc(100vh-4rem)] flex flex-col" x-data="posApp()">

    {{-- TOP BAR --}}
    <div class="flex items-center gap-2 px-2 py-2 bg-white dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700 flex-shrink-0">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" id="barcodeInput" x-model="searchTerm"
                   @input.debounce.300ms="searchProducts()"
                   @keydown.enter="barcodeSearch()"
                   placeholder="Search or scan barcode..."
                   autocomplete="off"
                   class="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-xl bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-600">
        </div>
        {{-- Camera Scan Button --}}
        <button @click="startScan()"
                class="p-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors flex-shrink-0"
                title="Scan Barcode">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9V6a1 1 0 011-1h3M3 15v3a1 1 0 001 1h3m11-4v3a1 1 0 01-1 1h-3m4-11V6a1 1 0 00-1-1h-3M8 7h.01M12 7h.01M16 7h.01M8 12h.01M12 12h.01M16 12h.01M8 17h.01M12 17h.01M16 17h.01"/>
            </svg>
        </button>

        <button @click="holdOrder()" x-show="cart.length > 0" x-cloak
                class="p-2.5 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-xl hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors text-xs font-medium whitespace-nowrap">
            Hold
        </button>
        <button @click="showHeld = true" class="relative p-2.5 bg-gray-100 dark:bg-slate-700 rounded-xl text-gray-600 dark:text-slate-300 hover:bg-gray-200 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            <span x-show="heldOrders.length > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-orange-500 text-white text-xs rounded-full flex items-center justify-center" x-text="heldOrders.length"></span>
        </button>
    </div>

    {{-- MAIN AREA --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- LEFT: PRODUCTS --}}
        <div class="flex-1 flex flex-col overflow-hidden" :class="showCart ? 'hidden lg:flex' : 'flex'">

            {{-- Category Tabs --}}
            <div class="flex gap-1.5 px-2 py-2 overflow-x-auto scrollbar-hide flex-shrink-0 bg-gray-50 dark:bg-slate-800/50">
                <button @click="activeCategory = null; filterProducts()"
                        class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="activeCategory === null ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-600'">
                    All
                </button>
                @foreach($categories as $cat)
                <button @click="activeCategory = {{ $cat->id }}; filterProducts()"
                        class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap"
                        :class="activeCategory === {{ $cat->id }} ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-600'">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>

            {{-- Products Grid --}}
            <div class="flex-1 overflow-y-auto p-2">
                {{-- Search Results --}}
                <div x-show="searchTerm.length > 0" x-cloak>
                    <p class="text-xs text-gray-400 px-1 mb-2" x-text="searchResults.length + ' results'"></p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2">
                        <template x-for="p in searchResults" :key="p.id">
                            <div @click="addToCart(p)" class="product-card bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 hover:border-blue-300 hover:shadow-sm">
                                <div class="w-full aspect-square rounded-lg bg-gray-100 dark:bg-slate-700 mb-2 overflow-hidden flex items-center justify-center">
                                    <img x-show="p.image" :src="p.image" class="w-full h-full object-cover">
                                    <svg x-show="!p.image" class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <p class="text-xs font-medium text-gray-900 dark:text-white truncate" x-text="p.name"></p>
                                <p class="text-sm font-bold text-blue-600 dark:text-blue-400 mt-0.5" x-text="'৳' + p.price"></p>
                                <p class="text-xs mt-0.5" :class="p.stock<=0?'text-red-500':p.stock<=5?'text-yellow-500':'text-gray-400'"
                                   x-text="p.stock<=0?'Out of stock':'Stock: '+p.stock"></p>
                            </div>
                        </template>
                        <div x-show="searchResults.length===0" class="col-span-full py-8 text-center text-sm text-gray-400">No products found</div>
                    </div>
                </div>

                {{-- All Products --}}
                <div x-show="searchTerm.length === 0" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2">
                    <template x-for="p in filteredProducts" :key="p.id">
                        <div @click="p.stock > 0 && addToCart(p)"
                             class="product-card bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 hover:border-blue-300 hover:shadow-sm"
                             :class="p.stock <= 0 ? 'opacity-50 cursor-not-allowed' : ''">
                            <div class="relative w-full aspect-square rounded-lg bg-gray-100 dark:bg-slate-700 mb-2 overflow-hidden flex items-center justify-center">
                                <img x-show="p.image" :src="p.image" class="w-full h-full object-cover">
                                <svg x-show="!p.image" class="w-8 h-8 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <div x-show="getCartQty(p.id) > 0" x-cloak class="absolute top-1 right-1 w-5 h-5 bg-blue-600 text-white text-xs rounded-full flex items-center justify-center font-bold" x-text="getCartQty(p.id)"></div>
                                <div x-show="p.stock <= 0" class="absolute inset-0 bg-black/40 flex items-center justify-center rounded-lg">
                                    <span class="text-white text-xs font-semibold bg-red-500 px-2 py-0.5 rounded">Out</span>
                                </div>
                            </div>
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate" x-text="p.name"></p>
                            <div class="flex items-center justify-between mt-0.5">
                                <p class="text-sm font-bold text-blue-600 dark:text-blue-400" x-text="'৳' + p.price"></p>
                                <p class="text-xs" :class="p.stock<=5?'text-yellow-500':'text-gray-400 dark:text-slate-500'" x-text="p.stock"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="filteredProducts.length === 0" class="col-span-full py-12 text-center text-sm text-gray-400">No products</div>
                </div>
            </div>
        </div>

        {{-- RIGHT: CART --}}
        <div class="w-full lg:w-80 xl:w-96 flex flex-col bg-white dark:bg-slate-800 border-l border-gray-100 dark:border-slate-700 flex-shrink-0"
             :class="showCart ? 'flex' : 'hidden lg:flex'">

            {{-- Cart Header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Cart</span>
                    <span x-show="cart.length > 0" class="w-5 h-5 bg-blue-600 text-white text-xs rounded-full flex items-center justify-center font-bold" x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <button @click="clearCart()" x-show="cart.length > 0" x-cloak class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button @click="showCart = false" class="lg:hidden p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </button>
                </div>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
                <template x-for="(item, idx) in cart" :key="item.product_id">
                    <div class="cart-item flex items-center gap-2 p-2.5 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate" x-text="item.name"></p>
                            <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold mt-0.5" x-text="'৳' + item.price + ' × ' + item.qty"></p>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button @click="decreaseQty(idx)" class="w-6 h-6 bg-white dark:bg-slate-600 border border-gray-200 dark:border-slate-500 rounded-lg flex items-center justify-center text-gray-600 dark:text-slate-200 hover:bg-red-50 hover:text-red-500 transition-colors font-bold text-sm">−</button>
                            <span class="w-6 text-center text-sm font-bold text-gray-900 dark:text-white" x-text="item.qty"></span>
                            <button @click="increaseQty(idx)" class="w-6 h-6 bg-white dark:bg-slate-600 border border-gray-200 dark:border-slate-500 rounded-lg flex items-center justify-center text-gray-600 dark:text-slate-200 hover:bg-green-50 hover:text-green-600 transition-colors font-bold text-sm">+</button>
                        </div>
                        <span class="w-14 text-right text-xs font-bold text-gray-900 dark:text-white flex-shrink-0" x-text="'৳' + (item.price * item.qty).toLocaleString()"></span>
                    </div>
                </template>
                <div x-show="cart.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-300 dark:text-slate-600">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <p class="text-sm">Cart empty</p>
                </div>
            </div>

            {{-- CHECKOUT PANEL --}}
            <div class="border-t border-gray-100 dark:border-slate-700 p-3 flex-shrink-0 space-y-2.5">
                {{-- Customer Info --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Customer Name</label>
                        <input type="text" x-model="customerName"
                               class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                               placeholder="Walk-in Customer">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Phone</label>
                        <input type="text" x-model="customerPhone"
                               class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                               placeholder="01XXXXXXXXX">
                    </div>
                </div>

                {{-- Discount + Shipping --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Discount ৳</label>
                        <input type="number" x-model.number="orderDiscount" @input="calcTotals()" min="0"
                               class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 text-right">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Shipping ৳</label>
                        <input type="number" x-model.number="shippingCharge" @input="calcTotals()" min="0"
                               class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 text-right">
                    </div>
                </div>

                {{-- Total --}}
                <div class="bg-blue-600 rounded-xl p-3 flex justify-between items-center">
                    <span class="text-blue-100 text-sm">Total</span>
                    <span class="text-white text-xl font-bold" x-text="'৳' + total.toLocaleString()"></span>
                </div>

                {{-- Payment Method --}}
                <div class="grid grid-cols-4 gap-1.5">
                    @foreach(['cash'=>'💵','bkash'=>'📱','nagad'=>'🟠','card'=>'💳'] as $method => $icon)
                    <button @click="paymentMethod = '{{ $method }}'"
                            class="py-2 rounded-xl text-xs font-medium transition-all border"
                            :class="paymentMethod === '{{ $method }}' ? 'bg-blue-600 text-white border-blue-600 scale-105' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:border-blue-300'">
                        {{ $icon }}<br>{{ ucfirst($method) }}
                    </button>
                    @endforeach
                </div>

                {{-- Paid + Change --}}
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Paid ৳</label>
                        <input type="number" x-model.number="paidAmount" @input="calcChange()" min="0"
                               class="w-full px-2 py-2 text-sm font-bold border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 text-right">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">Change ৳</label>
                        <div class="px-2 py-2 rounded-lg text-sm font-bold text-right"
                             :class="change >= 0 ? 'bg-green-50 dark:bg-green-900/20 text-green-600' : 'bg-red-50 dark:bg-red-900/20 text-red-600'"
                             x-text="'৳' + Math.abs(change).toLocaleString() + (change < 0 ? ' due' : '')">
                        </div>
                    </div>
                </div>

                {{-- Quick Amounts --}}
                <div class="flex gap-1.5 flex-wrap">
                    <button @click="paidAmount = total; calcChange()"
                            class="px-2.5 py-1.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 transition-colors font-medium">
                        Exact
                    </button>
                    <template x-for="amt in quickAmounts" :key="amt">
                        <button @click="paidAmount = amt; calcChange()"
                                class="px-2.5 py-1.5 text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                                x-text="'৳' + amt.toLocaleString()"></button>
                    </template>
                </div>

                {{-- CHECKOUT --}}
                <button @click="checkout()"
                        :disabled="cart.length === 0 || processing"
                        class="w-full py-3.5 rounded-xl text-white font-bold text-base transition-all"
                        :class="cart.length === 0 ? 'bg-gray-200 dark:bg-slate-700 text-gray-400 cursor-not-allowed' : processing ? 'bg-blue-400 cursor-wait' : 'bg-blue-600 hover:bg-blue-700 shadow-sm hover:shadow-md'">
                    <span x-show="!processing">💳 Checkout — ৳<span x-text="total.toLocaleString()"></span></span>
                    <span x-show="processing" x-cloak class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Processing...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Cart FAB --}}
    <button @click="showCart = true" x-show="!showCart && cart.length > 0"
            class="lg:hidden fixed bottom-6 right-4 z-40 w-14 h-14 bg-blue-600 text-white rounded-full shadow-xl flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold" x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
    </button>

    {{-- HELD ORDERS MODAL --}}
    <div x-show="showHeld" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
        <div @click.outside="showHeld = false" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-sm max-h-80 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Held Orders</h3>
                <button @click="showHeld = false" class="p-1 text-gray-400 text-xl leading-none">×</button>
            </div>
            <div class="overflow-y-auto flex-1 p-2 space-y-1.5">
                <template x-for="(held, idx) in heldOrders" :key="idx">
                    <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-900 dark:text-white" x-text="'Order ' + (idx+1) + ' · ' + held.cart.length + ' items'"></p>
                            <p class="text-xs text-gray-400" x-text="'৳' + held.total + ' · ' + held.time"></p>
                        </div>
                        <button @click="recallOrder(idx)" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700">Recall</button>
                        <button @click="removeHeld(idx)" class="px-2 py-1.5 text-xs bg-red-100 text-red-600 rounded-lg hover:bg-red-200">✕</button>
                    </div>
                </template>
                <div x-show="heldOrders.length === 0" class="py-8 text-center text-sm text-gray-400">No held orders</div>
            </div>
        </div>
    </div>

    {{-- SUCCESS MODAL --}}
    <div x-show="showSuccess" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-xs text-center p-6">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Sale Complete! 🎉</h3>
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-1">Order #<span x-text="lastOrderNumber"></span></p>
            <p x-show="change > 0" class="text-base font-bold text-green-600 dark:text-green-400 mb-4">Change: ৳<span x-text="change.toLocaleString()"></span></p>
            <div class="flex gap-2 mt-4">
                <a :href="'/pos/receipt/' + lastOrderId" target="_blank"
                   class="flex-1 py-2.5 border border-gray-200 dark:border-slate-600 text-sm text-gray-700 dark:text-slate-200 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    🖨️ Print
                </a>
                <button @click="newSale()"
                        class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition-colors">
                    New Sale
                </button>
            </div>
        </div>
    </div>

    {{-- CAMERA SCANNER MODAL --}}
    <div x-show="scanning" x-cloak
         class="fixed inset-0 bg-black z-50 flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 bg-black/80 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                <span class="text-white text-sm font-semibold">Scanning...</span>
            </div>
            <button @click="stopScan()" class="text-white p-2 hover:bg-white/10 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Camera View --}}
        <div class="relative flex-1 overflow-hidden">
            <div id="scannerVideo" class="w-full h-full" style="min-height:300px;"></div>

            {{-- Scan Frame --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none" style="z-index:10">
                <div class="relative w-72 h-44">
                    {{-- Corner guides --}}
                    <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-indigo-400 rounded-tl-lg"></div>
                    <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-indigo-400 rounded-tr-lg"></div>
                    <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-indigo-400 rounded-bl-lg"></div>
                    <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-indigo-400 rounded-br-lg"></div>
                    {{-- Scan line animation --}}
                    <div class="absolute left-2 right-2 h-0.5 bg-indigo-400/70 scan-line"></div>
                </div>
                <p class="absolute bottom-20 text-white/70 text-sm">Point camera at barcode</p>
            </div>

            {{-- Last scan result flash --}}
            <div x-show="scanFlash" x-cloak
                 class="absolute top-4 left-4 right-4 bg-green-500 text-white text-sm font-semibold px-4 py-3 rounded-xl flex items-center gap-2 shadow-lg">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-text="'Added: ' + lastScannedName"></span>
            </div>

            {{-- Not found flash --}}
            <div x-show="scanNotFound" x-cloak
                 class="absolute top-4 left-4 right-4 bg-red-500 text-white text-sm font-semibold px-4 py-3 rounded-xl flex items-center gap-2 shadow-lg">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span x-text="'Not found: ' + lastScanCode"></span>
            </div>
        </div>

        {{-- Camera switch button --}}
        <div class="bg-black/80 px-4 py-4 flex-shrink-0 flex items-center justify-between">
            <button @click="switchCamera()" class="flex items-center gap-2 text-white/70 text-sm px-4 py-2 bg-white/10 rounded-xl hover:bg-white/20 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Switch Camera
            </button>
            <p class="text-white/50 text-xs" x-text="'Cart: ' + cart.reduce((s,i)=>s+i.qty,0) + ' items'"></p>
            <button @click="stopScan()" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
                Done
            </button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function posApp() {
    return {
        allProducts:      @json($products),
        filteredProducts: @json($products),
        searchResults:    [],
        searchTerm:       '',
        activeCategory:   null,

        cart:           [],
        showCart:       false,
        orderDiscount:  0,
        shippingCharge: 0,
        subtotal:       0,
        total:          0,
        paidAmount:     0,
        change:         0,
        paymentMethod:  'cash',
        quickAmounts:   [100, 200, 500, 1000],

        customerName:  '',
        customerPhone: '',
        showHeld:    false,
        showSuccess: false,
        processing:  false,
        heldOrders:  JSON.parse(localStorage.getItem('pos_held') || '[]'),
        lastOrderId: null,
        lastOrderNumber: null,

        // Scanner
        scanning:        false,
        scanFlash:       false,
        scanNotFound:    false,
        lastScannedName: '',
        lastScanCode:    '',
        _scanner:        null,
        _cameras:        [],
        _camIdx:         0,
        _scanLock:       false,

        init() {
            this.filteredProducts = this.allProducts;
            this.$nextTick(() => document.getElementById('barcodeInput')?.focus());
        },

        async searchProducts() {
            if (this.searchTerm.length < 2) { this.searchResults = []; return; }
            const res = await fetch(`/api/products/search?q=${encodeURIComponent(this.searchTerm)}`);
            this.searchResults = await res.json();
        },

        async barcodeSearch() {
            if (!this.searchTerm.trim()) return;
            const res  = await fetch(`/pos/scan-barcode?barcode=${encodeURIComponent(this.searchTerm)}`);
            const data = await res.json();
            if (data.product) {
                this.addToCart(data.product);
                this.searchTerm = '';
                this.searchResults = [];
            }
        },

        filterProducts() {
            this.filteredProducts = this.activeCategory === null
                ? this.allProducts
                : this.allProducts.filter(p => p.category_id === this.activeCategory);
        },

        addToCart(p) {
            if (p.stock <= 0) return;
            const ex = this.cart.find(i => i.product_id === p.id);
            if (ex) { if (ex.qty < p.stock) ex.qty++; }
            else this.cart.push({ product_id: p.id, name: p.name, price: parseFloat(p.price), cost_price: parseFloat(p.cost_price||0), qty: 1, unit: p.unit||'pcs', stock: p.stock });
            this.calcTotals();
            this.showCart = true;
        },

        increaseQty(i) { if (this.cart[i].qty < this.cart[i].stock) this.cart[i].qty++; this.calcTotals(); },
        decreaseQty(i) { if (this.cart[i].qty > 1) this.cart[i].qty--; else this.cart.splice(i,1); this.calcTotals(); },
        getCartQty(id)  { return this.cart.find(i=>i.product_id===id)?.qty || 0; },

        clearCart() {
            this.cart = []; this.orderDiscount = 0; this.shippingCharge = 0; this.paidAmount = 0;
            this.calcTotals();
        },

        newSale() { this.showSuccess = false; this.clearCart(); this.customerName = ''; this.customerPhone = ''; document.getElementById('barcodeInput')?.focus(); },

        calcTotals() {
            this.subtotal = this.cart.reduce((s,i) => s + i.price * i.qty, 0);
            this.total    = Math.max(0, this.subtotal - this.orderDiscount + this.shippingCharge);
            const t = this.total;
            this.quickAmounts = t<=100 ? [100,200,500,1000] : t<=500 ? [500,1000,2000,5000] : [Math.ceil(t/100)*100, 2000, 5000, 10000];
            this.calcChange();
        },

        calcChange() { this.change = this.paidAmount - this.total; },

        async checkout() {
            if (!this.cart.length || this.processing) return;
            this.processing = true;
            try {
                const res = await fetch('/pos/checkout', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({
                        customer_name:  this.customerName || 'Walk-in Customer',
                        customer_phone: this.customerPhone || '',
                        items:          this.cart.map(i => ({ product_id:i.product_id, product_name:i.name, price:i.price, cost_price:i.cost_price, quantity:i.qty, discount:0, total:i.price*i.qty })),
                        subtotal:       this.subtotal,
                        discount:       this.orderDiscount,
                        shipping_charge:this.shippingCharge,
                        total:          this.total,
                        paid_amount:    this.paidAmount,
                        due_amount:     Math.max(0, this.total - this.paidAmount),
                        payment_method: this.paymentMethod,
                        source:         'pos',
                        status:         'completed',
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.lastOrderId = data.order_id;
                    this.lastOrderNumber = data.order_number;
                    this.showSuccess = true;
                    this.cart.forEach(i => { const p=this.allProducts.find(p=>p.id===i.product_id); if(p) p.stock-=i.qty; });
                } else {
                    alert('Error: ' + (data.message || 'Checkout failed'));
                }
            } catch(e) { alert('Network error. Try again.'); }
            finally { this.processing = false; }
        },

        holdOrder() {
            if (!this.cart.length) return;
            this.heldOrders.push({ cart:[...this.cart], total:this.total, discount:this.orderDiscount, time:new Date().toLocaleTimeString() });
            localStorage.setItem('pos_held', JSON.stringify(this.heldOrders));
            this.clearCart(); this.showHeld = false;
        },

        recallOrder(i) {
            const h = this.heldOrders[i];
            this.cart = [...h.cart]; this.orderDiscount = h.discount;
            this.calcTotals(); this.removeHeld(i); this.showHeld = false;
        },

        removeHeld(i) {
            this.heldOrders.splice(i,1);
            localStorage.setItem('pos_held', JSON.stringify(this.heldOrders));
        },

        // ── CAMERA SCANNER ──────────────────────────────
        async startScan() {
            if (typeof Html5Qrcode === 'undefined') {
                alert('Scanner library not loaded yet. Please wait a moment and try again.');
                return;
            }
            this.scanning   = true;
            this._scanLock  = false;
            await this.$nextTick();
            this._initScanner();
        },

        _initScanner() {
            // Destroy previous instance
            if (this._scanner) {
                try { this._scanner.stop(); } catch(e) {}
                this._scanner = null;
            }

            const facingMode = this._camIdx === 0 ? 'environment' : 'user';

            this._scanner = new Html5Qrcode('scannerVideo');
            this._scanner.start(
                { facingMode },
                {
                    fps: 15,
                    qrbox: { width: 260, height: 140 },
                    aspectRatio: 1.7,
                    disableFlip: false,
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats?.QR_CODE,
                        Html5QrcodeSupportedFormats?.CODE_128,
                        Html5QrcodeSupportedFormats?.CODE_39,
                        Html5QrcodeSupportedFormats?.EAN_13,
                        Html5QrcodeSupportedFormats?.EAN_8,
                        Html5QrcodeSupportedFormats?.UPC_A,
                        Html5QrcodeSupportedFormats?.UPC_E,
                    ].filter(Boolean),
                },
                (code) => {
                    if (!this._scanLock) this._onScan(code);
                },
                () => {} // ignore errors
            ).catch(err => {
                console.error(err);
                alert('Camera error: ' + err);
                this.scanning = false;
            });
        },

        async _onScan(code) {
            this._scanLock   = true;
            this.lastScanCode = code;
            this._beep();
            if (navigator.vibrate) navigator.vibrate(80);

            try {
                const res  = await fetch(`/pos/scan-barcode?barcode=${encodeURIComponent(code)}`);
                const data = await res.json();

                if (data.product) {
                    this.addToCart(data.product);
                    this.lastScannedName = data.product.name;
                    this.scanFlash    = true;
                    this.scanNotFound = false;
                    setTimeout(() => { this.scanFlash = false; }, 2000);
                } else {
                    this.scanNotFound = true;
                    this.scanFlash    = false;
                    setTimeout(() => { this.scanNotFound = false; }, 2000);
                }
            } catch(e) {}

            setTimeout(() => { this._scanLock = false; }, 1500);
        },

        stopScan() {
            if (this._scanner) {
                try { this._scanner.stop(); } catch(e) {}
                this._scanner = null;
            }
            this.scanning     = false;
            this.scanFlash    = false;
            this.scanNotFound = false;
        },

        switchCamera() {
            this._camIdx = this._camIdx === 0 ? 1 : 0;
            if (this.scanning) this._initScanner();
        },

        _beep() {
            try {
                const ctx  = new (window.AudioContext || window.webkitAudioContext)();
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = 1200;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
                osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.15);
            } catch(e) {}
        },
    }
}
</script>
@endpush
