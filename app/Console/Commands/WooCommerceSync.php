<?php
namespace App\Console\Commands;
use App\Models\WoocommerceSetting;
use App\Jobs\SyncWooCommerceOrders;
use App\Jobs\SyncWooCommerceStock;
use Illuminate\Console\Command;

class WooCommerceSync extends Command
{
    protected $signature = 'woo:sync {--type=all : orders|stock|all}';
    protected $description = 'Sync WooCommerce orders and stock for all businesses';

    public function handle(): void
    {
        $type = $this->option('type');
        $settings = WoocommerceSetting::where('is_active', true)->get();
        foreach ($settings as $setting) {
            if (in_array($type, ['orders', 'all'])) SyncWooCommerceOrders::dispatch($setting->business_id);
            if (in_array($type, ['stock', 'all']) && $setting->sync_stock) SyncWooCommerceStock::dispatch($setting->business_id);
        }
        $this->info("Dispatched sync jobs for {$settings->count()} stores.");
    }
}
