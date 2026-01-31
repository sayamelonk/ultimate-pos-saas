<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id')->nullable(); // Links to menu product (FK added in Phase 3)
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->decimal('yield_qty', 12, 4)->default(1); // Output quantity
            $table->uuid('yield_unit_id'); // Output unit
            $table->decimal('estimated_cost', 15, 2)->default(0); // Calculated from items
            $table->integer('prep_time_minutes')->nullable();
            $table->integer('cook_time_minutes')->nullable();
            $table->string('version', 20)->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // Note: product_id FK will be added in Phase 3 when products table exists
            $table->foreign('yield_unit_id')->references('id')->on('units')->onDelete('restrict');

            $table->index(['tenant_id', 'product_id']); // Product link index
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
