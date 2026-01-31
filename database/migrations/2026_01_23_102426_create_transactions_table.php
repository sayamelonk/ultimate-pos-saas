<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('outlet_id');
            $table->uuid('pos_session_id');
            $table->uuid('customer_id')->nullable();
            $table->uuid('user_id');
            $table->string('transaction_number', 50);
            $table->enum('type', ['sale', 'refund', 'void']);
            $table->uuid('original_transaction_id')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('service_charge_amount', 15, 2)->default(0);
            $table->decimal('rounding', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->decimal('payment_amount', 15, 2);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('service_charge_percentage', 5, 2)->default(0);
            $table->decimal('points_earned', 12, 2)->default(0);
            $table->decimal('points_redeemed', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'completed', 'voided'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('restrict');
            $table->foreign('pos_session_id')->references('id')->on('pos_sessions')->onDelete('restrict');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('original_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->unique(['tenant_id', 'transaction_number']);
            $table->index(['tenant_id', 'outlet_id', 'completed_at']);
            $table->index(['pos_session_id', 'status']);
            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
