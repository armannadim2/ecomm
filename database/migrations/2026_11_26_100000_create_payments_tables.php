<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per attempt to pay an order online (a customer may retry after a failed card).
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20);          // redsys | paypal
            $table->string('method', 20);           // card | bizum | paypal
            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('refunded_cents')->default(0);
            $table->string('status', 20)->index();  // pending | paid | failed | cancelled | expired
            // Redsys Ds_Order (12 digits) or PayPal order id: what the gateway knows the payment by.
            $table->string('reference', 64)->nullable()->unique();
            // Redsys authorisation code or PayPal capture id: needed for refunds.
            $table->string('transaction_id', 64)->nullable();
            $table->string('response_code', 20)->nullable();
            $table->string('failure_reason')->nullable();
            // Last gateway answer without card data (Redsys never sends the card number; PayPal payer data is removed).
            $table->json('gateway_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('return_request_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount_cents');
            // gateway = money sent back through Redsys/PayPal; manual = cash or bank transfer done by staff.
            $table->string('channel', 20);
            $table->string('reason', 500);
            $table->string('gateway_reference', 64)->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            // Same checkout form submitted twice (double click, reload) = same order.
            $table->string('idempotency_key', 64)->nullable()->unique()->after('ulid');
            $table->unsignedInteger('refunded_cents')->default(0)->after('savings_cents');
            // Online orders keep their stock reserved until this moment; then the order is cancelled.
            $table->timestamp('payment_expires_at')->nullable()->after('paid_at')->index();
            $table->timestamp('terms_accepted_at')->nullable()->after('placed_at');
            $table->string('cancel_reason', 30)->nullable()->after('cancelled_at');
            $table->string('carrier', 60)->nullable()->after('ship_province');
            $table->string('tracking_number', 80)->nullable()->after('carrier');
            $table->string('tracking_url')->nullable()->after('tracking_number');
            $table->string('shipping_zone', 20)->nullable()->after('tracking_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['payment_expires_at']);
            $table->dropColumn(['idempotency_key', 'refunded_cents', 'payment_expires_at', 'terms_accepted_at', 'cancel_reason', 'carrier', 'tracking_number', 'tracking_url', 'shipping_zone']);
        });
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('payments');
    }
};
