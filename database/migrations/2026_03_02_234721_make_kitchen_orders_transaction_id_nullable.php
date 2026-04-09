<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->foreignUuid('transaction_id')->nullable()->change();
        });

        Schema::table('kitchen_order_items', function (Blueprint $table) {
            $table->foreignUuid('transaction_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->foreignUuid('transaction_id')->nullable(false)->change();
        });

        Schema::table('kitchen_order_items', function (Blueprint $table) {
            $table->foreignUuid('transaction_item_id')->nullable(false)->change();
        });
    }
};
