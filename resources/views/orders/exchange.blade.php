@extends('layouts.app')
@section('title', 'Exchange')

@section('content')
<div class="max-w-5xl mx-auto" x-data="exchangeForm()">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('orders.show', $order) }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Exchange — Order #{{ $order->order_number }}</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $order->customer_name }} · return old items + buy new, pay only the difference</p>
        </div>
    </div>

    @if(session('error'))
    <div class="mb-4 px-4 py-2.5 text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('orders.exchange.store', $order) }}">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">

                {{-- ① RETURN items from the old order --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-200 dark:border-orange-900/40 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">① Returning from this order</h3>
                    @forelse($order->items as $item)
                    @if($item->product_id)
                    <div class="flex items-center gap-2 py-1.5 border-b border-gray-50 dark:border-slate-700 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 dark:text-slate-200 truncate">{{ $item->product_name }}</p>
                            <p class="text-xs text-gray-400">{{ $item->quantity }} bought</p>
                        </div>
                        <span class="text-xs text-gray-400">৳</span>
                        <input type="number" min="0" step="0.01" name="returns[{{ $item->id }}][price]"
                               value="{{ $item->price }}" @input="calc()"
                               class="ret-price w-20 px-1.5 py-1 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white"
                               data-id="{{ $item->id }}" title="Return price (editable)">
                        <span class="text-xs text-gray-400">×</span>
                        <input type="number" min="0" max="{{ $item->quantity }}" name="returns[{{ $item->id }}][qty]"
                               placeholder="0" @input="calc()"
                               class="ret-qty w-14 px-1.5 py-1 text-sm text-center border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white"
                               data-id="{{ $item->id }}" title="Return qty">
                    </div>
                    @endif
                    @empty
                    <p class="text-xs text-gray-400">This order has no returnable items.</p>
                    @endforelse
                </div>

                {{-- ② NEW items --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">② New products</h3>
                    <div class="relative mb-3">
                        <input type="text" id="exSearch" placeholder="Search product by name / SKU..."
                               autocomplete="off" @input="search($event.target.value)"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <div x-show="results.length" x-cloak class="absolute z-20 top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-lg max-h-64 overflow-y-auto">
                            <template x-for="p in results" :key="p.id">
                                <div @click="add(p)" class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer border-b border-gray-50 dark:border-slate-700 last:border-0">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-900 dark:text-white truncate" x-text="p.name"></p>
                                        <p class="text-xs" :class="p.stock<=0?'text-red-500':'text-gray-400'" x-text="(p.sku?p.sku+' · ':'')+(p.stock<=0?'Out of stock':'Stock: '+p.stock)"></p>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="'৳'+p.price"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <template x-for="(item, i) in cart" :key="i">
                        <div class="flex items-center gap-2 p-2.5 bg-gray-50 dark:bg-slate-700/50 rounded-xl mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="item.name"></p>
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="text-xs text-gray-400">৳</span>
                                    <input type="number" min="0" step="0.01" x-model.number="item.price" @input="calc()"
                                           :name="'items['+i+'][price]'"
                                           class="w-20 px-1.5 py-0.5 text-xs border border-gray-200 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                                </div>
                                <input type="hidden" :name="'items['+i+'][product_id]'" :value="item.product_id">
                                <input type="hidden" :name="'items['+i+'][product_name]'" :value="item.name">
                                <input type="hidden" :name="'items['+i+'][cost_price]'" :value="item.cost_price||0">
                                <input type="hidden" :name="'items['+i+'][quantity]'" :value="item.qty">
                                <input type="hidden" :name="'items['+i+'][discount]'" value="0">
                                <input type="hidden" :name="'items['+i+'][total]'" :value="item.price*item.qty">
                            </div>
                            <button type="button" @click="item.qty>1?item.qty--:null; calc()" class="w-7 h-7 bg-white dark:bg-slate-600 border border-gray-200 dark:border-slate-500 rounded-lg">−</button>
                            <span class="w-8 text-center text-sm font-semibold" x-text="item.qty"></span>
                            <button type="button" @click="item.qty++; calc()" class="w-7 h-7 bg-white dark:bg-slate-600 border border-gray-200 dark:border-slate-500 rounded-lg">+</button>
                            <span class="w-20 text-right text-sm font-semibold" x-text="'৳'+(item.price*item.qty).toLocaleString()"></span>
                            <button type="button" @click="cart.splice(i,1); calc()" class="p-1.5 text-gray-400 hover:text-red-500">✕</button>
                        </div>
                    </template>
                    <p x-show="!cart.length" class="text-center text-sm text-gray-400 py-4">Search and add new products</p>
                </div>
            </div>

            {{-- SUMMARY --}}
            <div class="space-y-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Settlement</h3>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">New products</span><span class="font-semibold">৳<span x-text="newSubtotal.toLocaleString()"></span></span></div>
                        <div class="flex justify-between text-orange-600 dark:text-orange-400"><span>Return credit</span><span>− ৳<span x-text="returnCredit.toLocaleString()"></span></span></div>
                        <div class="flex justify-between pt-2 border-t border-gray-100 dark:border-slate-700 text-base font-bold"><span>Net payable</span><span class="text-primary-600 dark:text-primary-400">৳<span x-text="netTotal.toLocaleString()"></span></span></div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                            <option value="cash">Cash</option><option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="bank">Bank</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Paid now ৳</label>
                        <input type="number" name="paid_amount" min="0" x-model.number="paid"
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                        <p class="text-xs text-gray-400 mt-1">Due after this: ৳<span x-text="Math.max(0, netTotal - paid).toLocaleString()"></span></p>
                    </div>
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" :disabled="cart.length===0"
                            class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-40 text-white text-sm font-semibold rounded-xl">
                        ↔️ Process Exchange
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function exchangeForm() {
    return {
        returns: {},
        cart: [],
        results: [],
        paid: 0,
        returnCredit: 0,
        newSubtotal: 0,
        netTotal: 0,
        searchT: null,
        calc() {
            let credit = 0;
            document.querySelectorAll('.ret-qty').forEach(qtyEl => {
                const q = parseInt(qtyEl.value) || 0;
                if (q <= 0) return;
                const priceEl = document.querySelector('.ret-price[data-id="' + qtyEl.dataset.id + '"]');
                const price = priceEl ? (parseFloat(priceEl.value) || 0) : 0;
                credit += q * price;
            });
            this.returnCredit = credit;
            this.newSubtotal  = this.cart.reduce((s, i) => s + (i.price * i.qty), 0);
            this.netTotal     = Math.max(0, this.newSubtotal - this.returnCredit);
        },
        search(q) {
            clearTimeout(this.searchT);
            if (q.length < 2) { this.results = []; return; }
            this.searchT = setTimeout(async () => {
                const res = await fetch(`/api/products/search?q=${encodeURIComponent(q)}`);
                this.results = await res.json();
            }, 300);
        },
        add(p) {
            const ex = this.cart.find(i => i.product_id === p.id);
            if (ex) ex.qty++;
            else this.cart.push({ product_id: p.id, name: p.name, price: parseFloat(p.price), cost_price: parseFloat(p.cost_price||0), qty: 1 });
            this.calc();
        },
    };
}
</script>
@endpush
@endsection
