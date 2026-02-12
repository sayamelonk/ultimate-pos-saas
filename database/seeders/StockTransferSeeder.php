<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockTransferSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedStockTransfersForTenant($tenant);
        }
    }

    private function seedStockTransfersForTenant(Tenant $tenant): void
    {
        $outlets = Outlet::where('tenant_id', $tenant->id)->get();
        $items = InventoryItem::where('tenant_id', $tenant->id)->get();
        $users = User::where('tenant_id', $tenant->id)->get();

        // Need at least 2 outlets for transfers
        if ($outlets->count() < 2) {
            // Create a second outlet for demo purposes
            $secondOutlet = Outlet::create([
                'tenant_id' => $tenant->id,
                'code' => 'OUT-02',
                'name' => 'Demo Outlet - Branch 2',
                'address' => 'Jl. Sudirman No. 456',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'postal_code' => '12920',
                'phone' => '021-5678901',
                'email' => 'branch2@demo.com',
                'opening_time' => '09:00',
                'closing_time' => '21:00',
                'receipt_footer' => 'Terima kasih!',
                'receipt_show_logo' => true,
                'is_active' => true,
            ]);

            // Create stock for the second outlet
            foreach ($items as $item) {
                InventoryStock::create([
                    'outlet_id' => $secondOutlet->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => fake()->randomFloat(2, 10, 100),
                    'reserved_qty' => 0,
                    'avg_cost' => $item->cost_price,
                    'last_cost' => $item->cost_price,
                    'last_received_at' => now(),
                ]);
            }

            $outlets = Outlet::where('tenant_id', $tenant->id)->get();
        }

        if ($outlets->count() < 2 || $items->isEmpty() || $users->isEmpty()) {
            return;
        }

        $owner = $users->first();
        $transferNumber = 1;

        // Create transfers with various statuses
        $transferConfigs = [
            ['status' => StockTransfer::STATUS_DRAFT, 'count' => 2],
            ['status' => StockTransfer::STATUS_PENDING, 'count' => 2],
            ['status' => StockTransfer::STATUS_IN_TRANSIT, 'count' => 3],
            ['status' => StockTransfer::STATUS_RECEIVED, 'count' => 5],
            ['status' => StockTransfer::STATUS_CANCELLED, 'count' => 1],
        ];

        foreach ($transferConfigs as $config) {
            for ($i = 0; $i < $config['count']; $i++) {
                // Randomly select from and to outlets
                $fromOutlet = $outlets->random();
                $toOutlet = $outlets->where('id', '!=', $fromOutlet->id)->random();

                $transferDate = fake()->dateTimeBetween('-30 days', 'now');

                $transfer = StockTransfer::create([
                    'tenant_id' => $tenant->id,
                    'from_outlet_id' => $fromOutlet->id,
                    'to_outlet_id' => $toOutlet->id,
                    'transfer_number' => 'TRF-'.str_pad($transferNumber++, 5, '0', STR_PAD_LEFT),
                    'transfer_date' => $transferDate,
                    'status' => $config['status'],
                    'notes' => fake()->optional(0.3)->sentence(),
                    'created_by' => $owner->id,
                    'approved_by' => in_array($config['status'], [
                        StockTransfer::STATUS_IN_TRANSIT,
                        StockTransfer::STATUS_RECEIVED,
                    ]) ? $owner->id : null,
                    'approved_at' => in_array($config['status'], [
                        StockTransfer::STATUS_IN_TRANSIT,
                        StockTransfer::STATUS_RECEIVED,
                    ]) ? $transferDate : null,
                    'received_by' => $config['status'] === StockTransfer::STATUS_RECEIVED ? $owner->id : null,
                    'received_at' => $config['status'] === StockTransfer::STATUS_RECEIVED
                        ? fake()->dateTimeBetween($transferDate, 'now')
                        : null,
                ]);

                // Add 3-8 items per transfer
                $transferItems = $items->random(fake()->numberBetween(3, 8));

                foreach ($transferItems as $item) {
                    $stock = InventoryStock::where('outlet_id', $fromOutlet->id)
                        ->where('inventory_item_id', $item->id)
                        ->first();

                    if (! $stock) {
                        continue;
                    }

                    $quantity = fake()->randomFloat(2, 5, min(30, (float) $stock->quantity * 0.3));
                    $costPrice = (float) ($stock->avg_cost ?: $item->cost_price);

                    $receivedQty = null;
                    if ($config['status'] === StockTransfer::STATUS_RECEIVED) {
                        // Most transfers are fully received, some have variance
                        $receivedQty = fake()->boolean(85) ? $quantity : $quantity * fake()->randomFloat(2, 0.9, 1.0);
                    }

                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'inventory_item_id' => $item->id,
                        'batch_id' => null,
                        'quantity' => $quantity,
                        'received_qty' => $receivedQty,
                        'unit_id' => $item->unit_id,
                        'cost_price' => $costPrice,
                        'notes' => null,
                    ]);

                    // Create stock movements for completed transfers
                    if ($config['status'] === StockTransfer::STATUS_RECEIVED) {
                        // Transfer out from source
                        $fromStock = InventoryStock::where('outlet_id', $fromOutlet->id)
                            ->where('inventory_item_id', $item->id)
                            ->first();

                        if ($fromStock) {
                            StockMovement::create([
                                'outlet_id' => $fromOutlet->id,
                                'inventory_item_id' => $item->id,
                                'batch_id' => null,
                                'type' => StockMovement::TYPE_TRANSFER_OUT,
                                'reference_type' => StockTransfer::class,
                                'reference_id' => $transfer->id,
                                'quantity' => -$quantity,
                                'cost_price' => $costPrice,
                                'stock_before' => (float) $fromStock->quantity,
                                'stock_after' => (float) $fromStock->quantity - $quantity,
                                'notes' => "Transfer to {$toOutlet->name}",
                                'created_by' => $owner->id,
                            ]);
                        }

                        // Transfer in to destination
                        $toStock = InventoryStock::where('outlet_id', $toOutlet->id)
                            ->where('inventory_item_id', $item->id)
                            ->first();

                        if ($toStock) {
                            StockMovement::create([
                                'outlet_id' => $toOutlet->id,
                                'inventory_item_id' => $item->id,
                                'batch_id' => null,
                                'type' => StockMovement::TYPE_TRANSFER_IN,
                                'reference_type' => StockTransfer::class,
                                'reference_id' => $transfer->id,
                                'quantity' => $receivedQty,
                                'cost_price' => $costPrice,
                                'stock_before' => (float) $toStock->quantity,
                                'stock_after' => (float) $toStock->quantity + $receivedQty,
                                'notes' => "Transfer from {$fromOutlet->name}",
                                'created_by' => $owner->id,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
