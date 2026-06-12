<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->decimal('rate_used', 10, 2)->nullable()->after('submitted_quantity');
            $table->decimal('amount', 12, 2)->nullable()->after('rate_used');
        });
    }

    public function down(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->dropColumn(['rate_used', 'amount']);
        });
    }
};
