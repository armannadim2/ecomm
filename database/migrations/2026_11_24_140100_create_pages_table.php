<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            // legal = required legal text (Aviso legal, Privacidad...), info = normal content page.
            $table->string('type', 20)->default('info');
            $table->boolean('is_published')->default(false);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
