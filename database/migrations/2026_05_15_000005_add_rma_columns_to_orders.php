<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'return_reason')) {
                $table->text('return_reason')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('orders', 'return_notes')) {
                $table->text('return_notes')->nullable()->after('return_reason');
            }
            if (!Schema::hasColumn('orders', 'return_condition')) {
                $table->string('return_condition', 20)->nullable()->after('return_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['return_reason', 'return_notes', 'return_condition']);
        });
    }
};
