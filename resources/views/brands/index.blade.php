{{-- ===================================================
     FILE: resources/views/brands/index.blade.php
     =================================================== --}}
@extends('layouts.app')
@section('title', 'Brands')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Brands</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $brands->count() }} brands</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Add Brand Form --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Add New Brand</h3>
        <form method="POST" action="{{ route('brands.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Brand Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="e.g. Samsung">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Logo</label>
                <label class="flex items-center gap-2 px-3 py-2.5 border border-dashed border-gray-200 dark:border-slate-600 rounded-lg cursor-pointer hover:border-blue-400 transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                    </svg>
                    <span class="text-xs text-gray-400">Upload logo</span>
                    <input type="file" name="logo" accept="image/*" class="hidden">
                </label>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">Add Brand</button>
        </form>
    </div>

    {{-- Brands Grid --}}
    <div class="lg:col-span-2">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @forelse($brands as $brand)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 text-center group relative">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-gray-100 dark:bg-slate-700 flex items-center justify-center overflow-hidden">
                    @if($brand->logo)
                        <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}" class="w-full h-full object-contain p-1">
                    @else
                        <span class="text-lg font-bold text-gray-400">{{ strtoupper(substr($brand->name, 0, 1)) }}</span>
                    @endif
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $brand->name }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $brand->products_count }} products</p>

                <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <form method="POST" action="{{ route('brands.destroy', $brand) }}" onsubmit="return confirm('Delete brand?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1 text-gray-400 hover:text-red-600 bg-white dark:bg-slate-700 rounded-lg shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="col-span-full py-12 text-center bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700">
                <p class="text-sm text-gray-400 dark:text-slate-500">No brands yet.</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

@endsection
