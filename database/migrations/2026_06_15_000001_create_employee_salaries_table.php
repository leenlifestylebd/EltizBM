<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Salary revision history. Each record is the monthly salary effective from a
 * given month, so a later raise never rewrites past months' pay. Payroll for a
 * month uses the latest record whose effective_from is on or before that month.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('amount', 12, 2);
            $table->date('effective_from'); // first day of the effective month
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });

        // Backfill: seed each salaried employee's current salary, effective from
        // their joining month (or far past), so existing payroll is unchanged.
        $employees = DB::table('employees')->where('salary', '>', 0)->get();
        foreach ($employees as $e) {
            $eff = $e->joining_date
                ? Carbon::parse($e->joining_date)->startOfMonth()->toDateString()
                : '2000-01-01';
            DB::table('employee_salaries')->insert([
                'business_id'    => $e->business_id,
                'employee_id'    => $e->id,
                'amount'         => $e->salary,
                'effective_from' => $eff,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
