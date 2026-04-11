# 09 - Pricing & Discounts Testing

## Payment Methods

### PM-001: Create payment method
- **Action**: POST `/api/v1/payment-methods`
- **Input**: `{"name": "GoPay", "type": "ewallet", "is_active": true}`
- **Expected**: Payment method created

### PM-002: List payment methods
- **Expected**: Shows all active payment methods

### PM-003: Edit payment method
- **Action**: PUT `/api/v1/payment-methods/{id}`
- **Expected**: Updated

### PM-004: Delete payment method
- **Action**: DELETE `/api/v1/payment-methods/{id}`
- **Expected**: Cannot delete if used in transactions

### PM-005: Toggle payment method active/inactive
- **Expected**: Hidden/shown in POS

---

## Discounts

### DISC-001: Create percentage discount
- **Action**: POST `/api/v1/discounts`
- **Input**:
  ```json
  {
    "name": "Diskon 10%",
    "type": "percentage",
    "value": 10,
    "min_purchase": 50000
  }
  ```
- **Expected**: Discount created

### DISC-002: Create fixed amount discount
- **Input**:
  ```json
  {
    "name": "Potongan Rp 5000",
    "type": "fixed",
    "value": 5000,
    "min_purchase": 30000
  }
  ```
- **Expected**: Fixed discount created

### DISC-003: Discount with code
- **Input**: `{"code": "HEMAT5000", ...}`
- **Expected**: Code-based discount

### DISC-004: Discount with date range
- **Input**: `{"start_date": "2026-04-01", "end_date": "2026-04-30"}`
- **Expected**: Only valid within range

### DISC-005: Discount for specific products
- **Input**: `{"product_ids": ["uuid1", "uuid2"]}`
- **Expected**: Only applies to selected products

### DISC-006: Discount for specific categories
- **Input**: `{"category_ids": ["uuid"]}`
- **Expected**: Only applies to category products

### DISC-007: Limit discount usage
- **Input**: `{"max_uses": 100, "max_uses_per_customer": 1}`
- **Expected**: Usage tracked and limited

---

## Price Rules

### PRICE-001: Time-based pricing
- **Input**: `{"time_start": "22:00", "time_end": "23:59", "adjustment": -10}`
- **Expected**: Discount applied during night hours

### PRICE-002: Day-based pricing
- **Input**: `{"days": ["monday", "tuesday"], "adjustment": -5}`
- **Expected**: Discount on specific days

### PRICE-003: Happy hour pricing
- **Input**: Discount during 14:00-17:00
- **Expected**: Applied automatically

---

## Customer Loyalty (Points)

### LOYAL-001: Award points on purchase
- **Setup**: Customer linked to transaction
- **Expected**: Points added based on purchase amount

### LOYAL-002: Redeem points
- **Setup**: Customer has accumulated points
- **Action**: Use points as payment
- **Expected**: Points deducted, value applied

### LOYAL-003: Points expiry
- **Setup**: Points older than 1 year
- **Expected**: Points expired

---

## Existing Test Files

> Most pricing tests are covered through integration tests in Transaction tests.
