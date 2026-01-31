# Phase 2: Services - Full Source Code

**Catatan:** Copy setiap service ke file yang sesuai di `app/Services/Inventory/`

---

## 1. StockService.php

**Location:** `app/Services/Inventory/StockService.php`

```php
<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function issueStock(
        string $outletId,
        string $inventoryItemId,
        float $quantity,
        string $userId,
        string $reason = '',
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $quantity, $userId, $reason, $referenceType, $referenceId) {
            $stock = InventoryStock::where('outlet_id', $outletId)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            if (! $stock) {
                throw new \Exception('No stock record found for this item at this outlet.');
            }

            $availableQty = $stock->quantity - ($stock->reserved_qty ?? 0);

            if ($availableQty < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$availableQty}, Requested: {$quantity}");
            }

            $stockBefore = $stock->quantity;
            $newQuantity = $stockBefore - $quantity;

            $stock->update([
                'quantity' => $newQuantity,
                'last_issued_at' => now(),
            ]);

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'type' => StockMovement::TYPE_OUT,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => -$quantity,
                'cost_price' => $stock->avg_cost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    public function receiveStock(
        string $outletId,
        string $inventoryItemId,
        float $quantity,
        float $unitCost,
        string $userId,
        string $reason = '',
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $quantity, $unitCost, $userId, $reason, $referenceType, $referenceId) {
            $stock = InventoryStock::firstOrCreate(
                [
                    'outlet_id' => $outletId,
                    'inventory_item_id' => $inventoryItemId,
                ],
                [
                    'quantity' => 0,
                    'reserved_qty' => 0,
                    'avg_cost' => 0,
                    'last_cost' => 0,
                ]
            );

            $stockBefore = $stock->quantity;
            $newQuantity = $stockBefore + $quantity;

            // Calculate new average cost
            $currentValue = $stock->quantity * $stock->avg_cost;
            $newValue = $quantity * $unitCost;
            $newAvgCost = $newQuantity > 0 ? ($currentValue + $newValue) / $newQuantity : $unitCost;

            $stock->update([
                'quantity' => $newQuantity,
                'avg_cost' => $newAvgCost,
                'last_cost' => $unitCost,
                'last_received_at' => now(),
            ]);

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'type' => StockMovement::TYPE_IN,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => $quantity,
                'cost_price' => $unitCost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    public function adjustStock(
        string $outletId,
        string $inventoryItemId,
        float $adjustmentQuantity,
        string $userId,
        string $reason = '',
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $adjustmentQuantity, $userId, $reason, $referenceType, $referenceId) {
            $stock = InventoryStock::firstOrCreate(
                [
                    'outlet_id' => $outletId,
                    'inventory_item_id' => $inventoryItemId,
                ],
                [
                    'quantity' => 0,
                    'reserved_qty' => 0,
                    'avg_cost' => 0,
                    'last_cost' => 0,
                ]
            );

            $stockBefore = $stock->quantity;
            $newQuantity = max(0, $stockBefore + $adjustmentQuantity);

            $stock->update([
                'quantity' => $newQuantity,
            ]);

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => $adjustmentQuantity,
                'cost_price' => $stock->avg_cost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    public function reserveStock(
        string $outletId,
        string $inventoryItemId,
        float $quantity
    ): void {
        $stock = InventoryStock::where('outlet_id', $outletId)
            ->where('inventory_item_id', $inventoryItemId)
            ->first();

        if (! $stock) {
            throw new \Exception('No stock record found.');
        }

        $availableQty = $stock->quantity - ($stock->reserved_qty ?? 0);

        if ($availableQty < $quantity) {
            throw new \Exception("Insufficient stock to reserve. Available: {$availableQty}, Requested: {$quantity}");
        }

        $stock->increment('reserved_qty', $quantity);
    }

    public function releaseReservation(
        string $outletId,
        string $inventoryItemId,
        float $quantity
    ): void {
        $stock = InventoryStock::where('outlet_id', $outletId)
            ->where('inventory_item_id', $inventoryItemId)
            ->first();

        if ($stock) {
            $stock->decrement('reserved_qty', min($quantity, $stock->reserved_qty));
        }
    }

    public function getAvailableStock(string $outletId, string $inventoryItemId): float
    {
        $stock = InventoryStock::where('outlet_id', $outletId)
            ->where('inventory_item_id', $inventoryItemId)
            ->first();

        if (! $stock) {
            return 0;
        }

        return $stock->quantity - ($stock->reserved_qty ?? 0);
    }

    public function getTotalStock(string $inventoryItemId, ?array $outletIds = null): float
    {
        $query = InventoryStock::where('inventory_item_id', $inventoryItemId);

        if ($outletIds) {
            $query->whereIn('outlet_id', $outletIds);
        }

        return $query->sum('quantity');
    }

    public function recordWaste(
        string $outletId,
        string $inventoryItemId,
        float $quantity,
        string $userId,
        string $reason = ''
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $quantity, $userId, $reason) {
            $stock = InventoryStock::where('outlet_id', $outletId)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            if (! $stock) {
                throw new \Exception('No stock record found for this item at this outlet.');
            }

            $stockBefore = $stock->quantity;
            $newQuantity = max(0, $stockBefore - $quantity);

            $stock->update([
                'quantity' => $newQuantity,
                'last_issued_at' => now(),
            ]);

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'type' => StockMovement::TYPE_WASTE,
                'quantity' => -$quantity,
                'cost_price' => $stock->avg_cost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }
}
```

---

## 2. RecipeCostService.php

**Location:** `app/Services/Inventory/RecipeCostService.php`

```php
<?php

namespace App\Services\Inventory;

use App\Models\Recipe;

class RecipeCostService
{
    public function updateRecipeCost(Recipe $recipe): Recipe
    {
        $recipe->load('items.inventoryItem');

        $totalCost = $recipe->calculateCost();

        $recipe->update([
            'estimated_cost' => $totalCost,
        ]);

        return $recipe->fresh();
    }

    public function getCostBreakdown(Recipe $recipe): array
    {
        $recipe->load('items.inventoryItem.unit');

        $ingredients = [];
        $totalCost = 0;

        foreach ($recipe->items as $item) {
            $itemCost = $item->calculateCost();
            $totalCost += $itemCost;

            $ingredients[] = [
                'name' => $item->inventoryItem->name,
                'sku' => $item->inventoryItem->sku,
                'quantity' => $item->quantity,
                'gross_quantity' => $item->getGrossQuantity(),
                'unit' => $item->inventoryItem->unit->abbreviation ?? '',
                'unit_cost' => $item->inventoryItem->cost_price ?? 0,
                'waste_percentage' => $item->waste_percentage ?? 0,
                'total_cost' => $itemCost,
                'percentage_of_total' => 0, // Will be calculated below
            ];
        }

        // Calculate percentage of total for each ingredient
        if ($totalCost > 0) {
            foreach ($ingredients as &$ingredient) {
                $ingredient['percentage_of_total'] = ($ingredient['total_cost'] / $totalCost) * 100;
            }
        }

        $yieldQty = $recipe->yield_qty ?? 1;
        $costPerUnit = $yieldQty > 0 ? $totalCost / $yieldQty : 0;

        return [
            'ingredients' => $ingredients,
            'total_cost' => $totalCost,
            'yield_quantity' => $yieldQty,
            'cost_per_unit' => $costPerUnit,
            'ingredient_count' => count($ingredients),
        ];
    }

    public function suggestSellingPrice(Recipe $recipe, float $marginPercentage = 30): float
    {
        $costPerUnit = $recipe->getCostPerUnit();

        if ($costPerUnit <= 0) {
            return 0;
        }

        // Price = Cost / (1 - Margin%)
        // E.g., if cost is 100 and margin is 30%, price = 100 / 0.7 = 142.86
        $marginFactor = 1 - ($marginPercentage / 100);

        return $marginFactor > 0 ? $costPerUnit / $marginFactor : 0;
    }

    public function calculateFoodCostPercentage(Recipe $recipe, float $sellingPrice): float
    {
        if ($sellingPrice <= 0) {
            return 0;
        }

        $costPerUnit = $recipe->getCostPerUnit();

        return ($costPerUnit / $sellingPrice) * 100;
    }

    public function recalculateAllRecipeCosts(string $tenantId): int
    {
        $recipes = Recipe::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($recipes as $recipe) {
            $this->updateRecipeCost($recipe);
            $count++;
        }

        return $count;
    }

    public function getRecipesByIngredient(string $tenantId, string $inventoryItemId): \Illuminate\Support\Collection
    {
        return Recipe::where('tenant_id', $tenantId)
            ->whereHas('items', function ($query) use ($inventoryItemId) {
                $query->where('inventory_item_id', $inventoryItemId);
            })
            ->with(['items.inventoryItem', 'yieldUnit'])
            ->get();
    }
}
```

---

## 3. StockAdjustmentService.php

**Location:** `app/Services/Inventory/StockAdjustmentService.php`

```php
<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function createAdjustment(
        string $tenantId,
        string $outletId,
        string $userId,
        string $type,
        array $items,
        ?string $adjustmentDate = null,
        ?string $reason = null,
        ?string $notes = null
    ): StockAdjustment {
        return DB::transaction(function () use ($tenantId, $outletId, $userId, $type, $items, $adjustmentDate, $reason, $notes) {
            $adjustmentNumber = $this->generateAdjustmentNumber($tenantId);

            $adjustment = StockAdjustment::create([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'adjustment_number' => $adjustmentNumber,
                'adjustment_date' => $adjustmentDate ?? now()->toDateString(),
                'type' => $type,
                'status' => 'draft',
                'reason' => $reason,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $totalVariance = 0;

            foreach ($items as $item) {
                $varianceQty = ($item['actual_quantity'] ?? 0) - ($item['system_quantity'] ?? 0);
                $totalVariance += abs($varianceQty);

                $adjustment->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'system_quantity' => $item['system_quantity'] ?? 0,
                    'actual_quantity' => $item['actual_quantity'] ?? 0,
                    'variance_quantity' => $varianceQty,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $adjustment->update(['total_variance' => $totalVariance]);

            return $adjustment;
        });
    }

    public function approveAdjustment(StockAdjustment $adjustment, string $approvedBy): StockAdjustment
    {
        if ($adjustment->status !== 'draft') {
            throw new \Exception('Only draft adjustments can be approved.');
        }

        return DB::transaction(function () use ($adjustment, $approvedBy) {
            $adjustment->load('items.inventoryItem');

            foreach ($adjustment->items as $item) {
                // Get or create stock record
                $stock = InventoryStock::firstOrCreate(
                    [
                        'outlet_id' => $adjustment->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'avg_cost' => 0,
                        'last_cost' => 0,
                    ]
                );

                $stockBefore = $stock->quantity;
                $variance = $item->variance_quantity;

                if ($variance != 0) {
                    // Update stock quantity
                    $newQuantity = $stockBefore + $variance;
                    $stock->update(['quantity' => max(0, $newQuantity)]);

                    // Create stock movement
                    StockMovement::create([
                        'outlet_id' => $adjustment->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'type' => StockMovement::TYPE_ADJUSTMENT,
                        'reference_type' => StockAdjustment::class,
                        'reference_id' => $adjustment->id,
                        'quantity' => $variance,
                        'cost_price' => $stock->avg_cost,
                        'stock_before' => $stockBefore,
                        'stock_after' => max(0, $newQuantity),
                        'notes' => "Stock adjustment: {$adjustment->adjustment_number} ({$adjustment->type})",
                        'created_by' => $approvedBy,
                    ]);
                }
            }

            $adjustment->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $adjustment->fresh();
        });
    }

    public function rejectAdjustment(StockAdjustment $adjustment): StockAdjustment
    {
        if ($adjustment->status !== 'draft') {
            throw new \Exception('Only draft adjustments can be rejected.');
        }

        $adjustment->update(['status' => 'rejected']);

        return $adjustment->fresh();
    }

    public function cancelAdjustment(StockAdjustment $adjustment): StockAdjustment
    {
        if (! in_array($adjustment->status, ['draft'])) {
            throw new \Exception('Only draft adjustments can be cancelled.');
        }

        $adjustment->update(['status' => 'cancelled']);

        return $adjustment->fresh();
    }

    private function generateAdjustmentNumber(string $tenantId): string
    {
        $prefix = 'ADJ';
        $date = now()->format('Ymd');

        $lastAdjustment = StockAdjustment::where('tenant_id', $tenantId)
            ->where('adjustment_number', 'like', "{$prefix}{$date}%")
            ->orderBy('adjustment_number', 'desc')
            ->first();

        if ($lastAdjustment) {
            $lastNumber = (int) substr($lastAdjustment->adjustment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }
}
```

---

## 4. StockTransferService.php

**Location:** `app/Services/Inventory/StockTransferService.php`

```php
<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function createTransfer(
        string $tenantId,
        string $sourceOutletId,
        string $destinationOutletId,
        string $userId,
        array $items,
        ?string $transferDate = null,
        ?string $notes = null
    ): StockTransfer {
        return DB::transaction(function () use ($tenantId, $sourceOutletId, $destinationOutletId, $userId, $items, $transferDate, $notes) {
            $transferNumber = $this->generateTransferNumber($tenantId);

            $transfer = StockTransfer::create([
                'tenant_id' => $tenantId,
                'from_outlet_id' => $sourceOutletId,
                'to_outlet_id' => $destinationOutletId,
                'transfer_number' => $transferNumber,
                'transfer_date' => $transferDate ?? now()->toDateString(),
                'status' => 'draft',
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                $transfer->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $transfer;
        });
    }

    public function approveTransfer(StockTransfer $transfer, string $approvedBy): StockTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new \Exception('Only draft transfers can be approved.');
        }

        return DB::transaction(function () use ($transfer, $approvedBy) {
            $transfer->load('items.inventoryItem');

            // Check if source outlet has enough stock
            foreach ($transfer->items as $item) {
                $sourceStock = InventoryStock::where('outlet_id', $transfer->from_outlet_id)
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->first();

                $availableQty = $sourceStock ? ($sourceStock->quantity - $sourceStock->reserved_qty) : 0;

                if ($availableQty < $item->quantity) {
                    throw new \Exception("Insufficient stock for item: {$item->inventoryItem->name}. Available: {$availableQty}, Requested: {$item->quantity}");
                }
            }

            // Reserve stock at source outlet
            foreach ($transfer->items as $item) {
                $sourceStock = InventoryStock::where('outlet_id', $transfer->from_outlet_id)
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->first();

                if ($sourceStock) {
                    $sourceStock->increment('reserved_qty', $item->quantity);
                }

                // Store cost price in transfer item
                $item->update(['cost_price' => $sourceStock->avg_cost ?? 0]);
            }

            $transfer->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $transfer->fresh();
        });
    }

    public function receiveTransfer(StockTransfer $transfer, string $receivedBy): StockTransfer
    {
        if (! in_array($transfer->status, ['approved', 'in_transit'])) {
            throw new \Exception('Only approved or in-transit transfers can be received.');
        }

        return DB::transaction(function () use ($transfer, $receivedBy) {
            $transfer->load('items.inventoryItem');

            foreach ($transfer->items as $item) {
                // Deduct from source outlet
                $sourceStock = InventoryStock::where('outlet_id', $transfer->from_outlet_id)
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->first();

                if ($sourceStock) {
                    $stockBefore = $sourceStock->quantity;
                    $sourceStock->decrement('quantity', $item->quantity);
                    $sourceStock->decrement('reserved_qty', $item->quantity);

                    // Create movement for source
                    StockMovement::create([
                        'outlet_id' => $transfer->from_outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'type' => StockMovement::TYPE_TRANSFER_OUT,
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'quantity' => -$item->quantity,
                        'cost_price' => $item->cost_price ?? $sourceStock->avg_cost,
                        'stock_before' => $stockBefore,
                        'stock_after' => $sourceStock->fresh()->quantity,
                        'notes' => "Transfer out: {$transfer->transfer_number}",
                        'created_by' => $receivedBy,
                    ]);
                }

                // Add to destination outlet
                $destStock = InventoryStock::firstOrCreate(
                    [
                        'outlet_id' => $transfer->to_outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'avg_cost' => 0,
                        'last_cost' => 0,
                    ]
                );

                $destStockBefore = $destStock->quantity;
                $costPrice = $item->cost_price ?? ($sourceStock->avg_cost ?? 0);

                // Calculate new average cost for destination
                $currentValue = $destStock->quantity * $destStock->avg_cost;
                $newValue = $item->quantity * $costPrice;
                $newQuantity = $destStock->quantity + $item->quantity;
                $newAvgCost = $newQuantity > 0 ? ($currentValue + $newValue) / $newQuantity : $costPrice;

                $destStock->update([
                    'quantity' => $newQuantity,
                    'avg_cost' => $newAvgCost,
                    'last_cost' => $costPrice,
                    'last_received_at' => now(),
                ]);

                // Create movement for destination
                StockMovement::create([
                    'outlet_id' => $transfer->to_outlet_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'type' => StockMovement::TYPE_TRANSFER_IN,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'quantity' => $item->quantity,
                    'cost_price' => $costPrice,
                    'stock_before' => $destStockBefore,
                    'stock_after' => $newQuantity,
                    'notes' => "Transfer in: {$transfer->transfer_number}",
                    'created_by' => $receivedBy,
                ]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => $receivedBy,
                'received_at' => now(),
            ]);

            return $transfer->fresh();
        });
    }

    public function cancelTransfer(StockTransfer $transfer, string $userId): StockTransfer
    {
        if (! in_array($transfer->status, ['draft', 'approved', 'in_transit'])) {
            throw new \Exception('This transfer cannot be cancelled.');
        }

        return DB::transaction(function () use ($transfer) {
            // If already approved, release reserved stock
            if (in_array($transfer->status, ['approved', 'in_transit'])) {
                $transfer->load('items');

                foreach ($transfer->items as $item) {
                    $sourceStock = InventoryStock::where('outlet_id', $transfer->from_outlet_id)
                        ->where('inventory_item_id', $item->inventory_item_id)
                        ->first();

                    if ($sourceStock) {
                        $sourceStock->decrement('reserved_qty', $item->quantity);
                    }
                }
            }

            $transfer->update(['status' => 'cancelled']);

            return $transfer->fresh();
        });
    }

    private function generateTransferNumber(string $tenantId): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');

        $lastTransfer = StockTransfer::where('tenant_id', $tenantId)
            ->where('transfer_number', 'like', "{$prefix}{$date}%")
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }
}
```

---

## 5. PurchaseOrderService.php

**Location:** `app/Services/Inventory/PurchaseOrderService.php`

```php
<?php

namespace App\Services\Inventory;

use App\Models\GoodsReceive;
use App\Models\InventoryStock;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function createPurchaseOrder(
        string $tenantId,
        string $supplierId,
        string $outletId,
        string $userId,
        array $items,
        ?string $expectedDate = null,
        ?string $notes = null
    ): PurchaseOrder {
        return DB::transaction(function () use ($tenantId, $supplierId, $outletId, $userId, $items, $expectedDate, $notes) {
            $poNumber = $this->generatePoNumber($tenantId);

            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'total_price' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $purchaseOrder = PurchaseOrder::create([
                'tenant_id' => $tenantId,
                'po_number' => $poNumber,
                'supplier_id' => $supplierId,
                'outlet_id' => $outletId,
                'status' => 'draft',
                'expected_date' => $expectedDate,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($itemsData as $itemData) {
                $purchaseOrder->items()->create($itemData);
            }

            return $purchaseOrder;
        });
    }

    public function approvePurchaseOrder(PurchaseOrder $purchaseOrder, string $approvedBy): PurchaseOrder
    {
        if ($purchaseOrder->status !== 'draft') {
            throw new \Exception('Only draft purchase orders can be approved.');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $purchaseOrder->fresh();
    }

    public function cancelPurchaseOrder(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if (! in_array($purchaseOrder->status, ['draft', 'approved', 'sent'])) {
            throw new \Exception('This purchase order cannot be cancelled.');
        }

        // Check if any goods have been received
        if ($purchaseOrder->goodsReceives()->where('status', 'completed')->exists()) {
            throw new \Exception('Cannot cancel purchase order with completed goods receives.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return $purchaseOrder->fresh();
    }

    public function updateReceivedQuantities(PurchaseOrder $purchaseOrder): void
    {
        // Update received quantities on PO items based on completed goods receives
        foreach ($purchaseOrder->items as $poItem) {
            $receivedQty = $purchaseOrder->goodsReceives()
                ->where('status', 'completed')
                ->join('goods_receive_items', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
                ->where('goods_receive_items.inventory_item_id', $poItem->inventory_item_id)
                ->sum('goods_receive_items.quantity');

            $poItem->update(['received_quantity' => $receivedQty]);
        }

        // Update PO status based on received quantities
        $this->updatePoStatus($purchaseOrder);
    }

    public function updatePoStatus(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('items');

        $totalOrdered = $purchaseOrder->items->sum('quantity');
        $totalReceived = $purchaseOrder->items->sum('received_quantity');

        if ($totalReceived == 0) {
            // No change if nothing received yet
            return;
        }

        if ($totalReceived >= $totalOrdered) {
            $purchaseOrder->update(['status' => 'received']);
        } else {
            $purchaseOrder->update(['status' => 'partially_received']);
        }
    }

    private function generatePoNumber(string $tenantId): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');

        $lastPo = PurchaseOrder::where('tenant_id', $tenantId)
            ->where('po_number', 'like', "{$prefix}{$date}%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }

    public function createGoodsReceive(
        PurchaseOrder $purchaseOrder,
        string $userId,
        array $items,
        ?string $invoiceNumber = null,
        ?string $receiveDate = null,
        ?string $notes = null
    ): GoodsReceive {
        return DB::transaction(function () use ($purchaseOrder, $userId, $items, $invoiceNumber, $receiveDate, $notes) {
            $grNumber = $this->generateGrNumber($purchaseOrder->tenant_id);

            $goodsReceive = GoodsReceive::create([
                'tenant_id' => $purchaseOrder->tenant_id,
                'outlet_id' => $purchaseOrder->outlet_id,
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'gr_number' => $grNumber,
                'receive_date' => $receiveDate ?? now()->toDateString(),
                'status' => 'draft',
                'invoice_number' => $invoiceNumber,
                'notes' => $notes,
                'received_by' => $userId,
            ]);

            $subtotal = 0;

            foreach ($items as $item) {
                $poItem = $purchaseOrder->items()->findOrFail($item['purchase_order_item_id']);

                $quantity = $item['quantity_received'] ?? 0;
                $unitPrice = $poItem->unit_price;
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $goodsReceive->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $poItem->inventory_item_id,
                    'quantity' => $quantity,
                    'stock_qty' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'unit_conversion' => 1,
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => 0,
                    'tax_amount' => 0,
                ]);
            }

            $goodsReceive->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            return $goodsReceive;
        });
    }

    public function completeGoodsReceive(GoodsReceive $goodsReceive, string $userId): GoodsReceive
    {
        if ($goodsReceive->status !== 'draft') {
            throw new \Exception('Only draft goods receives can be completed.');
        }

        return DB::transaction(function () use ($goodsReceive, $userId) {
            $goodsReceive->load(['items.inventoryItem', 'outlet']);

            foreach ($goodsReceive->items as $grItem) {
                // Update or create inventory stock
                $stock = InventoryStock::firstOrCreate(
                    [
                        'outlet_id' => $goodsReceive->outlet_id,
                        'inventory_item_id' => $grItem->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'avg_cost' => 0,
                        'last_cost' => 0,
                    ]
                );

                $stockBefore = $stock->quantity;
                $newQuantity = $stockBefore + $grItem->stock_qty;

                // Calculate new average cost
                $currentValue = $stock->quantity * $stock->avg_cost;
                $newValue = $grItem->stock_qty * $grItem->unit_price;
                $newAvgCost = $newQuantity > 0 ? ($currentValue + $newValue) / $newQuantity : $grItem->unit_price;

                $stock->update([
                    'quantity' => $newQuantity,
                    'avg_cost' => $newAvgCost,
                    'last_cost' => $grItem->unit_price,
                    'last_received_at' => now(),
                ]);

                // Create stock batch if batch number provided
                if ($grItem->batch_number || $grItem->expiry_date) {
                    StockBatch::create([
                        'outlet_id' => $goodsReceive->outlet_id,
                        'inventory_item_id' => $grItem->inventory_item_id,
                        'batch_number' => $grItem->batch_number,
                        'expiry_date' => $grItem->expiry_date,
                        'initial_qty' => $grItem->stock_qty,
                        'current_qty' => $grItem->stock_qty,
                        'cost_price' => $grItem->unit_price,
                        'goods_receive_item_id' => $grItem->id,
                        'status' => 'active',
                    ]);
                }

                // Create stock movement
                StockMovement::create([
                    'outlet_id' => $goodsReceive->outlet_id,
                    'inventory_item_id' => $grItem->inventory_item_id,
                    'type' => StockMovement::TYPE_IN,
                    'reference_type' => GoodsReceive::class,
                    'reference_id' => $goodsReceive->id,
                    'quantity' => $grItem->stock_qty,
                    'cost_price' => $grItem->unit_price,
                    'stock_before' => $stockBefore,
                    'stock_after' => $newQuantity,
                    'notes' => "Goods receive: {$goodsReceive->gr_number}",
                    'created_by' => $userId,
                ]);
            }

            // Update goods receive status
            $goodsReceive->update([
                'status' => 'completed',
            ]);

            // Update PO received quantities
            $this->updateReceivedQuantities($goodsReceive->purchaseOrder);

            return $goodsReceive->fresh();
        });
    }

    private function generateGrNumber(string $tenantId): string
    {
        $prefix = 'GR';
        $date = now()->format('Ymd');

        $lastGr = GoodsReceive::where('tenant_id', $tenantId)
            ->where('gr_number', 'like', "{$prefix}{$date}%")
            ->orderBy('gr_number', 'desc')
            ->first();

        if ($lastGr) {
            $lastNumber = (int) substr($lastGr->gr_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }
}
```

---

## Cara Copy ke Project:

### 1. Pastikan folder sudah ada:

```bash
mkdir -p app/Services/Inventory
```

### 2. Copy setiap service:

```bash
# StockService
cp phase2-services-code.txt /temp/StockService.php
# Atau copy manual dari file ini ke app/Services/Inventory/StockService.php

# RecipeCostService
copy ke app/Services/Inventory/RecipeCostService.php

# StockAdjustmentService
copy ke app/Services/Inventory/StockAdjustmentService.php

# StockTransferService
copy ke app/Services/Inventory/StockTransferService.php

# PurchaseOrderService
copy ke app/Services/Inventory/PurchaseOrderService.php
```

### 3. Pastikan namespace sudah benar:

Semua service harus punya namespace:
```php
namespace App\Services\Inventory;
```

### 4. Test service:

```bash
php artisan tinker
```

```php
// Test StockService
use App\Services\Inventory\StockService;
$stockService = app(StockService::class);
echo "StockService loaded!";
```

---

## Done!

Semua 5 services Phase 2 sudah siap di-copy ke project Anda! 🎉
