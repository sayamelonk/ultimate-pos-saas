<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kitchen Stations
        Schema::create('kitchen_stations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->string('color', 10)->default('#3B82F6');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'outlet_id', 'is_active']);
        });

        // Kitchen Orders
        Schema::create('kitchen_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('station_id')->nullable()->constrained('kitchen_stations')->nullOnDelete();
            $table->foreignUuid('table_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->string('order_type', 20)->default('dine_in');
            $table->string('table_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'cancelled'])->default('pending');
            $table->enum('priority', ['normal', 'rush', 'vip'])->default('normal');
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->index(['outlet_id', 'status', 'created_at']);
            $table->index(['station_id', 'status']);
        });

        // Kitchen Order Items
        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kitchen_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('station_id')->nullable()->constrained('kitchen_stations')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('quantity', 10, 4);
            $table->json('modifiers')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['kitchen_order_id', 'status']);
        });

        // Add send_to_kitchen column to products if not exists
        if (! Schema::hasColumn('products', 'send_to_kitchen')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('send_to_kitchen')->default(true)->after('is_active');
                $table->foreignUuid('kitchen_station_id')->nullable()->after('send_to_kitchen')
                    ->constrained('kitchen_stations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'kitchen_station_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['kitchen_station_id']);
                $table->dropColumn(['send_to_kitchen', 'kitchen_station_id']);
            });
        }

        Schema::dropIfExists('kitchen_order_items');
        Schema::dropIfExists('kitchen_orders');
        Schema::dropIfExists('kitchen_stations');
    }
};
