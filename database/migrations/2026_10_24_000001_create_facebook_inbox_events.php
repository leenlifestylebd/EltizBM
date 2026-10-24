<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_inbox_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('page_id', 64);
            $table->string('event_key', 64);
            $table->string('kind', 24);
            $table->longText('payload');
            $table->string('status', 24)->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('lease_hash', 64)->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->text('draft')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'event_key']);
            $table->index(['business_id', 'status', 'lease_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_inbox_events');
    }
};
