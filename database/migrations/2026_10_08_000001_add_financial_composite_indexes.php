<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive performance indexes for the financial hot paths. Every index is guarded
 * so re-running on a live database (where some may already exist) cannot fail.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('orders', ['business_id', 'status'], 'orders_business_status_index');
        $this->addIndex('orders', ['business_id', 'created_at'], 'orders_business_created_index');
        $this->addIndex('transactions', ['business_id', 'type', 'transaction_date'], 'transactions_business_type_date_index');
        $this->addIndex('production_entries', ['business_id', 'entry_date'], 'production_entries_business_entry_index');
        $this->addIndex('employee_payments', ['business_id', 'payment_date'], 'employee_payments_business_payment_index');
        $this->addIndex('purchase_orders', ['business_id', 'payment_status'], 'purchase_orders_business_paymentstatus_index');
        $this->addIndex('shipments', ['business_id', 'status'], 'shipments_business_status_index');
    }

    public function down(): void
    {
        foreach ([
            'orders'            => ['orders_business_status_index', 'orders_business_created_index'],
            'transactions'      => ['transactions_business_type_date_index'],
            'production_entries'=> ['production_entries_business_entry_index'],
            'employee_payments' => ['employee_payments_business_payment_index'],
            'purchase_orders'   => ['purchase_orders_business_paymentstatus_index'],
            'shipments'         => ['shipments_business_status_index'],
        ] as $table => $names) {
            foreach ($names as $name) {
                if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
    }

    private function indexExists(string $table, string $name): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list(\"{$table}\")"))
                ->contains(fn ($index) => ($index->name ?? null) === $name);
        }

        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn ($index) => ($index->Key_name ?? null) === $name);
    }
};
