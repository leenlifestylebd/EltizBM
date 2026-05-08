{{-- ===================================================
     FILE: resources/views/attributes/index.blade.php
     =================================================== --}}
@extends('layouts.app')
@section('title', 'Attributes')

@section('content')

<div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Attributes</h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4" x-data="attrForm()">

    {{-- Add Attribute --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Add Attribute</h3>

        <form method="POST" action="{{ route('attributes.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Attribute Name</label>
                <input type="text" name="name" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="e.g. Size, Color">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Type</label>
                <select name="type" x-model="type" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="select">Select (Dropdown)</option>
                    <option value="color">Color</option>
                    <option value="size">Size</option>
                    <option value="text">Text</option>
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-medium text-gray-700 dark:text-slate-300">Values</label>
                    <button type="button" @click="addValue()" class="text-xs text-blue-600 hover:underline">+ Add</button>
                </div>
                <div class="space-y-1.5" id="valuesContainer">
                    <template x-for="(val, idx) in values" :key="idx">
                        <div class="flex items-center gap-2">
                            <input type="text" :name="'values[' + idx + ']'" x-model="values[idx]"
                                   class="flex-1 px-2.5 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   :placeholder="type === 'color' ? '#FF0000' : 'Value ' + (idx+1)">
                            <button type="button" @click="values.splice(idx,1)" class="text-gray-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Save Attribute
            </button>
        </form>
    </div>

    {{-- Attributes List --}}
    <div class="lg:col-span-2 space-y-3">
        @forelse($attributes as $attribute)
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $attribute->name }}</span>
                    <span class="ml-2 text-xs bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 px-2 py-0.5 rounded">{{ $attribute->type }}</span>
                </div>
                <form method="POST" action="{{ route('attributes.destroy', $attribute) }}" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach($attribute->values as $val)
                    @if($attribute->type === 'color')
                        <div class="flex items-center gap-1 bg-gray-50 dark:bg-slate-700 px-2 py-1 rounded-lg">
                            <span class="w-4 h-4 rounded-full border border-gray-200" style="background: {{ $val->value }}"></span>
                            <span class="text-xs text-gray-600 dark:text-slate-300">{{ $val->value }}</span>
                        </div>
                    @else
                        <span class="text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 px-2 py-1 rounded-lg">{{ $val->value }}</span>
                    @endif
                @endforeach
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-12 text-center">
            <p class="text-sm text-gray-400 dark:text-slate-500">No attributes yet.</p>
        </div>
        @endforelse
    </div>

</div>

@endsection

@push('scripts')
<script>
function attrForm() {
    return {
        type: 'select',
        values: ['', ''],
        addValue() { this.values.push(''); }
    }
}
</script>
@endpush
