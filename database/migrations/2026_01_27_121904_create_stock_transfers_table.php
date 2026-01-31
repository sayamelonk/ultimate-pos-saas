<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('from_outlet_id');
            $table->uuid('to_outlet_id');
            $table->string('transfer_number', 50);
            $table->date('transfer_date');
            $table->string('status')->default('draft'); // draft, pending, in_transit, received, cancelled
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('received_by')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('from_outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('to_outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['tenant_id', 'transfer_number']);
            $table->index(['tenant_id', 'from_outlet_id', 'status']);
            $table->index(['tenant_id', 'to_outlet_id', 'status']);
            $table->index(['tenant_id', 'transfer_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
