<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Phase 0 - Subscription Plans
            SubscriptionPlanSeeder::class,

            // Phase 1 - Foundation
            PermissionSeeder::class,
            RoleSeeder::class,
            TenantSeeder::class,

            // Phase 2 - Inventory
            UnitSeeder::class,
            InventoryCategorySeeder::class,
            SupplierSeeder::class,
            InventoryItemSeeder::class,
            InventoryStockSeeder::class,

            // Phase 2 - Transactions (PO, GR, Adjustments)
            PurchaseOrderSeeder::class,
            GoodsReceiveSeeder::class,
            StockAdjustmentSeeder::class,
            WasteLogSeeder::class,
            RecipeSeeder::class,
            StockTransferSeeder::class,

            // Phase 3 - POS
            PriceSeeder::class,
            PaymentMethodSeeder::class,
            CustomerSeeder::class,

            // Phase 4 - Product & Menu Management
            ProductCategorySeeder::class,
            VariantGroupSeeder::class,
            ModifierGroupSeeder::class,
            ProductSeeder::class,
            ComboSeeder::class,
        ]);
    }
}
