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
        Schema::table('stock_batches', function (Blueprint $table) {
            // Rename old column names to new standard names
            if (Schema::hasColumn('stock_batches', 'current_qty') && ! Schema::hasColumn('stock_batches', 'current_quantity')) {
                $table->renameColumn('current_qty', 'current_quantity');
            }

            if (Schema::hasColumn('stock_batches', 'initial_qty') && ! Schema::hasColumn('stock_batches', 'initial_quantity')) {
                $table->renameColumn('initial_qty', 'initial_quantity');
            }

            if (Schema::hasColumn('stock_batches', 'cost_price') && ! Schema::hasColumn('stock_batches', 'unit_cost')) {
                $table->renameColumn('cost_price', 'unit_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            if (Schema::hasColumn('stock_batches', 'current_quantity')) {
                $table->renameColumn('current_quantity', 'current_qty');
            }

            if (Schema::hasColumn('stock_batches', 'initial_quantity')) {
                $table->renameColumn('initial_quantity', 'initial_qty');
            }

            if (Schema::hasColumn('stock_batches', 'unit_cost')) {
                $table->renameColumn('unit_cost', 'cost_price');
            }
        });
    }
};
