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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('type', 30)->default('raw_material')->after('name');
            $table->string('storage_location', 100)->nullable()->after('shelf_life_days');
            $table->boolean('is_perishable')->default(false)->after('storage_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['type', 'storage_location', 'is_perishable']);
        });
    }
};
