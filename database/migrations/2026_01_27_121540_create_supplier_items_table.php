<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_id');
            $table->uuid('inventory_item_id');
            $table->string('supplier_sku')->nullable(); // Supplier's product code
            $table->uuid('unit_id'); // Supplier's selling unit
            $table->decimal('unit_conversion', 12, 4)->default(1); // Conversion to stock unit
            $table->decimal('price', 15, 2)->default(0); // Purchase price
            $table->integer('lead_time_days')->default(0);
            $table->decimal('min_order_qty', 12, 4)->default(1);
            $table->boolean('is_preferred')->default(false); // Preferred supplier for this item
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');

            $table->unique(['supplier_id', 'inventory_item_id']);
            $table->index(['inventory_item_id', 'is_active']);
            $table->index(['inventory_item_id', 'is_preferred']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_items');
    }
};
