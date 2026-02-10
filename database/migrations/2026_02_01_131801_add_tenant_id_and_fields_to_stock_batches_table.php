<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            // Add tenant_id as the first column (after id)
            $table->foreignUuid('tenant_id')->after('id')->constrained()->cascadeOnDelete();

            // Rename columns to match new naming convention
            $table->renameColumn('initial_qty', 'initial_quantity');
            $table->renameColumn('current_qty', 'current_quantity');
            $table->renameColumn('cost_price', 'unit_cost');

            // Add missing columns
            $table->decimal('reserved_quantity', 15, 4)->default(0)->after('current_quantity');
            $table->string('supplier_batch_number')->nullable()->after('status');
            $table->text('notes')->nullable()->after('supplier_batch_number');

            // Change status to enum
            $table->enum('status', ['active', 'depleted', 'expired', 'disposed'])->default('active')->change();

            // Add indexes
            $table->index(['outlet_id', 'expiry_date', 'status']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['outlet_id', 'expiry_date', 'status']);
            $table->dropIndex(['expiry_date']);
            $table->dropColumn(['tenant_id', 'reserved_quantity', 'supplier_batch_number', 'notes']);

            $table->renameColumn('initial_quantity', 'initial_qty');
            $table->renameColumn('current_quantity', 'current_qty');
            $table->renameColumn('unit_cost', 'cost_price');
        });
    }
};
