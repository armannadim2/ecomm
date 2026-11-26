<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Proof of each cookie choice (AEPD): which version, what was accepted, when. No raw IP is stored. */
    public function up(): void
    {
        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->uuid('consent_id')->index();
            $table->unsignedSmallInteger('version');
            $table->boolean('analytics');
            $table->boolean('marketing');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
    }
};
