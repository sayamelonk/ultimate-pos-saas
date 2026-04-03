<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('floor_id')->constrained()->cascadeOnDelete();

            $table->string('number', 20);
            $table->string('name', 100)->nullable();
            $table->unsignedTinyInteger('capacity')->default(4);

            // Position for floor plan display
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(100);
            $table->integer('height')->default(100);
            $table->string('shape', 20)->default('rectangle'); // rectangle, circle, square

            // Status: available, occupied, reserved, dirty
            $table->string('status', 20)->default('available');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['outlet_id', 'number']);
            $table->index('tenant_id');
            $table->index('floor_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
