<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('plan');
            $table->json('print_settings')->nullable()->after('tags');
            $table->json('status_config')->nullable()->after('print_settings');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['tags', 'print_settings', 'status_config']);
        });
    }
};
