@extends('layouts.app')
@section('title', 'Barcode Print')

@section('content')

<div class="max-w-3xl mx-auto" x-data="barcodeApp()">

    <div class="flex items-center gap-3 mb-5">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Barcode Print</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Settings --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Print Settings</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Label Size</label>
                    <select x-model="labelSize" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="small">Small (38×25mm)</option>
                        <option value="medium" selected>Medium (58×40mm)</option>
                        <option value="large">Large (100×50mm)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Barcode Type</label>
                    <select x-model="barcodeType" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="CODE128">CODE128</option>
                        <option value="EAN13">EAN-13</option>
                        <option value="QR">QR Code</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Show Price</label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="showPrice" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        <span class="text-xs text-gray-600 dark:text-slate-300">Include price on label</span>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Show Product Name</label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="showName" checked class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        <span class="text-xs text-gray-600 dark:text-slate-300">Include product name</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Product Selection --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Select Products</h3>
                <div class="relative mb-3">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                    <input type="text" x-model="search" placeholder="Search products..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="max-h-64 overflow-y-auto space-y-1.5">
                    @foreach($products as $p)
                    <label class="flex items-center gap-3 p-2.5 hover:bg-gray-50 dark:hover:bg-slate-700/50 rounded-lg cursor-pointer"
                           x-show="search==='' || '{{ strtolower($p->name) }}'.includes(search.toLowerCase()) || '{{ $p->sku }}'.includes(search)">
                        <input type="checkbox" @change="toggleProduct({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ $p->sku ?? $p->id }}', {{ $p->price }})"
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $p->name }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $p->sku ?? 'No SKU' }} · ৳{{ number_format($p->price) }}</p>
                        </div>
                        <div>
                            <input type="number" min="1" value="1"
                                   @change="setQty({{ $p->id }}, $event.target.value)"
                                   class="w-14 px-2 py-1 text-xs text-center border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                                   placeholder="Qty" @click.prevent>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Selected + Print Button --}}
            <div class="flex items-center justify-between bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <p class="text-sm text-gray-600 dark:text-slate-300">
                    <span x-text="selected.length"></span> products selected
                    · <span x-text="selected.reduce((s,i)=>s+i.qty,0)"></span> labels
                </p>
                <button @click="printBarcodes()"
                        :disabled="selected.length === 0"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Barcodes
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
function barcodeApp() {
    return {
        selected: [], search: '', labelSize: 'medium', barcodeType: 'CODE128', showPrice: true, showName: true,

        toggleProduct(id, name, sku, price) {
            const idx = this.selected.findIndex(i => i.id === id);
            if (idx >= 0) this.selected.splice(idx, 1);
            else this.selected.push({ id, name, sku, price, qty: 1 });
        },

        setQty(id, qty) {
            const item = this.selected.find(i => i.id === id);
            if (item) item.qty = parseInt(qty) || 1;
        },

        printBarcodes() {
            if (!this.selected.length) return;
            const win = window.open('', '_blank');
            const sizes = { small:'38mm,25mm', medium:'58mm,40mm', large:'100mm,50mm' };
            const [w,h] = sizes[this.labelSize].split(',');

            let html = `<!DOCTYPE html><html><head><title>Barcodes</title>
                <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                <style>
                    * { margin:0; padding:0; box-sizing:border-box; }
                    body { display:flex; flex-wrap:wrap; gap:4px; padding:8px; background:white; }
                    .label { width:${w}; height:${h}; border:1px solid #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:4px; overflow:hidden; page-break-inside:avoid; }
                    .name { font-size:8px; text-align:center; font-family:Arial; font-weight:bold; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; width:100%; }
                    .price { font-size:10px; font-weight:bold; font-family:Arial; }
                    svg { max-width:100%; }
                    @media print { body { gap:2px; padding:4px; } }
                </style></head><body>`;

            this.selected.forEach(item => {
                for (let q = 0; q < item.qty; q++) {
                    html += `<div class="label">
                        ${this.showName ? `<div class="name">${item.name}</div>` : ''}
                        <svg id="bc-${item.id}-${q}"></svg>
                        ${this.showPrice ? `<div class="price">৳${parseFloat(item.price).toLocaleString()}</div>` : ''}
                    </div>`;
                }
            });

            html += `<script>
                window.onload = () => {
                    ${this.selected.map(item =>
                        `for(let q=0;q<${item.qty};q++){
                            try { JsBarcode('#bc-${item.id}-'+q, '${item.sku}', { format:'${this.barcodeType}', width:1.5, height:30, displayValue:true, fontSize:9, margin:2 }); } catch(e) {}
                        }`
                    ).join('\n')}
                    setTimeout(() => window.print(), 500);
                };
            <\/script></body></html>`;

            win.document.write(html);
            win.document.close();
        }
    }
}
</script>
@endpush