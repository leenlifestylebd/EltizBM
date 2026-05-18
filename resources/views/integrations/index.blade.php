{{-- ===================================================
     FILE: resources/views/integrations/index.blade.php
     =================================================== --}}
@extends('layouts.app')
@section('title', 'Integrations')

@section('content')

<div class="mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Integrations</h2>
    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Connect your tools and services</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    {{-- WooCommerce --}}
    <a href="{{ route('integrations.woocommerce') }}"
       class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 hover:border-purple-300 dark:hover:border-purple-600 hover:shadow-sm transition-all group">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                </svg>
            </div>
            @if($wooConnected)
                <span class="text-xs font-medium text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">Connected</span>
            @else
                <span class="text-xs font-medium text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded-full">Not connected</span>
            @endif
        </div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">WooCommerce</h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Sync products, orders, and stock with your WordPress store.</p>
        <p class="text-xs text-purple-600 dark:text-purple-400 mt-3 font-medium group-hover:underline">Configure →</p>
    </a>

    {{-- Courier Providers --}}
    <a href="{{ route('integrations.courier-providers') }}"
       class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-sm transition-all group">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded-full">BD Couriers</span>
        </div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Courier Providers</h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Connect Pathao, Steadfast, Redx and other BD courier services.</p>
        <p class="text-xs text-blue-600 dark:text-blue-400 mt-3 font-medium group-hover:underline">Configure →</p>
    </a>

    {{-- Payment Gateways --}}
    <a href="{{ route('integrations.payment-gateways') }}"
       class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 hover:border-pink-300 dark:hover:border-pink-600 hover:shadow-sm transition-all group">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            @if($bkashActive || $nagadActive)
                <span class="text-xs font-medium text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">
                    {{ collect(['bKash'=>$bkashActive,'Nagad'=>$nagadActive])->filter()->keys()->implode(', ') }} Active
                </span>
            @else
                <span class="text-xs font-medium text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded-full">Not configured</span>
            @endif
        </div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">Payment Gateways</h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Accept bKash & Nagad payments directly on orders.</p>
        <p class="text-xs text-pink-600 dark:text-pink-400 mt-3 font-medium group-hover:underline">Configure →</p>
    </a>

    {{-- SMS --}}
    <a href="{{ route('integrations.sms') }}"
       class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 hover:border-green-300 dark:hover:border-green-600 hover:shadow-sm transition-all group">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded-full">Optional</span>
        </div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">SMS Notifications</h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Send order confirmation and delivery SMS via SSL Wireless or Twilio.</p>
        <p class="text-xs text-green-600 dark:text-green-400 mt-3 font-medium group-hover:underline">Configure →</p>
    </a>

</div>

@endsection
