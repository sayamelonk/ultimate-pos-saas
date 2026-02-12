<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batch_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('stock_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained()->cascadeOnDelete();

            // Movement Type
            $table->enum('type', [
                'receive',      // Goods receive
                'sale',         // POS sale
                'transfer_out', // Transfer to another outlet
                'transfer_in',  // Transfer from another outlet
                'adjustment',   // Stock adjustment
                'waste',        // Waste/disposal
                'return',       // Customer return
                'expired',      // Marked as expired
                'production',   // Used in recipe/production
            ]);

            // Quantities
            $table->decimal('quantity', 15, 4); // Positive for in, negative for out
            $table->decimal('balance_before', 15, 4);
            $table->decimal('balance_after', 15, 4);

            // Reference
            $table->string('reference_type')->nullable(); // Model class
            $table->unsignedBigInteger('reference_id')->nullable(); // Model ID
            $table->string('reference_number')->nullable(); // Human readable ref

            $table->text('notes')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['stock_batch_id', 'created_at']);
            $table->index(['outlet_id', 'inventory_item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batch_movements');
    }
};
