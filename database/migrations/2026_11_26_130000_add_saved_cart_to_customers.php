<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The cart of a logged-in customer, so it follows them to another device: {"variant ulid": quantity}. */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->json('saved_cart')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('saved_cart');
        });
    }
};
