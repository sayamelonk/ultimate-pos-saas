<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: Combos are stored as products with product_type = 'combo'
        // This table stores combo-specific settings
        Schema::create('combos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->enum('pricing_type', ['fixed', 'sum', 'discount_percent', 'discount_amount'])->default('fixed');
            $table->decimal('discount_value', 15, 2)->default(0)->comment('Discount percent or amount');
            $table->boolean('allow_substitutions')->default(false);
            $table->integer('min_items')->default(1);
            $table->integer('max_items')->nullable();
            $table->timestamps();

            $table->unique('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combos');
    }
};
