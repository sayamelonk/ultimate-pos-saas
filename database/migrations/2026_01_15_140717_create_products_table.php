<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignUuid('recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
            $table->string('sku', 50);
            $table->string('barcode', 100)->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->enum('product_type', ['single', 'variant', 'combo'])->default('single');
            $table->boolean('track_stock')->default(true);
            $table->foreignUuid('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_in_pos')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->boolean('allow_notes')->default(true);
            $table->integer('prep_time_minutes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('tags')->nullable();
            $table->json('allergens')->nullable();
            $table->json('nutritional_info')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'category_id', 'is_active']);
            $table->index(['tenant_id', 'product_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
