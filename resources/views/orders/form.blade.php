@extends('layouts.app')
@section('title', isset($order) ? 'Edit Order' : 'New Order')

@section('content')

<div class="max-w-5xl mx-auto" x-data="orderForm()">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('orders.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
            {{ isset($order) ? 'Edit Order #'.$order->order_number : 'Create New Order' }}
        </h2>
    </div>

    <form method="POST"
          action="{{ isset($order) ? route('orders.update', $order) : route('orders.store') }}"
          id="orderForm">
        @csrf
        @if(isset($order)) @method('PUT') @endif

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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_name" id="customerName"
                                   value="{{ old('customer_name', isset($order) ? $order->customer_name : '') }}" required
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   placeholder="Customer name">
                            <input type="hidden" name="customer_id" id="customerId" value="{{ old('customer_id', isset($order) ? $order->customer_id : '') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Phone</label>
                            <input type="text" name="customer_phone" id="customerPhone"
                                   value="{{ old('customer_phone', isset($order) ? $order->customer_phone : '') }}"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   placeholder="01XXXXXXXXX">
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

                {{-- Product Search + Cart --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Order Items</h3>

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
                                        <p class="text-xs text-gray-400 dark:text-slate-500" x-text="'SKU: ' + (p.sku || 'N/A') + ' · Stock: ' + p.stock"></p>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white flex-shrink-0" x-text="'৳' + p.price"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Cart Items --}}
                    <div class="space-y-2 mb-4" x-show="cart.length > 0">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="item.name"></p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500" x-text="'৳' + item.price + ' / ' + item.unit"></p>
                                    <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id">
                                    <input type="hidden" :name="'items[' + index + '][product_name]'" :value="item.name">
                                    <input type="hidden" :name="'items[' + index + '][price]'" :value="item.price">
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
                    <button type="submit"
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
        cart: @json($cartItems ?? []),
        productResults: [],
        customerResults: [],
        discount: {{ old('discount', isset($order) ? $order->discount : 0) }},
        shippingCharge: {{ old('shipping_charge', isset($order) ? $order->shipping_charge : 0) }},
        paidAmount: {{ old('paid_amount', isset($order) ? $order->paid_amount : 0) }},
        subtotal: 0,
        total: 0,
        dueAmount: 0,
        searchTimeout: null,

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
            this.productResults = [];
            document.getElementById('productSearch').value = '';
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
        },
    }
}
</script>
@endpush
