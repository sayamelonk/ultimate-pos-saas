<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            // Drop the existing foreign key first
            $table->dropForeign(['inventory_item_id']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            // Make the column nullable
            $table->uuid('inventory_item_id')->nullable()->change();

            // Re-add the foreign key with nullOnDelete
            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->uuid('inventory_item_id')->nullable(false)->change();

            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('restrict');
        });
    }
};
