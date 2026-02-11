<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\Price;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PriceSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedPricesForTenant($tenant);
        }
    }

    private function seedPricesForTenant(Tenant $tenant): void
    {
        $outlets = Outlet::where('tenant_id', $tenant->id)->get();
        $items = InventoryItem::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        foreach ($outlets as $outlet) {
            foreach ($items as $item) {
                $costPrice = (float) $item->cost_price;

                // Calculate selling price with markup based on category
                $markup = $this->getMarkupForCategory($item->category?->name);
                $sellingPrice = $this->roundToNicePrice($costPrice * $markup);
                $memberPrice = $this->roundToNicePrice($sellingPrice * 0.9);
                $minSellingPrice = $this->roundToNicePrice($costPrice * 1.1);

                Price::create([
                    'tenant_id' => $tenant->id,
                    'inventory_item_id' => $item->id,
                    'outlet_id' => $outlet->id,
                    'selling_price' => $sellingPrice,
                    'member_price' => fake()->boolean(60) ? $memberPrice : null,
                    'min_selling_price' => fake()->boolean(40) ? $minSellingPrice : null,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function getMarkupForCategory(?string $categoryName): float
    {
        $markups = [
            // High margin items
            'Soft Drinks' => 2.5,
            'Coffee & Tea' => 3.0,
            'Juices' => 2.0,

            // Medium margin items
            'Beef' => 1.8,
            'Poultry' => 1.7,
            'Seafood' => 1.9,
            'Cheese' => 1.6,

            // Low margin items
            'Vegetables' => 1.4,
            'Fruits' => 1.4,
            'Flour & Grains' => 1.3,
            'Sugar & Sweeteners' => 1.3,
            'Spices & Seasonings' => 1.5,
            'Oils & Fats' => 1.3,

            // Non-food items
            'Packaging & Supplies' => 1.5,
            'Cleaning Supplies' => 1.4,
        ];

        return $markups[$categoryName] ?? 1.5;
    }

    private function roundToNicePrice(float $price): float
    {
        if ($price < 1000) {
            return ceil($price / 100) * 100;
        }

        if ($price < 10000) {
            return ceil($price / 500) * 500;
        }

        if ($price < 100000) {
            return ceil($price / 1000) * 1000;
        }

        return ceil($price / 5000) * 5000;
    }
}
