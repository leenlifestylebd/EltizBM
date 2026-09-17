@extends('layouts.app')
@section('title', 'Production Rates')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Production Rates</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Edit rates for new assignments. Existing work keeps its original rate.</p>
    </div>
    <button onclick="document.getElementById('addRateModal').classList.remove('hidden')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
        + Add Rate
    </button>
</div>

@if($errors->any())
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
    @foreach($errors->all() as $error)
        <p>{{ $error }}</p>
    @endforeach
</div>
@endif

{{-- Rates Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Product</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Stage</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Variant</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Color</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Size</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Rate/Unit</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Unit</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Status</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($rates as $rate)
                @php
                    $stageBadge = match($rate->stage ?? 'sewing') {
                        'ironing' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        'folding' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                        'packing' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                        default   => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
                    };
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $rate->product_name }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stageBadge }}">
                            {{ ucfirst($rate->stage ?? 'sewing') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ $rate->variant_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ $rate->color ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ $rate->size ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-bold text-primary-600 dark:text-primary-400">৳{{ number_format($rate->rate, 2) }}</td>
                    <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $rate->unit }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $rate->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $rate->is_active ? 'Active' : 'Archived' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($rate->is_active)
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" id="edit-rate-{{ $rate->id }}"
                                    data-url="{{ route('production.rates.update', $rate) }}"
                                    data-id="{{ $rate->id }}" data-rate="{{ $rate->rate }}"
                                    data-product="{{ $rate->full_name }} ({{ ucfirst($rate->stage ?? 'sewing') }})"
                                    data-unit="{{ $rate->unit }}" onclick="openRateEditor(this)"
                                    class="px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-lg">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('production.rates.destroy', $rate) }}"
                                  onsubmit="return confirm('Archive this rate? Existing work and earnings will be preserved.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1.5 text-xs text-gray-500 hover:text-red-600 rounded-lg">Archive</button>
                            </form>
                        </div>
                        @else
                        <span class="text-xs text-gray-400">Saved for existing work</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-12 text-center text-sm text-gray-400">No rates yet. Add your first rate!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Edit only the amount; product details remain attached to the original work. --}}
<dialog id="editRateModal" aria-labelledby="editRateTitle"
        class="w-full max-w-md rounded-2xl p-0 bg-white dark:bg-slate-800 text-gray-900 dark:text-white backdrop:bg-black/50">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
        <h3 id="editRateTitle" class="text-sm font-semibold">Edit Production Rate</h3>
        <button type="button" onclick="document.getElementById('editRateModal').close()" aria-label="Close"
                class="text-gray-400 hover:text-gray-600 text-xl">×</button>
    </div>
    <form id="editRateForm" method="POST" class="p-5 space-y-4">
        @csrf @method('PUT')
        <input type="hidden" name="_editing_rate_id" id="editingRateId">
        <p id="editingRateProduct" class="text-sm font-medium"></p>
        <p class="text-xs text-gray-500 dark:text-slate-400">The new rate applies to new assignments only. Previously assigned work and earnings keep the old rate.</p>
        <div>
            <label for="editingRateAmount" class="block text-xs font-medium mb-1.5">New rate (৳ / <span id="editingRateUnit"></span>)</label>
            <input id="editingRateAmount" type="number" name="rate" required min="0" max="9999999999.99" step="0.01"
                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        @error('rate')
        <p class="text-sm text-red-600" role="alert">{{ $message }}</p>
        @enderror
        <div class="flex gap-3">
            <button type="button" onclick="document.getElementById('editRateModal').close()"
                    class="flex-1 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg">Cancel</button>
            <button type="submit" class="flex-1 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">Save Rate</button>
        </div>
    </form>
</dialog>

{{-- Add Rate Modal --}}
<div id="addRateModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Add Production Rate</h3>
            <button onclick="document.getElementById('addRateModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
        </div>
        <form method="POST" action="{{ route('production.rates.store') }}" class="p-5 space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Stage <span class="text-red-500">*</span></label>
                    <select name="stage" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="cutting">Cutting (কাটিং)</option>
                        <option value="sewing">Sewing (সেলাই)</option>
                        <option value="ironing">Ironing (ইস্ত্রি)</option>
                        <option value="folding">Folding (ভাঁজ)</option>
                        <option value="packing">Packing (প্যাকিং)</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Product Name <span class="text-red-500">*</span></label>
                    <input type="text" name="product_name" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="e.g. Shirt, Pant">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Variant</label>
                    <input type="text" name="variant_name"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="e.g. Full Sleeve">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Color</label>
                    <input type="text" name="color"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="e.g. White, Red">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Size</label>
                    <input type="text" name="size"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="e.g. S, M, L, XL">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Rate (৳) <span class="text-red-500">*</span></label>
                    <input type="number" name="rate" required min="0" step="0.01"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Unit</label>
                    <select name="unit" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="piece">Piece</option>
                        <option value="dozen">Dozen</option>
                        <option value="set">Set</option>
                        <option value="meter">Meter</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Add Rate
            </button>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
function openRateEditor(button) {
    document.getElementById('editRateForm').action = button.dataset.url;
    document.getElementById('editingRateId').value = button.dataset.id;
    document.getElementById('editingRateProduct').textContent = button.dataset.product;
    document.getElementById('editingRateUnit').textContent = button.dataset.unit;
    document.getElementById('editingRateAmount').value = button.dataset.rate;
    document.getElementById('editRateModal').showModal();
    document.getElementById('editingRateAmount').focus();
    document.getElementById('editingRateAmount').select();
}
@if($errors->has('rate') && old('_editing_rate_id'))
const failedRateButton = document.getElementById('edit-rate-' + {{ Illuminate\Support\Js::from(old('_editing_rate_id')) }});
if (failedRateButton) {
    openRateEditor(failedRateButton);
    document.getElementById('editingRateAmount').value = {{ Illuminate\Support\Js::from(old('rate')) }};
}
@endif
</script>
@endpush
