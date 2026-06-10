<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('production_rate_id')->nullable()->constrained('production_rates')->onDelete('set null');
            $table->string('product_name');
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->unsignedInteger('assigned_quantity');
            $table->unsignedInteger('completed_quantity')->default(0);
            $table->enum('status', ['assigned', 'in_progress', 'partial', 'completed', 'cancelled'])->default('assigned');
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_tasks');
    }
};
