<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tax Mode Options:
     * - 'exclusive': Tax is added on top of price (default, current behavior)
     *   Example: Price Rp100,000 + Tax 11% = Rp111,000
     *
     * - 'inclusive': Tax is already included in price
     *   Example: Price Rp100,000 (includes Tax 11%)
     *   - Base Price: Rp90,090.09
     *   - Tax Amount: Rp9,909.91
     */
    public function up(): void
    {
        // Add tax_mode to tenants (tenant-level default)
        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('tax_mode', ['exclusive', 'inclusive'])
                ->default('exclusive')
                ->after('tax_enabled')
                ->comment('exclusive = tax added on top, inclusive = tax included in price');
        });

        // Add tax_mode to outlets (outlet-level override, nullable = inherit from tenant)
        Schema::table('outlets', function (Blueprint $table) {
            $table->enum('tax_mode', ['exclusive', 'inclusive'])
                ->nullable()
                ->after('tax_enabled')
                ->comment('null = inherit from tenant');
        });

        // Add tax_mode to transactions (to preserve which mode was used)
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('tax_mode', ['exclusive', 'inclusive'])
                ->default('exclusive')
                ->after('tax_percentage')
                ->comment('Tax calculation mode used for this transaction');
        });

        // Add tax_mode to held_orders (for held orders calculation)
        Schema::table('held_orders', function (Blueprint $table) {
            $table->enum('tax_mode', ['exclusive', 'inclusive'])
                ->default('exclusive')
                ->after('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('tax_mode');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('tax_mode');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('tax_mode');
        });

        Schema::table('held_orders', function (Blueprint $table) {
            $table->dropColumn('tax_mode');
        });
    }
};
