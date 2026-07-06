<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * orders.source is an ENUM('manual','woocommerce','pos','web'); saving the new
 * 'wholesale' channel throws "Data truncated for column 'source'" (a 500).
 * Convert to VARCHAR so any current/future channel is accepted (values are
 * validated in the app layer).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'source')) {
            try {
                DB::statement("ALTER TABLE `orders` MODIFY `source` VARCHAR(30) NOT NULL DEFAULT 'manual'");
            } catch (\Throwable $e) {
                // Leave as-is if the column differs from expectations.
            }
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: re-imposing the ENUM could reject 'wholesale' rows.
    }
};
