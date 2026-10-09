@extends('layouts.app')
@section('title', 'Assign Work')

@section('content')
@php
    $stages = ['cutting' => 'কাটিং', 'sewing' => 'সেলাই', 'ironing' => 'ইস্ত্রি', 'folding' => 'ভাঁজ', 'packing' => 'প্যাকিং'];
    $formState = [
        'stage' => old('stage', $sourceTask ? 'ironing' : 'sewing'),
        'employeeId' => (string) old('employee_id', ''),
        'rateId' => (string) old('production_rate_id', ''),
        'productName' => old('product_name', $sourceTask?->product_name ?? ''),
        'color' => old('color', $sourceTask?->color ?? ''),
        'size' => old('size', $sourceTask?->size ?? ''),
        'quantity' => old('assigned_quantity', ''),
        'deadline' => old('deadline', ''),
        'assignedDate' => old('assigned_date', today()->toDateString()),
    ];
    $workerOptions = $employees->map(fn ($employee) => $employee->only(['id', 'name', 'designation']))->values();
    $rateOptions = $rates->map(fn ($rate) => $rate->only(['id', 'stage', 'product_name', 'variant_name', 'color', 'size', 'rate', 'unit']))->values();
    $fieldClass = 'w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500';
@endphp

<div class="flex items-center justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">কাজ দিন <span class="text-sm font-normal text-gray-500 dark:text-slate-400">/ Assign Work</span></h2>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">কর্মী বাছুন, কাজের বিবরণ ও পরিমাণ দিন।</p>
    </div>
    <a href="{{ route('production.tasks.index') }}" class="shrink-0 px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300">কাজের তালিকা</a>
</div>

@if($errors->any())
<div role="alert" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
    <p class="text-sm font-semibold text-red-700 dark:text-red-300 mb-1">চিহ্নিত তথ্যগুলো ঠিক করে আবার জমা দিন।</p>
    @foreach($errors->all() as $error)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endforeach
</div>
@endif

@if($sourceTask)
<div class="mb-4 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl text-sm text-orange-800 dark:text-orange-300">
    <strong>Sewing Task #{{ $sourceTask->id }} থেকে কাজ দেওয়া হচ্ছে</strong>
    <p class="mt-1">{{ $sourceTask->product_name }} · দেওয়া যাবে {{ $sourceTask->forwardable_quantity }} পিস (সম্পন্ন {{ $sourceTask->completed_quantity }}, আগে দেওয়া {{ $sourceTask->forwardedQuantity() }})</p>
</div>
@endif

<form method="POST" action="{{ route('production.tasks.store') }}" x-data="taskCreate()" class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">
    @csrf
    @if($sourceTask)
        <input type="hidden" name="source_task_id" value="{{ $sourceTask->id }}">
    @endif

    <div class="xl:col-span-2 space-y-4 min-w-0">
        <section class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4 sm:p-5 space-y-4" aria-labelledby="worker-heading">
            <h3 id="worker-heading" class="text-sm font-semibold text-gray-900 dark:text-white"><span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400">১</span>কাকে, কোন কাজ দেবেন?</h3>
            <fieldset>
                <legend class="text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">কাজের ধাপ / Stage <span class="text-red-500">*</span></legend>
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                    @foreach($stages as $value => $label)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="stage" value="{{ $value }}" x-model="stage" @change="changeStage()" @checked($formState['stage'] === $value) class="peer sr-only">
                        <span class="flex flex-col items-center rounded-lg border px-2 py-2.5 text-sm peer-focus-visible:ring-2 peer-focus-visible:ring-primary-500 peer-focus-visible:ring-offset-2"
                              :class="stage === '{{ $value }}' ? 'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'border-gray-200 text-gray-600 dark:border-slate-600 dark:text-slate-300'">
                            <span class="font-medium">{{ $label }}</span><span class="text-xs mt-0.5">{{ ucfirst($value) }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
            </fieldset>
            <div>
                <label for="employee_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">কর্মী / Worker <span class="text-red-500">*</span></label>
                @if($employees->count() > 8)
                <input type="search" x-model="workerSearch" aria-label="কর্মীর নাম খুঁজুন" placeholder="কর্মীর নাম খুঁজুন…" class="{{ $fieldClass }} mb-2">
                @endif
                <select id="employee_id" name="employee_id" x-model="employeeId" required class="{{ $fieldClass }}" @error('employee_id') aria-invalid="true" @enderror>
                    <option value="">কর্মী নির্বাচন করুন</option>
                    <template x-for="worker in filteredWorkers" :key="worker.id">
                        <option :value="String(worker.id)" x-text="worker.name + (worker.designation ? ' — ' + worker.designation : '')"></option>
                    </template>
                </select>
                <p x-cloak x-show="workerSearch && filteredWorkers.length === 0" class="mt-2 text-sm text-gray-500">এই নামে কোনো কর্মী পাওয়া যায়নি।</p>
                @error('employee_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                @if($employees->isEmpty())
                <p class="mt-2 text-sm text-amber-700 dark:text-amber-400">কাজ দেওয়ার জন্য আগে একজন সক্রিয় কর্মী যোগ করুন।</p>
                @endif
            </div>
        </section>

        <section class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4 sm:p-5 space-y-4" aria-labelledby="work-heading">
            <h3 id="work-heading" class="text-sm font-semibold text-gray-900 dark:text-white"><span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400">২</span>কী কাজ দেবেন?</h3>
            <div>
                <label for="production_rate_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">পণ্যের রেট / Production Rate <span class="font-normal text-gray-500 dark:text-slate-400">(ঐচ্ছিক)</span></label>
                <select id="production_rate_id" name="production_rate_id" x-model="rateId" @change="fillFromRate()" class="{{ $fieldClass }}" aria-describedby="rate-help" @error('production_rate_id') aria-invalid="true" @enderror>
                    <option value="">রেট ছাড়া কাজ দিন — পণ্যের নাম নিজে লিখুন</option>
                    <template x-for="rate in filteredRates" :key="rate.id">
                        <option :value="String(rate.id)" x-text="rateLabel(rate)"></option>
                    </template>
                </select>
                <p id="rate-help" class="mt-1.5 text-xs text-gray-500 dark:text-slate-400">রেট বাছলে পণ্যের নাম, রং ও সাইজ নিজে থেকে বসবে।</p>
                <p x-cloak x-show="filteredRates.length === 0" class="text-sm text-amber-700 dark:text-amber-400 mt-2">এই ধাপে রেট নেই। পণ্যের নাম লিখে রেট ছাড়াও কাজ দিতে পারবেন।</p>
                @error('production_rate_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="product_name" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">পণ্যের নাম / Product <span class="text-red-500">*</span></label>
                <input id="product_name" type="text" name="product_name" x-model="productName" value="{{ $formState['productName'] }}" required maxlength="255" placeholder="যেমন: পাঞ্জাবি, শার্ট" class="{{ $fieldClass }}" @error('product_name') aria-invalid="true" @enderror>
                @error('product_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">রং / Color</label>
                    <input id="color" type="text" name="color" x-model="color" value="{{ $formState['color'] }}" placeholder="যেমন: সাদা" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="size" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">সাইজ / Size</label>
                    <input id="size" type="text" name="size" x-model="size" value="{{ $formState['size'] }}" placeholder="যেমন: M, L, 50/21" class="{{ $fieldClass }}">
                </div>
            </div>
        </section>

        <section class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4 sm:p-5 space-y-4" aria-labelledby="quantity-heading">
            <h3 id="quantity-heading" class="text-sm font-semibold text-gray-900 dark:text-white"><span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400">৩</span>কত পিস, কবে লাগবে?</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="assigned_quantity" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">পরিমাণ / Quantity <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input id="assigned_quantity" type="number" name="assigned_quantity" x-model="quantity" value="{{ $formState['quantity'] }}" min="1" step="1" @if($sourceTask) max="{{ $sourceTask->forwardable_quantity }}" @endif required placeholder="0" class="{{ $fieldClass }} pr-14" @error('assigned_quantity') aria-invalid="true" @enderror>
                        <span class="absolute right-4 top-3 text-sm text-gray-500 pointer-events-none">পিস</span>
                    </div>
                    @if($sourceTask)<p class="mt-1 text-xs text-gray-500">সর্বোচ্চ {{ $sourceTask->forwardable_quantity }} পিস</p>@endif
                    @error('assigned_quantity') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="deadline" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">শেষ তারিখ / Deadline <span class="font-normal text-gray-500 dark:text-slate-400">(ঐচ্ছিক)</span></label>
                    <input id="deadline" type="date" name="deadline" x-model="deadline" value="{{ $formState['deadline'] }}" min="{{ today()->toDateString() }}" class="{{ $fieldClass }}" @error('deadline') aria-invalid="true" @enderror>
                    @error('deadline') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <details @if(old('notes') || old('assigned_date') || $errors->hasAny(['notes', 'assigned_date'])) open @endif>
                <summary class="cursor-pointer text-sm text-primary-700 dark:text-primary-400 py-1">আগের তারিখ / বিশেষ নির্দেশনা (ঐচ্ছিক)</summary>
                <div class="mt-3 mb-3">
                    <label for="assigned_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">কাজ দেওয়ার তারিখ / Assign Date</label>
                    <input id="assigned_date" type="date" name="assigned_date" x-model="assignedDate" value="{{ $formState['assignedDate'] }}" max="{{ today()->toDateString() }}" class="{{ $fieldClass }}" @error('assigned_date') aria-invalid="true" @enderror>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-slate-400">আজকের তারিখ দেওয়া আছে। আগের দিনে কাজ দিয়ে থাকলে বদলে নিন।</p>
                    @error('assigned_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <label for="notes" class="sr-only">বিশেষ নির্দেশনা / Notes</label>
                <textarea id="notes" name="notes" rows="2" placeholder="কাজের জন্য কোনো বিশেষ নির্দেশনা থাকলে লিখুন…" class="{{ $fieldClass }} mt-2">{{ old('notes') }}</textarea>
            </details>
        </section>
    </div>

    <aside class="xl:sticky xl:top-20 bg-white dark:bg-slate-800 rounded-xl border border-primary-200 dark:border-slate-600 p-4 sm:p-5 min-w-0" aria-labelledby="summary-heading">
        <h3 id="summary-heading" class="text-sm font-semibold text-gray-900 dark:text-white">কাজের সারাংশ</h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">জমা দেওয়ার আগে মিলিয়ে নিন</p>
        <dl class="mt-5 space-y-3 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-slate-400">কর্মী</dt><dd class="text-right font-medium text-gray-900 dark:text-white break-words min-w-0" x-text="selectedWorker?.name || 'নির্বাচন করুন'"></dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-slate-400">কাজের ধাপ</dt><dd class="font-medium text-gray-900 dark:text-white" x-text="stageLabels[stage]"></dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-slate-400">পণ্য</dt><dd class="text-right font-medium text-gray-900 dark:text-white break-words min-w-0" x-text="[productName, color, size].filter(Boolean).join(' · ') || 'পণ্যের নাম লিখুন'"></dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-slate-400">পরিমাণ</dt><dd class="font-medium text-gray-900 dark:text-white" x-text="quantity ? quantity + ' পিস' : '—'"></dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-slate-400">শেষ তারিখ</dt><dd class="font-medium text-gray-900 dark:text-white" x-text="deadline || 'নির্ধারিত নয়'"></dd></div>
        </dl>
        <p x-cloak x-show="assignedDate && assignedDate !== today" class="mt-3 text-xs text-gray-500 dark:text-slate-400" x-text="'কাজ দেওয়ার তারিখ: ' + assignedDate"></p>
        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-slate-700" aria-live="polite">
            <template x-if="selectedRate">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400" x-text="'রেট: ৳' + money(selectedRate.rate) + ' / ' + selectedRate.unit"></p>
                    <div class="flex flex-wrap items-center justify-between gap-2 mt-2"><span class="text-sm text-gray-600 dark:text-slate-300">সম্ভাব্য মোট</span><strong class="text-xl text-primary-700 dark:text-primary-400" x-text="estimatedAmount === null ? '—' : '৳' + money(estimatedAmount)"></strong></div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">সব পিস সম্পন্ন হলে এই রেটে হিসাব। জমা দেওয়া কাজ অনুযায়ী চূড়ান্ত পাওনা হবে।</p>
                </div>
            </template>
            <p x-show="!selectedRate" class="text-sm text-amber-700 dark:text-amber-400">রেট বাছা হয়নি। কাজ দিতে পারবেন; কাজ জমার সময় রেট না দিলে পাওনা ৳০ হবে।</p>
        </div>
        <button type="submit" @disabled($employees->isEmpty()) class="w-full mt-5 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">কাজ দিন / Assign Work</button>
        <a href="{{ route('production.tasks.index') }}" class="block text-center mt-3 py-2 text-sm text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-white">বাতিল করুন</a>
    </aside>
</form>
@endsection

@push('scripts')
<script>
function taskCreate() {
    const allRates = {{ Illuminate\Support\Js::from($rateOptions) }};
    const workers = {{ Illuminate\Support\Js::from($workerOptions) }};
    return {
        ...{{ Illuminate\Support\Js::from($formState) }},
        stageLabels: {{ Illuminate\Support\Js::from($stages) }},
        workerSearch: '',
        today: {{ Illuminate\Support\Js::from(today()->toDateString()) }},
        init() {
            // Dynamic options must exist before restoring a rejected form's selections.
            if (!this.selectedRate) this.rateId = '';
            this.$nextTick(() => {
                this.$el.querySelector('[name="employee_id"]').value = this.employeeId;
                this.$el.querySelector('[name="production_rate_id"]').value = this.rateId;
            });
        },
        get filteredWorkers() {
            const query = this.workerSearch.trim().toLocaleLowerCase();
            return workers.filter(worker => String(worker.id) === this.employeeId ||
                [worker.name, worker.designation].filter(Boolean).join(' ').toLocaleLowerCase().includes(query));
        },
        get selectedWorker() {
            return workers.find(worker => String(worker.id) === this.employeeId);
        },
        get filteredRates() {
            return allRates.filter(rate => (rate.stage || 'sewing') === this.stage);
        },
        get selectedRate() {
            return this.filteredRates.find(rate => String(rate.id) === this.rateId);
        },
        get estimatedAmount() {
            const quantity = Number(this.quantity);
            return this.selectedRate && Number.isInteger(quantity) && quantity > 0
                ? quantity * Number(this.selectedRate.rate) : null;
        },
        money(value) {
            return Number(value).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        rateLabel(rate) {
            return [rate.product_name, rate.variant_name, rate.color, rate.size].filter(Boolean).join(' · ')
                + ' — ৳' + this.money(rate.rate) + '/' + rate.unit;
        },
        changeStage() {
            // Never carry a different stage's rate into the next assignment.
            this.rateId = '';
        },
        fillFromRate() {
            const rate = this.selectedRate;
            if (!rate) return;
            this.productName = rate.product_name || '';
            this.color = rate.color || '';
            this.size = rate.size || '';
        },
    };
}
</script>
@endpush
