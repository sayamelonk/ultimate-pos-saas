<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_transfer_id');
            $table->uuid('inventory_item_id');
            $table->uuid('batch_id')->nullable(); // For batch-tracked items
            $table->decimal('quantity', 12, 4); // Requested quantity
            $table->decimal('received_qty', 12, 4)->nullable(); // Actual received (may differ)
            $table->uuid('unit_id');
            $table->decimal('cost_price', 15, 2)->default(0); // Cost at transfer
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('batch_id')->references('id')->on('stock_batches')->onDelete('set null');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');

            $table->index(['stock_transfer_id']);
            $table->index(['inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
