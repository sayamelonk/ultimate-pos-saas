<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventoryCategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedCategoriesForTenant($tenant);
        }
    }

    private function createCategory(string $tenantId, ?string $parentId, string $name, string $description, int $sortOrder): InventoryCategory
    {
        return InventoryCategory::create([
            'tenant_id' => $tenantId,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function seedCategoriesForTenant(Tenant $tenant): void
    {
        // Proteins
        $proteins = $this->createCategory($tenant->id, null, 'Proteins', 'Meat, poultry, seafood, and other protein sources', 1);
        $this->createCategory($tenant->id, $proteins->id, 'Beef', 'Beef cuts and ground beef', 1);
        $this->createCategory($tenant->id, $proteins->id, 'Poultry', 'Chicken, duck, turkey', 2);
        $this->createCategory($tenant->id, $proteins->id, 'Seafood', 'Fish, shrimp, squid, etc.', 3);

        // Produce
        $produce = $this->createCategory($tenant->id, null, 'Produce', 'Fresh fruits and vegetables', 2);
        $this->createCategory($tenant->id, $produce->id, 'Vegetables', 'Fresh vegetables', 1);
        $this->createCategory($tenant->id, $produce->id, 'Fruits', 'Fresh fruits', 2);
        $this->createCategory($tenant->id, $produce->id, 'Herbs', 'Fresh herbs and aromatics', 3);

        // Dairy
        $dairy = $this->createCategory($tenant->id, null, 'Dairy & Eggs', 'Milk products and eggs', 3);
        $this->createCategory($tenant->id, $dairy->id, 'Milk & Cream', 'Fresh milk, cream, and milk products', 1);
        $this->createCategory($tenant->id, $dairy->id, 'Cheese', 'Various types of cheese', 2);
        $this->createCategory($tenant->id, $dairy->id, 'Butter & Margarine', 'Butter and margarine products', 3);
        $this->createCategory($tenant->id, $dairy->id, 'Eggs', 'Chicken eggs and other eggs', 4);

        // Dry Goods
        $dryGoods = $this->createCategory($tenant->id, null, 'Dry Goods', 'Shelf-stable dry ingredients', 4);
        $this->createCategory($tenant->id, $dryGoods->id, 'Flour & Grains', 'Flour, rice, pasta, grains', 1);
        $this->createCategory($tenant->id, $dryGoods->id, 'Sugar & Sweeteners', 'Sugar, honey, syrups', 2);
        $this->createCategory($tenant->id, $dryGoods->id, 'Spices & Seasonings', 'Dried spices and seasonings', 3);
        $this->createCategory($tenant->id, $dryGoods->id, 'Canned Goods', 'Canned and preserved foods', 4);

        // Beverages
        $beverages = $this->createCategory($tenant->id, null, 'Beverages', 'Drinks and beverage ingredients', 5);
        $this->createCategory($tenant->id, $beverages->id, 'Coffee & Tea', 'Coffee beans, tea leaves, supplies', 1);
        $this->createCategory($tenant->id, $beverages->id, 'Soft Drinks', 'Carbonated and non-carbonated drinks', 2);
        $this->createCategory($tenant->id, $beverages->id, 'Juices', 'Fresh and packaged juices', 3);

        // Standalone categories
        $this->createCategory($tenant->id, null, 'Sauces & Condiments', 'Sauces, dressings, and condiments', 6);
        $this->createCategory($tenant->id, null, 'Oils & Fats', 'Cooking oils and fats', 7);
        $this->createCategory($tenant->id, null, 'Packaging & Supplies', 'Takeaway containers, napkins, etc.', 8);
        $this->createCategory($tenant->id, null, 'Cleaning Supplies', 'Cleaning and sanitation products', 9);
    }
}
