<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Customer returns and exchanges (backlog ORD-04). Refund amounts are integer cents, IVA included. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            // Human reference shown to customers and staff, e.g. DEV-2026-00001.
            $table->string('number', 30)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16);                   // return · exchange
            $table->string('status', 16)->index();
            $table->string('reason', 24);
            $table->text('comment')->nullable();
            $table->unsignedInteger('refund_cents')->default(0); // value of the items: what a refund would be
            $table->text('staff_note')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->string('locale', 5)->default('es');
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('resolved_at')->nullable(); // refunded or exchanged
            $table->timestamps();
        });

        Schema::create('return_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            // Copied so the request stays readable if the product changes.
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_request_items');
        Schema::dropIfExists('return_requests');
    }
};
