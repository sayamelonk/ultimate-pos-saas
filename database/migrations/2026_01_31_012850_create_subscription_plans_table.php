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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Starter, Professional, Enterprise
            $table->string('slug')->unique(); // starter, professional, enterprise
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2); // Harga bulanan
            $table->decimal('price_yearly', 12, 2); // Harga tahunan (sudah diskon)
            $table->integer('max_outlets')->default(1); // Limit outlet
            $table->integer('max_users')->default(2); // Limit user
            $table->json('features')->nullable(); // Fitur yang tersedia
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
