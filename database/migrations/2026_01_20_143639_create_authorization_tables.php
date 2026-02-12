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
        // SPV/Manager PIN for authorization
        Schema::create('user_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('pin_hash'); // Hashed 4-6 digit PIN
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        // Authorization settings per tenant
        Schema::create('authorization_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();

            // Actions that require authorization
            $table->boolean('require_auth_void')->default(true);
            $table->boolean('require_auth_refund')->default(true);
            $table->boolean('require_auth_discount')->default(false);
            $table->decimal('discount_threshold_percent', 5, 2)->default(20); // Require auth if discount > 20%
            $table->boolean('require_auth_price_override')->default(true);
            $table->boolean('require_auth_no_sale')->default(true); // Open cash drawer without sale
            $table->boolean('require_auth_reprint')->default(false);
            $table->boolean('require_auth_cancel_order')->default(true);

            // PIN settings
            $table->integer('pin_length')->default(4); // 4 or 6 digit
            $table->integer('max_pin_attempts')->default(3);
            $table->integer('lockout_minutes')->default(5); // Lockout after max attempts

            $table->timestamps();

            $table->unique('tenant_id');
        });

        // Authorization logs - track all approvals
        Schema::create('authorization_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();

            // Who requested and who approved
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('authorized_by')->nullable()->constrained('users')->nullOnDelete();

            // Action details
            $table->enum('action_type', [
                'void',
                'refund',
                'discount',
                'price_override',
                'no_sale',
                'reprint',
                'cancel_order',
                'other',
            ]);
            $table->enum('status', ['pending', 'approved', 'denied', 'expired'])->default('pending');

            // Reference to what was being authorized
            $table->string('reference_type')->nullable(); // Model class (Transaction, etc)
            $table->uuid('reference_id')->nullable(); // Model ID
            $table->string('reference_number')->nullable(); // Human readable (TRX-xxx)

            // Details
            $table->decimal('amount', 15, 2)->nullable(); // Amount involved
            $table->text('reason')->nullable(); // Reason for request
            $table->text('notes')->nullable(); // Additional notes
            $table->json('metadata')->nullable(); // Extra data (original price, new price, etc)

            // Timestamps
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Request expires if not actioned

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'outlet_id', 'action_type']);
            $table->index(['tenant_id', 'requested_at']);
            $table->index(['authorized_by', 'responded_at']);
        });

        // PIN attempt tracking (for lockout)
        Schema::create('pin_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete(); // Who entered wrong PIN
            $table->string('attempted_for')->nullable(); // User ID they tried to auth as
            $table->boolean('success')->default(false);
            $table->string('ip_address')->nullable();
            $table->timestamp('attempted_at');

            $table->index(['tenant_id', 'outlet_id', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pin_attempts');
        Schema::dropIfExists('authorization_logs');
        Schema::dropIfExists('authorization_settings');
        Schema::dropIfExists('user_pins');
    }
};
