<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_adjustment_id');
            $table->uuid('inventory_item_id');
            $table->uuid('batch_id')->nullable(); // For batch-tracked items
            $table->decimal('system_qty', 15, 4); // System stock before adjustment
            $table->decimal('actual_qty', 15, 4); // Physical count / new value
            $table->decimal('difference', 15, 4); // actual - system
            $table->decimal('cost_price', 15, 2)->default(0); // Cost at time of adjustment
            $table->decimal('value_difference', 15, 2)->default(0); // difference * cost
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_adjustment_id')->references('id')->on('stock_adjustments')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('batch_id')->references('id')->on('stock_batches')->onDelete('set null');

            $table->index(['stock_adjustment_id']);
            $table->index(['inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
