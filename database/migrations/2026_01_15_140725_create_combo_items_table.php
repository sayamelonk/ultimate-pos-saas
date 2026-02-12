<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combo_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('combo_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('group_name')->nullable()->comment('e.g., Main, Side, Drink');
            $table->integer('quantity')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('allow_variant_selection')->default(true);
            $table->decimal('price_adjustment', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['combo_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
