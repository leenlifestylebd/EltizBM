@extends('layouts.app')
@section('title', 'Courier Providers')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('integrations.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Courier Providers</h2>
    </div>

    @php
    $providers = [
        'pathao'    => ['name'=>'Pathao',    'logo'=>'🟢', 'key_label'=>'API Key',  'secret_label'=>'API Secret'],
        'steadfast' => ['name'=>'Steadfast', 'logo'=>'🔵', 'key_label'=>'API Key',  'secret_label'=>'Secret Key'],
        'redx'      => ['name'=>'RedX',      'logo'=>'🔴', 'key_label'=>'API Key',  'secret_label'=>'API Secret'],
        'paperfly'  => ['name'=>'Paperfly',  'logo'=>'🟡', 'key_label'=>'API Key',  'secret_label'=>'API Secret'],
        'sundarban' => ['name'=>'Sundarban', 'logo'=>'🟠', 'key_label'=>'Username', 'secret_label'=>'Password'],
        'custom'    => ['name'=>'Custom',    'logo'=>'⚙️', 'key_label'=>'API Key',  'secret_label'=>'API Secret'],
    ];
    @endphp

    @foreach($providers as $key => $provider)
    @php $setting = $settings[$key] ?? null; @endphp

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-3"
         x-data="{ open: {{ $setting && $setting->api_key ? 'true' : 'false' }} }">

        {{-- Header --}}
        <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
            <div class="flex items-center gap-3">
                <span class="text-xl">{{ $provider['logo'] }}</span>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $provider['name'] }}</h3>
                    @if($setting && $setting->api_key)
                        <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full inline-block"></span>
                            Connected
                        </span>
                    @else
                        <span class="text-xs text-gray-400 dark:text-slate-500">Not connected</span>
                    @endif
                </div>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        {{-- Form --}}
        <div x-show="open" x-cloak class="mt-4">
            <form method="POST" action="{{ route('integrations.courier-providers.save') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="provider" value="{{ $key }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                            {{ $provider['key_label'] }}
                        </label>
                        <input type="text" name="api_key"
                               value="{{ $setting->api_key ?? '' }}"
                               placeholder="Enter {{ $provider['key_label'] }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                            {{ $provider['secret_label'] }}
                        </label>
                        <input type="text" name="api_secret"
                               value="{{ $setting->api_secret ?? '' }}"
                               placeholder="Enter {{ $provider['secret_label'] }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ ($setting && $setting->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                        <span class="text-xs text-gray-600 dark:text-slate-300">Enable this courier</span>
                    </label>

                    <button type="submit"
                            class="px-4 py-1.5 bg-primary-600 text-white text-xs font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        Save
                    </button>
                </div>
            </form>

            {{-- Webhook URL (paste this into the courier's merchant panel) --}}
            @if($key === 'steadfast')
            <div class="mt-3 p-3 bg-gray-50 dark:bg-slate-700/40 border border-gray-100 dark:border-slate-600 rounded-lg">
                <p class="text-xs font-medium text-gray-700 dark:text-slate-200 mb-1">Webhook URL — for real-time status</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">
                    Paste this into your Steadfast merchant panel (Settings → Webhook / Notification URL).
                    Without it, statuses update only when you press <strong>Sync Status</strong>.
                </p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 text-xs text-gray-700 dark:text-slate-200 font-mono break-all">{{ url('/webhook/steadfast') }}</code>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ url('/webhook/steadfast') }}'); this.textContent='Copied'"
                            class="px-2.5 py-1 text-xs font-medium bg-white dark:bg-slate-600 border border-gray-200 dark:border-slate-500 text-gray-600 dark:text-slate-200 rounded-lg hover:bg-gray-50">
                        Copy
                    </button>
                </div>
            </div>
            @endif

            {{-- Disconnect --}}
            @if($setting && $setting->api_key)
            <form method="POST" action="{{ route('integrations.courier-providers.save') }}" class="mt-2">
                @csrf
                <input type="hidden" name="provider" value="{{ $key }}">
                <input type="hidden" name="api_key" value="">
                <input type="hidden" name="api_secret" value="">
                <input type="hidden" name="is_active" value="0">
                <button type="submit"
                        onclick="return confirm('Disconnect {{ $provider['name'] }}?')"
                        class="text-xs text-red-600 dark:text-red-400 hover:underline">
                    Disconnect {{ $provider['name'] }}
                </button>
            </form>
            @endif

        </div>
    </div>
    @endforeach

</div>
@endsection