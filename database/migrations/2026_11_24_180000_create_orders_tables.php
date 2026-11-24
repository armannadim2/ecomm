<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            // Human order number shown to customers and staff, e.g. TM-2026-00001.
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->index();
            $table->string('fulfillment', 20);
            $table->string('payment_method', 20);
            $table->string('payment_status', 20)->default('pending');
            // Code the customer shows at the counter (Click & Collect).
            $table->string('pickup_code', 20)->nullable()->index();

            // Contact and billing data copied at checkout (orders must not change when the customer edits their profile).
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 30);
            $table->string('tax_id', 20)->nullable();

            // Delivery address (home delivery only).
            $table->string('ship_line1')->nullable();
            $table->string('ship_line2')->nullable();
            $table->string('ship_postal_code', 10)->nullable();
            $table->string('ship_city')->nullable();
            $table->string('ship_province')->nullable();

            // Amounts in cents, IVA included.
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('shipping_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->unsignedInteger('tax_cents');
            $table->unsignedInteger('savings_cents')->default(0);

            $table->string('locale', 5)->default('es');
            $table->boolean('marketing_opt_in')->default(false);
            $table->text('customer_note')->nullable();
            $table->text('staff_note')->nullable();
            $table->timestamp('placed_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Copied at checkout so the order stays correct if the product changes later.
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku', 64);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('compare_at_price_cents')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedTinyInteger('tax_rate');
            $table->unsignedInteger('line_total_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
