# Phase 2: Services - Inventory Management

## Overview

Dokumentasi ini mencakup semua **Services** untuk Phase 2 Inventory Management. Services adalah business logic layer yang menghandle operasi kompleks yang melibatkan multiple models dan database transactions.

---

## Service Structure

```
app/Services/Inventory/
├── StockService.php              ⭐⭐⭐ - Stock in/out logic
├── RecipeCostService.php         ⭐⭐ - Recipe costing & pricing
├── StockAdjustmentService.php    ⭐⭐ - Stock opname workflow
├── StockTransferService.php      ⭐⭐ - Transfer antar outlet
└── PurchaseOrderService.php      ⭐⭐⭐ - PO & Goods Receive workflow
```

---

## 1. StockService ⭐⭐⭐

**File:** `app/Services/Inventory/StockService.php`

Service paling penting untuk inventory management. Menghandle semua operasi stock: stock in, stock out, adjustment, reservation, dan waste recording.

### Methods Overview

| Method | Purpose | Returns |
|--------|---------|---------|
| `issueStock()` | Stock keluar (penjualan/pemakaian) | StockMovement |
| `receiveStock()` | Stock masuk (dari PO/transfer) | StockMovement |
| `adjustStock()` | Adjust stock (stock opname) | StockMovement |
| `reserveStock()` | Reserve stock sementara | void |
| `releaseReservation()` | Release reservasi | void |
| `getAvailableStock()` | Cek available stock | float |
| `getTotalStock()` | Total stock multi-outlet | float |
| `recordWaste()` | Catat waste/expired | StockMovement |

### Method Details

#### issueStock()
Mengurangi stock (stock out) untuk penjualan atau pemakaian.

**Parameters:**
- `$outletId` - ID outlet
- `$inventoryItemId` - ID item
- `$quantity` - Quantity yang dikeluarkan (positive)
- `$userId` - User yang melakukan
- `$reason` - Alasan pengeluaran
- `$referenceType` - Reference model (Order, Recipe, dll)
- `$referenceId` - Reference ID

**Features:**
- ✅ Cek available stock (quantity - reserved)
- ✅ Validate sufficient stock
- ✅ Create StockMovement (TYPE_OUT)
- ✅ Update `last_issued_at`

**Example:**
```php
$stockService = app(StockService::class);

// Issue stock untuk penjualan
$movement = $stockService->issueStock(
    $outlet->id,
    $item->id,
    10.5,
    auth()->id(),
    'Sales Order #ORD-001',
    Order::class,
    $order->id
);

// Stock berkurang 10.5 units
```

#### receiveStock()
Menambah stock (stock in) dengan **Weighted Average Cost (WAC)** calculation.

**WAC Formula:**
```php
$currentValue = $stock->quantity * $stock->avg_cost;
$newValue = $quantity * $unitCost;
$newAvgCost = ($currentValue + $newValue) / ($stock->quantity + $quantity);
```

**Example:**
```php
// Sebelumnya: 50 kg @ 10k/kg = 500k
// Terima: 100 kg @ 15k/kg = 1.500k
// New Avg: (500k + 1.500k) / 150 = 13.333k/kg

$movement = $stockService->receiveStock(
    $outlet->id,
    $item->id,
    100.0,
    15000,  // 15k per unit
    auth()->id(),
    'Purchase #PO-001',
    GoodsReceive::class,
    $gr->id
);
```

#### adjustStock()
Adjust stock untuk stock opname/correction.

**Parameters:**
- `$adjustmentQuantity` - Positive (tambah) atau Negative (kurang)

**Example:**
```php
// Stock opname: selisih +5 (lebih banyak)
$stockService->adjustStock(
    $outlet->id,
    $item->id,
    5.0,  // Tambah 5 unit
    auth()->id(),
    'Stock Opname Jan 2025 - Selisih +5',
    StockAdjustment::class,
    $adjustment->id
);

// Stock opname: selisih -3 (kurang)
$stockService->adjustStock(
    $outlet->id,
    $item->id,
    -3.0,  // Kurangi 3 unit
    auth()->id(),
    'Stock Opname Jan 2025 - Selisih -3',
    StockAdjustment::class,
    $adjustment->id
);
```

#### reserveStock() & releaseReservation()
Stock reservation untuk hold stock sementara.

**Use Case:**
- Customer pesan tapi belum bayar
- Hold stock untuk production
- Reserve untuk transfer antar outlet

**Example:**
```php
// Reserve stock
$stockService->reserveStock($outlet->id, $item->id, 20.0);
// 20 unit sekarang reserved, tidak bisa dijual

// Release reservation (customer batal)
$stockService->releaseReservation($outlet->id, $item->id, 20.0);
// 20 unit sekarang available kembali
```

#### getAvailableStock()
Get available stock (quantity - reserved).

**Example:**
```php
$available = $stockService->getAvailableStock($outlet->id, $item->id);
// Returns: 50.0 (kalau total 70, reserved 20)
```

#### getTotalStock()
Get total stock across multiple outlets.

**Example:**
```php
// Total across ALL outlets
$total = $stockService->getTotalStock($item->id);

// Total untuk specific outlets saja
$total = $stockService->getTotalStock($item->id, [$outlet1->id, $outlet2->id]);
```

#### recordWaste()
Record waste/expired stock.

**Example:**
```php
$movement = $stockService->recordWaste(
    $outlet->id,
    $item->id,
    5.5,
    auth()->id(),
    'Expired batch BATCH-2025-01'
);

// Stock berkurang 5.5 units
// Movement type: TYPE_WASTE
```

---

## 2. RecipeCostService ⭐⭐

**File:** `app/Services/Inventory/RecipeCostService.php`

Service untuk menghitung cost recipe, breakdown cost, dan pricing recommendations.

### Methods Overview

| Method | Purpose | Returns |
|--------|---------|---------|
| `updateRecipeCost()` | Update recipe estimated cost | Recipe |
| `getCostBreakdown()` | Detailed cost breakdown per ingredient | array |
| `suggestSellingPrice()` | Hitung suggested selling price | float |
| `calculateFoodCostPercentage()` | Hitung food cost percentage | float |
| `recalculateAllRecipeCosts()` | Batch update semua recipe costs | int (count) |
| `getRecipesByIngredient()` | Cari recipes yang pakai ingredient | Collection |

### Key Methods Explained

#### getCostBreakdown()
Get detailed cost breakdown dengan percentages.

**Returns:**
```php
[
    'ingredients' => [
        [
            'name' => 'Tepung Terigu',
            'sku' => 'SKU-001',
            'quantity' => 2.0,           // Net quantity
            'gross_quantity' => 2.1,     // Dengan waste
            'unit' => 'kg',
            'unit_cost' => 15000,
            'waste_percentage' => 5.0,
            'total_cost' => 31500,
            'percentage_of_total' => 21.0, // 21% dari total cost
        ],
    ],
    'total_cost' => 150000,
    'yield_quantity' => 10,
    'cost_per_unit' => 15000,
    'ingredient_count' => 5
]
```

**Example:**
```php
$recipeCostService = app(RecipeCostService::class);
$breakdown = $recipeCostService->getCostBreakdown($recipe);

foreach ($breakdown['ingredients'] as $ingredient) {
    echo "{$ingredient['name']}: " . formatRupiah($ingredient['total_cost']);
    echo " ({$ingredient['percentage_of_total']}%)\n";
}

echo "Cost per Unit: " . formatRupiah($breakdown['cost_per_unit']);
```

#### suggestSellingPrice()
Hitung suggested selling price berdasarkan margin percentage.

**Formula:**
```php
$price = $cost / (1 - $marginPercentage / 100)
```

**Example:**
```php
// Cost per unit: 20,000
// Margin yang diinginkan: 30%

$suggestedPrice = $recipeCostService->suggestSellingPrice($recipe, 30);
// Returns: 28,571.43

// Calculation:
// 20,000 / (1 - 0.3) = 20,000 / 0.7 = 28,571.43

// Profit: 28,571.43 - 20,000 = 8,571.43 (30% dari selling price)
```

#### calculateFoodCostPercentage()
Hitung food cost percentage berdasarkan selling price.

**Formula:**
```php
$foodCostPct = ($cost / $sellingPrice) * 100
```

**Industry Standards:**
- **Ideal:** 25-35%
- **Good:** 28-32%
- **Warning:** >35% (profit tipis)

**Example:**
```php
$recipe = Recipe::find($recipeId);
$foodCostPct = $recipeCostService->calculateFoodCostPercentage($recipe, 50000);
// Returns: 40% (cost 20k / price 50k)

if ($foodCostPct > 35) {
    // Warning: food cost terlalu tinggi
}
```

#### getRecipesByIngredient()
Cari semua recipes yang menggunakan ingredient tertentu.

**Use Case:**
- Supplier price change → impact analysis
- Ingredient expired → cari recipes yang terpengaruh

**Example:**
```php
// Cari semua recipes yang pakai tepung
$recipes = $recipeCostService->getRecipesByIngredient($tenantId, $tepungId);

foreach ($recipes as $recipe) {
    echo "Recipe: {$recipe->name}";
    echo "Old cost: {$recipe->estimated_cost}";

    // Update recipe cost setelah harga tepung berubah
    $recipeCostService->updateRecipeCost($recipe);

    echo "New cost: {$recipe->estimated_cost}";
}
```

---

## 3. PurchaseOrderService ⭐⭐⭐

**File:** `app/Services/Inventory/PurchaseOrderService.php`

Service untuk menghandle purchase order workflow dari creation sampai goods receive.

### Methods Overview

| Method | Purpose | Returns |
|--------|---------|---------|
| `createPurchaseOrder()` | Buat PO baru | PurchaseOrder |
| `approvePurchaseOrder()` | Approve PO | PurchaseOrder |
| `cancelPurchaseOrder()` | Cancel PO | PurchaseOrder |
| `createGoodsReceive()` | Buat Goods Receive | GoodsReceive |
| `completeGoodsReceive()` | Complete GR → Update stock | GoodsReceive |
| `updateReceivedQuantities()` | Update PO received qty | void |
| `updatePoStatus()` | Update PO status | void |

### Complete PO Workflow

```php
$poService = app(PurchaseOrderService::class);

// 1. Create PO
$po = $poService->createPurchaseOrder(
    $tenant->id,
    $supplier->id,
    $outlet->id,
    auth()->id(),
    [
        [
            'inventory_item_id' => $item1->id,
            'quantity' => 100,
            'unit_price' => 15000,
            'notes' => 'Premium quality',
        ],
        [
            'inventory_item_id' => $item2->id,
            'quantity' => 50,
            'unit_price' => 25000,
        ],
    ],
    now()->addDays(7)->toDateString(),
    'Urgent order'
);

// PO Number: PO202501280001 (auto-generated)
// Status: draft

// 2. Approve PO
$po = $poService->approvePurchaseOrder($po, auth()->id());
// Status: draft → approved

// 3. Create Goods Receive (saat barang datang)
$gr = $poService->createGoodsReceive(
    $po,
    auth()->id(),
    [
        [
            'purchase_order_item_id' => $poItem1->id,
            'quantity_received' => 95,  // Partial receive
            'batch_number' => 'BATCH-2025-01-28',
            'expiry_date' => '2026-01-28',
        ],
    ],
    'INV-2025-00123',
    now()->toDateString(),
    'Received in good condition'
);

// GR Number: GR202501280001 (auto-generated)
// Status: draft

// 4. Complete Goods Receive → Update Stock
$gr = $poService->completeGoodsReceive($gr, auth()->id());

// What happens:
// ✅ Update InventoryStock quantity & WAC
// ✅ Create StockBatch (jika ada batch/expiry)
// ✅ Create StockMovement (TYPE_IN)
// ✅ Update PO status (approved → partially_received / received)
// ✅ Update PO received quantities
```

### Key Method Details

#### completeGoodsReceive()
Ini adalah method terpenting yang menghubungkan PO dengan stock management.

**What happens when called:**
1. Load GoodsReceive dengan items
2. Loop setiap GR item:
   - Update/create InventoryStock
   - Calculate new WAC
   - Create StockBatch (jika ada batch/expiry)
   - Create StockMovement (audit trail)
3. Update GR status: draft → completed
4. Update PO received quantities
5. Update PO status berdasarkan received qty

**Example:**
```php
$gr = $poService->completeGoodsReceive($goodsReceive, auth()->id());

// Setelah ini:
// - Stock bertambah
// - Batch tercatat (FEFO ready)
// - Movement tercatat untuk audit trail
// - PO status updated
```

---

## 4. StockTransferService ⭐⭐

**File:** `app/Services/Inventory/StockTransferService.php`

Service untuk transfer stock antar outlet dengan reservation system.

### Methods Overview

| Method | Purpose | Returns |
|--------|---------|---------|
| `createTransfer()` | Buat transfer request | StockTransfer |
| `approveTransfer()` | Approve → Reserve stock | StockTransfer |
| `receiveTransfer()` | Receive → Move stock | StockTransfer |
| `cancelTransfer()` | Cancel → Release reserved | StockTransfer |

### Complete Transfer Workflow

```php
$transferService = app(StockTransferService::class);

// 1. Create Transfer
$transfer = $transferService->createTransfer(
    $tenant->id,
    $outletA->id,  // Source
    $outletB->id,  // Destination
    auth()->id(),
    [
        [
            'inventory_item_id' => $item->id,
            'quantity' => 50,
            'notes' => 'Emergency stock',
        ],
    ],
    now()->toDateString(),
    'Urgent transfer needed'
);

// Transfer Number: TRF202501280001
// Status: draft

// 2. Approve Transfer → Reserve Stock di Source
try {
    $transfer = $transferService->approveTransfer($transfer, auth()->id());
    // Status: draft → approved
    // Stock di source: reserved_qty += 50
} catch (\Exception $e) {
    echo $e->getMessage();
    // "Insufficient stock for item: Flour. Available: 30, Requested: 50"
}

// 3. [Optional] Update status jika dalam pengiriman
$transfer->update(['status' => 'in_transit']);

// 4. Receive Transfer di Destination
$transfer = $transferService->receiveTransfer($transfer, auth()->id());

// What happens:
// ✅ Source outlet: quantity -= 50, reserved_qty -= 50
// ✅ Destination outlet: quantity += 50
// ✅ Create movements: TYPE_TRANSFER_OUT & TYPE_TRANSFER_IN
// ✅ Update destination WAC
// ✅ Status: approved → received

// 5. [Alternative] Cancel Transfer
$transfer = $transferService->cancelTransfer($transfer, auth()->id());

// Status: → cancelled
// Reserved stock released back ke available
```

### Key Method Details

#### approveTransfer()
Validate dan reserve stock di source outlet.

**Logic:**
1. Validate sufficient stock di source untuk SEMUA items
2. Reserve stock (increment `reserved_qty`)
3. Store cost price di transfer items
4. Update status: draft → approved

**Example:**
```php
try {
    $transfer = $transferService->approveTransfer($transfer, auth()->id());
    // Stock sekarang reserved, tidak bisa dijual di source
} catch (\Exception $e) {
    // Insufficient stock - transfer tidak bisa diproses
}
```

#### receiveTransfer()
Pindahkan stock dari source ke destination.

**Logic:**
1. Deduct dari source outlet
2. Release reservation
3. Add ke destination outlet
4. Create movements (out & in)
5. Update destination WAC
6. Update status: approved/in_transit → received

**Example:**
```php
$transfer = $transferService->receiveTransfer($transfer, auth()->id());

// Source: -50 units
// Destination: +50 units
// Both have audit trail movements
```

---

## 5. StockAdjustmentService ⭐⭐

**File:** `app/Services/Inventory/StockAdjustmentService.php`

Service untuk stock adjustment (stock opname/correction).

### Methods Overview

| Method | Purpose | Returns |
|--------|---------|---------|
| `createAdjustment()` | Buat adjustment draft | StockAdjustment |
| `approveAdjustment()` | Approve → Update stock | StockAdjustment |
| `rejectAdjustment()` | Reject (tanpa update) | StockAdjustment |
| `cancelAdjustment()` | Cancel draft | StockAdjustment |

### Complete Adjustment Workflow

```php
$adjService = app(StockAdjustmentService::class);

// 1. Create Adjustment (Stock Opname)
$adjustment = $adjService->createAdjustment(
    $tenant->id,
    $outlet->id,
    auth()->id(),
    StockAdjustment::TYPE_STOCK_TAKE,
    [
        [
            'inventory_item_id' => $item1->id,
            'system_quantity' => 100,  // Menurut sistem
            'actual_quantity' => 95,   // Hasil hitung
            'notes' => 'Found 5 expired items',
        ],
        [
            'inventory_item_id' => $item2->id,
            'system_quantity' => 50,
            'actual_quantity' => 53,   // Selisih +3
            'notes' => 'Found extra items',
        ],
    ],
    now()->toDateString(),
    'Monthly stock take',
    'January 2025 stock opname'
);

// Adjustment Number: ADJ202501280001 (auto-generated)
// Auto-calculate variance:
// Item 1: 95 - 100 = -5
// Item 2: 53 - 50 = +3
// Status: draft

// 2. Approve Adjustment → Update Stock
$adjustment = $adjService->approveAdjustment($adjustment, auth()->id());

// What happens:
// ✅ Item 1: Stock -5 (100 → 95)
// ✅ Item 2: Stock +3 (50 → 53)
// ✅ Create StockMovement untuk tiap item (TYPE_ADJUSTMENT)
// ✅ Status: draft → approved

// 3. [Alternative] Reject Adjustment
$adjustment = $adjService->rejectAdjustment($adjustment);
// Status: → rejected
// Stock TIDAK diubah

// 4. [Alternative] Cancel Draft
$adjustment = $adjService->cancelAdjustment($adjustment);
// Status: → cancelled
```

---

## Service Integration Examples

### Example 1: Purchase to Stock Flow

```php
$poService = app(PurchaseOrderService::class);

// Step 1: Create & Approve PO
$po = $poService->createPurchaseOrder($tenantId, $supplierId, $outletId, $userId, $items);
$po = $poService->approvePurchaseOrder($po, $userId);

// Step 2: Goods Receive
$gr = $poService->createGoodsReceive($po, $userId, $receiveItems, $invoiceNo);

// Step 3: Complete → Update Stock (internal calls StockService logic)
$gr = $poService->completeGoodsReceive($gr, $userId);

// Result:
// - InventoryStock updated
// - StockBatch created (FEFO ready)
// - StockMovement created (audit trail)
// - PO received quantities updated
// - PO status updated
```

### Example 2: Transfer Flow

```php
$transferService = app(StockTransferService::class);

// Create & Approve
$transfer = $transferService->createTransfer($tenantId, $fromOutlet, $toOutlet, $userId, $items);
$transfer = $transferService->approveTransfer($transfer, $userId);
// Stock di source sekarang reserved

// Ship to destination
$transfer->update(['status' => 'in_transit']);

// Receive at destination
$transfer = $transferService->receiveTransfer($transfer, $userId);
// Stock berpindah source → destination
```

### Example 3: Stock Opname

```php
$adjService = app(StockAdjustmentService::class);

// Create adjustment
$adjustment = $adjService->createAdjustment($tenantId, $outletId, $userId, $type, $items);

// Approve → Update stock
$adjustment = $adjService->approveAdjustment($adjustment, $userId);
// Stock updated sesuai actual quantities
```

---

## Common Patterns

### 1. Always Use Transactions

Semua write operations wrapped dalam `DB::transaction()`:

```php
return DB::transaction(function () use (...) {
    // Multiple operations
    // If anything fails, everything rolls back
});
```

**Benefits:**
- ✅ Data consistency
- ✅ Automatic rollback on error
- ✅ All-or-nothing operations

### 2. Always Create StockMovements

Setiap stock change SELALU dicatat:

```php
StockMovement::create([
    'type' => StockMovement::TYPE_XXX,
    'quantity' => $quantity,
    'stock_before' => $stockBefore,
    'stock_after' => $stockAfter,
    'reference_type' => Model::class,
    'reference_id' => $model->id,
    'created_by' => $userId,
]);
```

**Benefits:**
- ✅ Complete audit trail
- ✅ Track stock flow
- ✅ Error investigation

### 3. Use Weighted Average Cost (WAC)

Untuk stock IN, hitung WAC:

```php
$currentValue = $stock->quantity * $stock->avg_cost;
$newValue = $quantity * $unitCost;
$newAvgCost = ($currentValue + $newValue) / ($stock->quantity + $quantity);
```

**Benefits:**
- ✅ Accurate cost valuation
- ✅ Smooth cost changes
- ✅ Industry standard

### 4. Reserve Stock for Future Use

```php
// Reserve
$stockService->reserveStock($outletId, $itemId, $quantity);

// Issue (akan deduct dari reserved)
$stockService->issueStock($outletId, $itemId, $quantity, ...);

// Atau release kalau batal
$stockService->releaseReservation($outletId, $itemId, $quantity);
```

**Benefits:**
- ✅ Prevent overselling
- ✅ Hold stock for orders
- ✅ Track commitments

---

## Best Practices

### 1. Error Handling

Throw exceptions dengan pesan jelas:

```php
if ($availableQty < $quantity) {
    throw new \Exception("Insufficient stock. Available: {$availableQty}, Requested: {$quantity}");
}
```

### 2. Validation

Validate sebelum update:

```php
// Check status
if ($adjustment->status !== 'draft') {
    throw new \Exception('Only draft adjustments can be approved.');
}

// Check stock availability
if ($availableQty < $quantity) {
    throw new \Exception("Insufficient stock");
}
```

### 3. Auto-Generated Numbers

Gunakan format: `PREFIX + DATE + SEQUENCE`

```
PO202501280001  = PO #1 on 2025-01-28
GR202501280003  = GR #3 on 2025-01-28
TRF202501280002 = Transfer #2 on 2025-01-28
ADJ202501280001 = Adjustment #1 on 2025-01-28
```

---

## Next Steps

Lanjut ke dokumentasi berikutnya:
- [Phase 2: Controllers](./phase2-controllers.md) - REST API endpoints
- [Phase 2: Form Requests](./phase2-requests.md) - Validation rules

Kembali ke:
- [Phase 2: Models](./phase2-models-1-master-data.md)
- [Phase 2: Migrations](./phase2-migrations.md)
