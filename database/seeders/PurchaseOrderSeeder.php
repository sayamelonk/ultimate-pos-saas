<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedPurchaseOrdersForTenant($tenant);
        }
    }

    private function seedPurchaseOrdersForTenant(Tenant $tenant): void
    {
        $outlets = Outlet::where('tenant_id', $tenant->id)->get();
        $suppliers = Supplier::where('tenant_id', $tenant->id)->get();
        $items = InventoryItem::where('tenant_id', $tenant->id)->get();
        $users = User::where('tenant_id', $tenant->id)->get();

        if ($outlets->isEmpty() || $suppliers->isEmpty() || $items->isEmpty() || $users->isEmpty()) {
            return;
        }

        $owner = $users->first();
        $poNumber = 1;

        foreach ($outlets as $outlet) {
            // Create various PO statuses
            $statuses = [
                ['status' => PurchaseOrder::STATUS_DRAFT, 'count' => 2],
                ['status' => PurchaseOrder::STATUS_SUBMITTED, 'count' => 2],
                ['status' => PurchaseOrder::STATUS_APPROVED, 'count' => 3],
                ['status' => PurchaseOrder::STATUS_PARTIAL, 'count' => 2],
                ['status' => PurchaseOrder::STATUS_RECEIVED, 'count' => 5],
                ['status' => PurchaseOrder::STATUS_CANCELLED, 'count' => 1],
            ];

            foreach ($statuses as $statusConfig) {
                for ($i = 0; $i < $statusConfig['count']; $i++) {
                    $supplier = $suppliers->random();
                    $orderDate = fake()->dateTimeBetween('-60 days', 'now');
                    $expectedDate = fake()->dateTimeBetween($orderDate, '+14 days');

                    $po = PurchaseOrder::create([
                        'tenant_id' => $tenant->id,
                        'outlet_id' => $outlet->id,
                        'supplier_id' => $supplier->id,
                        'po_number' => 'PO-'.str_pad($poNumber++, 5, '0', STR_PAD_LEFT),
                        'order_date' => $orderDate,
                        'expected_date' => $expectedDate,
                        'status' => $statusConfig['status'],
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'discount_amount' => 0,
                        'total' => 0,
                        'notes' => fake()->optional(0.3)->sentence(),
                        'terms' => $supplier->payment_terms > 0 ? "Net {$supplier->payment_terms} days" : 'Cash on Delivery',
                        'created_by' => $owner->id,
                        'approved_by' => in_array($statusConfig['status'], [
                            PurchaseOrder::STATUS_APPROVED,
                            PurchaseOrder::STATUS_PARTIAL,
                            PurchaseOrder::STATUS_RECEIVED,
                        ]) ? $owner->id : null,
                        'approved_at' => in_array($statusConfig['status'], [
                            PurchaseOrder::STATUS_APPROVED,
                            PurchaseOrder::STATUS_PARTIAL,
                            PurchaseOrder::STATUS_RECEIVED,
                        ]) ? fake()->dateTimeBetween($orderDate, 'now') : null,
                    ]);

                    // Add 3-8 items per PO
                    $poItems = $items->random(fake()->numberBetween(3, 8));
                    $subtotal = 0;
                    $taxTotal = 0;

                    foreach ($poItems as $item) {
                        $quantity = fake()->randomFloat(2, 5, 50);
                        $unitPrice = (float) $item->cost_price * fake()->randomFloat(2, 0.9, 1.1);
                        $discountPercent = fake()->randomElement([0, 0, 0, 5, 10]);
                        $taxPercent = 11;

                        $itemSubtotal = $quantity * $unitPrice;
                        $discountAmount = $itemSubtotal * ($discountPercent / 100);
                        $afterDiscount = $itemSubtotal - $discountAmount;
                        $taxAmount = $afterDiscount * ($taxPercent / 100);
                        $total = $afterDiscount + $taxAmount;

                        $receivedQty = 0;
                        if ($statusConfig['status'] === PurchaseOrder::STATUS_RECEIVED) {
                            $receivedQty = $quantity;
                        } elseif ($statusConfig['status'] === PurchaseOrder::STATUS_PARTIAL) {
                            $receivedQty = $quantity * fake()->randomFloat(2, 0.3, 0.7);
                        }

                        PurchaseOrderItem::create([
                            'purchase_order_id' => $po->id,
                            'inventory_item_id' => $item->id,
                            'unit_id' => $item->unit_id,
                            'unit_conversion' => 1,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'discount_percent' => $discountPercent,
                            'discount_amount' => $discountAmount,
                            'tax_percent' => $taxPercent,
                            'tax_amount' => $taxAmount,
                            'total' => $total,
                            'received_qty' => $receivedQty,
                            'notes' => null,
                        ]);

                        $subtotal += $afterDiscount;
                        $taxTotal += $taxAmount;
                    }

                    $po->update([
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxTotal,
                        'total' => $subtotal + $taxTotal,
                    ]);
                }
            }
        }
    }
}
