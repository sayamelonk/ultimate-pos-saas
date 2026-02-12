<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 50);
            $table->string('barcode', 100)->nullable();
            $table->string('name')->comment('Auto-generated: Product Name - Option1/Option2');
            $table->json('option_ids')->comment('Array of variant_option IDs');
            $table->decimal('price', 15, 2)->comment('Final calculated price');
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->foreignUuid('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->foreignUuid('recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        // Pivot table for product variant groups
        Schema::create('product_variant_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('variant_group_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'variant_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_groups');
        Schema::dropIfExists('product_variants');
    }
};
