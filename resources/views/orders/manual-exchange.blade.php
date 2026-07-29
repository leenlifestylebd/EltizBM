@extends('layouts.app')
@section('title', 'Manual Exchange')

@section('content')
<div class="max-w-5xl mx-auto" x-data="manualExchange()">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('orders.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Manual Exchange</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400">For a return from a past order that was never entered in the app — pick returned + new items</p>
        </div>
    </div>

    @if(session('error'))
    <div class="mb-4 px-4 py-2.5 text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('orders.manual-exchange.store') }}">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">

                {{-- Customer --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <label class="block text-xs text-gray-500 mb-1">Customer name <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" required placeholder="Customer name"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                    <input type="text" name="customer_phone" placeholder="Phone (optional)"
                           class="w-full mt-2 px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                </div>

                {{-- ① RETURNED items (manual: search + qty + price) --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-200 dark:border-orange-900/40 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">① Returned items (from the old invoice)</h3>
                    <input type="text" placeholder="Search a returned product..." autocomplete="off"
                           @input="search($event.target.value, 'ret')"
                           class="w-full mb-2 px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-400">
                    <div x-show="retResults.length" x-cloak class="border border-gray-100 dark:border-slate-700 rounded-lg mb-2 max-h-40 overflow-y-auto">
                        <template x-for="p in retResults" :key="p.id">
                            <div @click="addRet(p)" class="px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer flex justify-between">
                                <span x-text="p.name"></span><span class="text-gray-400" x-text="'৳'+p.price"></span>
                            </div>
                        </template>
                    </div>
                    <template x-for="(r, i) in retCart" :key="i">
                        <div class="flex items-center gap-2 py-1.5 border-b border-gray-50 dark:border-slate-700 last:border-0">
                            <span class="flex-1 text-sm text-gray-800 dark:text-slate-200 truncate" x-text="r.name"></span>
                            <span class="text-xs text-gray-400">৳</span>
                            <input type="number" min="0" step="0.01" x-model.number="r.price" @input="calc()" :name="'returns['+i+'][price]'"
                                   class="w-20 px-1.5 py-0.5 text-xs border border-gray-200 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white" title="Return price">
                            <span class="text-xs text-gray-400">×</span>
                            <input type="number" min="1" x-model.number="r.qty" @input="calc()" :name="'returns['+i+'][quantity]'"
                                   class="w-14 px-1.5 py-0.5 text-xs text-center border border-gray-200 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                            <input type="hidden" :name="'returns['+i+'][product_id]'" :value="r.product_id">
                            <button type="button" @click="retCart.splice(i,1); calc()" class="p-1 text-gray-400 hover:text-red-500">✕</button>
                        </div>
                    </template>
                    <p x-show="!retCart.length" class="text-xs text-gray-400 py-2">Search returned products, set their price &amp; qty.</p>
                </div>

                {{-- ② NEW items --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">② New products</h3>
                    <input type="text" placeholder="Search a new product..." autocomplete="off"
                           @input="search($event.target.value, 'new')"
                           class="w-full mb-2 px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <div x-show="newResults.length" x-cloak class="border border-gray-100 dark:border-slate-700 rounded-lg mb-2 max-h-40 overflow-y-auto">
                        <template x-for="p in newResults" :key="p.id">
                            <div @click="addNew(p)" class="px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer flex justify-between">
                                <span x-text="p.name"></span><span class="text-gray-400" x-text="'৳'+p.price"></span>
                            </div>
                        </template>
                    </div>
                    <template x-for="(item, i) in cart" :key="i">
                        <div class="flex items-center gap-2 py-1.5 border-b border-gray-50 dark:border-slate-700 last:border-0">
                            <span class="flex-1 text-sm text-gray-800 dark:text-slate-200 truncate" x-text="item.name"></span>
                            <span class="text-xs text-gray-400">৳</span>
                            <input type="number" min="0" step="0.01" x-model.number="item.price" @input="calc()" :name="'items['+i+'][price]'"
                                   class="w-20 px-1.5 py-0.5 text-xs border border-gray-200 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                            <span class="text-xs text-gray-400">×</span>
                            <input type="number" min="1" x-model.number="item.qty" @input="calc()"
                                   class="w-14 px-1.5 py-0.5 text-xs text-center border border-gray-200 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                            <input type="hidden" :name="'items['+i+'][product_id]'" :value="item.product_id">
                            <input type="hidden" :name="'items['+i+'][product_name]'" :value="item.name">
                            <input type="hidden" :name="'items['+i+'][cost_price]'" :value="item.cost_price||0">
                            <input type="hidden" :name="'items['+i+'][quantity]'" :value="item.qty">
                            <input type="hidden" :name="'items['+i+'][discount]'" value="0">
                            <input type="hidden" :name="'items['+i+'][total]'" :value="item.price*item.qty">
                            <button type="button" @click="cart.splice(i,1); calc()" class="p-1 text-gray-400 hover:text-red-500">✕</button>
                        </div>
                    </template>
                    <p x-show="!cart.length" class="text-xs text-gray-400 py-2">Search and add new products.</p>
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
function manualExchange() {
    return {
        retCart: [], cart: [], retResults: [], newResults: [],
        paid: 0, returnCredit: 0, newSubtotal: 0, netTotal: 0, t: null,
        calc() {
            this.returnCredit = this.retCart.reduce((s, r) => s + ((r.price||0) * (r.qty||0)), 0);
            this.newSubtotal  = this.cart.reduce((s, i) => s + ((i.price||0) * (i.qty||0)), 0);
            this.netTotal     = Math.max(0, this.newSubtotal - this.returnCredit);
        },
        async search(q, which) {
            clearTimeout(this.t);
            if (q.length < 2) { which === 'ret' ? this.retResults = [] : this.newResults = []; return; }
            this.t = setTimeout(async () => {
                const res = await fetch(`/api/products/search?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                if (which === 'ret') this.retResults = data; else this.newResults = data;
            }, 300);
        },
        addRet(p) { this.retCart.push({ product_id: p.id, name: p.name, price: parseFloat(p.price), qty: 1 }); this.retResults = []; this.calc(); },
        addNew(p) {
            const ex = this.cart.find(i => i.product_id === p.id);
            if (ex) ex.qty++;
            else this.cart.push({ product_id: p.id, name: p.name, price: parseFloat(p.price), cost_price: parseFloat(p.cost_price||0), qty: 1 });
            this.newResults = []; this.calc();
        },
    };
}
</script>
@endpush
@endsection
