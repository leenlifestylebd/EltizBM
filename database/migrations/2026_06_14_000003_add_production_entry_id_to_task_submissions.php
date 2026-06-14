<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link each task submission to the production entry (payment) it created, so an
 * admin can edit or delete a submission and we can adjust the matching payment
 * and the task's completed quantity in lock-step.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('production_entry_id')->nullable()->after('production_task_id');
            $table->index('production_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->dropIndex(['production_entry_id']);
            $table->dropColumn('production_entry_id');
        });
    }
};
