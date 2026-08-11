<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Factory workers log in with phone + PIN and have no email, but
     * EmployeeController@setPin creates their user with 'email' => null while
     * users.email was NOT NULL — so setting a worker PIN for anyone without an
     * existing account failed with "Column 'email' cannot be null" (HTTP 500).
     *
     * Make the column nullable. The unique index is kept: MySQL allows any number
     * of NULLs in a unique index, so multiple PIN-only workers coexist fine.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverting would break every PIN-only worker account, so this is a no-op
        // rather than a change that fails on real data.
    }
};
