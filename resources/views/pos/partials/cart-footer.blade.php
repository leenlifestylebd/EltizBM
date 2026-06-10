{{-- FILE: resources/views/pos/partials/cart-footer.blade.php --}}

{{-- Totals --}}
<div class="space-y-1.5">
    <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400">
        <span>Subtotal</span>
        <span x-text="'৳' + subtotal.toLocaleString()"></span>
    </div>
    <div x-show="discount > 0" class="flex justify-between text-xs text-red-500 dark:text-red-400">
        <span>Discount</span>
        <span x-text="'-৳' + discount.toLocaleString()"></span>
    </div>
    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
        <span>Extra Discount</span>
        <div class="flex items-center gap-1">
            <span class="text-gray-400">৳</span>
            <input type="number" x-model.number="extraDiscount" @input="calcTotals()"
                   min="0" class="w-16 text-xs px-1.5 py-1 border border-gray-200 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 text-right">
        </div>
    </div>
    <div class="flex justify-between font-bold text-base text-gray-900 dark:text-white border-t border-gray-100 dark:border-slate-700 pt-2">
        <span>Total</span>
        <span x-text="'৳' + total.toLocaleString()"></span>
    </div>
</div>

{{-- Payment method quick select --}}
<div class="grid grid-cols-3 gap-1.5">
    @foreach([
        ['value'=>'cash',  'label'=>'Cash'],
        ['value'=>'bkash', 'label'=>'bKash'],
        ['value'=>'nagad', 'label'=>'Nagad'],
        ['value'=>'card',  'label'=>'Card'],
        ['value'=>'rocket','label'=>'Rocket'],
        ['value'=>'other', 'label'=>'Other'],
    ] as $pm)
    <button type="button" @click="paymentMethod = '{{ $pm['value'] }}'"
            :class="paymentMethod === '{{ $pm['value'] }}' ? 'bg-primary-600 text-white' : 'bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-600'"
            class="py-2 rounded-lg text-xs font-medium transition-colors text-center">
        {{ $pm['label'] }}
    </button>
    @endforeach
</div>

{{-- Cash input for cash payment --}}
<div x-show="paymentMethod === 'cash'" class="space-y-2">
    <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">৳</span>
        <input type="number" x-model.number="cashReceived"
               class="w-full pl-7 pr-3 py-2.5 text-base font-bold border border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 text-right"
               :placeholder="total">
    </div>
    <div x-show="cashReceived >= total && cashReceived > 0"
         class="flex justify-between items-center p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
        <span class="text-xs font-medium text-green-700 dark:text-green-400">Change</span>
        <span class="text-sm font-bold text-green-700 dark:text-green-400"
              x-text="'৳' + Math.max(0, cashReceived - total).toLocaleString()"></span>
    </div>
</div>

{{-- Checkout Button --}}
<button @click="openCheckout()"
        :disabled="cart.length === 0"
        :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'"
        class="w-full py-3.5 bg-green-600 text-white font-bold text-sm rounded-xl transition-colors flex items-center justify-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    <span x-text="cart.length === 0 ? 'Add items to cart' : 'Charge ৳' + total.toLocaleString()"></span>
</button>

{{-- Hold / Recent Sales links --}}
<div class="flex justify-between text-xs text-gray-400 dark:text-slate-500">
    <button @click="clearCart()" x-show="cart.length > 0" class="hover:text-red-500 transition-colors">🗑 Clear cart</button>
    <a href="{{ route('orders.index') }}?source=pos" class="hover:text-primary-600 transition-colors">📋 Recent sales</a>
</div>
