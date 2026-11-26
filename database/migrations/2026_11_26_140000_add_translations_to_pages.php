<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Title and text of a page in other languages: {"ca": {"title": …, "body": …}, "en": {…}}. Spanish stays in title/body. */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};
