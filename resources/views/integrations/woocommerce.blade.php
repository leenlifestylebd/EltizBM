@extends('layouts.app')
@section('title', 'WooCommerce Integration')

@section('content')

<div class="max-w-2xl mx-auto" x-data="wooForm()">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('integrations.index') }}"
           class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">WooCommerce Integration</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Connect your WooCommerce store</p>
            </div>
        </div>
    </div>

    {{-- Connection Status --}}
    @if($wooSettings?->is_active)
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl p-4 mb-4 flex items-center gap-3">
        <div class="w-8 h-8 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-green-800 dark:text-green-400">Connected</p>
            <p class="text-xs text-green-600 dark:text-green-500 truncate">{{ $wooSettings->store_url }}</p>
        </div>
        @if($wooSettings->last_synced_at)
        <p class="text-xs text-green-600 dark:text-green-500 flex-shrink-0">Last sync: {{ $wooSettings->last_synced_at->diffForHumans() }}</p>
        @endif
    </div>
    @endif

    {{-- Settings Form --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">API Credentials</h3>

        <form method="POST" action="{{ route('integrations.woocommerce.save') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                    Store URL <span class="text-red-500">*</span>
                </label>
                <input type="url" name="store_url"
                       value="{{ old('store_url', $wooSettings->store_url ?? '') }}" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="https://yourstore.com">
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Your WooCommerce store URL (without trailing slash)</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                    Consumer Key <span class="text-red-500">*</span>
                </label>
                <input type="text" name="consumer_key"
                       value="{{ old('consumer_key', $wooSettings?->consumer_key ? '••••••••' . substr($wooSettings->consumer_key, -6) : '') }}" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                       placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                    Consumer Secret <span class="text-red-500">*</span>
                </label>
                <input type="text" name="consumer_secret"
                       value="{{ old('consumer_secret', $wooSettings?->consumer_secret ? '••••••••' . substr($wooSettings->consumer_secret, -6) : '') }}" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                       placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
            </div>

            {{-- How to get keys --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg p-3">
                <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1">How to get API keys?</p>
                <ol class="text-xs text-blue-600 dark:text-blue-400 space-y-0.5 list-decimal list-inside">
                    <li>Go to WooCommerce → Settings → Advanced → REST API</li>
                    <li>Click "Add Key"</li>
                    <li>Set Permissions to <strong>Read/Write</strong></li>
                    <li>Copy Consumer Key and Consumer Secret</li>
                </ol>
            </div>

            {{-- Sync Options --}}
            <div class="border-t border-gray-100 dark:border-slate-700 pt-4">
                <p class="text-xs font-semibold text-gray-700 dark:text-slate-300 mb-3">Sync Options</p>
                <div class="space-y-2.5">
                    @foreach([
                        ['key' => 'sync_products', 'label' => 'Sync Products', 'desc' => 'Import/update products from WooCommerce'],
                        ['key' => 'sync_orders',   'label' => 'Sync Orders',   'desc' => 'Import orders from WooCommerce'],
                        ['key' => 'sync_stock',    'label' => 'Sync Stock',    'desc' => 'Push stock levels to WooCommerce'],
                    ] as $opt)
                    <label class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $opt['label'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $opt['desc'] }}</p>
                        </div>
                        <div class="relative ml-3 flex-shrink-0">
                            <input type="hidden" name="{{ $opt['key'] }}" value="0">
                            <input type="checkbox" name="{{ $opt['key'] }}" value="1"
                                   {{ old($opt['key'], $wooSettings?->{$opt['key']} ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 pt-2">
                <button type="button" @click="testConnection()"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-200 dark:border-slate-600 text-sm font-medium text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4" :class="testing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="testing ? 'Testing...' : 'Test Connection'"></span>
                </button>
                <button type="submit"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Settings
                </button>
            </div>
        </form>

        {{-- Test Result --}}
        <div x-show="testResult" x-cloak
             class="mt-3 p-3 rounded-lg text-sm"
             :class="testSuccess ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-700'
                                 : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700'">
            <p x-text="testResult"></p>
        </div>
    </div>

    {{-- Sync Panel (only if connected) --}}
    @if($wooSettings?->is_active)
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Manual Sync</h3>

        <form method="POST" action="{{ route('integrations.woocommerce.sync') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                @foreach([
                    ['value' => 'pull_all', 'label' => '⬇️ Pull from WooCommerce', 'desc' => 'Products + Orders'],
                    ['value' => 'push_all', 'label' => '⬆️ Push to WooCommerce',   'desc' => 'Stock levels'],
                    ['value' => 'full',     'label' => '🔄 Full Two-Way Sync',      'desc' => 'Everything'],
                ] as $opt)
                <label class="flex flex-col p-3 border border-gray-200 dark:border-slate-600 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                    <input type="radio" name="sync_type" value="{{ $opt['value'] }}" class="sr-only"
                           {{ $opt['value'] === 'full' ? 'checked' : '' }}>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $opt['label'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $opt['desc'] }}</span>
                </label>
                @endforeach
            </div>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Start Sync Now
            </button>
        </form>
    </div>

    {{-- Webhook Info --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Webhook Setup (Auto-sync)</h3>
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
            Set up webhooks in WooCommerce to automatically sync orders and product updates in real-time.
        </p>

        <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-3 mb-3">
            <p class="text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Webhook URL</p>
            <div class="flex items-center gap-2">
                <code class="flex-1 text-xs text-gray-700 dark:text-slate-200 font-mono break-all">{{ url('/webhooks/woocommerce') }}</code>
                <button type="button" onclick="navigator.clipboard.writeText('{{ url('/webhooks/woocommerce') }}')"
                        class="p-1.5 text-gray-400 hover:text-gray-600 bg-white dark:bg-slate-600 rounded border border-gray-200 dark:border-slate-500 flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="text-xs text-gray-500 dark:text-slate-400 space-y-1">
            <p class="font-semibold text-gray-700 dark:text-slate-300">Add these topics in WooCommerce → Settings → Advanced → Webhooks:</p>
            <p>• <span class="font-mono bg-gray-100 dark:bg-slate-700 px-1 rounded">order.created</span> → New orders auto-import</p>
            <p>• <span class="font-mono bg-gray-100 dark:bg-slate-700 px-1 rounded">order.updated</span> → Order status sync</p>
            <p>• <span class="font-mono bg-gray-100 dark:bg-slate-700 px-1 rounded">product.updated</span> → Product/stock sync</p>
        </div>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
function wooForm() {
    return {
        testing: false,
        testResult: '',
        testSuccess: false,

        async testConnection() {
            this.testing    = true;
            this.testResult = '';

            try {
                const res  = await fetch('{{ route("integrations.woocommerce.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                });
                const data = await res.json();
                this.testSuccess = data.success;
                this.testResult  = data.success
                    ? `✓ Connected! WooCommerce v${data.wc_version} at ${data.store}`
                    : `✗ ${data.message}`;
            } catch (e) {
                this.testSuccess = false;
                this.testResult  = '✗ Network error: ' + e.message;
            } finally {
                this.testing = false;
            }
        }
    }
}
</script>
@endpush
