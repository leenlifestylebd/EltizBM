@extends('layouts.app')
@section('title', 'Business Settings')

@section('content')

<div class="max-w-3xl mx-auto">

    {{-- Settings Tabs --}}
    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto scrollbar-hide">
        @foreach([
            ['route'=>'settings.general',          'label'=>'Profile'],
            ['route'=>'settings.business',         'label'=>'Business'],
            ['route'=>'settings.user-management',  'label'=>'Users'],
            ['route'=>'settings.print-templates',  'label'=>'Print'],
            ['route'=>'settings.account-categories','label'=>'Categories'],
            ['route'=>'settings.theme',            'label'=>'Theme'],
        ] as $tab)
        <a href="{{ route($tab['route']) }}"
           class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
                  {{ request()->routeIs($tab['route'])
                      ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm'
                      : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">
            {{ $tab['label'] }}
        </a>
        @endforeach
    </div>

    {{-- LOGO UPLOAD (separate form — must NOT be nested in main form) --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Branding — Logo</h3>
        <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
            {{-- Preview --}}
            <div class="w-16 h-16 bg-white dark:bg-slate-700 rounded-xl border border-gray-200 dark:border-slate-600 flex items-center justify-center overflow-hidden flex-shrink-0">
                @if($business->logo)
                    <img src="{{ Storage::url($business->logo) }}" alt="Logo" class="w-full h-full object-contain p-1">
                @else
                    <svg class="w-8 h-8 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                @endif
            </div>
            {{-- Upload form (standalone) --}}
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-white">Business Logo</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">PNG, JPG — max 2MB. Shown on invoices &amp; labels.</p>
                <form method="POST" action="{{ route('settings.logo.upload') }}" enctype="multipart/form-data" class="mt-2">
                    @csrf
                    <label class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-primary-600 text-white rounded-lg cursor-pointer hover:bg-primary-700 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Upload Logo
                        <input type="file" name="logo" accept="image/*" class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
        </div>
    </div>

    {{-- MAIN SETTINGS FORM --}}
    <form method="POST" action="{{ route('settings.business.update') }}" class="space-y-4">
        @csrf

        @if(session('success'))
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-400">
            {{ session('success') }}
        </div>
        @endif

        {{-- Business Info --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Business Info</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Business Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $business->name) }}" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Business ID</label>
                    <div class="flex items-center gap-2">
                        <input type="text" value="{{ $business->id ? 'BIZ-'.str_pad($business->id,6,'0',STR_PAD_LEFT) : '' }}" readonly
                               class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-gray-50 dark:bg-slate-700/50 text-gray-500 dark:text-slate-400 font-mono">
                        <button type="button"
                                onclick="navigator.clipboard.writeText('BIZ-{{ str_pad($business->id,6,'0',STR_PAD_LEFT) }}')"
                                class="px-3 py-2.5 text-xs font-medium bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Domain / Website</label>
                    <input type="text" name="domain" value="{{ old('domain', $business->domain) }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="www.example.com">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $business->phone) }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="01XXXXXXXXX">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $business->email) }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Business Address</label>
                    <textarea name="address" rows="2"
                              class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                              placeholder="Full address — appears on invoices">{{ old('address', $business->address) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Localization --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Localization</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Currency</label>
                    <select name="currency" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="BDT" {{ $business->currency === 'BDT' ? 'selected':'' }}>BDT — Bangladeshi Taka</option>
                        <option value="USD" {{ $business->currency === 'USD' ? 'selected':'' }}>USD — US Dollar</option>
                        <option value="INR" {{ $business->currency === 'INR' ? 'selected':'' }}>INR — Indian Rupee</option>
                        <option value="EUR" {{ $business->currency === 'EUR' ? 'selected':'' }}>EUR — Euro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $business->currency_symbol) }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="&#2547;">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Timezone</label>
                    <select name="timezone" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="Asia/Dhaka"   {{ $business->timezone === 'Asia/Dhaka'   ? 'selected':'' }}>Asia/Dhaka (UTC+6)</option>
                        <option value="Asia/Kolkata" {{ $business->timezone === 'Asia/Kolkata' ? 'selected':'' }}>Asia/Kolkata (UTC+5:30)</option>
                        <option value="UTC"          {{ $business->timezone === 'UTC'          ? 'selected':'' }}>UTC</option>
                        <option value="Asia/Dubai"   {{ $business->timezone === 'Asia/Dubai'   ? 'selected':'' }}>Asia/Dubai (UTC+4)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- VAT & Tax --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5"
             x-data="{ vatEnabled: {{ $business->vat_enabled ? 'true':'false' }}, taxEnabled: {{ $business->tax_enabled ? 'true':'false' }} }">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">VAT Configuration</h3>

            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl mb-3">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Enable VAT</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Show VAT line on invoices</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="vat_enabled" value="0">
                    <input type="checkbox" name="vat_enabled" value="1" x-model="vatEnabled"
                           {{ $business->vat_enabled ? 'checked':'' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 dark:bg-slate-600 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                </label>
            </div>

            <div x-show="vatEnabled" class="grid grid-cols-3 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">VAT Label</label>
                    <input type="text" name="vat_label" value="{{ old('vat_label', $business->vat_label) }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="VAT">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">VAT Rate (%)</label>
                    <input type="number" name="vat_rate" value="{{ old('vat_rate', $business->vat_rate) }}" min="0" max="100" step="0.01"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Mode</label>
                    <select name="vat_mode" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="inclusive" {{ $business->vat_mode === 'inclusive' ? 'selected':'' }}>Inclusive (in total)</option>
                        <option value="exclusive" {{ $business->vat_mode === 'exclusive' ? 'selected':'' }}>Exclusive (added on top)</option>
                    </select>
                </div>
            </div>

            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 pt-3 border-t border-gray-100 dark:border-slate-700">Tax Configuration</h3>

            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl mb-3">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Enable Tax</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Additional tax separate from VAT</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="tax_enabled" value="0">
                    <input type="checkbox" name="tax_enabled" value="1" x-model="taxEnabled"
                           {{ $business->tax_enabled ? 'checked':'' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 dark:bg-slate-600 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                </label>
            </div>

            <div x-show="taxEnabled" class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Tax Label</label>
                    <input type="text" name="tax_label" value="{{ old('tax_label', $business->tax_label) }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="Tax">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Tax Rate (%)</label>
                    <input type="number" name="tax_rate" value="{{ old('tax_rate', $business->tax_rate) }}" min="0" max="100" step="0.01"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Mode</label>
                    <select name="tax_mode" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="exclusive">Exclusive</option>
                        <option value="inclusive">Inclusive</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Courier / Label Settings --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Courier Label Settings</h3>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">Appears on Steadfast &amp; other courier labels.</p>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                    Merchant ID
                    <span class="text-gray-400 font-normal">(shown on the right side of label)</span>
                </label>
                <input type="text" name="label_merchant_id"
                       value="{{ old('label_merchant_id', $business->print_settings['label_merchant_id'] ?? '') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono"
                       placeholder="e.g. MER-12345 (leave blank to auto-generate)">
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                    Your Steadfast or other courier Merchant/Account ID. Leave blank to auto-generate from Business ID.
                </p>
            </div>
        </div>

        <button type="submit"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 rounded-xl text-sm transition-colors">
            Save Business Settings
        </button>

    </form>
</div>

@endsection
