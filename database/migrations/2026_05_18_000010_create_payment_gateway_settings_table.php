<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('gateway'); // bkash, nagad
            $table->boolean('is_active')->default(false);
            $table->boolean('sandbox')->default(true);
            $table->json('credentials'); // encrypted credentials
            $table->timestamps();
            $table->unique(['business_id', 'gateway']);
        });

        // Add payment_gateway_tx_id to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('payment_method');
            $table->string('gateway_payment_id')->nullable()->after('payment_gateway');
            $table->string('gateway_trx_id')->nullable()->after('gateway_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'gateway_payment_id', 'gateway_trx_id']);
        });
    }
};
