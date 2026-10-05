<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single-warehouse model: stock is tracked on products.stock only, and each
 * business keeps exactly one "Main Warehouse" row.
 *
 * Nothing is dropped. The warehouses / warehouse_stocks tables and the
 * orders.warehouse_id column are retained so historical records stay readable
 * and a rollback keeps working; application code no longer reads or writes them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warehouses') || !Schema::hasTable('businesses')) {
            return;
        }

        $businessIds = DB::table('businesses')->pluck('id');

        foreach ($businessIds as $businessId) {
            $warehouses = DB::table('warehouses')->where('business_id', $businessId)->get();

            if ($warehouses->isEmpty()) {
                DB::table('warehouses')->insert([
                    'business_id' => $businessId,
                    'name'        => 'Main Warehouse',
                    'address'     => null,
                    'is_default'  => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                continue;
            }

            // Keep the row that is already the default, otherwise the first one,
            // so historical orders keep resolving to a real warehouse.
            $keep = $warehouses->firstWhere('is_default', true) ?? $warehouses->first();

            DB::table('warehouses')
                ->where('business_id', $businessId)
                ->update(['is_default' => false]);

            DB::table('warehouses')
                ->where('id', $keep->id)
                ->update(['is_default' => true, 'name' => 'Main Warehouse', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Intentionally empty: renaming the surviving warehouse back is not
        // meaningful, and the tables and orders.warehouse_id are left intact.
    }
};