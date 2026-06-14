@extends('layouts.app')
@section('title', 'Cutting List')

@section('content')

<style>
    .print-only { display: none; }
    @media print {
        body * { visibility: hidden; }
        #cutting-print, #cutting-print * { visibility: visible; }
        #cutting-print { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .print-exclude { display: none !important; }
        #cutting-print table { font-size: 12px; }
    }
</style>

{{-- Header --}}
<div class="flex items-center justify-between mb-4 no-print">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">✂️ Cutting List</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Tick the products you want, then print the cutting list</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('inventory.cutting-list.export', request()->only('search')) }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <button onclick="window.print()" type="button"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
    </div>
</div>

<script>
    function cuttingList() {
        return {
            q: '',
            sel: [],
            modalOpen: false,
            // Full list stays in memory so selection survives searching — search only
            // filters what's shown, never reloads the page or drops ticks.
            items: @json($products->map(fn($p) => ['id' => $p->id, 's' => strtolower($p->name . ' ' . $p->sku), 'short' => $p->shortfall])),
            matches(item) { const t = this.q.trim().toLowerCase(); return t === '' || item.s.includes(t); },
            visible(id) { const it = this.items.find(i => i.id === id); return it ? this.matches(it) : true; },
            filteredIds() { return this.items.filter(i => this.matches(i)).map(i => i.id); },
            noResults() { return this.q.trim() !== '' && this.filteredIds().length === 0; },
            has(id) { return this.sel.includes(id); },
            toggle(id) { const i = this.sel.indexOf(id); i > -1 ? this.sel.splice(i, 1) : this.sel.push(id); },
            get allOn() { const f = this.filteredIds(); return f.length > 0 && f.every(id => this.sel.includes(id)); },
            toggleAll() {
                const f = this.filteredIds();
                if (this.allOn) { this.sel = this.sel.filter(id => !f.includes(id)); }
                else { f.forEach(id => { if (!this.sel.includes(id)) this.sel.push(id); }); }
            },
            get count() { return this.sel.length; },
            shortOf(id) { const it = this.items.find(i => i.id === id); return it ? it.short : 0; },
            get total() { return this.sel.reduce((s, id) => s + this.shortOf(id), 0); }
        };
    }
</script>

<div x-data="cuttingList()">

{{-- Summary (live, reflects selection) --}}
<div class="grid grid-cols-2 gap-3 mb-4 no-print">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-gray-100 dark:border-slate-700 text-center">
        <p class="text-xl font-bold text-gray-900 dark:text-white"><span x-text="count"></span><span class="text-sm font-normal text-gray-400"> / {{ $totalProducts }}</span></p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Products selected</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-orange-100 dark:border-orange-900/30 text-center">
        <p class="text-xl font-bold text-orange-600 dark:text-orange-400" x-text="total.toLocaleString()"></p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Pieces to produce</p>
    </div>
</div>

{{-- Send to Cutting action --}}
@if($cuttingWorkers->count())
<div class="mb-4 no-print">
    <button type="button" @click="modalOpen = true" :disabled="count === 0"
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
        ✂️ Send to Cutting
        <span x-show="count > 0" class="bg-white/20 px-2 py-0.5 rounded-full text-xs" x-text="count + ' product' + (count > 1 ? 's' : '')"></span>
    </button>
    <p x-show="count === 0" class="text-xs text-center text-gray-400 dark:text-slate-500 mt-1">Tick products above to assign them for cutting</p>
</div>
@else
<div class="mb-4 no-print bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-3 text-xs text-amber-700 dark:text-amber-400">
    No active employees to assign cutting. <a href="{{ route('employees.index') }}" class="underline">Add an employee</a> first.
</div>
@endif

{{-- Search (live, no reload) + select-all toolbar --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4 no-print">
    <div class="flex gap-2 mb-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" x-model="q" placeholder="Search product or SKU..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <button type="button" x-show="q" @click="q = ''" class="px-3 py-2 text-sm text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Clear</button>
    </div>
    @if($products->count())
    <div class="flex items-center gap-3 text-xs">
        <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
            <input type="checkbox" :checked="allOn" @change="toggleAll()"
                   class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
            <span class="font-medium text-gray-700 dark:text-slate-200" x-text="q ? 'Select shown' : 'Select all'"></span>
        </label>
        <span class="text-gray-400">·</span>
        <button type="button" @click="sel = []" class="text-gray-500 dark:text-slate-400 hover:text-primary-600">Clear selection</button>
        <span class="text-gray-400 ml-auto"><span class="font-semibold text-primary-600" x-text="count"></span> selected</span>
    </div>
    @endif
</div>

{{-- Printable area --}}
<div id="cutting-print" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">

    {{-- Print-only header --}}
    <div class="print-only px-4 pt-4 pb-2">
        <h1 style="font-size:18px;font-weight:bold;">Cutting / Production List — {{ $business->name ?? '' }}</h1>
        <p style="font-size:12px;">Generated: {{ now()->format('d M Y, h:i A') }} · <span x-text="count"></span> products · <span x-text="total"></span> pieces to produce</p>
    </div>

    {{-- Desktop table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="no-print text-center px-3 py-3 w-10">
                        <input type="checkbox" :checked="allOn" @change="toggleAll()"
                               class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                    </th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Product</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">SKU</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Category</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">In Stock</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">Minimum</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-xs">To Produce</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors"
                    x-show="visible({{ $product->id }})"
                    :class="has({{ $product->id }}) ? '' : 'opacity-40 print-exclude'">
                    <td class="no-print text-center px-3 py-3">
                        <input type="checkbox" :checked="has({{ $product->id }})" @change="toggle({{ $product->id }})"
                               class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $product->name }}</td>
                    <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-slate-400">{{ $product->sku ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300">{{ $product->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold {{ $product->stock <= 0 ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">{{ $product->stock }}</span>
                        <span class="text-xs text-gray-400 dark:text-slate-500">{{ $product->unit }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-slate-300">{{ $product->low_stock_alert }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-sm font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">+{{ $product->shortfall }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                        🎉 All products are at or above their minimum stock — nothing to produce.
                    </td>
                </tr>
                @endforelse
                <tr x-show="noResults()" x-cloak>
                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No products match your search.</td>
                </tr>
            </tbody>
            @if($products->count())
            <tfoot>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-t-2 border-gray-200 dark:border-slate-600 font-semibold">
                    <td class="no-print"></td>
                    <td colspan="5" class="px-4 py-3 text-right text-gray-700 dark:text-slate-200">Total pieces to produce</td>
                    <td class="px-4 py-3 text-center text-orange-600 dark:text-orange-400" x-text="total.toLocaleString()"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($products as $product)
        <div class="p-4 flex items-center gap-3" x-show="visible({{ $product->id }})" :class="has({{ $product->id }}) ? '' : 'opacity-40 print-exclude'">
            <input type="checkbox" :checked="has({{ $product->id }})" @change="toggle({{ $product->id }})"
                   class="no-print w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500 flex-shrink-0">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product->name }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                    {{ $product->sku ?? '—' }} · stock {{ $product->stock }} / min {{ $product->low_stock_alert }}
                </p>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="inline-flex px-2.5 py-1 rounded-lg text-sm font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">+{{ $product->shortfall }}</span>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">to produce</p>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-sm text-gray-400 dark:text-slate-500">
            🎉 All products at or above minimum stock.
        </div>
        @endforelse
    </div>
</div>

{{-- Send to Cutting modal --}}
@if($cuttingWorkers->count())
<div x-show="modalOpen" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 no-print"
     style="background: rgba(0,0,0,0.5)">
    <div @click.outside="modalOpen = false"
         class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-xl w-full max-w-md">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">✂️ Send to Cutting</h3>
            <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('inventory.cutting-list.send') }}" class="p-5 space-y-4">
            @csrf
            <template x-for="id in sel" :key="id">
                <input type="hidden" name="product_ids[]" :value="id">
            </template>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-400">
                <span class="font-semibold" x-text="count"></span> product(s) ·
                <span class="font-semibold" x-text="total.toLocaleString()"></span> pieces to cut.
                Each product's cutting quantity = its shortfall (minimum − current stock).
            </div>

            {{-- Cutting worker --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Cutting Worker <span class="text-red-500">*</span></label>
                <select name="employee_id" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Select worker</option>
                    @foreach($cuttingWorkers as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}{{ $emp->designation ? ' — '.$emp->designation : '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Cutting rate (optional) --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Cutting Rate <span class="text-gray-400">(optional)</span></label>
                <select name="production_rate_id"
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">-- Rate ছাড়াই assign করুন --</option>
                    @foreach($cuttingRates as $rate)
                    <option value="{{ $rate->id }}">{{ $rate->product_name }}{{ $rate->color ? ' ('.$rate->color.')' : '' }} — ৳{{ number_format($rate->rate) }}/{{ $rate->unit }}</option>
                    @endforeach
                </select>
                @if($cuttingRates->isEmpty())
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Cutting rate নেই — <a href="{{ route('production.rates') }}" class="underline">যোগ করুন</a>। Rate ছাড়াও assign করা যাবে।</p>
                @endif
            </div>

            <div class="flex gap-2 pt-1">
                <button type="submit" :disabled="count === 0"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg disabled:opacity-40 transition-colors">
                    ✂️ Assign Cutting
                </button>
                <button type="button" @click="modalOpen = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-sm rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endif

</div>{{-- /x-data --}}

@endsection
