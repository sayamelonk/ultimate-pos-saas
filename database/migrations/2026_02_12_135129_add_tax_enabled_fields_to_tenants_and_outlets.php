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
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('tax_enabled')->default(true)->after('tax_percentage');
            $table->boolean('service_charge_enabled')->default(false)->after('service_charge_percentage');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->boolean('tax_enabled')->nullable()->after('tax_percentage');
            $table->boolean('service_charge_enabled')->nullable()->after('service_charge_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['tax_enabled', 'service_charge_enabled']);
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['tax_enabled', 'service_charge_enabled']);
        });
    }
};
