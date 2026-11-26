<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Last number used per series and year. Locked while issuing, so numbers never repeat or skip.
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('series', 5);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['series', 'year']);
        });

        // Issued invoices are never edited or deleted (Real Decreto 1619/2012); mistakes are fixed with a corrective invoice.
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);              // simplified | full | corrective
            $table->string('series', 5);             // S | F | R
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->string('code', 20)->unique();    // e.g. S2026-000001
            $table->foreignId('corrects_invoice_id')->nullable()->constrained('invoices')->restrictOnDelete();
            $table->foreignId('payment_refund_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('issued_at');

            // Copies taken when issuing: later changes to the shop or the customer never change an invoice.
            $table->json('seller');                  // {name, tax_id, address}
            $table->string('buyer_name')->nullable();
            $table->string('buyer_tax_id', 20)->nullable();
            $table->string('buyer_address')->nullable();

            $table->json('lines');                   // [{description, quantity, unit_price_cents, tax_rate, total_cents}]
            $table->json('tax_breakdown');           // [{rate, base_cents, tax_cents, total_cents}]
            // Signed cents: corrective invoices are negative.
            $table->integer('base_cents');
            $table->integer('tax_cents');
            $table->integer('total_cents');
            $table->string('reason', 500)->nullable();

            // Chained fingerprint (VeriFactu-ready): each invoice includes the previous one's hash.
            $table->string('previous_hash', 64)->nullable();
            $table->string('hash', 64);
            $table->timestamps();

            $table->unique(['series', 'year', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_sequences');
    }
};
