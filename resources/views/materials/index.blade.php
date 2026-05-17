@extends('layouts.app')
@section('title', 'Material Purchases')
@section('content')

@php
$catColors = [
    'fabric'     => 'blue',
    'accessory'  => 'orange',
    'packaging'  => 'gray',
    'embroidery' => 'pink',
    'marketing'  => 'purple',
    'logistics'  => 'indigo',
    'other'      => 'slate',
];
@endphp

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Material Purchases</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
            {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }} — Raw materials, accessories, marketing
        </p>
    </div>
    {{-- Export --}}
    <div class="flex items-center gap-2" x-data="{ open: false }" @click.outside="open = false">
        <button @click="open = !open"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
            <svg class="w-3 h-3 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition
             class="absolute right-4 mt-24 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
            @php $qs = http_build_query(['month'=>$month,'category'=>$cat]); @endphp
            <a href="{{ route('materials.export') }}?format=csv&{{ $qs }}"
               class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('materials.export') }}?format=pdf&{{ $qs }}"
               class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-400">
    ✓ {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- LEFT: Add Form --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Add Purchase</h3>
        <form method="POST" action="{{ route('materials.store') }}" class="space-y-3" x-data="{ hasQtyRate: false }">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Item Name <span class="text-red-500">*</span></label>
                <input type="text" name="item_name" required value="{{ old('item_name') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="e.g. Fabric, Zipper, Meta Ads...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Category <span class="text-red-500">*</span></label>
                <select name="category" required
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                <input type="date" name="purchase_date" required value="{{ old('purchase_date', today()->format('Y-m-d')) }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            {{-- Amount OR Qty × Rate --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300">Amount</label>
                    <button type="button" @click="hasQtyRate = !hasQtyRate"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                        <span x-text="hasQtyRate ? '− Simple Amount' : '+ Qty × Rate'"></span>
                    </button>
                </div>
                <div x-show="!hasQtyRate">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">৳</span>
                        <input type="number" name="amount" min="0" step="0.01" value="{{ old('amount') }}"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Total amount">
                    </div>
                </div>
                <div x-show="hasQtyRate" x-cloak class="grid grid-cols-2 gap-2">
                    <div>
                        <input type="number" name="quantity" min="0" step="0.01" value="{{ old('quantity') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Quantity">
                    </div>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">৳</span>
                        <input type="number" name="rate" min="0" step="0.01" value="{{ old('rate') }}"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Rate/unit">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Shop / Supplier</label>
                <input type="text" name="shop_name" value="{{ old('shop_name') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Shop name (optional)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Color</label>
                <input type="text" name="color" value="{{ old('color') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="For fabric (optional)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Payment Method</label>
                <select name="payment_method"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(['cash','bkash','nagad','rocket','bank','card','other'] as $m)
                    <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Optional notes">
            </div>
            @if($errors->any())
            <p class="text-xs text-red-500">{{ $errors->first() }}</p>
            @endif
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Add Purchase
            </button>
        </form>
    </div>

    {{-- RIGHT: List + Summary --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Filter bar --}}
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="month" name="month" value="{{ $month }}"
                   class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <select name="category"
                    class="flex-1 text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Categories</option>
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ $cat === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit"
                    class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm transition-colors">Filter</button>
        </form>

        {{-- Category Breakdown --}}
        @if($byCategory->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @php $maxCat = $byCategory->max('amount') ?: 1; @endphp
            @foreach($byCategory as $catKey => $catData)
            @php $color = $catColors[$catKey] ?? 'gray'; @endphp
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-medium text-gray-700 dark:text-slate-300 truncate">
                        {{ $categories[$catKey] ?? ucfirst($catKey) }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $catData['count'] }}</span>
                </div>
                <p class="text-sm font-bold text-gray-900 dark:text-white">৳{{ number_format($catData['amount']) }}</p>
                <div class="mt-1.5 w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full bg-{{ $color }}-500"
                         style="width:{{ min(100, ($catData['amount']/$maxCat)*100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Purchase List (Table 6 style) --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Purchase List</h3>
                <span class="text-xs font-bold text-gray-900 dark:text-white">Total: ৳{{ number_format($totalAmount) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Item</th>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Shop</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden md:table-cell">Color</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden md:table-cell">Qty</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden md:table-cell">Rate</th>
                            <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">T-Cost</th>
                            <th class="px-2 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @forelse($purchases as $p)
                        @php $color = $catColors[$p->category] ?? 'gray'; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $p->purchase_date->format('d/M/y') }}
                            </td>
                            <td class="px-4 py-2.5">
                                <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $p->item_name }}</p>
                                <span class="inline-flex items-center px-1.5 py-0.5 text-xs rounded-full bg-{{ $color }}-50 dark:bg-{{ $color }}-900/30 text-{{ $color }}-700 dark:text-{{ $color }}-400">
                                    {{ $p->category_label }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-slate-400 hidden sm:table-cell">{{ $p->shop_name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center text-xs text-gray-500 dark:text-slate-400 hidden md:table-cell">{{ $p->color ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center text-xs text-gray-600 dark:text-slate-300 hidden md:table-cell">
                                {{ $p->quantity ? number_format($p->quantity, 0) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-center text-xs text-gray-600 dark:text-slate-300 hidden md:table-cell">
                                {{ $p->rate ? '৳'.number_format($p->rate, 0) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-900 dark:text-white">
                                ৳{{ number_format($p->amount) }}
                            </td>
                            <td class="px-2 py-2.5">
                                <form method="POST" action="{{ route('materials.destroy', $p) }}"
                                      onsubmit="return confirm('Delete this entry?')"
                                      class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1 text-gray-300 hover:text-red-500 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                                No purchases for this period
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($purchases->isNotEmpty())
                    <tfoot class="bg-gray-50 dark:bg-slate-700/50 border-t-2 border-gray-200 dark:border-slate-600">
                        <tr>
                            <td colspan="6" class="px-4 py-2.5 font-bold text-gray-900 dark:text-white text-sm hidden md:table-cell">Total</td>
                            <td colspan="2" class="px-4 py-2.5 font-bold text-gray-900 dark:text-white text-sm md:hidden">Total</td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-900 dark:text-white text-base">৳{{ number_format($totalAmount) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
