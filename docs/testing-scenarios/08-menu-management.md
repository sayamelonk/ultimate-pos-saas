# 08 - Menu & Product Management Testing

## Product Categories

### CAT-001: Create category
- **Action**: POST `/api/v1/categories`
- **Input**: `{"name": "Makanan", "description": "Makanan berat"}`
- **Expected**: Category created
- **Test File**: `tests/Feature/Api/V1/CategoryApiTest.php::test_can_create_category`

### CAT-002: List categories
- **URL**: `/api/v1/categories`
- **Expected**: Lists all categories

### CAT-003: Edit category
- **Action**: PUT `/api/v1/categories/{id}`
- **Expected**: Category updated

### CAT-004: Delete category
- **Action**: DELETE `/api/v1/categories/{id}`
- **Expected**: Category deleted (if no products)

### CAT-005: Category with products cannot be deleted
- **Setup**: Category has products
- **Action**: Delete category
- **Expected**: Error, must move/delete products first

---

## Products

### PROD-001: Create product
- **Action**: POST `/api/v1/products`
- **Input**:
  ```json
  {
    "name": "Nasi Goreng",
    "category_id": "uuid",
    "prices": [
      {"outlet_id": "uuid", "price": 25000}
    ],
    "is_active": true
  }
  ```
- **Expected**: Product created
- **Test File**: `tests/Feature/Api/V1/ProductApiTest.php::test_can_create_product`

### PROD-002: Create product with variants
- **Setup**: Create variant group first
- **Input**: Product with variant options
- **Expected**: Product with variant prices

### PROD-003: Create product with modifiers
- **Setup**: Create modifier groups
- **Input**: Product with modifier groups
- **Expected**: Product with modifier options

### PROD-004: Create combo product
- **Input**: Combo with multiple items
- **Expected**: Combo product created with items

### PROD-005: Edit product
- **Action**: PUT `/api/v1/products/{id}`
- **Expected**: Product updated

### PROD-006: Toggle product active/inactive
- **Action**: Toggle is_active
- **Expected**: Product shown/hidden in POS

### PROD-007: Delete product
- **Action**: DELETE `/api/v1/products/{id}`
- **Expected**: Product deleted

### PROD-008: Product image upload
- **Action**: Upload product image
- **Expected**: Image saved, displayed in POS

---

## Product Variants

### VAR-001: Create variant group
- **Action**: POST `/api/v1/variant-groups`
- **Input**: `{"name": "Ukuran", "options": ["S", "M", "L"]}`
- **Expected**: Variant group with options created

### VAR-002: Assign variants to product
- **Setup**: Product + Variant group
- **Action**: Assign to product
- **Expected**: Product has variant prices

### VAR-003: Different prices per variant
- **Setup**: Variant "Small" price 20000, "Large" price 35000
- **Expected**: POS shows different prices

---

## Modifiers & Add-ons

### MOD-001: Create modifier group
- **Action**: POST `/api/v1/modifier-groups`
- **Input**:
  ```json
  {
    "name": "Level Pedas",
    "modifiers": [
      {"name": "Tidak Pedas", "price_adjustment": 0},
      {"name": "Sedang", "price_adjustment": 0},
      {"name": "Pedas", "price_adjustment": 1000}
    ]
  }
  ```
- **Expected**: Modifier group created

### MOD-002: Assign modifiers to product
- **Setup**: Product + Modifier group
- **Action**: Assign to product
- **Expected**: POS shows modifier selection

### MOD-003: Modifier price adjustment
- **Setup**: Modifier with price_adjustment
- **Action**: Select modifier
- **Expected**: Price adjusted accordingly

---

## Product Pricing

### PRICE-001: Set outlet-specific pricing
- **Setup**: Multiple outlets
- **Input**: Different prices per outlet
- **Expected**: POS uses outlet-specific price

### PRICE-002: Bulk price edit
- **Action**: Bulk update prices
- **Expected**: All selected products updated

### PRICE-003: Price history
- **Expected**: Track price changes

---

## Sync to Mobile App

### SYNC-001: Sync categories to mobile
- **URL**: `/api/v1/mobile/sync`
- **Expected**: Categories synced

### SYNC-002: Sync products to mobile
- **Expected**: Products synced with prices
- **Test File**: `tests/Feature/Api/V1/MobileSyncMasterTest.php::test_mobile_sync_returns_products`

### SYNC-003: Sync only active products
- **Setup**: Inactive product
- **Expected**: Not synced to mobile

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V1/CategoryApiTest.php` | Category tests |
| `tests/Feature/Api/V1/ProductApiTest.php` | Product tests |
| `tests/Feature/Api/V1/MobileSyncMasterTest.php` | Mobile sync tests |
