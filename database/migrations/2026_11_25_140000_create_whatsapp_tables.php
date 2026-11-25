<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp customer service (WhatsApp Cloud API + AI assistant).
 * One conversation per customer WhatsApp number; messages are kept 2 years
 * (privacy policy), pruned by `whatsapp:prune`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->string('wa_id', 32)->unique();           // customer number in international format, digits only
            $table->string('profile_name')->nullable();
            $table->string('status', 16)->default('open');   // open · human · closed
            $table->boolean('agent_paused')->default(false);  // a person has taken over
            $table->string('handoff_reason', 500)->nullable();
            $table->timestamp('last_inbound_at')->nullable(); // starts Meta's 24-hour service window
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('direction', 8);                   // in · out
            $table->string('author', 16);                     // customer · agent · staff · system
            $table->text('body');
            $table->string('wa_message_id', 191)->nullable()->unique(); // Meta id: makes webhook retries harmless
            $table->string('status', 16)->nullable();         // sent · delivered · read · failed
            $table->text('error')->nullable();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->json('meta')->nullable();                 // message type, token usage…
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
    }
};
