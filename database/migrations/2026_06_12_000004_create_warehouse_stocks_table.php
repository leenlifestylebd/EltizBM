<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'warehouse_id']);
            $table->index(['business_id', 'product_id']);
        });

        // Mark one warehouse as the WooCommerce default if none is set
        // (is_default already exists on warehouses table)
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
