@extends('layouts.app')
@section('title', 'Payment Gateways')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6" x-data="{ tab: '{{ session('active_tab', 'bkash') }}' }">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('integrations.index') }}" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Payment Gateways</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Accept bKash & Nagad payments on orders</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl text-sm text-green-700 dark:text-green-400 flex items-center gap-2"
         x-data x-init="setTimeout(() => $el.remove(), 3000)">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl text-sm text-red-700 dark:text-red-400">
        {{ session('error') }}
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-2 mb-5">
        <button @click="tab='bkash'"
                :class="tab==='bkash' ? 'bg-pink-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700'"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all">
            <span class="text-base">🟣</span> bKash
            @if($bkash?->is_active)
            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
            @endif
        </button>
        <button @click="tab='nagad'"
                :class="tab==='nagad' ? 'bg-orange-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700'"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all">
            <span class="text-base">🟠</span> Nagad
            @if($nagad?->is_active)
            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
            @endif
        </button>
    </div>

    {{-- bKash Panel --}}
    <div x-show="tab==='bkash'" x-cloak>
        <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-5 bg-gradient-to-r from-pink-600 to-pink-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center">
                        <span class="text-2xl font-black text-pink-600">b</span>
                    </div>
                    <div>
                        <h2 class="text-white font-bold text-lg">bKash Payment</h2>
                        <p class="text-pink-100 text-xs">Tokenized Checkout API v1.2.0-beta</p>
                    </div>
                </div>
                @if($bkash?->is_active)
                <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">● ACTIVE</span>
                @else
                <span class="bg-pink-800/50 text-pink-200 text-xs px-3 py-1 rounded-full">Inactive</span>
                @endif
            </div>

            <div class="p-6">
                {{-- How to get credentials --}}
                <div class="bg-pink-50 dark:bg-pink-900/10 border border-pink-100 dark:border-pink-800 rounded-xl p-4 mb-5">
                    <p class="text-xs font-bold text-pink-700 dark:text-pink-400 mb-2">📋 How to get bKash credentials:</p>
                    <ol class="text-xs text-pink-600 dark:text-pink-400 space-y-1 list-decimal list-inside">
                        <li>Go to <strong>merchants.bkash.com</strong> → Apply for merchant account</li>
                        <li>After approval, go to API credentials section</li>
                        <li>Get: App Key, App Secret, Username, Password</li>
                        <li>For testing, use sandbox credentials from bKash developer portal</li>
                    </ol>
                </div>

                <form method="POST" action="{{ route('payment.settings.save') }}" x-data="{ showPass: false }">
                    @csrf
                    <input type="hidden" name="gateway" value="bkash">

                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1.5">App Key *</label>
                            <input type="text" name="app_key" value="{{ $bkash?->credentials['app_key'] ?? '' }}" required
                                   placeholder="your-app-key"
                                   class="w-full bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm text-gray-800 dark:text-white focus:outline-none focus:border-pink-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1.5">App Secret *</label>
                            <input type="text" name="app_secret" value="{{ $bkash?->credentials['app_secret'] ?? '' }}" required
                                   placeholder="your-app-secret"
                                   class="w-full bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm text-gray-800 dark:text-white focus:outline-none focus:border-pink-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1.5">Username *</label>
                            <input type="text" name="username" value="{{ $bkash?->credentials['username'] ?? '' }}" required
                                   placeholder="sandbox@bkash.com"
                                   class="w-full bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm text-gray-800 dark:text-white focus:outline-none focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1.5">Password *</label>
                            <div class="relative">
                                <input :type="showPass ? 'text' : 'password'" name="password" required
                                       value="{{ $bkash?->credentials['password'] ?? '' }}"
                                       placeholder="••••••••"
                                       class="w-full bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2.5 pr-10 text-sm text-gray-800 dark:text-white focus:outline-none focus:border-pink-500">
                                <button type="button" @click="showPass=!showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Callback URL --}}
                    <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-3 mb-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">Callback URL (add to bKash portal):</p>
                        <p class="text-xs font-mono text-gray-700 dark:text-slate-300 break-all">{{ url('/payment/bkash/{order_id}/callback') }}</p>
                    </div>

                    <div class="flex items-center gap-4 mb-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="sandbox" value="1" {{ $bkash?->sandbox ?? true ? 'checked' : '' }}
                                   class="w-4 h-4 rounded accent-pink-600">
                            <span class="text-sm text-gray-700 dark:text-slate-300">Sandbox / Test mode</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $bkash?->is_active ? 'checked' : '' }}
                                   class="w-4 h-4 rounded accent-pink-600">
                            <span class="text-sm text-gray-700 dark:text-slate-300">Enable bKash</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-3 bg-pink-600 hover:bg-pink-700 text-white font-semibold rounded-xl text-sm transition-colors">
                        Save bKash Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Nagad Panel --}}
    <div x-show="tab==='nagad'" x-cloak>
        <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-5 bg-gradient-to-r from-orange-500 to-orange-600 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center">
                        <span class="text-2xl font-black text-orange-500">N</span>
                    </div>
                    <div>
                        <h2 class="text-white font-bold text-lg">Nagad Payment</h2>
                        <p class="text-orange-100 text-xs">Remote Payment Gateway API</p>
                    </div>
                </div>
                @if($nagad?->is_active)
                <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">● ACTIVE</span>
                @else
                <span class="bg-orange-800/50 text-orange-200 text-xs px-3 py-1 rounded-full">Inactive</span>
                @endif
            </div>

            <div class="p-6">
                <div class="bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-800 rounded-xl p-4 mb-5">
                    <p class="text-xs font-bold text-orange-700 dark:text-orange-400 mb-2">📋 How to get Nagad credentials:</p>
                    <ol class="text-xs text-orange-600 dark:text-orange-400 space-y-1 list-decimal list-inside">
                        <li>Visit <strong>nagad.com.bd</strong> → Merchant Registration</li>
                        <li>After approval, access Merchant Portal</li>
                        <li>Get: Merchant ID, Public Key, Private Key (RSA)</li>
                        <li>For testing: <strong>sandbox.mynagad.com</strong> credentials</li>
                    </ol>
                </div>

                <form method="POST" action="{{ route('payment.settings.save') }}">
                    @csrf
                    <input type="hidden" name="gateway" value="nagad">

                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1.5">Merchant ID *</label>
                            <input type="text" name="merchant_id" value="{{ $nagad?->credentials['merchant_id'] ?? '' }}" required
                                   placeholder="683002007104225"
                                   class="w-full bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm text-gray-800 dark:text-white focus:outline-none focus:border-orange-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1.5">Merchant Public Key (RSA) *</label>
                            <textarea name="public_key" rows="3" required
                                      placeholder="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA..."
                                      class="w-full bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-xs text-gray-800 dark:text-white focus:outline-none focus:border-orange-500 font-mono resize-none">{{ $nagad?->credentials['public_key'] ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1.5">Merchant Private Key (RSA) *</label>
                            <textarea name="private_key" rows="3" required
                                      placeholder="MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEA..."
                                      class="w-full bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-xs text-gray-800 dark:text-white focus:outline-none focus:border-orange-500 font-mono resize-none">{{ $nagad?->credentials['private_key'] ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mb-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="sandbox" value="1" {{ $nagad?->sandbox ?? true ? 'checked' : '' }}
                                   class="w-4 h-4 rounded accent-orange-500">
                            <span class="text-sm text-gray-700 dark:text-slate-300">Sandbox / Test mode</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $nagad?->is_active ? 'checked' : '' }}
                                   class="w-4 h-4 rounded accent-orange-500">
                            <span class="text-sm text-gray-700 dark:text-slate-300">Enable Nagad</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl text-sm transition-colors">
                        Save Nagad Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
