<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class InventoryStockSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedStocksForTenant($tenant);
        }
    }

    private function seedStocksForTenant(Tenant $tenant): void
    {
        $outlets = Outlet::where('tenant_id', $tenant->id)->get();
        $items = InventoryItem::where('tenant_id', $tenant->id)->get();

        foreach ($outlets as $outlet) {
            foreach ($items as $item) {
                $costPrice = (float) $item->cost_price;

                // Generate realistic stock levels
                $stockLevel = $this->generateStockLevel($item);
                $avgCost = $costPrice * fake()->randomFloat(2, 0.95, 1.05);
                $lastCost = $costPrice * fake()->randomFloat(2, 0.9, 1.1);

                InventoryStock::create([
                    'outlet_id' => $outlet->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $stockLevel,
                    'reserved_qty' => $stockLevel > 10 ? fake()->randomFloat(2, 0, min(5, $stockLevel * 0.1)) : 0,
                    'avg_cost' => $avgCost,
                    'last_cost' => $lastCost,
                    'last_received_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
                    'last_issued_at' => fake()->optional(0.7)->dateTimeBetween('-7 days', 'now'),
                ]);
            }
        }
    }

    private function generateStockLevel(InventoryItem $item): float
    {
        $minStock = (float) $item->min_stock;
        $maxStock = (float) ($item->max_stock ?? $minStock * 5);

        // 70% chance of normal stock, 20% low stock, 10% high stock
        $stockScenario = fake()->randomFloat(2, 0, 1);

        if ($stockScenario < 0.1) {
            // Out of stock or very low
            return fake()->randomFloat(2, 0, $minStock * 0.5);
        }

        if ($stockScenario < 0.3) {
            // Low stock (below reorder point)
            return fake()->randomFloat(2, $minStock * 0.5, $minStock * 1.5);
        }

        if ($stockScenario < 0.9) {
            // Normal stock level
            return fake()->randomFloat(2, $minStock * 1.5, $maxStock * 0.7);
        }

        // High stock (near max)
        return fake()->randomFloat(2, $maxStock * 0.7, $maxStock);
    }
}
