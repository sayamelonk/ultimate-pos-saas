<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('table_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->nullable()->constrained()->nullOnDelete();

            $table->string('order_number')->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', [
                'pending',
                'waiting_payment',
                'paid',
                'pay_at_counter',
                'processing',
                'completed',
                'cancelled',
                'expired',
            ])->default('pending');

            $table->enum('payment_method', ['qris', 'pay_at_counter'])->nullable();

            // Xendit fields
            $table->string('xendit_invoice_id')->nullable();
            $table->string('xendit_invoice_url')->nullable();
            $table->json('xendit_response')->nullable();
            $table->timestamp('xendit_expired_at')->nullable();

            // Totals
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('service_charge_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('tax_mode')->default('exclusive');
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('service_charge_percentage', 5, 2)->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->index('xendit_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_orders');
    }
};
