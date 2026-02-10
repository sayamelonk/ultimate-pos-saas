<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id');
            $table->uuid('inventory_item_id');
            $table->string('batch_number', 50);
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('initial_qty', 15, 4);
            $table->decimal('current_qty', 15, 4);
            $table->decimal('cost_price', 15, 2)->default(0); // Cost for this batch
            $table->uuid('goods_receive_item_id')->nullable(); // Link to receiving
            $table->string('status')->default('available'); // available, depleted, expired, disposed
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');

            $table->unique(['outlet_id', 'inventory_item_id', 'batch_number']);
            $table->index(['outlet_id', 'inventory_item_id', 'status']);
            $table->index(['outlet_id', 'expiry_date']); // For expiry alerts
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
