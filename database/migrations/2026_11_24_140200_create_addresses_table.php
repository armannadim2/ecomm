<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            // NIF/NIE/CIF: only needed when the customer wants a full invoice (factura completa).
            $table->string('tax_id', 20)->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('postal_code', 10);
            $table->string('city');
            $table->string('province');
            $table->string('country', 2)->default('ES');
            $table->string('phone', 30);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
