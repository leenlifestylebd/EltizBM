<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly int    $businessId,
        private readonly string $type,       // order_confirmation | shipment_update | payment_received
        private readonly array  $data        // serializable array of order/shipment info
    ) {
        $this->onQueue('sms');
    }

    public function handle(): void
    {
        try {
            $sms = new SmsService($this->businessId);

            match ($this->type) {
                'order_confirmation' => $this->sendOrderConfirmation($sms),
                'shipment_update'    => $this->sendShipmentUpdate($sms),
                'payment_received'   => $this->sendPaymentReceived($sms),
                default => Log::warning("Unknown SMS type: {$this->type}"),
            };
        } catch (\Exception $e) {
            Log::error("SendSmsJob failed [{$this->type}]", [
                'business_id' => $this->businessId,
                'error'       => $e->getMessage(),
            ]);
            throw $e; // Re-throw so queue retries
        }
    }

    private function sendOrderConfirmation(SmsService $sms): void
    {
        if (!($this->data['phone'] ?? null)) return;

        $message = "Dear {$this->data['customer_name']}, your order #{$this->data['order_number']} (৳{$this->data['total']}) has been confirmed. Thank you! - {$this->data['business_name']}";
        $sms->send($this->data['phone'], $message);
    }

    private function sendShipmentUpdate(SmsService $sms): void
    {
        if (!($this->data['phone'] ?? null)) return;

        $message = "Your order #{$this->data['order_number']} has been shipped via {$this->data['courier']}. Tracking: {$this->data['tracking_id']}. - {$this->data['business_name']}";
        $sms->send($this->data['phone'], $message);
    }

    private function sendPaymentReceived(SmsService $sms): void
    {
        if (!($this->data['phone'] ?? null)) return;

        $message = "Payment of ৳{$this->data['amount']} received for order #{$this->data['order_number']}. Thank you! - {$this->data['business_name']}";
        $sms->send($this->data['phone'], $message);
    }
}
