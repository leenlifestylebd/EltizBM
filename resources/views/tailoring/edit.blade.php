@extends('layouts.app')
@section('title', 'Edit Order – ' . $tailoring->order_number)

@section('content')

<div class="flex items-center gap-3 mb-4">
    <a href="{{ route('tailoring.show', $tailoring) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Edit Order</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5 font-mono">{{ $tailoring->order_number }}</p>
    </div>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-400">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('tailoring.update', $tailoring) }}" x-data="tailoringForm()">
@csrf @method('PUT')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- LEFT: Customer + Measurements --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Customer Info --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Customer Info
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Existing Customer <span class="text-gray-400 font-normal">(optional)</span></label>
                    <select name="customer_id" @change="selectCustomer($event.target)"
                            class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
                        <option value="">— Select or type manually —</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone ?? '' }}"
                                {{ $tailoring->customer_id == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}@if($c->phone) ({{ $c->phone }})@endif
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Customer Name <span class="text-red-400">*</span></label>
                    <input type="text" name="customer_name" x-model="customerName" required
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Phone</label>
                    <input type="text" name="customer_phone" x-model="customerPhone"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
                </div>
            </div>
        </div>

        {{-- Measurements --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">📏 Measurements <span class="text-xs font-normal text-gray-400">(inches)</span></h3>
            @php
                $measurements = [
                    'height'     => ['label' => 'Height',      'val' => $tailoring->height],
                    'body_fit'   => ['label' => 'Body Fit',    'val' => $tailoring->body_fit],
                    'body_loose' => ['label' => 'Body Loose',  'val' => $tailoring->body_loose],
                    'shoulder'   => ['label' => 'Shoulder',    'val' => $tailoring->shoulder],
                    'sleeve'     => ['label' => 'Sleeve',      'val' => $tailoring->sleeve],
                    'sleeve_open'=> ['label' => 'Sleeve Open', 'val' => $tailoring->sleeve_open],
                    'neck'       => ['label' => 'Neck',        'val' => $tailoring->neck],
                    'round_gher' => ['label' => 'Round Gher',  'val' => $tailoring->round_gher],
                ];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($measurements as $field => $m)
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ $m['label'] }}</label>
                    <input type="number" name="{{ $field }}" value="{{ old($field, $m['val']) }}" step="0.5" min="0" max="100"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
                </div>
                @endforeach
            </div>
            <div class="mt-3">
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Measurement Note</label>
                <textarea name="measurement_note" rows="2"
                          class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400 resize-none">{{ old('measurement_note', $tailoring->measurement_note) }}</textarea>
            </div>
        </div>

        {{-- Fabric & Design --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">🧵 Fabric & Design</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Fabric Type</label>
                    <input type="text" name="fabric_type" value="{{ old('fabric_type', $tailoring->fabric_type) }}"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Color</label>
                    <input type="text" name="color" value="{{ old('color', $tailoring->color) }}"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Design Note</label>
                    <textarea name="design_note" rows="3"
                              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400 resize-none">{{ old('design_note', $tailoring->design_note) }}</textarea>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT: Pricing + Delivery --}}
    <div class="space-y-4">

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">💰 Pricing</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Total Price <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">৳</span>
                        <input type="number" name="price" x-model.number="price" step="0.01" min="0" required
                               class="w-full pl-7 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Advance Paid</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">৳</span>
                        <input type="number" name="advance_paid" x-model.number="advance" step="0.01" min="0"
                               class="w-full pl-7 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
                    </div>
                </div>
                <div class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-slate-700/50 rounded-lg" x-show="price > 0 || advance > 0">
                    <span class="text-xs text-gray-500">Due Amount</span>
                    <span class="text-sm font-semibold" :class="due > 0 ? 'text-red-500' : 'text-green-500'">
                        ৳<span x-text="Math.max(0, due).toLocaleString()"></span>
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">📅 Delivery</h3>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Delivery Date</label>
                <input type="date" name="delivery_date" value="{{ old('delivery_date', $tailoring->delivery_date?->format('Y-m-d')) }}"
                       class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <button type="submit"
                    class="w-full px-4 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                Save Changes
            </button>
            <a href="{{ route('tailoring.show', $tailoring) }}"
               class="w-full px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors text-center">
                Cancel
            </a>
        </div>
    </div>

</div>
</form>

<script>
function tailoringForm() {
    return {
        customerName: '{{ old('customer_name', $tailoring->customer_name) }}',
        customerPhone: '{{ old('customer_phone', $tailoring->customer_phone) }}',
        price: {{ old('price', $tailoring->price) }},
        advance: {{ old('advance_paid', $tailoring->advance_paid) }},
        get due() { return this.price - this.advance; },
        selectCustomer(sel) {
            const opt = sel.options[sel.selectedIndex];
            if (opt.value) {
                this.customerName  = opt.dataset.name  || '';
                this.customerPhone = opt.dataset.phone || '';
            }
        }
    }
}
</script>

@endsection
