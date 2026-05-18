@extends('layouts.app')
@section('title', 'Import Products')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6" x-data="csvImport()">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('products.index') }}" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Import Products</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Upload CSV to bulk import products</p>
        </div>
    </div>

    {{-- Success Result --}}
    @if(session('import_success'))
    <div class="mb-5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-2xl p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 bg-green-100 dark:bg-green-800 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-green-800 dark:text-green-300">{{ session('import_success') }}</p>
                <a href="{{ route('products.index') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                    View all products →
                </a>
            </div>
        </div>

        @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="border-t border-green-200 dark:border-green-700 pt-3 mt-3">
            <p class="text-xs font-semibold text-orange-700 dark:text-orange-400 mb-2">
                ⚠ {{ count(session('import_errors')) }} row(s) skipped:
            </p>
            <div class="space-y-1 max-h-40 overflow-y-auto">
                @foreach(session('import_errors') as $err)
                <p class="text-xs text-orange-600 dark:text-orange-400 font-mono bg-orange-50 dark:bg-orange-900/20 px-2 py-1 rounded">{{ $err }}</p>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Step 1: Download Template --}}
    <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-5 mb-4">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 bg-primary-50 dark:bg-primary-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="text-sm font-bold text-primary-600 dark:text-primary-400">1</span>
            </div>
            <div>
                <p class="font-semibold text-gray-800 dark:text-white text-sm">Download Template</p>
                <p class="text-xs text-gray-500 dark:text-slate-400">Fill in this CSV template with your product data</p>
            </div>
        </div>
        <a href="{{ route('products.import.template') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download Template (CSV)
        </a>

        {{-- Column Guide --}}
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-700">
                        <th class="px-3 py-2 text-left text-gray-600 dark:text-slate-300 font-semibold rounded-l-lg">Column</th>
                        <th class="px-3 py-2 text-left text-gray-600 dark:text-slate-300 font-semibold">Required</th>
                        <th class="px-3 py-2 text-left text-gray-600 dark:text-slate-300 font-semibold rounded-r-lg">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @php $cols = [
                        ['name',            '✅ Yes', 'Product name'],
                        ['sku',             'No',     'Auto-generated if empty'],
                        ['barcode',         'No',     'Leave empty to generate later'],
                        ['category',        'No',     'Auto-created if not exists'],
                        ['brand',           'No',     'Auto-created if not exists'],
                        ['price',           '✅ Yes', 'Selling price (number only)'],
                        ['cost_price',      'No',     'Purchase cost'],
                        ['sale_price',      'No',     'Discounted price'],
                        ['stock',           'No',     'Current quantity (default 0)'],
                        ['low_stock_alert', 'No',     'Alert threshold (default 5)'],
                        ['unit',            'No',     'pcs, kg, ltr etc (default pcs)'],
                        ['description',     'No',     'Product description'],
                        ['is_active',       'No',     '1 = active, 0 = inactive'],
                    ]; @endphp
                    @foreach($cols as [$col, $req, $note])
                    <tr>
                        <td class="px-3 py-1.5 font-mono text-primary-600 dark:text-primary-400 font-semibold">{{ $col }}</td>
                        <td class="px-3 py-1.5 {{ str_contains($req,'Yes') ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-400' }}">{{ $req }}</td>
                        <td class="px-3 py-1.5 text-gray-500 dark:text-slate-400">{{ $note }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Step 2: Upload --}}
    <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-5">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 bg-green-50 dark:bg-green-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="text-sm font-bold text-green-600 dark:text-green-400">2</span>
            </div>
            <div>
                <p class="font-semibold text-gray-800 dark:text-white text-sm">Upload Your CSV</p>
                <p class="text-xs text-gray-500 dark:text-slate-400">Max 5MB · CSV format only</p>
            </div>
        </div>

        <form method="POST" action="{{ route('products.import.process') }}" enctype="multipart/form-data" @submit="submitting = true">
            @csrf

            {{-- Drop Zone --}}
            <div class="relative mb-4">
                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt"
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                       @change="handleFile($event)" required>
                <div class="border-2 border-dashed rounded-2xl p-8 text-center transition-all"
                     :class="fileName ? 'border-green-400 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-slate-600 hover:border-primary-400 hover:bg-primary-50/30 dark:hover:bg-primary-900/10'">
                    <template x-if="!fileName">
                        <div>
                            <div class="w-12 h-12 bg-gray-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-700 dark:text-slate-300">Drag & drop your CSV here</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">or click to browse</p>
                        </div>
                    </template>
                    <template x-if="fileName">
                        <div>
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-green-700 dark:text-green-400" x-text="fileName"></p>
                            <p class="text-xs text-green-600 dark:text-green-500 mt-1" x-text="rowCount ? rowCount + ' data rows detected' : ''"></p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Preview Table --}}
            <div x-show="previewRows.length > 0" x-cloak class="mb-4">
                <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Preview (first 5 rows)</p>
                <div class="overflow-x-auto border border-gray-100 dark:border-slate-700 rounded-xl">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-slate-700">
                            <tr>
                                <template x-for="h in previewHeaders" :key="h">
                                    <th class="px-3 py-2 text-left text-gray-600 dark:text-slate-300 font-semibold whitespace-nowrap" x-text="h"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                            <template x-for="(row, i) in previewRows" :key="i">
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                    <template x-for="(cell, j) in row" :key="j">
                                        <td class="px-3 py-2 text-gray-700 dark:text-slate-300 whitespace-nowrap max-w-32 truncate" x-text="cell || '—'"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Validation errors from server --}}
            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl">
                <p class="text-xs font-semibold text-red-700 dark:text-red-400">{{ $errors->first() }}</p>
            </div>
            @endif

            {{-- Submit --}}
            <button type="submit"
                    :disabled="!fileName || submitting"
                    class="w-full py-3 font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-2"
                    :class="fileName && !submitting
                        ? 'bg-green-600 hover:bg-green-700 text-white cursor-pointer'
                        : 'bg-gray-100 dark:bg-slate-700 text-gray-400 dark:text-slate-500 cursor-not-allowed'">
                <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span x-text="submitting ? 'Importing...' : (fileName ? 'Import Products' : 'Select a CSV file first')"></span>
            </button>
        </form>
    </div>

</div>

<script>
function csvImport() {
    return {
        fileName: '',
        rowCount: 0,
        previewHeaders: [],
        previewRows: [],
        submitting: false,

        handleFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.fileName = file.name;
            this.previewHeaders = [];
            this.previewRows = [];

            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                const lines = text.split('\n').filter(l => l.trim());
                this.rowCount = Math.max(0, lines.length - 1);

                // Parse CSV (simple)
                const parse = (line) => {
                    const result = [];
                    let cur = '', inQ = false;
                    for (let c of line) {
                        if (c === '"') { inQ = !inQ; }
                        else if (c === ',' && !inQ) { result.push(cur.trim()); cur = ''; }
                        else cur += c;
                    }
                    result.push(cur.trim());
                    return result;
                };

                if (lines.length > 0) this.previewHeaders = parse(lines[0]);
                for (let i = 1; i <= Math.min(5, lines.length - 1); i++) {
                    this.previewRows.push(parse(lines[i]));
                }
            };
            reader.readAsText(file);
        }
    }
}
</script>
@endsection
