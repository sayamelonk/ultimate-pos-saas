<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name');
            $table->enum('type', ['cash', 'card', 'digital_wallet', 'transfer', 'other']);
            $table->string('provider')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('charge_percentage', 5, 2)->default(0);
            $table->decimal('charge_fixed', 12, 2)->default(0);
            $table->boolean('requires_reference')->default(false);
            $table->boolean('opens_cash_drawer')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
