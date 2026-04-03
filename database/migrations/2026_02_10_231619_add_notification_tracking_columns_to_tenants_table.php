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
            // Subscription expiry reminder tracking (H-7, H-3, H-1)
            $table->timestamp('subscription_reminder_h7_at')->nullable()->after('email');
            $table->timestamp('subscription_reminder_h3_at')->nullable()->after('subscription_reminder_h7_at');
            $table->timestamp('subscription_reminder_h1_at')->nullable()->after('subscription_reminder_h3_at');

            // Data deletion warning tracking (H-30, H-7 before deletion)
            $table->timestamp('data_deletion_warning_1_at')->nullable()->after('subscription_reminder_h1_at');
            $table->timestamp('data_deletion_warning_2_at')->nullable()->after('data_deletion_warning_1_at');

            // Soft delete flag for deleted tenants
            $table->boolean('is_deleted')->default(false)->after('data_deletion_warning_2_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_reminder_h7_at',
                'subscription_reminder_h3_at',
                'subscription_reminder_h1_at',
                'data_deletion_warning_1_at',
                'data_deletion_warning_2_at',
                'is_deleted',
            ]);
        });
    }
};
