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
        // Add missing columns
        if (! Schema::hasColumn('stock_batches', 'tenant_id')) {
            Schema::table('stock_batches', function (Blueprint $table) {
                $table->uuid('tenant_id')->nullable()->after('id');
            });

            // Update tenant_id from outlet relationship
            DB::statement('
                UPDATE stock_batches sb
                INNER JOIN outlets o ON sb.outlet_id = o.id
                SET sb.tenant_id = o.tenant_id
                WHERE sb.tenant_id IS NULL
            ');

            // Add foreign key
            Schema::table('stock_batches', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->index(['tenant_id', 'expiry_date']);
            });
        }

        if (! Schema::hasColumn('stock_batches', 'reserved_quantity')) {
            Schema::table('stock_batches', function (Blueprint $table) {
                // Handle both old (current_qty) and new (current_quantity) column names
                $afterColumn = Schema::hasColumn('stock_batches', 'current_qty') ? 'current_qty' : 'current_quantity';
                $table->decimal('reserved_quantity', 15, 4)->default(0)->after($afterColumn);
            });
        }

        // Only add unit_cost if neither unit_cost nor cost_price exists
        if (! Schema::hasColumn('stock_batches', 'unit_cost') && ! Schema::hasColumn('stock_batches', 'cost_price')) {
            Schema::table('stock_batches', function (Blueprint $table) {
                $table->decimal('unit_cost', 15, 4)->default(0)->after('reserved_quantity');
            });
        }

        if (! Schema::hasColumn('stock_batches', 'supplier_batch_number')) {
            Schema::table('stock_batches', function (Blueprint $table) {
                $table->string('supplier_batch_number')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('stock_batches', 'notes')) {
            Schema::table('stock_batches', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('supplier_batch_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            if (Schema::hasColumn('stock_batches', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
            if (Schema::hasColumn('stock_batches', 'reserved_quantity')) {
                $table->dropColumn('reserved_quantity');
            }
            if (Schema::hasColumn('stock_batches', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
            if (Schema::hasColumn('stock_batches', 'supplier_batch_number')) {
                $table->dropColumn('supplier_batch_number');
            }
            if (Schema::hasColumn('stock_batches', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
