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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('logo')->nullable();

            // Contact
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            // Settings
            $table->string('currency', 10)->default('IDR');
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->decimal('tax_percentage', 5, 2)->default(11.00);
            $table->decimal('service_charge_percentage', 5, 2)->default(0);

            // Subscription
            $table->string('subscription_plan', 50)->default('free');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->integer('max_outlets')->default(1);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
