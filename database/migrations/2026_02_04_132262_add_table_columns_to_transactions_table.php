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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('table_id')->nullable()->after('pos_session_id')->constrained()->nullOnDelete();
            $table->foreignUuid('table_session_id')->nullable()->after('table_id')->constrained()->nullOnDelete();
            $table->string('order_type', 20)->default('dine_in')->after('table_session_id'); // dine_in, takeaway, delivery

            $table->index('table_id');
            $table->index('table_session_id');
            $table->index('order_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropForeign(['table_session_id']);
            $table->dropIndex(['table_id']);
            $table->dropIndex(['table_session_id']);
            $table->dropIndex(['order_type']);
            $table->dropColumn(['table_id', 'table_session_id', 'order_type']);
        });
    }
};
