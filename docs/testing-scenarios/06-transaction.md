# 06 - Transaction & Checkout Testing

## Basic Checkout

### TXN-001: Simple checkout - cash payment
- **Setup**: Open session, add items to cart
- **Action**: POST `/api/v2/orders/checkout`
- **Input**:
  ```json
  {
    "items": [
      {"product_id": "uuid", "quantity": 2, "price": 25000}
    ],
    "payments": [
      {"method": "cash", "amount": 50000}
    ]
  }
  ```
- **Expected**:
  - Transaction created
  - Transaction number format: `{OUTLET_CODE}-{YYYYMMDD}-{SEQ}`
  - Change calculated
  - Cash drawer updated
- **Test File**: `tests/Feature/Api/V1/TransactionCheckoutTest.php::test_can_checkout_with_cash`

### TXN-002: Checkout with exact payment
- **Setup**: Total = 50000
- **Input**: Cash payment = 50000
- **Expected**: Change = 0

### TXN-003: Checkout with card payment
- **Input**: Payment method = card
- **Expected**: Transaction recorded, no cash involved
- **Test File**: `tests/Feature/Api/V1/TransactionCheckoutTest.php::test_can_checkout_with_card`

### TXN-004: Checkout with multiple payment methods
- **Input**: Cash 30000 + Card 20000 = Total 50000
- **Expected**: Both payments recorded
- **Test File**: `tests/Feature/Api/V2/OrderPayTest.php::test_checkout_split_payment`

### TXN-005: Checkout fails with insufficient payment
- **Input**: Total = 50000, Payment = 40000
- **Expected**: Validation error

---

## Discount Application

### DISC-001: Apply item discount
- **Setup**: Item with price 25000
- **Input**: Discount 10%
- **Expected**: Final price = 22500

### DISC-002: Apply order discount
- **Input**: Order discount 5000
- **Expected**: Total reduced by 5000

### DISC-003: Discount with minimum purchase
- **Setup**: Discount requires min purchase 50000
- **Input**: Order total = 30000
- **Expected**: Discount not applied (min not met)

### DISC-004: Discount code
- **Input**: Discount code "DISKON10"
- **Expected**: 10% off applied

### DISC-005: Invalid discount code
- **Input**: Discount code "INVALID"
- **Expected**: Error "Invalid discount code"

### DISC-006: Discount priority/order
- **Expected**: Item discount applied before order discount

---

## Tax & Service Charge

### TAX-001: Checkout with tax (exclusive)
- **Setup**: Tax mode = exclusive, tax = 10%
- **Input**: Subtotal = 100000
- **Expected**:
  - Tax = 10000
  - Grand total = 110000
- **Test File**: `tests/Feature/Api/V2/OrderTaxModeTest.php::test_checkout_tax_exclusive`

### TAX-002: Checkout with tax (inclusive)
- **Setup**: Tax mode = inclusive, tax = 10%
- **Input**: Grand total displayed = 100000
- **Expected**:
  - Tax portion extracted
  - Actual subtotal = 90909
- **Test File**: `tests/Feature/Api/V2/OrderTaxModeTest.php::test_checkout_tax_inclusive`

### TAX-003: Checkout with service charge
- **Setup**: Service charge = 5%
- **Expected**: Service charge added to total

### TAX-004: Tax + Service charge combined
- **Expected**: Both applied correctly

### TAX-005: Zero-rated tax (tax-exempt items)
- **Setup**: Item marked tax-exempt
- **Expected**: Tax not applied to item

---

## Order Void & Refund

### VOID-001: Void entire transaction
- **Setup**: Create transaction
- **Action**: POST `/api/v2/orders/{id}/void`
- **Input**: `{"reason": "Wrong order"}`
- **Expected**:
  - Transaction status = 'voided'
  - Stock reverted (if inventory enabled)
  - Audit log created
- **Test File**: `tests/Feature/Api/V1/TransactionVoidRefundTest.php::test_can_void_transaction`

### VOID-002: Void requires authorization
- **Expected**: PIN or manager approval required

### VOID-003: Void not allowed after X minutes
- **Setup**: Transaction older than limit
- **Expected**: Void not allowed

### REFUND-001: Refund item
- **Setup**: Create transaction
- **Action**: POST `/api/v2/orders/{id}/refund`
- **Input**: `{"item_id": "uuid", "quantity": 1, "reason": "Wrong item"}`
- **Expected**:
  - Item refunded
  - Money returned
  - Transaction updated
- **Test File**: `tests/Feature/Api/V1/TransactionVoidRefundTest.php::test_can_refund_item`

### REFUND-002: Partial refund
- **Setup**: Transaction with 3 items
- **Input**: Refund 1 item
- **Expected**: Only 1 item refunded

### REFUND-003: Full refund
- **Input**: Refund all items
- **Expected**: Full amount returned

---

## Transaction Receipt

### RECEIPT-001: Generate receipt
- **Setup**: Complete transaction
- **Expected**: Receipt number generated

### RECEIPT-002: Print receipt
- **Action**: Print receipt
- **Expected**: Receipt prints correctly

### RECEIPT-003: Email receipt
- **Input**: customer_email
- **Expected**: Receipt emailed

### RECEIPT-004: Receipt contains all required info
- **Expected**:
  - Transaction number
  - Date/time
  - Items with prices
  - Tax breakdown
  - Payment method
  - Change (if cash)

---

## Transaction History

### HISTORY-001: View transaction list
- **URL**: `/transactions`
- **Expected**: Lists all transactions for outlet

### HISTORY-002: Filter transactions by date
- **Input**: Start date, end date
- **Expected**: Filtered transaction list

### HISTORY-003: View transaction details
- **URL**: `/transactions/{id}`
- **Expected**: Full transaction details shown

### HISTORY-004: Search transaction by number
- **Input**: Transaction number
- **Expected**: Transaction found

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V1/TransactionCheckoutTest.php` | All checkout tests |
| `tests/Feature/Api/V1/TransactionVoidRefundTest.php` | Void and refund tests |
| `tests/Feature/Api/V2/OrderApiTest.php` | Order API tests |
| `tests/Feature/Api/V2/OrderPayTest.php` | Payment tests |
| `tests/Feature/Api/V2/OrderTaxModeTest.php` | Tax mode tests |
| `tests/Feature/TaxInclusiveTest.php` | Tax inclusive tests |
