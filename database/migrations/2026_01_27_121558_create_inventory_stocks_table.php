<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id');
            $table->uuid('inventory_item_id');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_qty', 15, 4)->default(0); // Reserved for orders
            $table->decimal('avg_cost', 15, 2)->default(0); // Weighted average cost
            $table->decimal('last_cost', 15, 2)->default(0); // Last purchase cost
            $table->timestamp('last_received_at')->nullable();
            $table->timestamp('last_issued_at')->nullable();
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');

            $table->unique(['outlet_id', 'inventory_item_id']);
            $table->index(['outlet_id', 'quantity']); // For low stock queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
