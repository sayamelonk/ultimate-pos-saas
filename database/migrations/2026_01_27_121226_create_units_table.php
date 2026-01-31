<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->string('name', 50);
            $table->string('abbreviation', 10);
            $table->string('type')->default('weight'); // weight, volume, piece, length
            $table->uuid('base_unit_id')->nullable(); // For conversions (e.g., g -> kg)
            $table->decimal('conversion_factor', 12, 6)->default(1); // How many base units in this unit
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('base_unit_id')->references('id')->on('units')->onDelete('set null');

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
