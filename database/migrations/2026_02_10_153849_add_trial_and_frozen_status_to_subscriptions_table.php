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
        // First, modify the status enum to include trial and frozen
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });

        // Add trial tracking fields
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('billing_cycle');
            $table->timestamp('trial_ends_at')->nullable()->after('is_trial');
            $table->timestamp('grace_period_ends_at')->nullable()->after('ends_at');
            $table->timestamp('frozen_at')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_trial', 'trial_ends_at', 'grace_period_ends_at', 'frozen_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'cancelled', 'expired', 'pending', 'past_due'])->default('pending')->change();
        });
    }
};
