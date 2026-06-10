<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add consignment_id to shipments (Steadfast stores this but column was missing)
        if (!Schema::hasColumn('shipments', 'consignment_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->string('consignment_id')->nullable()->after('tracking_id')->index();
            });
        }

        // 2. Add color + size to production_entries (ProductionController stored these but columns were missing)
        if (!Schema::hasColumn('production_entries', 'color')) {
            Schema::table('production_entries', function (Blueprint $table) {
                $table->string('color', 100)->nullable()->after('production_rate_id');
            });
        }

        if (!Schema::hasColumn('production_entries', 'size')) {
            Schema::table('production_entries', function (Blueprint $table) {
                $table->string('size', 100)->nullable()->after('color');
            });
        }

        // 3. Add fabric_consumption to products for garment BOM prep
        if (!Schema::hasColumn('products', 'fabric_consumption')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('fabric_consumption', 8, 3)->nullable()->after('weight')
                    ->comment('Fabric yards/meters needed per piece');
            });
        }
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('consignment_id');
        });

        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropColumn(['color', 'size']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('fabric_consumption');
        });
    }
};
