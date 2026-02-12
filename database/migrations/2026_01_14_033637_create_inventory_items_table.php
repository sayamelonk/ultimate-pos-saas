<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('unit_id'); // Primary/stock unit
            $table->uuid('purchase_unit_id')->nullable(); // Unit for purchasing (e.g., carton)
            $table->string('sku', 50);
            $table->string('barcode', 50)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('purchase_unit_conversion', 12, 4)->default(1); // How many stock units in purchase unit
            $table->decimal('cost_price', 15, 2)->default(0); // Average/standard cost
            $table->decimal('min_stock', 12, 4)->default(0); // Minimum stock level
            $table->decimal('max_stock', 12, 4)->nullable(); // Maximum stock level
            $table->decimal('reorder_point', 12, 4)->default(0); // When to reorder
            $table->decimal('reorder_qty', 12, 4)->default(0); // How much to reorder
            $table->integer('shelf_life_days')->nullable(); // For expiry tracking
            $table->boolean('track_batches')->default(false); // Enable batch/lot tracking
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('inventory_categories')->onDelete('set null');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');
            $table->foreign('purchase_unit_id')->references('id')->on('units')->onDelete('set null');

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
