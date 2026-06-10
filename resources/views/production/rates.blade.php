@extends('layouts.app')
@section('title', 'Production Rates')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Production Rates</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Set rates for each product/variant</p>
    </div>
    <button onclick="document.getElementById('addRateModal').classList.remove('hidden')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
        + Add Rate
    </button>
</div>

{{-- Rates Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Product</th>
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
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $rate->product_name }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ $rate->variant_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ $rate->color ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ $rate->size ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-bold text-primary-600 dark:text-primary-400">৳{{ number_format($rate->rate) }}</td>
                    <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $rate->unit }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $rate->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $rate->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <form method="POST" action="{{ route('production.rates.destroy', $rate) }}" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-gray-400">No rates yet. Add your first rate!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

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