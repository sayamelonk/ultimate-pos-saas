<?php

namespace Database\Seeders;

use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\InventoryStock;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class GoodsReceiveSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedGoodsReceivesForTenant($tenant);
        }
    }

    private function seedGoodsReceivesForTenant(Tenant $tenant): void
    {
        // Get POs that have been received (partial or full)
        $purchaseOrders = PurchaseOrder::where('tenant_id', $tenant->id)
            ->whereIn('status', [PurchaseOrder::STATUS_PARTIAL, PurchaseOrder::STATUS_RECEIVED])
            ->with(['items.inventoryItem', 'outlet', 'supplier'])
            ->get();

        $users = User::where('tenant_id', $tenant->id)->get();

        if ($purchaseOrders->isEmpty() || $users->isEmpty()) {
            return;
        }

        $owner = $users->first();
        $grNumber = 1;

        foreach ($purchaseOrders as $po) {
            $receiveDate = fake()->dateTimeBetween($po->order_date, 'now');

            $gr = GoodsReceive::create([
                'tenant_id' => $tenant->id,
                'outlet_id' => $po->outlet_id,
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'gr_number' => 'GR-'.str_pad($grNumber++, 5, '0', STR_PAD_LEFT),
                'receive_date' => $receiveDate,
                'status' => GoodsReceive::STATUS_COMPLETED,
                'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
                'invoice_date' => $receiveDate,
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total' => 0,
                'notes' => fake()->optional(0.2)->sentence(),
                'received_by' => $owner->id,
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            foreach ($po->items as $poItem) {
                if ($poItem->received_qty <= 0) {
                    continue;
                }

                $quantity = $poItem->received_qty;
                $stockQty = $quantity * $poItem->unit_conversion;

                // Calculate expiry date based on shelf life
                $expiryDate = null;
                if ($poItem->inventoryItem->shelf_life_days) {
                    $expiryDate = now()->addDays($poItem->inventoryItem->shelf_life_days);
                }

                $itemSubtotal = $quantity * $poItem->unit_price;
                $discountAmount = $itemSubtotal * ($poItem->discount_percent / 100);
                $afterDiscount = $itemSubtotal - $discountAmount;
                $taxAmount = $afterDiscount * ($poItem->tax_percent / 100);
                $total = $afterDiscount + $taxAmount;

                GoodsReceiveItem::create([
                    'goods_receive_id' => $gr->id,
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $poItem->inventory_item_id,
                    'unit_id' => $poItem->unit_id,
                    'unit_conversion' => $poItem->unit_conversion,
                    'quantity' => $quantity,
                    'stock_qty' => $stockQty,
                    'unit_price' => $poItem->unit_price,
                    'discount_percent' => $poItem->discount_percent,
                    'discount_amount' => $discountAmount,
                    'tax_percent' => $poItem->tax_percent,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'batch_number' => $poItem->inventoryItem->track_batches ? 'BATCH-'.fake()->numerify('######') : null,
                    'production_date' => $poItem->inventoryItem->track_batches ? fake()->dateTimeBetween('-30 days', 'now') : null,
                    'expiry_date' => $expiryDate,
                    'notes' => null,
                ]);

                // Create stock movement
                $stock = InventoryStock::where('outlet_id', $po->outlet_id)
                    ->where('inventory_item_id', $poItem->inventory_item_id)
                    ->first();

                if ($stock) {
                    $stockBefore = (float) $stock->quantity;
                    $costPrice = $total / $stockQty;

                    StockMovement::create([
                        'outlet_id' => $po->outlet_id,
                        'inventory_item_id' => $poItem->inventory_item_id,
                        'batch_id' => null,
                        'type' => StockMovement::TYPE_IN,
                        'reference_type' => GoodsReceive::class,
                        'reference_id' => $gr->id,
                        'quantity' => $stockQty,
                        'cost_price' => $costPrice,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore + $stockQty,
                        'notes' => "Goods receive from PO: {$po->po_number}",
                        'created_by' => $owner->id,
                    ]);
                }

                $subtotal += $afterDiscount;
                $taxTotal += $taxAmount;
                $discountTotal += $discountAmount;
            }

            $gr->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'discount_amount' => $discountTotal,
                'total' => $subtotal + $taxTotal,
            ]);
        }
    }
}
