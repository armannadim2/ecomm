<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Visual editor: work in progress, not shown in the shop until "Publicar" copies it to `blocks`.
            $table->json('draft_blocks')->nullable()->after('blocks');
            $table->timestamp('draft_saved_at')->nullable()->after('draft_blocks');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['draft_blocks', 'draft_saved_at']);
        });
    }
};
