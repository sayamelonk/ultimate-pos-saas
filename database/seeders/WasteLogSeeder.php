<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WasteLog;
use Illuminate\Database\Seeder;

class WasteLogSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedWasteLogsForTenant($tenant);
        }
    }

    private function seedWasteLogsForTenant(Tenant $tenant): void
    {
        $outlets = Outlet::where('tenant_id', $tenant->id)->get();
        $users = User::where('tenant_id', $tenant->id)->get();

        // Get perishable items (with shelf life)
        $perishableItems = InventoryItem::where('tenant_id', $tenant->id)
            ->whereNotNull('shelf_life_days')
            ->where('shelf_life_days', '<=', 30)
            ->get();

        if ($outlets->isEmpty() || $users->isEmpty() || $perishableItems->isEmpty()) {
            return;
        }

        $owner = $users->first();

        foreach ($outlets as $outlet) {
            // Create 15-25 waste logs
            $wasteCount = fake()->numberBetween(15, 25);

            for ($i = 0; $i < $wasteCount; $i++) {
                $item = $perishableItems->random();
                $wasteDate = fake()->dateTimeBetween('-30 days', 'now');

                $stock = InventoryStock::where('outlet_id', $outlet->id)
                    ->where('inventory_item_id', $item->id)
                    ->first();

                if (! $stock) {
                    continue;
                }

                $costPrice = (float) ($stock->avg_cost ?: $item->cost_price);
                $quantity = fake()->randomFloat(2, 0.5, 5);
                $totalCost = $quantity * $costPrice;

                $reasons = [
                    WasteLog::REASON_EXPIRED => [
                        'Product passed expiry date',
                        'Expired stock found during check',
                        'End of shelf life',
                    ],
                    WasteLog::REASON_SPOILED => [
                        'Found spoiled during prep',
                        'Quality deterioration',
                        'Improper storage damage',
                    ],
                    WasteLog::REASON_DAMAGED => [
                        'Dropped during handling',
                        'Packaging damage',
                        'Container broken',
                    ],
                    WasteLog::REASON_PREPARATION => [
                        'Prep waste - trimmings',
                        'Cutting waste',
                        'Peeling waste',
                    ],
                    WasteLog::REASON_OVERPRODUCTION => [
                        'Over-prepped for service',
                        'Excess production',
                        'Unsold prepared items',
                    ],
                ];

                $reason = fake()->randomElement(array_keys($reasons));
                $notes = fake()->randomElement($reasons[$reason]);

                $wasteLog = WasteLog::create([
                    'tenant_id' => $tenant->id,
                    'outlet_id' => $outlet->id,
                    'inventory_item_id' => $item->id,
                    'batch_id' => null,
                    'waste_date' => $wasteDate,
                    'quantity' => $quantity,
                    'unit_id' => $item->unit_id,
                    'cost_price' => $costPrice,
                    'total_cost' => $totalCost,
                    'reason' => $reason,
                    'notes' => $notes,
                    'logged_by' => $owner->id,
                ]);

                // Create stock movement
                $stockBefore = (float) $stock->quantity;

                StockMovement::create([
                    'outlet_id' => $outlet->id,
                    'inventory_item_id' => $item->id,
                    'batch_id' => null,
                    'type' => StockMovement::TYPE_WASTE,
                    'reference_type' => WasteLog::class,
                    'reference_id' => $wasteLog->id,
                    'quantity' => -$quantity,
                    'cost_price' => $costPrice,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $quantity,
                    'notes' => "Waste: {$reason} - {$notes}",
                    'created_by' => $owner->id,
                ]);
            }
        }
    }
}
