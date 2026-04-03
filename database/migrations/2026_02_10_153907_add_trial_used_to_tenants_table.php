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
            $table->boolean('trial_used')->default(false)->after('subscription_expires_at');
            $table->timestamp('trial_started_at')->nullable()->after('trial_used');
            $table->timestamp('trial_ended_at')->nullable()->after('trial_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['trial_used', 'trial_started_at', 'trial_ended_at']);
        });
    }
};
