<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // orders — heavily filtered in listing/reports
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->hasIndex('orders', 'orders_status_index'))
                $table->index('status', 'orders_status_index');
            if (!$this->hasIndex('orders', 'orders_source_index'))
                $table->index('source', 'orders_source_index');
            if (!$this->hasIndex('orders', 'orders_payment_status_index'))
                $table->index('payment_status', 'orders_payment_status_index');
            if (!$this->hasIndex('orders', 'orders_created_at_index'))
                $table->index('created_at', 'orders_created_at_index');
        });

        // transactions — used in every financial report
        Schema::table('transactions', function (Blueprint $table) {
            if (!$this->hasIndex('transactions', 'transactions_type_index'))
                $table->index('type', 'transactions_type_index');
            if (!$this->hasIndex('transactions', 'transactions_transaction_date_index'))
                $table->index('transaction_date', 'transactions_transaction_date_index');
            if (!$this->hasIndex('transactions', 'transactions_category_id_index'))
                $table->index('category_id', 'transactions_category_id_index');
        });

        // production_entries — worker ledger, payroll queries
        Schema::table('production_entries', function (Blueprint $table) {
            if (!$this->hasIndex('production_entries', 'production_entries_entry_date_index'))
                $table->index('entry_date', 'production_entries_entry_date_index');
            if (!$this->hasIndex('production_entries', 'production_entries_employee_id_index'))
                $table->index('employee_id', 'production_entries_employee_id_index');
        });

        // employee_payments — payroll queries
        Schema::table('employee_payments', function (Blueprint $table) {
            if (!$this->hasIndex('employee_payments', 'employee_payments_payment_date_index'))
                $table->index('payment_date', 'employee_payments_payment_date_index');
        });

        // shipments — courier status checks
        Schema::table('shipments', function (Blueprint $table) {
            if (!$this->hasIndex('shipments', 'shipments_status_index'))
                $table->index('status', 'shipments_status_index');
        });

        // employees — active filter
        Schema::table('employees', function (Blueprint $table) {
            if (!$this->hasIndex('employees', 'employees_status_index'))
                $table->index('status', 'employees_status_index');
        });

        // products — stock and active filter
        Schema::table('products', function (Blueprint $table) {
            if (!$this->hasIndex('products', 'products_is_active_index'))
                $table->index('is_active', 'products_is_active_index');
        });

        // material_purchases — payment method filter
        Schema::table('material_purchases', function (Blueprint $table) {
            if (!$this->hasIndex('material_purchases', 'material_purchases_payment_method_index'))
                $table->index('payment_method', 'material_purchases_payment_method_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_source_index');
            $table->dropIndex('orders_payment_status_index');
            $table->dropIndex('orders_created_at_index');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_type_index');
            $table->dropIndex('transactions_transaction_date_index');
            $table->dropIndex('transactions_category_id_index');
        });
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropIndex('production_entries_entry_date_index');
            $table->dropIndex('production_entries_employee_id_index');
        });
        Schema::table('employee_payments', function (Blueprint $table) {
            $table->dropIndex('employee_payments_payment_date_index');
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_status_index');
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_status_index');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_is_active_index');
        });
        Schema::table('material_purchases', function (Blueprint $table) {
            $table->dropIndex('material_purchases_payment_method_index');
        });
    }

    /**
     * DB-agnostic index existence check (MySQL + SQLite).
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) return true;
            }
            return false;
        }

        // MySQL / MariaDB
        $result = DB::select(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?",
            [$table, $indexName]
        );
        return ($result[0]->cnt ?? 0) > 0;
    }
};
