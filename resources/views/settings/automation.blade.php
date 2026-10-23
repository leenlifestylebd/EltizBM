@extends('layouts.app')
@section('title', 'API & Automation')
@section('content')
<div class="max-w-3xl mx-auto space-y-5">
    <a href="{{ route('settings.general') }}" class="text-sm text-primary-600 dark:text-primary-400">&larr; Settings</a>
    <div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">API & Automation</h1>
        <p class="text-sm text-gray-600 dark:text-slate-300 mt-2">Connections for {{ $business->name }} only. Save your keys here, then connect your automation service. Saving does not send messages or create orders.</p>
    </div>
    @if($errors->any())
        <div role="alert" class="p-4 rounded-xl bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200">
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if($newToken)
        <div class="p-5 rounded-xl border border-primary-300 dark:border-primary-700 bg-white dark:bg-slate-800">
            <h2 class="font-semibold text-gray-900 dark:text-white">Copy your new API token now</h2>
            <p class="text-sm text-gray-600 dark:text-slate-300 mt-2">This is the only time it is shown. The previous token no longer works. Store it in your automation service's credentials, not in messages.</p>
            <label for="new-token" class="block text-sm mt-3">New API token</label>
            <input id="new-token" readonly value="{{ $newToken }}" autocomplete="off" class="w-full p-3 mt-1 border rounded-lg bg-gray-50 dark:bg-slate-900 dark:border-slate-600 text-gray-900 dark:text-white">
        </div>
    @endif
    <div class="p-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 space-y-3">
        <h2 class="font-semibold text-gray-900 dark:text-white">ERP API access</h2>
        <p class="text-sm text-gray-600 dark:text-slate-300">Status: {{ $automation?->enabled && $automation?->token_hash ? 'Enabled' : 'Disabled' }}
            @if($automation?->token_suffix) Ã‚Â· Token ending {{ $automation->token_suffix }} @endif
        </p>
        <label for="api-base" class="block text-sm">API base URL</label>
        <input id="api-base" readonly value="{{ url('/api/v1/bot') }}" class="w-full p-3 border rounded-lg bg-gray-50 dark:bg-slate-900 dark:border-slate-600 text-gray-900 dark:text-white">
        <p class="text-sm text-gray-600 dark:text-slate-300">Use a Bearer token. Every order or accounting write must include a unique Idempotency-Key. Business identity comes from the token.</p>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('settings.automation.token') }}">
                @csrf
                @if($automation?->token_hash)
                    <label class="block text-sm mb-2"><input type="checkbox" required> Replace my current token and disconnect clients using it</label>
                @endif
                <button class="px-4 py-2 rounded-lg bg-primary-600 text-white">{{ $automation?->token_hash ? 'Replace API token' : 'Generate API token' }}</button>
            </form>
            @if($automation?->token_hash)
                <form method="POST" action="{{ route('settings.automation.revoke') }}">
                    @csrf @method('DELETE')
                    <button class="px-4 py-2 rounded-lg border border-red-300 text-red-600 dark:text-red-300">Revoke API token</button>
                </form>
            @endif
        </div>
    </div>
    <section class="p-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 space-y-4" aria-labelledby="facebook-title">
        <h2 id="facebook-title" class="font-semibold text-gray-900 dark:text-white">Facebook Page</h2>
        @php
            $facebookConnected = ($settings['facebook_connection_mode'] ?? '') === 'oauth' && isset($configured['facebook_page_token']);
        @endphp
        @if($facebookConnected)
            <p class="font-medium text-green-700 dark:text-green-300">Connected: {{ $settings['facebook_page_name'] ?? 'Facebook Page' }}</p>
            <p class="text-sm text-gray-600 dark:text-slate-300">Page ID: {{ $settings['facebook_page_id'] ?? '' }}</p>
            <p class="text-sm text-gray-600 dark:text-slate-300">Last access check: {{ $settings['facebook_checked_at'] ?? 'Not checked' }}. Permissions may change on Facebook; use Check access if needed.</p>
        @else
            <p class="text-sm text-gray-600 dark:text-slate-300">Connect your Facebook account, then choose the Page for this business. No access token to copy.</p>
        @endif
        @if($facebookReady)
            <a href="{{ route('settings.facebook.start') }}" class="inline-flex px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium">{{ $facebookConnected ? 'Reconnect / Change Page' : 'Connect Facebook' }}</a>
            @if($facebookConnected)
                <form method="POST" action="{{ route('settings.facebook.check') }}">
                    @csrf
                    <button class="px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600">Check access</button>
                </form>
            @endif
        @else
            <button disabled class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-600 dark:bg-slate-700 dark:text-slate-300 cursor-not-allowed">Connect Facebook — setup pending</button>
            <p role="status" class="text-sm text-amber-800 dark:text-amber-200">The service owner needs to finish the Meta app setup. You do not need to create a developer app or paste keys here.</p>
        @endif
        @if($facebookConnected || isset($configured['facebook_page_token']))
            <form method="POST" action="{{ route('settings.facebook.disconnect') }}" class="space-y-2">
                @csrf @method('DELETE')
                <label class="flex gap-2 items-center text-sm"><input type="checkbox" required> Disconnect this business's Facebook Page</label>
                <button class="px-4 py-2 rounded-lg border border-red-300 text-red-700 dark:text-red-300">Disconnect Facebook</button>
            </form>
        @endif
        <p class="text-sm text-gray-600 dark:text-slate-300">Page connection does not activate replies. Your automation service must be set up separately. Disconnect removes this app's saved Page access; permissions can also be removed in Facebook Business Integrations.</p>
    </section>
    <form method="POST" action="{{ route('settings.automation.update') }}" autocomplete="off" class="space-y-5">
        @csrf
        <div class="p-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white">Access permissions</h2>
            <input type="hidden" name="enabled" value="0">
            <label class="flex gap-2 items-center"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $automation?->enabled ?? false))> Enable this business's API</label>
            <input type="hidden" name="abilities_present" value="1">
            @php
                $selected = session()->hasOldInput('abilities_present') ? old('abilities', []) : ($automation?->abilities ?? ['catalog:read']);
            @endphp
            @foreach($abilities as $value => $label)
                <label class="flex gap-2 items-start text-sm"><input type="checkbox" name="abilities[]" value="{{ $value }}" @checked(in_array($value, $selected))> {{ $label }}</label>
            @endforeach
            <p class="text-sm text-gray-600 dark:text-slate-300">Enable only what your automation needs. Credential access is separate and disabled by default.</p>
        </div>
        @php
            $groups = [
                'OpenRouter' => [
                    ['openrouter_api_key', 'API key', 'sk-or-v1-Ã¢â‚¬Â¦', true],
                    ['openrouter_model', 'Model ID', 'provider/model:free', false],
                ],
                'Telegram' => [
                    ['telegram_bot_token', 'Bot token', '123456789:your-bot-token', true],
                    ['telegram_allowed_user_ids', 'Allowed staff user IDs', '123456789, 987654321', false],
                ],
                'Facebook' => [
                    ['facebook_page_id', 'Page ID', '123456789012345', false],
                    ['facebook_page_token', 'Page access token', 'Paste your Page access token', true],
                    ['facebook_app_secret', 'App secret', 'Paste your Meta app secret', true],
                    ['facebook_verify_token', 'Webhook verify token', 'Choose a random verification token', true],
                ],
                'n8n' => [
                    ['n8n_webhook_url', 'Workflow webhook URL', 'https://your-n8n.example/webhook/your-workflow', false],
                    ['n8n_webhook_secret', 'Webhook authentication secret', 'Paste the secret expected by your workflow', true],
                ],
            ];
        @endphp
        @foreach($groups as $heading => $fields)
            @continue($heading === 'Facebook' && $facebookConnected)
            @if($heading === 'Facebook')
                <details><summary class="cursor-pointer text-sm text-gray-600 dark:text-slate-300">Advanced: manual Facebook connection</summary>
            @endif
            <section class="p-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 space-y-4">
                <h2 class="font-semibold text-gray-900 dark:text-white">{{ $heading }}</h2>
                @foreach($fields as [$field, $label, $placeholder, $secret])
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ $label }}
                            @if($secret && isset($configured[$field])) <span class="text-green-700 dark:text-green-300"> Ã‚Â· Saved</span> @endif
                        </label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $secret ? 'password' : ($field === 'n8n_webhook_url' ? 'url' : 'text') }}"
                            value="{{ $secret ? '' : old($field, $settings[$field] ?? '') }}"
                            placeholder="{{ $secret && isset($configured[$field]) ? 'Leave blank to keep the saved key' : $placeholder }}"
                            autocomplete="{{ $secret ? 'new-password' : 'off' }}" spellcheck="false"
                            class="w-full mt-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                        @if($secret && isset($configured[$field]))
                            <label class="flex gap-2 items-center text-sm mt-2"><input type="checkbox" name="clear_secrets[]" value="{{ $field }}"> Remove saved key</label>
                        @endif
                    </div>
                @endforeach
            </section>
            @if($heading === 'Facebook') </details> @endif
        @endforeach
        <p class="text-sm text-gray-600 dark:text-slate-300">Saved service keys are encrypted. Blank secret fields keep existing keys. These settings do not connect accounts or register webhooks automatically.</p>
        <button class="w-full px-4 py-3 rounded-xl bg-primary-600 text-white font-medium">Save automation settings</button>
    </form>
</div>
@endsection
