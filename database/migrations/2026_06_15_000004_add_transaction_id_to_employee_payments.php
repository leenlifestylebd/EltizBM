<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link an employee payment to the cash-book expense transaction it created
 * (advance/salary), so deleting a wrong payment also reverses its expense.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->nullable()->after('id');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payments', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }
};
