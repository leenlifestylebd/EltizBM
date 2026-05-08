<?php
namespace App\Jobs;
use App\Models\WoocommerceSetting;
use App\Services\WooCommerceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncWooCommerceStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public int $timeout = 120;
    public function __construct(public int $businessId) {}
    public function handle(): void
    {
        $settings = WoocommerceSetting::where('business_id',$this->businessId)->where('is_active',true)->where('sync_stock',true)->first();
        if (!$settings) return;
        $service = new WooCommerceService($settings);
        $service->pushStockToWoo($this->businessId);
    }
}
