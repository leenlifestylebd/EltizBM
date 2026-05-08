@extends('layouts.app')
@section('title', 'Print Templates')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto scrollbar-hide">
        @foreach([['route'=>'settings.general','label'=>'Profile'],['route'=>'settings.business','label'=>'Business'],['route'=>'settings.user-management','label'=>'Users'],['route'=>'settings.subscription','label'=>'Subscription'],['route'=>'settings.warehouses','label'=>'Warehouses'],['route'=>'settings.print-templates','label'=>'Print']] as $tab)
        <a href="{{ route($tab['route']) }}" class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap {{ request()->routeIs($tab['route']) ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">{{ $tab['label'] }}</a>
        @endforeach
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Invoice & Receipt Settings</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Invoice Footer Text</label>
                <textarea rows="2" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                          placeholder="Thank you for your business!">{{ $business->name }} — Thank you for your purchase!</textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Invoice Paper Size</label>
                    <select class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="a4" selected>A4</option>
                        <option value="a5">A5</option>
                        <option value="letter">Letter</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Receipt Paper Width</label>
                    <select class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="58mm">58mm (Thermal)</option>
                        <option value="80mm" selected>80mm (Thermal)</option>
                        <option value="a4">A4 (Laser)</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('orders.invoice', ['order' => 1]) }}" target="_blank"
                   class="flex-1 py-2.5 text-center text-sm font-medium bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                    Preview Invoice
                </a>
                <a href="{{ route('pos.receipt', ['order' => 1]) }}" target="_blank"
                   class="flex-1 py-2.5 text-center text-sm font-medium bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                    Preview Receipt
                </a>
            </div>
        </div>
    </div>

</div>

@endsection


{{-- ============================================================
     FILE: resources/views/settings/warehouses.blade.php
     ============================================================ --}}