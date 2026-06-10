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

<div class="max-w-lg mx-auto" x-data="taskCreate()">
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
        <form method="POST" action="{{ route('production.tasks.store') }}" class="space-y-4">
            @csrf

            {{-- Worker --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Worker *</label>
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

            {{-- Production Rate (optional auto-fill) --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Production Rate * <span class="text-gray-400">(amount এখান থেকে calculate হবে)</span></label>
                <select name="production_rate_id" required @change="fillFromRate($event.target.value)"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">-- Rate Select করুন --</option>
                    @foreach($rates as $rate)
                    <option value="{{ $rate->id }}"
                            data-product="{{ $rate->product_name }}"
                            data-color="{{ $rate->color }}"
                            data-size="{{ $rate->size }}">
                        {{ $rate->product_name }}{{ $rate->size ? ' ['.$rate->size.']' : '' }}{{ $rate->color ? ' ('.$rate->color.')' : '' }} — ৳{{ number_format($rate->rate) }}/{{ $rate->unit }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Product Name --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Product Name *</label>
                <input type="text" name="product_name" x-model="productName" value="{{ old('product_name') }}" required
                       placeholder="e.g. Panjabi, Shirt"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            {{-- Color + Size --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Color</label>
                    <input type="text" name="color" x-model="color" value="{{ old('color') }}"
                           placeholder="e.g. White, Black"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Size</label>
                    <input type="text" name="size" x-model="size" value="{{ old('size') }}"
                           placeholder="e.g. 50/21, M, L"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>

            {{-- Quantity --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Assigned Quantity *</label>
                <input type="number" name="assigned_quantity" value="{{ old('assigned_quantity') }}" min="1" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
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
    const rates = @json($rates);
    return {
        productName: '{{ old('product_name') }}',
        color: '{{ old('color') }}',
        size: '{{ old('size') }}',
        fillFromRate(rateId) {
            if (!rateId) return;
            const rate = rates.find(r => r.id == rateId);
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
