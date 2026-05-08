<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add type column to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('type', ['salary', 'production'])->default('salary')->after('status');
            $table->integer('working_days')->default(26)->after('type');
            $table->decimal('per_day_salary', 12, 2)->default(0)->after('working_days');
        });

        // Production rates table
        Schema::create('production_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->decimal('rate', 12, 2)->default(0);
            $table->string('unit')->default('piece');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Production entries table
        Schema::create('production_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('production_rate_id')->constrained('production_rates')->onDelete('cascade');
            $table->date('entry_date');
            $table->integer('quantity')->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Employee payments table (advance/salary)
        Schema::create('employee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->date('payment_date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('type', ['advance', 'salary', 'bonus', 'deduction']);
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payments');
        Schema::dropIfExists('production_entries');
        Schema::dropIfExists('production_rates');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['type', 'working_days', 'per_day_salary']);
        });
    }
};