<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The `stage` columns were ENUM('sewing','ironing','folding','packing'),
 * so saving a 'cutting' rate or task threw a SQL error (500). Widen both
 * to VARCHAR so the cutting stage — and any future stage — is allowed.
 * Stages are validated in the app layer, so a plain string is sufficient.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return; // ENUM only constrains on MySQL; other drivers store stage as text already.
        }

        DB::statement("ALTER TABLE production_rates MODIFY stage VARCHAR(20) NOT NULL DEFAULT 'sewing'");
        DB::statement("ALTER TABLE production_tasks MODIFY stage VARCHAR(20) NOT NULL DEFAULT 'sewing'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Revert to the original ENUM (drops the cutting option). Best-effort:
        // any existing 'cutting' rows would block this, which is the intended guard.
        DB::statement("ALTER TABLE production_rates MODIFY stage ENUM('sewing','ironing','folding','packing') NOT NULL DEFAULT 'sewing'");
        DB::statement("ALTER TABLE production_tasks MODIFY stage ENUM('sewing','ironing','folding','packing') NOT NULL DEFAULT 'sewing'");
    }
};
