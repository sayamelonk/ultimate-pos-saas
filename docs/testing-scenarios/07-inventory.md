# 07 - Inventory Management Testing

> **Note**: Inventory features availability depends on subscription tier:
> - Growth+: Basic Inventory
> - Professional+: Advanced Inventory (Recipe, BOM)

## Inventory Items

### INV-001: Create inventory item
- **Action**: POST `/api/v2/inventory/items`
- **Input**:
  ```json
  {
    "name": "Beras",
    "sku": "BERAS-001",
    "category_id": "uuid",
    "unit_id": "uuid",
    "initial_stock": 100,
    "min_stock": 20
  }
  ```
- **Expected**: Item created with stock
- **Test File**: `tests/Feature/Api/V2/InventoryApiTest.php::test_can_create_inventory_item`

### INV-002: View inventory items
- **URL**: `/inventory/items`
- **Expected**: Lists all inventory items with stock

### INV-003: Edit inventory item
- **Action**: PUT `/api/v2/inventory/items/{id}`
- **Expected**: Item updated

### INV-004: Delete inventory item
- **Action**: DELETE `/api/v2/inventory/items/{id}`
- **Expected**: Item deleted (if not used)

### INV-005: Low stock alert
- **Setup**: Item stock below min_stock
- **Expected**: Alert/warning shown

---

## Stock Management

### STOCK-001: Add stock (goods receive)
- **Action**: POST `/api/v2/inventory/receive`
- **Input**:
  ```json
  {
    "item_id": "uuid",
    "quantity": 50,
    "cost": 15000,
    "supplier_id": "uuid",
    "expiry_date": "2026-12-31"
  }
  ```
- **Expected**: Stock increased, movement recorded

### STOCK-002: View stock by outlet
- **Expected**: Shows stock for specific outlet

### STOCK-003: Stock transfer between outlets
- **Action**: POST `/api/v2/inventory/transfer`
- **Input**:
  ```json
  {
    "item_id": "uuid",
    "from_outlet_id": "uuid",
    "to_outlet_id": "uuid",
    "quantity": 10
  }
  ```
- **Expected**: Stock reduced from source, added to destination

### STOCK-004: Stock adjustment
- **Action**: POST `/api/v2/inventory/adjustment`
- **Input**:
  ```json
  {
    "item_id": "uuid",
    "adjustment": 5,
    "reason": "Stock count correction"
  }
  ```
- **Expected**: Stock adjusted, reason logged

### STOCK-005: Stock take (physical inventory)
- **Action**: POST `/api/v2/inventory/stock-take`
- **Input**: List of items with physical counts
- **Expected**: Adjustments created

---

## Stock Batch & Expiry

### BATCH-001: Track stock batches
- **Setup**: Add stock with batch info
- **Expected**: Stock tracked by batch, expiry date

### BATCH-002: FIFO stock usage
- **Setup**: Multiple batches, different expiry
- **Action**: Use stock
- **Expected**: Oldest batch used first

### BATCH-003: Expiring stock report
- **URL**: `/inventory/reports/expiring`
- **Setup**: Stock expiring within 30 days
- **Expected**: Report shows expiring items

### BATCH-004: Expired stock handling
- **Setup**: Stock past expiry
- **Expected**: Marked as expired, cannot be used

---

## Recipe & Bill of Materials

### RECIPE-001: Create recipe
- **Action**: POST `/api/v2/inventory/recipes`
- **Input**:
  ```json
  {
    "product_id": "uuid",
    "items": [
      {"inventory_item_id": "uuid", "quantity": 0.5}
    ],
    "yield": 1
  }
  ```
- **Expected**: Recipe created

### RECIPE-002: Calculate recipe cost
- **Setup**: Create recipe with items
- **Expected**: Shows ingredient cost per product

### RECIPE-003: Auto-deduct inventory on sale
- **Setup**: Product with recipe
- **Action**: Sell product
- **Expected**: Inventory auto-deducted based on recipe

### RECIPE-004: Recipe cost analysis report
- **URL**: `/inventory/recipes/cost-analysis`
- **Expected**: Shows cost per product

---

## Suppliers

### SUPP-001: Create supplier
- **Action**: POST `/api/v2/inventory/suppliers`
- **Input**:
  ```json
  {
    "name": "PT Supplier Jaya",
    "email": "supplier@example.com",
    "phone": "021123456",
    "address": "Jakarta"
  }
  ```
- **Expected**: Supplier created

### SUPP-002: Link supplier to items
- **Expected**: Can set default supplier for items

### SUPP-003: Purchase order to supplier
- **Action**: Create PO
- **Expected**: PO created with items and quantities

---

## Inventory Reports

### REPORT-001: Stock valuation report
- **URL**: `/inventory/reports/valuation`
- **Expected**: Shows current stock value

### REPORT-002: Stock movement report
- **URL**: `/inventory/reports/movements`
- **Expected**: Shows all stock changes (in/out/adjust)

### REPORT-003: COGS report
- **URL**: `/inventory/reports/cogs`
- **Expected**: Cost of goods sold

### REPORT-004: Food cost percentage
- **URL**: `/inventory/reports/food-cost`
- **Expected**: Food cost as percentage of revenue

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V2/InventoryApiTest.php` | All inventory tests |
| `tests/Unit/Services/StockServiceTest.php` | Stock service tests |
