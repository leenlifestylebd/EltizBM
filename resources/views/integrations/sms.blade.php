{{-- ============================================================
     FILE: resources/views/integrations/sms.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'SMS Settings')

@section('content')

<div class="max-w-2xl mx-auto" x-data="smsApp()">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('integrations.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">SMS Notifications</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Send automated SMS to customers</p>
        </div>
    </div>

    {{-- API Config --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Provider Settings</h3>

        <form method="POST" action="{{ route('integrations.sms.save') }}" class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">SMS Provider</label>
                <select name="provider" x-model="provider"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select provider</option>
                    <option value="ssl_wireless" {{ $smsSettings?->provider === 'ssl_wireless' ? 'selected':'' }}>SSL Wireless (Bangladesh)</option>
                    <option value="alpha_net"    {{ $smsSettings?->provider === 'alpha_net'    ? 'selected':'' }}>Alpha Net (Bangladesh)</option>
                    <option value="twilio"       {{ $smsSettings?->provider === 'twilio'       ? 'selected':'' }}>Twilio (International)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                    <span x-text="provider === 'twilio' ? 'Account SID:Auth Token' : 'API Key'"></span>
                    <span class="text-red-500">*</span>
                </label>
                <input type="text" name="api_key" value="{{ $smsSettings?->api_key ? '••••••••' : '' }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono"
                       :placeholder="provider === 'twilio' ? 'ACxxxxxxxx:your_auth_token' : 'Your API key'">
                <p x-show="provider === 'twilio'" class="text-xs text-gray-400 mt-1">Format: AccountSID:AuthToken</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Sender ID / From Number</label>
                <input type="text" name="sender_id" value="{{ $smsSettings?->sender_id }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                       placeholder="e.g. YourBrand or +1234567890">
            </div>

            {{-- Notification Triggers --}}
            <div class="border-t border-gray-100 dark:border-slate-700 pt-4">
                <p class="text-xs font-semibold text-gray-700 dark:text-slate-300 mb-3">Notification Triggers</p>
                <div class="space-y-2.5">
                    @foreach([
                        ['key'=>'order_confirm',   'label'=>'Order Confirmation',    'desc'=>'Send SMS when new order is placed'],
                        ['key'=>'shipment_update', 'label'=>'Shipment Update',       'desc'=>'Send SMS when order is shipped'],
                        ['key'=>'payment_received','label'=>'Payment Received',      'desc'=>'Send SMS when payment is collected'],
                    ] as $trigger)
                    <label class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $trigger['label'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $trigger['desc'] }}</p>
                        </div>
                        <div class="relative ml-3 flex-shrink-0">
                            <input type="hidden" name="{{ $trigger['key'] }}" value="0">
                            <input type="checkbox" name="{{ $trigger['key'] }}" value="1"
                                   {{ $smsSettings?->{$trigger['key']} ? 'checked':'' }}
                                   class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer peer-checked:bg-primary-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" @click="testSms()"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-200 dark:border-slate-600 text-sm font-medium text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4" :class="testing?'animate-spin':''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span x-text="testing ? 'Sending...' : 'Test SMS'"></span>
                </button>
                <button type="submit"
                        class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors">
                    Save Settings
                </button>
            </div>
        </form>

        <div x-show="testResult" x-cloak
             class="mt-3 p-3 rounded-lg text-sm"
             :class="testSuccess ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200'"
             x-text="testResult">
        </div>
    </div>

    {{-- SMS Templates Preview --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">SMS Templates</h3>
        <div class="space-y-3">
            @foreach([
                ['label'=>'Order Confirmation', 'msg'=>'Dear {customer_name}, your order #{order_number} has been confirmed. Total: ৳{total}. Thank you! - {business_name}'],
                ['label'=>'Shipment Update',    'msg'=>'Dear {customer_name}, your order #{order_number} has been shipped via {courier}. Tracking: {tracking_id}. - {business_name}'],
                ['label'=>'Payment Received',   'msg'=>'Dear {customer_name}, we received ৳{amount} for order #{order_number}. Due: ৳{due}. - {business_name}'],
            ] as $tmpl)
            <div class="p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                <p class="text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ $tmpl['label'] }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 font-mono leading-relaxed">{{ $tmpl['msg'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function smsApp() {
    return {
        provider: '{{ $smsSettings?->provider ?? '' }}',
        testing: false,
        testResult: '',
        testSuccess: false,

        async testSms() {
            const phone = prompt('Enter phone number to test (e.g. 01XXXXXXXXX):');
            if (!phone) return;

            this.testing = true;
            try {
                const res  = await fetch('/integrations/sms/test', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ phone }),
                });
                const data = await res.json();
                this.testSuccess = data.success;
                this.testResult  = data.message;
            } catch(e) {
                this.testSuccess = false;
                this.testResult  = 'Network error: ' + e.message;
            } finally {
                this.testing = false;
            }
        }
    }
}
</script>
@endpush
