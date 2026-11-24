<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Products can be uploaded by several staff members (Colaboradores) into the
 * one store. Uploads without publish permission wait for review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('created_by_staff_id')->nullable()->after('brand_id')
                ->constrained('staff')->nullOnDelete();
            $table->foreignId('reviewed_by_staff_id')->nullable()->after('created_by_staff_id')
                ->constrained('staff')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('published_at');
            $table->text('review_note')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_staff_id');
            $table->dropConstrainedForeignId('reviewed_by_staff_id');
            $table->dropColumn(['submitted_at', 'review_note']);
        });
    }
};
