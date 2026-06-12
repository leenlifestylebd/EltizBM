<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\StockService;
use Illuminate\Console\Command;

class SeedWarehouseStock extends Command
{
    protected $signature   = 'app:seed-warehouse-stock';
    protected $description = 'Seed warehouse_stocks table from existing product.stock values (run once after migration)';

    public function handle(StockService $stock): int
    {
        $businesses = Business::all();
        $total = 0;

        foreach ($businesses as $biz) {
            $defaultId = $stock->defaultWarehouseId($biz->id);
            if (! $defaultId) {
                $this->warn("Business [{$biz->name}] has no warehouse — skipping.");
                continue;
            }
            $seeded = $stock->seedFromProductStock($biz->id);
            $this->info("Business [{$biz->name}] → {$seeded} products seeded into warehouse #{$defaultId}.");
            $total += $seeded;
        }

        $this->newLine();
        $this->info("Done! {$total} products seeded total.");
        return self::SUCCESS;
    }
}
