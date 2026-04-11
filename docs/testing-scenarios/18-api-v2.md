# 18 - API V2 Endpoints Testing

## Base URL
```
/api/v2/
```

## Orders

### POST /orders/checkout
- **Description**: Process checkout
- **Test File**: `tests/Feature/Api/V2/OrderApiTest.php::test_can_checkout`
- **Expected**: 201 Created, transaction

### POST /orders/{id}/pay
- **Description**: Pay order
- **Test File**: `tests/Feature/Api/V2/OrderPayTest.php::test_can_pay_order`
- **Expected**: 200 OK

### POST /orders/{id}/void
- **Description**: Void order
- **Test File**: `tests/Feature/Api/V2/OrderApiTest.php::test_can_void_order`
- **Expected**: 200 OK

### GET /orders
- **Description**: List orders
- **Test File**: `tests/Feature/Api/V2/OrderApiTest.php::test_can_get_orders`
- **Expected**: 200 OK

### GET /orders/{id}
- **Description**: Get order details
- **Expected**: 200 OK

---

## Sessions

### GET /sessions
- **Description**: List sessions
- **Test File**: `tests/Feature/Api/V2/SessionApiTest.php::test_can_get_sessions`
- **Expected**: 200 OK

### POST /sessions/open
- **Description**: Open session
- **Test File**: `tests/Feature/Api/V2/SessionApiTest.php::test_can_open_session`
- **Expected**: 201 Created

### POST /sessions/{id}/close
- **Description**: Close session
- **Test File**: `tests/Feature/Api/V2/SessionApiTest.php::test_can_close_session`
- **Expected**: 200 OK

---

## Cash Drawer

### GET /cash-drawer
- **Description**: Get current drawer balance
- **Test File**: `tests/Feature/Api/V2/CashDrawerApiTest.php::test_can_get_cash_drawer`
- **Expected**: 200 OK

### POST /cash-drawer/cash-in
- **Description**: Add cash to drawer
- **Expected**: 200 OK

### POST /cash-drawer/cash-out
- **Description**: Remove cash from drawer
- **Expected**: 200 OK

### GET /cash-drawer/logs
- **Description**: Get drawer transaction logs
- **Test File**: `tests/Feature/Api/V2/CashDrawerApiTest.php::test_can_get_cash_drawer_logs`
- **Expected**: 200 OK

---

## Inventory

### GET /inventory/items
- **Description**: List inventory items
- **Test File**: `tests/Feature/Api/V2/InventoryApiTest.php::test_can_get_inventory_items`
- **Expected**: 200 OK

### POST /inventory/items
- **Description**: Create inventory item
- **Test File**: `tests/Feature/Api/V2/InventoryApiTest.php::test_can_create_inventory_item`
- **Expected**: 201 Created

### GET /inventory/stocks
- **Description**: Get stock levels
- **Expected**: 200 OK

### POST /inventory/receive
- **Description**: Receive goods
- **Expected**: 201 Created

### POST /inventory/transfer
- **Description**: Transfer stock between outlets
- **Expected**: 201 Created

### POST /inventory/adjustment
- **Description**: Adjust stock
- **Expected**: 201 Created

---

## KDS (Kitchen Display System)

### GET /kitchen/orders
- **Description**: Get pending kitchen orders
- **Test File**: `tests/Feature/Api/V2/KDSApiTest.php::test_can_get_pending_orders`
- **Expected**: 200 OK

### PUT /kitchen/orders/{id}/items/{item_id}
- **Description**: Update item status
- **Test File**: `tests/Feature/Api/V2/KDSApiTest.php::test_can_update_item_status`
- **Expected**: 200 OK

### PUT /kitchen/orders/{id}/complete
- **Description**: Complete order
- **Expected**: 200 OK

---

## Waiter

### GET /waiter/tables
- **Description**: Get assigned tables
- **Test File**: `tests/Feature/Api/V2/WaiterApiTest.php::test_can_get_assigned_tables`
- **Expected**: 200 OK

### POST /waiter/orders
- **Description**: Create order from table
- **Test File**: `tests/Feature/Api/V2/WaiterApiTest.php::test_can_take_order`
- **Expected**: 201 Created

### PUT /waiter/orders/{id}/status
- **Description**: Update order status
- **Expected**: 200 OK

---

## Reports

### GET /reports/sales
- **Description**: Get sales report
- **Test File**: `tests/Feature/Api/V2/ReportsApiTest.php::test_can_get_sales_report`
- **Expected**: 200 OK

### GET /reports/products
- **Description**: Get product sales report
- **Expected**: 200 OK

### GET /reports/transactions
- **Description**: Get transaction report
- **Expected**: 200 OK

---

## Sync

### GET /sync
- **Description**: Full data sync
- **Test File**: `tests/Feature/Api/V2/SyncApiTest.php::test_can_sync`
- **Expected**: 200 OK

### GET /sync/products
- **Description**: Sync products only
- **Test File**: `tests/Feature/Api/V2/SyncProductCompletenessTest.php::test_sync_products_complete`
- **Expected**: 200 OK

---

## Settings

### GET /settings
- **Description**: Get all settings
- **Test File**: `tests/Feature/Api/V2/SettingsApiTest.php::test_can_get_settings`
- **Expected**: 200 OK

### PUT /settings
- **Description**: Update settings
- **Expected**: 200 OK

### GET /settings/tax
- **Description**: Get tax settings
- **Test File**: `tests/Feature/Api/V2/SettingsTaxTest.php::test_can_get_tax_settings`
- **Expected**: 200 OK

### PUT /settings/tax
- **Description**: Update tax settings
- **Test File**: `tests/Feature/Api/V2/SettingsTaxTest.php::test_can_update_tax_settings`
- **Expected**: 200 OK

---

## QR Order

### GET /qr-order/menu/{table_id}
- **Description**: Get QR menu for table
- **Test File**: `tests/Feature/QrOrder/QrOrderMenuTest.php::test_api_returns_menu`
- **Expected**: 200 OK

### POST /qr-order/orders
- **Description**: Place QR order
- **Expected**: 201 Created

### GET /qr-order/orders/{id}
- **Description**: Get QR order status
- **Expected**: 200 OK

---

## API V2 Test Coverage

| Endpoint | Test File |
|----------|-----------|
| POST /orders/checkout | `tests/Feature/Api/V2/OrderApiTest.php` |
| POST /orders/{id}/pay | `tests/Feature/Api/V2/OrderPayTest.php` |
| GET /sessions | `tests/Feature/Api/V2/SessionApiTest.php` |
| POST /sessions/open | `tests/Feature/Api/V2/SessionApiTest.php` |
| POST /sessions/{id}/close | `tests/Feature/Api/V2/SessionApiTest.php` |
| GET /cash-drawer | `tests/Feature/Api/V2/CashDrawerApiTest.php` |
| GET /inventory/items | `tests/Feature/Api/V2/InventoryApiTest.php` |
| POST /inventory/items | `tests/Feature/Api/V2/InventoryApiTest.php` |
| GET /kitchen/orders | `tests/Feature/Api/V2/KDSApiTest.php` |
| PUT /kitchen/orders/{id}/items/{item_id} | `tests/Feature/Api/V2/KDSApiTest.php` |
| GET /waiter/tables | `tests/Feature/Api/V2/WaiterApiTest.php` |
| POST /waiter/orders | `tests/Feature/Api/V2/WaiterApiTest.php` |
| GET /reports/sales | `tests/Feature/Api/V2/ReportsApiTest.php` |
| GET /sync | `tests/Feature/Api/V2/SyncApiTest.php` |
| GET /sync/products | `tests/Feature/Api/V2/SyncProductCompletenessTest.php` |
| GET /settings | `tests/Feature/Api/V2/SettingsApiTest.php` |
| GET /settings/tax | `tests/Feature/Api/V2/SettingsTaxTest.php` |
| PUT /settings/tax | `tests/Feature/Api/V2/SettingsTaxTest.php` |
