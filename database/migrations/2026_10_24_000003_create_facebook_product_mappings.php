<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_product_mappings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('page_id', 64);
            $t->string('source_type', 8);
            $t->string('external_id', 100);
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->boolean('auto_reply')->default(false);
            $t->timestamps();
            $t->unique(['business_id', 'page_id', 'source_type', 'external_id'], 'facebook_product_context_unique');
        });
        Schema::create('facebook_conversation_contexts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('page_id', 64);
            $t->string('sender_hash', 64);
            $t->string('ad_id', 64);
            $t->timestamp('seen_at');
            $t->unique(['business_id', 'page_id', 'sender_hash'], 'facebook_conversation_context_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_conversation_contexts');
        Schema::dropIfExists('facebook_product_mappings');
    }
};
