<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('outlet_id');
            $table->uuid('purchase_order_id')->nullable(); // Can receive without PO
            $table->uuid('supplier_id');
            $table->string('gr_number', 50);
            $table->date('receive_date');
            $table->string('status')->default('draft'); // draft, completed, cancelled
            $table->string('invoice_number')->nullable(); // Supplier invoice
            $table->date('invoice_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->uuid('received_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('restrict');

            $table->unique(['tenant_id', 'gr_number']);
            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->index(['tenant_id', 'receive_date']);
            $table->index(['purchase_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receives');
    }
};
