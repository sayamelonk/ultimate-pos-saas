<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receive_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('goods_receive_id');
            $table->uuid('purchase_order_item_id')->nullable();
            $table->uuid('inventory_item_id');
            $table->uuid('unit_id');
            $table->decimal('unit_conversion', 12, 4)->default(1);
            $table->decimal('quantity', 12, 4); // Received quantity
            $table->decimal('stock_qty', 12, 4); // Converted to stock unit
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('batch_number', 50)->nullable(); // For batch tracking
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('goods_receive_id')->references('id')->on('goods_receives')->onDelete('cascade');
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items')->onDelete('set null');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');

            $table->index(['goods_receive_id']);
            $table->index(['inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receive_items');
    }
};
