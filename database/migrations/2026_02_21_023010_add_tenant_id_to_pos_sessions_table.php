<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First add the column without FK constraint
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
        });

        // Update existing records to set tenant_id from outlet
        // Use database-agnostic query (works for both MySQL and SQLite)
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('UPDATE pos_sessions
                SET tenant_id = (SELECT tenant_id FROM outlets WHERE outlets.id = pos_sessions.outlet_id)
                WHERE outlet_id IS NOT NULL');
        } else {
            DB::statement('UPDATE pos_sessions ps
                JOIN outlets o ON ps.outlet_id = o.id
                SET ps.tenant_id = o.tenant_id');
        }

        // Now make it not nullable and add FK
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
