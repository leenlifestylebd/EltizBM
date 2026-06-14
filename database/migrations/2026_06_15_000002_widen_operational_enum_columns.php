<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert the operational ENUM columns we actually extend over time to VARCHAR.
 * ENUMs throw "Data truncated" (a 500) the moment a new value is saved — this
 * already bit production_rates.stage and employees.type. Values are validated
 * in the app layer, so VARCHAR is safe and future-proof.
 */
return new class extends Migration
{
    private array $cols = [
        // [table, column, varchar length, default ('' means keep NOT NULL, no default)]
        ['attendance',        'status', 20, 'present'],
        ['stock_adjustments', 'type',   20, 'increase'],
        ['production_tasks',  'status', 20, 'assigned'],
        ['employee_payments', 'type',   20, ''],
        ['tailoring_orders',  'stage',  20, 'pending'],
        ['orders',            'status', 30, 'pending'],
        ['orders',            'payment_method', 30, 'cash'],
    ];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->cols as [$table, $col, $len, $default]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $col)) {
                continue;
            }
            $defaultSql = $default === '' ? '' : " DEFAULT '" . $default . "'";
            try {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$col}` VARCHAR({$len}) NOT NULL{$defaultSql}");
            } catch (\Throwable $e) {
                // Skip columns that differ from expectations rather than abort the run.
            }
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: re-imposing the ENUMs could reject rows that
        // now hold newer values. Left as VARCHAR.
    }
};
