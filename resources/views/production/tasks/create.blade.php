@extends('layouts.app')
@section('title', 'Assign Task')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Assign Task</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Assign work to a production worker</p>
    </div>
    <a href="{{ route('production.tasks.index') }}"
       class="px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50">
        Back
    </a>
</div>

@if($errors->any())
<div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
    @foreach($errors->all() as $err)
    <p class="text-xs text-red-600 dark:text-red-400">{{ $err }}</p>
    @endforeach
</div>
@endif

@if($sourceTask)
<div class="mb-4 p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl flex items-start gap-2">
    <span class="text-orange-500 text-base">🔥</span>
    <div class="text-xs text-orange-700 dark:text-orange-300">
        <strong>Forwarding from Sewing Task #{{ $sourceTask->id }}</strong> —
        {{ $sourceTask->product_name }}{{ $sourceTask->color ? ', '.$sourceTask->color : '' }}{{ $sourceTask->size ? ', sz '.$sourceTask->size : '' }}.
        Forwardable: <strong>{{ $sourceTask->forwardable_quantity }} pieces</strong>
        ({{ $sourceTask->completed_quantity }} completed, {{ $sourceTask->forwardedQuantity() }} already forwarded).
    </div>
</div>
@endif

<div class="max-w-lg mx-auto" x-data="taskCreate()">
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
        <form method="POST" action="{{ route('production.tasks.store') }}" class="space-y-4">
            @csrf

            @if($sourceTask)
            <input type="hidden" name="source_task_id" value="{{ $sourceTask->id }}">
            @endif

            {{-- Stage --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Stage <span class="text-red-500">*</span></label>
                <select name="stage" x-model="stage"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="cutting">Cutting (কাটিং)</option>
                    <option value="sewing">Sewing (সেলাই)</option>
                    <option value="ironing">Ironing (ইস্ত্রি)</option>
                    <option value="folding">Folding (ভাঁজ)</option>
                    <option value="packing">Packing (প্যাকিং)</option>
                </select>
            </div>

            {{-- Worker --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Worker <span class="text-red-500">*</span></label>
                <select name="employee_id" required
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Worker</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }}{{ $emp->designation ? ' — '.$emp->designation : '' }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Production Rate (filtered by stage) --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                    Production Rate <span class="text-gray-400">(optional — থাকলে amount auto হবে)</span>
                </label>
                <select name="production_rate_id" @change="fillFromRate($event.target.value)"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">-- Rate ছাড়াই assign করুন --</option>
                    <template x-for="rate in filteredRates" :key="rate.id">
                        <option :value="rate.id"
                                :data-product="rate.product_name"
                                :data-color="rate.color"
                                :data-size="rate.size"
                                x-text="rate.product_name + (rate.size ? ' ['+rate.size+']' : '') + (rate.color ? ' ('+rate.color+')' : '') + (rate.variant_name ? ' — '+rate.variant_name : '') + ' — ৳' + rate.rate + '/' + rate.unit">
                        </option>
                    </template>
                </select>
                <p x-show="filteredRates.length === 0" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    এই stage-এ কোনো rate নেই —
                    <a href="{{ route('production.rates') }}" class="underline">এখানে যোগ করুন</a>
                    অথবা rate ছাড়াই assign করুন।
                </p>
            </div>

            {{-- Product Name --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Product Name <span class="text-red-500">*</span></label>
                <input type="text" name="product_name" x-model="productName"
                       value="{{ old('product_name', $sourceTask?->product_name) }}" required
                       placeholder="e.g. Panjabi, Shirt"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            {{-- Color + Size --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Color</label>
                    <input type="text" name="color" x-model="color"
                           value="{{ old('color', $sourceTask?->color) }}"
                           placeholder="e.g. White, Black"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Size</label>
                    <input type="text" name="size" x-model="size"
                           value="{{ old('size', $sourceTask?->size) }}"
                           placeholder="e.g. 50/21, M, L"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>

            {{-- Quantity --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                    Assigned Quantity <span class="text-red-500">*</span>
                    @if($sourceTask)
                    <span class="text-gray-400">(max {{ $sourceTask->forwardable_quantity }})</span>
                    @endif
                </label>
                <input type="number" name="assigned_quantity"
                       value="{{ old('assigned_quantity') }}"
                       min="1"
                       @if($sourceTask) max="{{ $sourceTask->forwardable_quantity }}" @endif
                       required
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            {{-- Assign Date (back-date supported) --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Assign Date</label>
                <input type="date" name="assigned_date" value="{{ old('assigned_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">আগের দিনে কাজ দেওয়া হলে সেই তারিখ দিন — না দিলে আজ ধরা হবে।</p>
            </div>

            {{-- Deadline --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Deadline <span class="text-gray-400">(optional)</span></label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" min="{{ today()->toDateString() }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Notes <span class="text-gray-400">(optional)</span></label>
                <textarea name="notes" rows="2" placeholder="Any special instructions..."
                          class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-primary-600 text-white font-medium py-2.5 text-sm rounded-lg hover:bg-primary-700">
                    Assign Task
                </button>
                <a href="{{ route('production.tasks.index') }}"
                   class="px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function taskCreate() {
    const allRates = @json($rates);
    return {
        stage: '{{ old('stage', $sourceTask ? 'ironing' : 'sewing') }}',
        productName: '{{ old('product_name', $sourceTask?->product_name) }}',
        color: '{{ old('color', $sourceTask?->color) }}',
        size: '{{ old('size', $sourceTask?->size) }}',

        get filteredRates() {
            return allRates.filter(r => (r.stage || 'sewing') === this.stage);
        },

        fillFromRate(rateId) {
            if (!rateId) return;
            const rate = allRates.find(r => r.id == rateId);
            if (rate) {
                this.productName = rate.product_name || '';
                this.color       = rate.color || '';
                this.size        = rate.size  || '';
            }
        }
    }
}
</script>
@endpush
