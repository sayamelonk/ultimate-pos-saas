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
        if (! Schema::hasTable('stock_batches')) {
            Schema::create('stock_batches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('inventory_item_id')->constrained()->cascadeOnDelete();
                $table->uuid('goods_receive_item_id')->nullable(); // FK added later after goods_receive_items table exists

                // Batch Information
                $table->string('batch_number')->index();
                $table->date('production_date')->nullable();
                $table->date('expiry_date')->nullable()->index();

                // Quantity Tracking
                $table->decimal('initial_quantity', 15, 4);
                $table->decimal('current_quantity', 15, 4);
                $table->decimal('reserved_quantity', 15, 4)->default(0); // For pending orders

                // Cost
                $table->decimal('unit_cost', 15, 4)->default(0);

                // Status
                $table->enum('status', ['active', 'depleted', 'expired', 'disposed'])->default('active');

                // Reference
                $table->string('supplier_batch_number')->nullable(); // Original batch from supplier
                $table->text('notes')->nullable();

                $table->timestamps();

                // Indexes for quick lookups
                $table->index(['outlet_id', 'inventory_item_id', 'status']);
                $table->index(['outlet_id', 'expiry_date', 'status']);
                $table->index(['tenant_id', 'expiry_date']);
            });
        }

        // Batch Settings per Tenant
        if (! Schema::hasTable('batch_settings')) {
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

        // Stock Batch Movements - Track every in/out of batches
        if (! Schema::hasTable('stock_batch_movements')) {
            Schema::create('stock_batch_movements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('stock_batch_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('inventory_item_id')->constrained()->cascadeOnDelete();

                // Movement Type
                $table->enum('type', [
                    'receive',      // Goods receive
                    'sale',         // POS sale
                    'transfer_out', // Transfer to another outlet
                    'transfer_in',  // Transfer from another outlet
                    'adjustment',   // Stock adjustment
                    'waste',        // Waste/disposal
                    'return',       // Customer return
                    'expired',      // Marked as expired
                    'production',   // Used in recipe/production
                ]);

                // Quantities
                $table->decimal('quantity', 15, 4); // Positive for in, negative for out
                $table->decimal('balance_before', 15, 4);
                $table->decimal('balance_after', 15, 4);

                // Reference
                $table->string('reference_type')->nullable(); // Model class
                $table->unsignedBigInteger('reference_id')->nullable(); // Model ID
                $table->string('reference_number')->nullable(); // Human readable ref

                $table->text('notes')->nullable();
                $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();

                $table->timestamps();

                $table->index(['stock_batch_id', 'created_at']);
                $table->index(['outlet_id', 'inventory_item_id', 'type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batch_movements');
        Schema::dropIfExists('batch_settings');
        Schema::dropIfExists('stock_batches');
    }
};
