<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id');
            $table->uuid('inventory_item_id');
            $table->uuid('batch_id')->nullable();
            $table->string('type'); // in, out, adjustment, transfer_in, transfer_out, waste
            $table->string('reference_type')->nullable(); // goods_receive, order, adjustment, transfer, waste
            $table->uuid('reference_id')->nullable();
            $table->decimal('quantity', 15, 4); // Positive for in, negative for out
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('stock_before', 15, 4);
            $table->decimal('stock_after', 15, 4);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('stock_batches')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['outlet_id', 'inventory_item_id', 'created_at']);
            $table->index(['outlet_id', 'type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
