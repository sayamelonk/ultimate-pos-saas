# 11 - QR Order System Testing

> **Note**: QR Order feature requires Growth+ subscription

## QR Menu Setup

### QR-001: Generate QR code for table
- **Setup**: Create table
- **Action**: Generate QR code
- **Expected**: QR code generated with unique URL
- **Test File**: `tests/Feature/QrOrder/QrOrderMenuTest.php::test_qr_menu_loads`

### QR-002: QR code contains correct URL
- **Expected**: URL format: `{domain}/qr-order/{tenant_id}/{table_id}`

### QR-003: QR code printable
- **Action**: Print QR code
- **Expected**: QR code printable format

### QR-004: Multiple QR codes per table
- **Setup**: Multiple QR codes for same table
- **Expected**: Each QR code unique

---

## Customer QR Order Flow

### QR-CUST-001: Scan QR code loads menu
- **Action**: Customer scans QR
- **URL**: `/qr-order/{tenant_id}/{table_id}`
- **Expected**: Menu page loads with categories and products
- **Test File**: `tests/Feature/QrOrder/QrOrderMenuTest.php::test_can_view_menu`

### QR-CUST-002: Add item to cart
- **Action**: Select item, add to cart
- **Expected**: Item added, cart updated
- **Test File**: `tests/Feature/QrOrder/QrOrderMenuTest.php::test_can_add_item_to_cart`

### QR-CUST-003: View cart
- **Action**: View cart
- **Expected**: Shows all items, quantities, prices

### QR-CUST-004: Update cart item quantity
- **Action**: Change quantity
- **Expected**: Cart updated, price recalculated

### QR-CUST-005: Remove item from cart
- **Action**: Remove item
- **Expected**: Item removed from cart

### QR-CUST-006: Apply discount code
- **Action**: Enter discount code
- **Expected**: Discount applied if valid

### QR-CUST-007: Place order
- **Action**: Submit order
- **Expected**:
  - Order created
  - Confirmation shown
  - Order sent to kitchen
- **Test File**: `tests/Feature/QrOrder/QrOrderMenuTest.php::test_can_place_order`

### QR-CUST-008: Order status tracking
- **URL**: Order confirmation page
- **Expected**: Real-time order status updates

---

## QR Order Management (Admin/Waiter)

### QR-ADMIN-001: View QR orders list
- **URL**: `/qr-orders`
- **Expected**: Lists all QR orders
- **Test File**: `tests/Feature/QrOrder/QrOrderManagementTest.php::test_can_view_qr_orders`

### QR-ADMIN-002: View QR order details
- **URL**: `/qr-orders/{id}`
- **Expected**: Shows order details and items

### QR-ADMIN-003: Update QR order status
- **Action**: Update status
- **Expected**: Status updated
- **Test File**: `tests/Feature/QrOrder/QrOrderManagementTest.php::test_can_update_order_status`

### QR-ADMIN-004: Print kitchen ticket
- **Action**: Print QR order ticket
- **Expected**: Kitchen ticket printed

### QR-ADMIN-005: Merge QR orders
- **Setup**: Multiple orders for same table
- **Action**: Merge orders
- **Expected**: Orders combined

### QR-ADMIN-006: Cancel QR order
- **Action**: Cancel order
- **Expected**: Order cancelled, notification sent

---

## QR Order Payment

### QR-PAY-001: Pay QR order at counter
- **Setup**: QR order created
- **Action**: Pay at counter
- **Expected**: Order marked paid
- **Test File**: `tests/Feature/QrOrder/QrOrderCheckoutTest.php::test_can_pay_at_counter`

### QR-PAY-002: Pay QR order online
- **Action**: Online payment (Xendit)
- **Expected**: Payment processed

### QR-PAY-003: Split payment for QR order
- **Action**: Partially pay
- **Expected**: Remaining balance tracked

### QR-PAY-004: QR order payment webhook
- **Webhook**: Xendit payment callback
- **Expected**: Order updated to paid
- **Test File**: `tests/Feature/QrOrder/QrOrderWebhookTest.php::test_webhook_processes_payment`

### QR-PAY-005: Payment timeout
- **Setup**: Unpaid order after X minutes
- **Expected**: Order auto-cancelled or reminder sent

---

## QR Order API

### QR-API-001: Get QR menu
- **URL**: `/api/v2/qr-order/menu/{table_id}`
- **Expected**: Returns categories, products, prices
- **Test File**: `tests/Feature/QrOrder/QrOrderMenuTest.php::test_api_returns_menu`

### QR-API-002: Place QR order via API
- **URL**: `/api/v2/qr-order/orders`
- **Action**: POST order
- **Expected**: Order created
- **Test File**: `tests/Feature/QrOrder/QrOrderMenuTest.php::test_api_can_place_order`

### QR-API-003: Get QR order status
- **URL**: `/api/v2/qr-order/orders/{id}`
- **Expected**: Returns order with status

---

## Table Management Integration

### QR-TABLE-001: Link QR to table session
- **Setup**: Customer scans QR
- **Expected**: Table session started

### QR-TABLE-002: View table status
- **Expected**: Shows which tables have active orders

### QR-TABLE-003: Free table after payment
- **Setup**: Order paid
- **Expected**: Table available for next customer

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/QrOrder/QrOrderMenuTest.php` | QR menu tests |
| `tests/Feature/QrOrder/QrOrderManagementTest.php` | QR order management |
| `tests/Feature/QrOrder/QrOrderCheckoutTest.php` | QR checkout tests |
| `tests/Feature/QrOrder/QrOrderWebhookTest.php` | Webhook tests |
