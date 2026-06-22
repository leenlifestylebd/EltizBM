<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the payroll/ledger hot path. The ledger loops per
 * employee and filters a date range on production_entries and employee_payments,
 * and the team views filter attendance by business + date. Single-column indexes
 * exist; these composites serve the combined filters far better.
 */
return new class extends Migration
{
    private array $indexes = [
        ['production_entries', ['employee_id', 'entry_date'],   'prod_entries_emp_date_idx'],
        ['employee_payments',  ['employee_id', 'payment_date'], 'emp_payments_emp_date_idx'],
        ['attendance',         ['business_id', 'attendance_date'], 'attendance_biz_date_idx'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $cols, $name]) {
            if (!Schema::hasTable($table)) continue;
            foreach ($cols as $c) if (!Schema::hasColumn($table, $c)) continue 2;
            try {
                Schema::table($table, fn($t) => $t->index($cols, $name));
            } catch (\Throwable $e) {
                // Index already exists (or equivalent) — leave it.
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, $cols, $name]) {
            if (!Schema::hasTable($table)) continue;
            try {
                Schema::table($table, fn($t) => $t->dropIndex($name));
            } catch (\Throwable $e) {}
        }
    }
};
