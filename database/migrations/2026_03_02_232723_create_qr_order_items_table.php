<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('qr_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained()->nullOnDelete();

            $table->string('item_name');
            $table->string('item_sku')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('modifiers_total', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->json('modifiers')->nullable();
            $table->text('item_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_order_items');
    }
};
