<?php

namespace Database\Seeders;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class KitchenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->command->error('No tenant found. Please run TenantSeeder first.');

            return;
        }

        $outlet = Outlet::where('tenant_id', $tenant->id)->first();
        if (! $outlet) {
            $this->command->error('No outlet found. Please run OutletSeeder first.');

            return;
        }

        // Create Kitchen Stations
        $stations = [
            [
                'name' => 'Main Kitchen',
                'code' => 'MAIN',
                'color' => '#EF4444',
                'description' => 'Main kitchen for hot dishes',
                'sort_order' => 1,
            ],
            [
                'name' => 'Grill Station',
                'code' => 'GRILL',
                'color' => '#F97316',
                'description' => 'Grilled items and steaks',
                'sort_order' => 2,
            ],
            [
                'name' => 'Cold Station',
                'code' => 'COLD',
                'color' => '#3B82F6',
                'description' => 'Salads, desserts, cold beverages',
                'sort_order' => 3,
            ],
            [
                'name' => 'Beverage Station',
                'code' => 'BEV',
                'color' => '#10B981',
                'description' => 'All beverages and drinks',
                'sort_order' => 4,
            ],
        ];

        $createdStations = [];
        foreach ($stations as $stationData) {
            $station = KitchenStation::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'outlet_id' => $outlet->id,
                    'code' => $stationData['code'],
                ],
                array_merge($stationData, [
                    'tenant_id' => $tenant->id,
                    'outlet_id' => $outlet->id,
                    'is_active' => true,
                ])
            );
            $createdStations[] = $station;
        }

        $this->command->info('Created '.count($createdStations).' kitchen stations.');

        // Create Kitchen Orders from existing transactions
        $this->createKitchenOrdersFromTransactions($tenant, $outlet, $createdStations);
    }

    private function createKitchenOrdersFromTransactions(Tenant $tenant, Outlet $outlet, array $stations): void
    {
        // Get transactions that don't have kitchen orders yet
        $transactions = Transaction::where('tenant_id', $tenant->id)
            ->where('outlet_id', $outlet->id)
            ->where('status', 'completed')
            ->whereDoesntHave('kitchenOrder')
            ->with('items')
            ->take(10)
            ->get();

        if ($transactions->isEmpty()) {
            $this->command->warn('No transactions found to create kitchen orders.');

            return;
        }

        $statuses = [
            KitchenOrder::STATUS_PENDING,
            KitchenOrder::STATUS_PENDING,
            KitchenOrder::STATUS_PREPARING,
            KitchenOrder::STATUS_PREPARING,
            KitchenOrder::STATUS_READY,
        ];

        $count = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->items->isEmpty()) {
                continue;
            }

            $station = $stations[array_rand($stations)];
            $status = $statuses[array_rand($statuses)];

            $kitchenOrder = KitchenOrder::create([
                'tenant_id' => $tenant->id,
                'outlet_id' => $outlet->id,
                'transaction_id' => $transaction->id,
                'station_id' => $station->id,
                'order_number' => $transaction->transaction_number,
                'order_type' => $transaction->order_type ?? 'dine_in',
                'table_name' => 'Table '.rand(1, 20),
                'customer_name' => $transaction->customer_name ?? 'Guest',
                'status' => $status,
                'priority' => ['normal', 'normal', 'normal', 'rush', 'vip'][array_rand(['normal', 'normal', 'normal', 'rush', 'vip'])],
                'created_at' => $transaction->created_at,
                'started_at' => $status !== KitchenOrder::STATUS_PENDING ? now()->subMinutes(rand(1, 10)) : null,
                'completed_at' => $status === KitchenOrder::STATUS_READY ? now() : null,
            ]);

            // Create kitchen order items from transaction items
            foreach ($transaction->items as $item) {
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kitchenOrder->id,
                    'transaction_item_id' => $item->id,
                    'station_id' => $station->id,
                    'item_name' => $item->item_name ?? $item->product?->name ?? 'Unknown Item',
                    'quantity' => $item->quantity,
                    'modifiers' => $item->modifiers ?? [],
                    'notes' => $item->notes,
                    'status' => $status === KitchenOrder::STATUS_READY ? 'ready' : ($status === KitchenOrder::STATUS_PREPARING ? 'preparing' : 'pending'),
                    'started_at' => $status !== KitchenOrder::STATUS_PENDING ? now()->subMinutes(rand(1, 5)) : null,
                    'completed_at' => $status === KitchenOrder::STATUS_READY ? now() : null,
                ]);
            }

            $count++;
        }

        $this->command->info("Created $count kitchen orders from existing transactions.");
    }
}
