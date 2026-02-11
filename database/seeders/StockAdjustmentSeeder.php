<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockAdjustmentSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedStockAdjustmentsForTenant($tenant);
        }
    }

    private function seedStockAdjustmentsForTenant(Tenant $tenant): void
    {
        $outlets = Outlet::where('tenant_id', $tenant->id)->get();
        $items = InventoryItem::where('tenant_id', $tenant->id)->get();
        $users = User::where('tenant_id', $tenant->id)->get();

        if ($outlets->isEmpty() || $items->isEmpty() || $users->isEmpty()) {
            return;
        }

        $owner = $users->first();
        $adjNumber = 1;

        foreach ($outlets as $outlet) {
            // Create different types of adjustments
            $adjustments = [
                ['type' => StockAdjustment::TYPE_STOCK_TAKE, 'status' => StockAdjustment::STATUS_APPROVED, 'count' => 3],
                ['type' => StockAdjustment::TYPE_CORRECTION, 'status' => StockAdjustment::STATUS_APPROVED, 'count' => 2],
                ['type' => StockAdjustment::TYPE_STOCK_TAKE, 'status' => StockAdjustment::STATUS_DRAFT, 'count' => 1],
            ];

            foreach ($adjustments as $adjConfig) {
                for ($i = 0; $i < $adjConfig['count']; $i++) {
                    $adjustmentDate = fake()->dateTimeBetween('-30 days', 'now');

                    $reasons = [
                        StockAdjustment::TYPE_STOCK_TAKE => [
                            'Monthly stock take',
                            'Weekly inventory count',
                            'Physical count adjustment',
                            'End of month verification',
                        ],
                        StockAdjustment::TYPE_CORRECTION => [
                            'System error correction',
                            'Manual count correction',
                            'Data entry mistake',
                        ],
                        StockAdjustment::TYPE_OPENING_BALANCE => [
                            'Opening balance setup',
                            'Initial stock entry',
                        ],
                    ];

                    $reason = fake()->randomElement($reasons[$adjConfig['type']]);

                    $adjustment = StockAdjustment::create([
                        'tenant_id' => $tenant->id,
                        'outlet_id' => $outlet->id,
                        'adjustment_number' => 'ADJ-'.str_pad($adjNumber++, 5, '0', STR_PAD_LEFT),
                        'adjustment_date' => $adjustmentDate,
                        'type' => $adjConfig['type'],
                        'status' => $adjConfig['status'],
                        'reason' => $reason,
                        'notes' => fake()->optional(0.3)->sentence(),
                        'created_by' => $owner->id,
                        'approved_by' => $adjConfig['status'] === StockAdjustment::STATUS_APPROVED ? $owner->id : null,
                        'approved_at' => $adjConfig['status'] === StockAdjustment::STATUS_APPROVED ? $adjustmentDate : null,
                    ]);

                    // Add 5-15 items per adjustment
                    $adjItems = $items->random(fake()->numberBetween(5, 15));

                    foreach ($adjItems as $item) {
                        $stock = InventoryStock::where('outlet_id', $outlet->id)
                            ->where('inventory_item_id', $item->id)
                            ->first();

                        if (! $stock) {
                            continue;
                        }

                        $systemQty = (float) $stock->quantity;
                        // Generate actual qty with variance (-20% to +20%)
                        $variance = fake()->randomFloat(2, -0.2, 0.2);
                        $actualQty = max(0, $systemQty * (1 + $variance));

                        // Sometimes make it exact (no variance)
                        if (fake()->boolean(30)) {
                            $actualQty = $systemQty;
                        }

                        $difference = $actualQty - $systemQty;
                        $costPrice = (float) ($stock->avg_cost ?: $item->cost_price);
                        $valueDifference = $difference * $costPrice;

                        $adjItem = StockAdjustmentItem::create([
                            'stock_adjustment_id' => $adjustment->id,
                            'inventory_item_id' => $item->id,
                            'batch_id' => null,
                            'system_qty' => $systemQty,
                            'actual_qty' => $actualQty,
                            'difference' => $difference,
                            'cost_price' => $costPrice,
                            'value_difference' => $valueDifference,
                            'notes' => abs($difference) > 0 ? fake()->optional(0.3)->sentence() : null,
                        ]);

                        // Create stock movement for approved adjustments with variance
                        if ($adjConfig['status'] === StockAdjustment::STATUS_APPROVED && abs($difference) > 0.01) {
                            StockMovement::create([
                                'outlet_id' => $outlet->id,
                                'inventory_item_id' => $item->id,
                                'batch_id' => null,
                                'type' => StockMovement::TYPE_ADJUSTMENT,
                                'reference_type' => StockAdjustment::class,
                                'reference_id' => $adjustment->id,
                                'quantity' => $difference,
                                'cost_price' => $costPrice,
                                'stock_before' => $systemQty,
                                'stock_after' => $actualQty,
                                'notes' => "Adjustment: {$adjustment->adjustment_number} - {$reason}",
                                'created_by' => $owner->id,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
