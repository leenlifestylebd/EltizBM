<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * employees.type was ENUM('salary','production'), so a 'both'
 * (salary + production) worker could not be saved. Widen to VARCHAR so
 * the hybrid type — and any future type — is allowed. Type is only a
 * label/filter now; the money math is driven by salary > 0 + production.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE employees MODIFY type VARCHAR(20) NOT NULL DEFAULT 'salary'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE employees MODIFY type ENUM('salary','production') NOT NULL DEFAULT 'salary'");
    }
};
