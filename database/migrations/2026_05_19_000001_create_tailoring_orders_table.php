<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tailoring_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('order_number', 20)->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 20)->nullable();

            // Measurements (in inches unless noted)
            $table->decimal('height',      5, 2)->nullable();
            $table->decimal('body_fit',    5, 2)->nullable();
            $table->decimal('body_loose',  5, 2)->nullable();
            $table->decimal('shoulder',    5, 2)->nullable();
            $table->decimal('sleeve',      5, 2)->nullable();
            $table->decimal('sleeve_open', 5, 2)->nullable();
            $table->decimal('neck',        5, 2)->nullable();
            $table->decimal('round_gher',  5, 2)->nullable();
            $table->text('measurement_note')->nullable();

            // Design / fabric
            $table->string('fabric_type')->nullable();
            $table->string('color')->nullable();
            $table->text('design_note')->nullable();

            // Pricing
            $table->decimal('price',        10, 2)->default(0);
            $table->decimal('advance_paid', 10, 2)->default(0);

            // Stage tracking
            $table->enum('stage', ['pending','cutting','stitching','finishing','ready','delivered','cancelled'])
                  ->default('pending');

            $table->date('delivery_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'stage']);
            $table->index(['business_id', 'created_at']);
        });

        // Add tailoring_order_id to production_tasks for linking
        Schema::table('production_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('tailoring_order_id')->nullable()->after('business_id');
            $table->foreign('tailoring_order_id')->references('id')->on('tailoring_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_tasks', function (Blueprint $table) {
            $table->dropForeign(['tailoring_order_id']);
            $table->dropColumn('tailoring_order_id');
        });
        Schema::dropIfExists('tailoring_orders');
    }
};
