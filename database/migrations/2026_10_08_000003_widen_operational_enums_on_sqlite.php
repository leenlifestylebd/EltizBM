<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The existing enum-widening migrations are MySQL-only, which leaves SQLite with strict
 * CHECK constraints (e.g. production_tasks.stage rejecting 'cutting'). This new,
 * additive migration widens the same columns on SQLite so local/dev databases match
 * production. It does nothing on other drivers and never drops data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        foreach (['production_rates', 'production_tasks'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'stage')) {
                Schema::table($table, fn (Blueprint $b) => $b->string('stage', 20)->default('sewing')->change());
            }
        }

        if (Schema::hasTable('employee_payments') && Schema::hasColumn('employee_payments', 'type')) {
            Schema::table('employee_payments', fn (Blueprint $b) => $b->string('type', 20)->default('salary')->change());
        }

        if (Schema::hasTable('stock_adjustments') && Schema::hasColumn('stock_adjustments', 'type')) {
            Schema::table('stock_adjustments', fn (Blueprint $b) => $b->string('type', 20)->default('increase')->change());
        }
    }

    public function down(): void
    {
        // Widening is intentionally irreversible — no data is dropped.
    }
};
