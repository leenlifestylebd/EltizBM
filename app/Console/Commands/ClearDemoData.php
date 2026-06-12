<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearDemoData extends Command
{
    protected $signature   = 'app:demo-clear {--force : Skip confirmation prompt}';
    protected $description = 'Delete all transactional demo data (orders, production, payments) while keeping products & employees';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->warn('This will DELETE all transactional data:');
            $this->line('  • Orders & POS sales');
            $this->line('  • Production entries, tasks, submissions');
            $this->line('  • Employee payments');
            $this->line('  • Tailoring orders');
            $this->line('  • Material purchases & purchase orders');
            $this->line('  • Transactions, shipments, stock adjustments');
            $this->line('  • Attendance & payroll periods');
            $this->line('  • Activity log');
            $this->newLine();
            $this->line('Products, employees, production rates, and settings are KEPT.');
            $this->newLine();

            if (! $this->confirm('Are you sure you want to proceed?')) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        $this->info('Clearing demo data…');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            // Production
            'task_submissions',
            'production_tasks',
            'production_entries',
            'production_logs',
            'payroll_periods',
            'attendance',
            // Employee finance
            'employee_payments',
            // Sales
            'order_items',
            'shipments',
            'orders',
            // Tailoring
            'tailoring_orders',
            // Procurement
            'purchase_order_items',
            'purchase_orders',
            'material_purchases',
            // Finance
            'transactions',
            // Inventory adjustments
            'stock_adjustments',
            // Audit
            'activity_logs',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  ✓ {$table}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Reset customer running balances
        DB::table('customers')->update(['due_amount' => 0, 'total_purchase' => 0]);
        $this->line('  ✓ customers (balances reset)');

        // Reset vendor balances
        DB::table('vendors')->update(['due_amount' => 0]);
        $this->line('  ✓ vendors (balances reset)');

        $this->newLine();
        $this->info('Done! All demo data cleared.');

        return self::SUCCESS;
    }
}
