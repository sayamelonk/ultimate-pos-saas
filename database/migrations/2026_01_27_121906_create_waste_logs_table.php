<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('outlet_id');
            $table->uuid('inventory_item_id');
            $table->uuid('batch_id')->nullable(); // For batch-tracked items
            $table->date('waste_date');
            $table->decimal('quantity', 12, 4);
            $table->uuid('unit_id');
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0); // quantity * cost
            $table->string('reason'); // expired, spoiled, damaged, preparation, overproduction, other
            $table->text('notes')->nullable();
            $table->uuid('logged_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('batch_id')->references('id')->on('stock_batches')->onDelete('set null');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');
            $table->foreign('logged_by')->references('id')->on('users')->onDelete('restrict');

            $table->index(['tenant_id', 'outlet_id', 'waste_date']);
            $table->index(['tenant_id', 'inventory_item_id']);
            $table->index(['tenant_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_logs');
    }
};
