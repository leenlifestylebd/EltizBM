{{-- ============================================================
     FILE: resources/views/inventory/purchase-orders.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Purchase Orders')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Purchase Orders</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $purchaseOrders->total() }} orders</p>
    </div>
    <button @click="$dispatch('open-po-modal')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
            x-data>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New PO
    </button>
</div>

{{-- PO List --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-4">
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">PO Number</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Vendor</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Total</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Paid</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Status</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Expected</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($purchaseOrders as $po)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3 font-semibold text-primary-600 dark:text-primary-400 text-xs">{{ $po->po_number }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-300">{{ $po->vendor?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">৳{{ number_format($po->total) }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-slate-300">৳{{ number_format($po->paid_amount) }}</td>
                    <td class="px-4 py-3 text-center">
                        @php $colors=['draft'=>'gray','ordered'=>'blue','received'=>'green','partial'=>'yellow','cancelled'=>'red']; $c=$colors[$po->status]??'gray'; @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700 dark:bg-{{ $c }}-900/30 dark:text-{{ $c }}-400">{{ ucfirst($po->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-slate-400">
                        {{ $po->expected_date ? \Carbon\Carbon::parse($po->expected_date)->format('d M Y') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('inventory.purchase-orders') }}?view={{ $po->id }}"
                               class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if($po->status !== 'received')
                            <form method="POST" action="{{ route('inventory.purchase-orders.store') }}">
                                @csrf
                                <input type="hidden" name="action" value="mark_received">
                                <input type="hidden" name="po_id" value="{{ $po->id }}">
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg" title="Mark Received">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No purchase orders yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($purchaseOrders as $po)
        <div class="p-4">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $po->po_number }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $po->vendor?->name ?? 'No vendor' }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-900 dark:text-white">৳{{ number_format($po->total) }}</p>
                    @php $c=$colors[$po->status]??'gray'; @endphp
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700 mt-0.5">{{ ucfirst($po->status) }}</span>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-sm text-gray-400">No purchase orders yet</div>
        @endforelse
    </div>

    @if($purchaseOrders->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $purchaseOrders->withQueryString()->links() }}</div>
    @endif
</div>

{{-- New PO Modal --}}
<div x-data="{ open: false }" @open-po-modal.window="open = true">
    <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
        <div @click.outside="open = false" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">New Purchase Order</h3>
                <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <form method="POST" action="{{ route('inventory.purchase-orders.store') }}" class="p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Vendor</label>
                        <select name="vendor_id" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">Select vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Expected Date</label>
                        <input type="date" name="expected_date"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>

                {{-- Items --}}
                <div x-data="poForm()">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-medium text-gray-700 dark:text-slate-300">Items</label>
                        <button type="button" @click="addItem()" class="text-xs text-primary-600 hover:underline">+ Add Item</button>
                    </div>
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="grid grid-cols-12 gap-1.5 mb-2">
                            <div class="col-span-5">
                                <select :name="'items['+idx+'][product_id]'"
                                        class="w-full px-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                    <option value="">Product</option>
                                    @foreach($allProducts as $p)
                                    <option value="{{ $p->id }}" :selected="item.product_id == {{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input type="number" :name="'items['+idx+'][quantity]'" x-model.number="item.qty" @input="calcTotal()" min="1"
                                       placeholder="Qty" class="w-full px-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 text-center">
                            </div>
                            <div class="col-span-3">
                                <div class="relative">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">৳</span>
                                    <input type="number" :name="'items['+idx+'][cost_price]'" x-model.number="item.price" @input="calcTotal()" min="0" step="0.01"
                                           placeholder="Price" class="w-full pl-5 pr-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                                </div>
                            </div>
                            <div class="col-span-1 flex items-center">
                                <input type="hidden" :name="'items['+idx+'][total]'" :value="(item.qty * item.price).toFixed(2)">
                                <button type="button" @click="items.splice(idx,1); calcTotal()" class="w-full text-gray-400 hover:text-red-500 text-lg leading-none">×</button>
                            </div>
                        </div>
                    </template>
                    <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-white mt-2 px-1">
                        <span>Total</span>
                        <span x-text="'৳' + total.toLocaleString()"></span>
                        <input type="hidden" name="total" :value="total">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none" placeholder="Optional..."></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="submit" name="status" value="draft"
                            class="flex-1 py-2.5 border border-gray-200 dark:border-slate-600 text-sm font-medium text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Save as Draft
                    </button>
                    <button type="submit" name="status" value="ordered"
                            class="flex-1 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Place Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function poForm() {
    return {
        items: [{ product_id: '', qty: 1, price: 0 }],
        total: 0,
        addItem() { this.items.push({ product_id: '', qty: 1, price: 0 }); },
        calcTotal() { this.total = this.items.reduce((s, i) => s + (i.qty * i.price), 0); },
    }
}
</script>
@endpush
