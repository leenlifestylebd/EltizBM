<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =============================================
        // Bill of Materials — what materials a product needs
        // =============================================
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('product_id');
            $table->string('material_name');
            $table->string('category')->default('fabric');
            $table->decimal('quantity_per_piece', 10, 4);
            $table->string('unit', 30)->default('yard');   // yard, meter, pcs, gram
            $table->decimal('wastage_percent', 5, 2)->default(0);
            $table->string('color', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'product_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // =============================================
        // Payroll Periods — lock/finalize monthly payroll
        // =============================================
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('period_month', 7);   // e.g. "2026-05"
            $table->decimal('gross_earning', 12, 2)->default(0);
            $table->integer('total_pieces')->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('net_due', 12, 2)->default(0);
            $table->boolean('is_finalized')->default(false);
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'employee_id', 'period_month'], 'payroll_unique_period');
            $table->index(['business_id', 'period_month']);
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('bill_of_materials');
    }
};
