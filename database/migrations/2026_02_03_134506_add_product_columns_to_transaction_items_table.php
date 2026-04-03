<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            // Product-based columns (nullable for backward compatibility)
            $table->uuid('product_id')->nullable()->after('inventory_item_id');
            $table->uuid('product_variant_id')->nullable()->after('product_id');

            // Modifiers stored as JSON (selected modifiers with their prices)
            $table->json('modifiers')->nullable()->after('notes');

            // Additional pricing breakdown
            $table->decimal('base_price', 15, 2)->default(0)->after('unit_price');
            $table->decimal('variant_price_adjustment', 15, 2)->default(0)->after('base_price');
            $table->decimal('modifiers_total', 15, 2)->default(0)->after('variant_price_adjustment');

            // Item notes (for special requests)
            $table->text('item_notes')->nullable()->after('modifiers');

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();

            // Indexes
            $table->index('product_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variant_id']);
            $table->dropIndex(['transaction_items_product_id_index']);
            $table->dropIndex(['transaction_items_product_variant_id_index']);

            $table->dropColumn([
                'product_id',
                'product_variant_id',
                'modifiers',
                'base_price',
                'variant_price_adjustment',
                'modifiers_total',
                'item_notes',
            ]);
        });
    }
};
