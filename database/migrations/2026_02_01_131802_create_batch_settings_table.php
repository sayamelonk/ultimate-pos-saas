<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();

            // Expiry Warning Thresholds (in days)
            $table->integer('expiry_warning_days')->default(30); // Yellow warning
            $table->integer('expiry_critical_days')->default(7); // Red warning

            // Auto-actions
            $table->boolean('auto_mark_expired')->default(true);
            $table->boolean('block_expired_sales')->default(true);
            $table->boolean('enable_fefo')->default(true); // First Expired First Out

            // Notification Settings
            $table->boolean('notify_expiry_warning')->default(true);
            $table->boolean('notify_expiry_critical')->default(true);
            $table->boolean('daily_expiry_report')->default(false);

            // Batch Number Settings
            $table->boolean('auto_generate_batch')->default(true);
            $table->string('batch_prefix')->default('BTH');
            $table->string('batch_format')->default('PREFIX-YYYYMMDD-SEQ'); // BTH-20260120-001

            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_settings');
    }
};
