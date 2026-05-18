@extends('layouts.app')
@section('title', 'Barcode Print')

@section('content')

<div class="max-w-4xl mx-auto" x-data="barcodeApp()">

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Barcode Print</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                Generate barcodes → print labels → stick on products → scan at POS
            </p>
        </div>
        <button onclick="generateAllBarcodes(this)"
                class="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition-colors">
            ⚡ Generate Missing Barcodes
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- LEFT: Settings --}}
        <div class="space-y-4">

            {{-- Print Settings --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-3">Label Settings</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Label Size</label>
                        <select x-model="labelSize" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:ring-2 focus:ring-primary-500">
                            <option value="small">Small (38×25mm)</option>
                            <option value="medium" selected>Medium (58×40mm)</option>
                            <option value="large">Large (100×50mm)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Barcode Type</label>
                        <select x-model="barcodeType" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:ring-2 focus:ring-primary-500">
                            <option value="CODE128">CODE128 (universal)</option>
                            <option value="EAN13">EAN-13 (retail)</option>
                            <option value="QR">QR Code</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showName" class="w-3.5 h-3.5 text-primary-600 border-gray-300 rounded">
                            <span class="text-xs text-gray-600 dark:text-slate-300">Show product name</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showSku" class="w-3.5 h-3.5 text-primary-600 border-gray-300 rounded">
                            <span class="text-xs text-gray-600 dark:text-slate-300">Show SKU</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showPrice" class="w-3.5 h-3.5 text-primary-600 border-gray-300 rounded">
                            <span class="text-xs text-gray-600 dark:text-slate-300">Show price</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showBiz" class="w-3.5 h-3.5 text-primary-600 border-gray-300 rounded">
                            <span class="text-xs text-gray-600 dark:text-slate-300">Show business name</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Live Preview --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-3">Label Preview</h3>
                <div class="flex justify-center">
                    <div id="labelPreview" class="border border-dashed border-gray-300 dark:border-slate-600 bg-white rounded p-2 flex flex-col items-center gap-1 text-center"
                         style="width:116px; min-height:80px;">
                        <div id="previewName" class="text-xs font-bold text-gray-800 leading-tight truncate w-full">Product Name</div>
                        <canvas id="previewCanvas" width="100" height="32"></canvas>
                        <div id="previewSku" class="text-xs text-gray-500 leading-none">SKU-001</div>
                        <div id="previewPrice" class="text-sm font-bold text-gray-800 leading-none">৳1,200</div>
                        <div id="previewBiz" class="text-xs text-gray-400 leading-none">My Shop</div>
                    </div>
                </div>
                <p class="text-xs text-gray-400 dark:text-slate-500 text-center mt-2">Approximate preview</p>
            </div>

            {{-- Action bar --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 space-y-3">
                <div class="flex justify-between text-xs text-gray-600 dark:text-slate-300">
                    <span><span x-text="selected.length"></span> products</span>
                    <span><span x-text="selected.reduce((s,i)=>s+i.qty,0)"></span> labels</span>
                </div>
                <button @click="printBarcodes()"
                        :disabled="selected.length === 0"
                        class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Barcodes
                </button>
                <button @click="clearAll()" x-show="selected.length > 0"
                        class="w-full py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                    Clear selection
                </button>
            </div>
        </div>

        {{-- RIGHT: Product List --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                    <div class="relative flex-1">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        <input type="text" x-model="search" placeholder="Search products..."
                               class="w-full pl-8 pr-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <button @click="selectAll()" class="text-xs text-primary-600 hover:underline dark:text-primary-400">All</button>
                    <button @click="clearAll()" class="text-xs text-gray-400 hover:underline">None</button>
                </div>

                <div class="divide-y divide-gray-50 dark:divide-slate-700 max-h-[520px] overflow-y-auto">
                    @forelse($products as $p)
                    <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors"
                         id="product-row-{{ $p->id }}"
                         x-show="!search || '{{ strtolower($p->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($p->sku ?? '') }}'.includes(search.toLowerCase()) || '{{ $p->barcode ?? '' }}'.includes(search)">
                        <label class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer">
                            <input type="checkbox"
                                   :checked="isSelected({{ $p->id }})"
                                   @change="toggleProduct({{ $p->id }}, {{ json_encode($p->name) }}, {{ json_encode($p->sku ?? $p->barcode ?? (string)$p->id) }}, {{ $p->price }}, {{ json_encode($p->barcode ?? '') }})"
                                   class="w-3.5 h-3.5 text-primary-600 border-gray-300 rounded flex-shrink-0">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ $p->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                                    @if($p->sku)<span class="font-mono">{{ $p->sku }}</span> · @endif৳{{ number_format($p->price) }}
                                </p>
                                <p class="text-xs font-mono mt-0.5" id="bc-text-{{ $p->id }}">
                                    @if($p->barcode)
                                        <span class="text-green-600 dark:text-green-400">✓ {{ $p->barcode }}</span>
                                    @else
                                        <span class="text-orange-400">No barcode</span>
                                    @endif
                                </p>
                            </div>
                        </label>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            @if(!$p->barcode)
                            <button onclick="generateBarcode({{ $p->id }}, this)"
                                    class="px-2 py-1 text-xs bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-lg hover:bg-primary-200 transition-colors font-medium whitespace-nowrap">
                                Generate
                            </button>
                            @endif
                            <input type="number" min="1" max="999"
                                   :value="getQty({{ $p->id }})"
                                   @change="setQty({{ $p->id }}, $event.target.value)"
                                   class="w-14 px-2 py-1 text-xs text-center border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                                   placeholder="Qty">
                        </div>
                    </div>
                    @empty
                    <div class="py-12 text-center text-sm text-gray-400">No products found</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const BIZ_NAME = @json(auth()->user()->business?->name ?? config('app.name'));
const CSRF     = document.querySelector('meta[name=csrf-token]')?.content;

// Generate barcode for a single product
async function generateBarcode(productId, btn) {
    btn.disabled    = true;
    btn.textContent = '...';
    try {
        const res  = await fetch(`/products/${productId}/generate-barcode`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            // Update UI
            const bcText = document.getElementById(`bc-text-${productId}`);
            if (bcText) bcText.innerHTML = `<span class="text-green-600">✓ ${data.barcode}</span>`;
            btn.remove();
        }
    } catch(e) {
        btn.disabled    = false;
        btn.textContent = 'Generate';
        alert('Error generating barcode');
    }
}

// Generate barcodes for ALL products without one
async function generateAllBarcodes(btn) {
    if (!confirm('Generate barcodes for all products that don\'t have one?')) return;
    btn.disabled    = true;
    btn.textContent = 'Generating...';
    try {
        const res  = await fetch('/products/generate-barcodes-bulk', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            alert(`✅ Generated barcodes for ${data.count} products. Refreshing...`);
            window.location.reload();
        }
    } catch(e) {
        alert('Error generating barcodes');
    }
    btn.disabled    = false;
    btn.textContent = '⚡ Generate Missing Barcodes';
}

function barcodeApp() {
    return {
        selected: [],
        search: '',
        labelSize: 'medium',
        barcodeType: 'CODE128',
        showName: true,
        showSku: true,
        showPrice: true,
        showBiz: false,

        init() {
            this.$watch('barcodeType', () => this.updatePreview());
            this.$watch('showName',    () => this.updatePreview());
            this.$watch('showSku',     () => this.updatePreview());
            this.$watch('showPrice',   () => this.updatePreview());
            this.$watch('showBiz',     () => this.updatePreview());
            this.updatePreview();
        },

        isSelected(id) {
            return this.selected.some(i => i.id === id);
        },

        getQty(id) {
            return this.selected.find(i => i.id === id)?.qty ?? 1;
        },

        toggleProduct(id, name, sku, price, barcode) {
            const idx = this.selected.findIndex(i => i.id === id);
            if (idx >= 0) {
                this.selected.splice(idx, 1);
            } else {
                this.selected.push({ id, name, sku, price, barcode: barcode || sku, qty: 1 });
            }
        },

        setQty(id, qty) {
            const item = this.selected.find(i => i.id === id);
            if (item) item.qty = Math.max(1, parseInt(qty) || 1);
        },

        selectAll() {
            document.querySelectorAll('[type=checkbox]').forEach(cb => {
                if (!cb.checked) cb.click();
            });
        },

        clearAll() {
            this.selected = [];
        },

        updatePreview() {
            document.getElementById('previewName').style.display  = this.showName  ? 'block' : 'none';
            document.getElementById('previewSku').style.display   = this.showSku   ? 'block' : 'none';
            document.getElementById('previewPrice').style.display = this.showPrice ? 'block' : 'none';
            document.getElementById('previewBiz').style.display   = this.showBiz   ? 'block' : 'none';

            const canvas = document.getElementById('previewCanvas');
            if (this.barcodeType === 'QR') {
                canvas.style.display = 'none';
            } else {
                canvas.style.display = 'block';
                try {
                    JsBarcode(canvas, 'PREVIEW', { format: this.barcodeType === 'EAN13' ? 'EAN13' : 'CODE128', width: 1.2, height: 28, displayValue: false, margin: 0 });
                } catch(e) {}
            }
        },

        printBarcodes() {
            if (!this.selected.length) return;

            const sizes = { small: ['38mm','25mm'], medium: ['58mm','40mm'], large: ['100mm','50mm'] };
            const [lw, lh] = sizes[this.labelSize];

            const items  = this.selected;
            const config = { barcodeType: this.barcodeType, showName: this.showName, showSku: this.showSku, showPrice: this.showPrice, showBiz: this.showBiz };

            const win = window.open('', '_blank', 'width=800,height=600');

            const labels = items.flatMap(item =>
                Array(item.qty).fill(null).map((_, i) => ({
                    id: item.id, i,
                    name: item.name,
                    sku:  item.sku,
                    price: item.price,
                    barcode: item.barcode,
                }))
            );

            const html = `<!DOCTYPE html><html>
<head>
<meta charset="UTF-8">
<title>Barcodes</title>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"><\/script>
<style>
  * { margin:0;padding:0;box-sizing:border-box; }
  body { background:#fff; display:flex; flex-wrap:wrap; gap:4px; padding:6px; }
  .label { width:${lw}; height:${lh}; border:1px solid #ccc; border-radius:2px; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:3px; overflow:hidden; page-break-inside:avoid; }
  .l-name  { font-size:7px; font-family:Arial; font-weight:700; text-align:center; max-width:100%; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; line-height:1.2; }
  .l-sku   { font-size:6px; font-family:'Courier New'; text-align:center; color:#555; line-height:1.2; }
  .l-price { font-size:9px; font-family:Arial; font-weight:700; line-height:1.2; }
  .l-biz   { font-size:6px; font-family:Arial; color:#888; line-height:1.2; }
  .l-bc    { max-width:95%; }
  .l-qr    { max-width:60%; }
  @media print { @page { margin:4mm; } body { gap:2px; padding:2px; } }
</style>
</head>
<body>
${labels.map(l => `
<div class="label">
  ${config.showName  ? `<div class="l-name">${l.name}</div>` : ''}
  ${config.barcodeType === 'QR'
    ? `<div id="qr_${l.id}_${l.i}" class="l-qr"></div>`
    : `<svg id="bc_${l.id}_${l.i}" class="l-bc"></svg>`
  }
  ${config.showSku   ? `<div class="l-sku">${l.sku}</div>` : ''}
  ${config.showPrice ? `<div class="l-price">&#2547;${parseFloat(l.price).toLocaleString()}</div>` : ''}
  ${config.showBiz   ? `<div class="l-biz">${BIZ_NAME}</div>` : ''}
</div>`).join('')}
<script>
window.onload = () => {
  ${labels.map(l => {
    const code = l.barcode.replace(/'/g,"\\\\\'");
    if (config.barcodeType === 'QR') {
      return `try { new QRCode(document.getElementById('qr_${l.id}_${l.i}'), { text:'${code}', width:48, height:48, correctLevel:0 }); } catch(e){}`;
    } else {
      const fmt = config.barcodeType === 'EAN13' ? 'EAN13' : 'CODE128';
      return `try { JsBarcode('#bc_${l.id}_${l.i}','${code}',{format:'${fmt}',width:1.4,height:28,displayValue:true,fontSize:7,margin:1}); } catch(e){ try { JsBarcode('#bc_${l.id}_${l.i}','${l.id}',{format:'CODE128',width:1.4,height:28,displayValue:true,fontSize:7,margin:1}); }catch(e2){} }`;
    }
  }).join('\n')}
  setTimeout(() => { window.focus(); window.print(); }, 800);
};
<\/script>
</body></html>`;

            win.document.write(html);
            win.document.close();
        }
    };
}
</script>
@endpush
