@extends('layouts.app')
@section('title', 'Product Sync')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('products.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Product Sync</h2>
    </div>

    @if(!$wooSettings || !$wooSettings->is_active)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-5 text-center">
        <svg class="w-10 h-10 text-yellow-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-400 mb-1">WooCommerce Not Connected</p>
        <p class="text-xs text-yellow-600 dark:text-yellow-500 mb-4">Connect your WooCommerce store first to sync products.</p>
        <a href="{{ route('integrations.woocommerce') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors">
            Connect WooCommerce
        </a>
    </div>
    @else
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-center gap-3 mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div class="w-8 h-8 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-green-800 dark:text-green-400">Connected to {{ $wooSettings->store_url }}</p>
                @if($wooSettings->last_synced_at)
                    <p class="text-xs text-green-600 dark:text-green-500">Last sync: {{ $wooSettings->last_synced_at->diffForHumans() }}</p>
                @endif
            </div>
        </div>

        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Sync Options</h3>
        <form method="POST" action="{{ route('products.do-sync') }}" class="space-y-3">
            @csrf
            <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                <input type="radio" name="sync_type" value="pull_all" checked class="text-blue-600">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Pull all products from WooCommerce</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Import products that don't exist here yet</p>
                </div>
            </label>
            <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                <input type="radio" name="sync_type" value="push_all" class="text-blue-600">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Push stock updates to WooCommerce</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Update stock quantities in WooCommerce</p>
                </div>
            </label>
            <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                <input type="radio" name="sync_type" value="full" class="text-blue-600">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Full two-way sync</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Sync products and stock both ways</p>
                </div>
            </label>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg text-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Start Sync
            </button>
        </form>
    </div>
    @endif
</div>

@endsection
