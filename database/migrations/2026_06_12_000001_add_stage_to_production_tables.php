<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_rates', function (Blueprint $table) {
            $table->enum('stage', ['sewing', 'ironing', 'folding', 'packing'])
                  ->default('sewing')
                  ->after('unit');
        });

        Schema::table('production_tasks', function (Blueprint $table) {
            $table->enum('stage', ['sewing', 'ironing', 'folding', 'packing'])
                  ->default('sewing')
                  ->after('production_rate_id');
            $table->unsignedBigInteger('source_task_id')
                  ->nullable()
                  ->after('stage');
            $table->foreign('source_task_id')
                  ->references('id')->on('production_tasks')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('production_tasks', function (Blueprint $table) {
            $table->dropForeign(['source_task_id']);
            $table->dropColumn(['stage', 'source_task_id']);
        });

        Schema::table('production_rates', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};
