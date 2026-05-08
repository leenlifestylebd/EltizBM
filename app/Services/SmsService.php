<?php

namespace App\Services;

use App\Models\SmsSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected ?SmsSetting $settings;

    public function __construct(int $businessId)
    {
        $this->settings = SmsSetting::where('business_id', $businessId)->first();
    }

    // =============================================
    // SEND SMS
    // =============================================
    public function send(string $phone, string $message): bool
    {
        if (!$this->settings || !$this->settings->api_key) {
            Log::info('SMS not configured for business');
            return false;
        }

        $phone = $this->formatPhone($phone);

        return match($this->settings->provider) {
            'ssl_wireless' => $this->sendViaSslWireless($phone, $message),
            'twilio'       => $this->sendViaTwilio($phone, $message),
            'alpha_net'    => $this->sendViaAlphaNet($phone, $message),
            default        => false,
        };
    }

    // =============================================
    // SSL WIRELESS (Bangladesh)
    // =============================================
    private function sendViaSslWireless(string $phone, string $message): bool
    {
        try {
            $response = Http::get('https://msg.smsclub.mobi/sms/api', [
                'user'     => $this->settings->sender_id,
                'password' => $this->settings->api_key,
                'sms'      => $message,
                'num'      => $phone,
                'type'     => 'text',
            ]);

            Log::info('SSL Wireless SMS sent', ['phone' => $phone, 'response' => $response->body()]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SSL Wireless SMS failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // =============================================
    // ALPHA NET (Bangladesh)
    // =============================================
    private function sendViaAlphaNet(string $phone, string $message): bool
    {
        try {
            $response = Http::post('https://alphanet.net.bd/api/v2/SendSMS', [
                'api_key'    => $this->settings->api_key,
                'senderid'   => $this->settings->sender_id,
                'number'     => $phone,
                'message'    => $message,
                'type'       => 'text',
            ]);

            return $response->successful() && ($response->json('status') ?? '') === 'success';
        } catch (\Exception $e) {
            Log::error('AlphaNet SMS failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // =============================================
    // TWILIO
    // =============================================
    private function sendViaTwilio(string $phone, string $message): bool
    {
        try {
            // Extract account SID from api_key: "SID:AUTH_TOKEN"
            [$accountSid, $authToken] = explode(':', $this->settings->api_key . ':');

            $response = Http::withBasicAuth($accountSid, $authToken)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $this->settings->sender_id,
                    'To'   => '+88' . $phone,
                    'Body' => $message,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Twilio SMS failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // =============================================
    // PRE-BUILT MESSAGE TEMPLATES
    // =============================================
    public function sendOrderConfirmation(Order $order): bool
    {
        if (!$this->settings?->order_confirm || !$order->customer_phone) return false;

        $message = "Dear {$order->customer_name}, your order #{$order->order_number} has been confirmed. Total: ৳{$order->total}. Thank you! - {$order->business->name}";

        return $this->send($order->customer_phone, $message);
    }

    public function sendShipmentUpdate(Order $order, string $courierName, string $trackingId = ''): bool
    {
        if (!$this->settings?->shipment_update || !$order->customer_phone) return false;

        $tracking = $trackingId ? " Tracking: {$trackingId}." : '';
        $message  = "Dear {$order->customer_name}, your order #{$order->order_number} has been shipped via {$courierName}.{$tracking} - {$order->business->name}";

        return $this->send($order->customer_phone, $message);
    }

    public function sendPaymentReceived(Order $order, float $amount): bool
    {
        if (!$this->settings?->payment_received || !$order->customer_phone) return false;

        $message = "Dear {$order->customer_name}, we received ৳{$amount} for order #{$order->order_number}. Due: ৳{$order->due_amount}. - {$order->business->name}";

        return $this->send($order->customer_phone, $message);
    }

    // =============================================
    // FORMAT PHONE (BD)
    // =============================================
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '880')) return $phone;
        if (str_starts_with($phone, '0'))  return '880' . substr($phone, 1);

        return '880' . $phone;
    }

    // =============================================
    // TEST SMS
    // =============================================
    public function test(string $phone): array
    {
        $result = $this->send($phone, 'Test SMS from Fulfillo. If you received this, SMS is working! ✓');
        return [
            'success' => $result,
            'message' => $result ? 'SMS sent successfully!' : 'SMS failed. Check your API credentials.',
        ];
    }
}
