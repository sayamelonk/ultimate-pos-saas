<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('variant_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('value')->nullable()->comment('For color: hex code, for image: path');
            $table->decimal('price_adjustment', 15, 2)->default(0)->comment('Add to base price');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['variant_group_id', 'name']);
            $table->index(['variant_group_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_options');
    }
};
