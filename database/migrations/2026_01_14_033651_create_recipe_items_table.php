<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('recipe_id');
            $table->uuid('inventory_item_id');
            $table->decimal('quantity', 12, 4); // Amount needed per yield
            $table->uuid('unit_id'); // Unit for this ingredient
            $table->decimal('waste_percentage', 5, 2)->default(0); // Expected waste %
            $table->text('notes')->nullable(); // Preparation notes
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');

            $table->unique(['recipe_id', 'inventory_item_id']);
            $table->index(['recipe_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
    }
};
