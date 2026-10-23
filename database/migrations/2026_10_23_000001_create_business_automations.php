<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('token_hash', 64)->nullable()->unique();
            $table->string('token_suffix', 4)->nullable();
            $table->json('abilities')->nullable();
            $table->json('settings')->nullable();
            $table->text('secrets')->nullable();
            $table->timestamps();
        });
        Schema::create('bot_api_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('request_key', 128);
            $table->string('payload_hash', 64);
            $table->unsignedSmallInteger('status');
            $table->longText('response');
            $table->timestamps();
            $table->unique(['business_id', 'request_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_api_requests');
        Schema::dropIfExists('business_automations');
    }
};
