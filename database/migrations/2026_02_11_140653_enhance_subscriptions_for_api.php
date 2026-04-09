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
        // First, alter the status enum to include new statuses (MySQL only)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active', 'cancelled', 'expired', 'pending', 'past_due', 'trial', 'frozen') DEFAULT 'pending'");
        }
        // SQLite doesn't enforce enum constraints, so we can skip this step

        Schema::table('subscriptions', function (Blueprint $table) {
            // Add price column to track subscription price
            if (! Schema::hasColumn('subscriptions', 'price')) {
                $table->decimal('price', 12, 2)->default(0)->after('billing_cycle');
            }

            // Add current period columns
            if (! Schema::hasColumn('subscriptions', 'current_period_start')) {
                $table->timestamp('current_period_start')->nullable()->after('ends_at');
            }
            if (! Schema::hasColumn('subscriptions', 'current_period_end')) {
                $table->timestamp('current_period_end')->nullable()->after('current_period_start');
            }
        });

        // Add period columns to subscription_invoices
        Schema::table('subscription_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_invoices', 'period_start')) {
                $table->timestamp('period_start')->nullable()->after('billing_cycle');
            }
            if (! Schema::hasColumn('subscription_invoices', 'period_end')) {
                $table->timestamp('period_end')->nullable()->after('period_start');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $columns = ['period_start', 'period_end'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('subscription_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $columns = ['price', 'current_period_start', 'current_period_end'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Revert status enum (MySQL only)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active', 'cancelled', 'expired', 'pending', 'past_due') DEFAULT 'pending'");
        }
    }
};
