# 10 - KDS & Waiter System Testing

> **Note**: KDS and Waiter features require Professional+ subscription

## Kitchen Display System (KDS)

### KDS-001: View KDS dashboard
- **URL**: `/kds`
- **Expected**: Shows pending orders
- **Test File**: `tests/Feature/Api/V2/KDSApiTest.php::test_kds_dashboard_loads`

### KDS-002: Orders appear in KDS
- **Setup**: Create new order
- **Expected**: Order appears in KDS queue

### KDS-003: Mark item as in-progress
- **Setup**: Order in KDS
- **Action**: Mark item as "cooking"
- **Expected**: Item status updated
- **Test File**: `tests/Feature/Api/V2/KDSApiTest.php::test_can_update_item_status`

### KDS-004: Mark item as completed
- **Setup**: Item in-progress
- **Action**: Mark item as "done"
- **Expected**: Item status = completed

### KDS-005: Order completed notification
- **Setup**: All items completed
- **Expected**: Notification sent to waiter/POS

### KDS-006: Bump order from KDS
- **Action**: Bump completed order
- **Expected**: Order removed from KDS

### KDS-007: KDS by station
- **Setup**: Multiple stations (grill, fryer, etc.)
- **Expected**: Each station sees only relevant items

### KDS-008: KDS order timer
- **Setup**: Order waiting
- **Expected**: Timer shows waiting duration

### KDS-009: Rush order priority
- **Setup**: Mark order as rush
- **Expected**: Order shown with priority indicator

### KDS-010: KDS sound notification
- **Expected**: Sound plays for new orders

---

## Waiter System

### WAIT-001: Waiter app login
- **URL**: `/waiter`
- **Expected**: Waiter interface loads

### WAIT-002: View assigned tables
- **Expected**: Shows tables assigned to waiter

### WAIT-003: Take order from table
- **Action**: Select table, add items
- **Expected**: Order created, sent to kitchen
- **Test File**: `tests/Feature/Api/V2/WaiterApiTest.php::test_can_take_order`

### WAIT-004: Update order from table
- **Setup**: Existing order
- **Action**: Add/remove items
- **Expected**: Order updated

### WAIT-005: View order status
- **Expected**: Shows current status (pending/cooking/done)

### WAIT-006: Notify waiter when order ready
- **Setup**: Kitchen completes order
- **Expected**: Waiter notified

### WAIT-007: Transfer table to another waiter
- **Action**: Transfer table assignment
- **Expected**: Other waiter can now see table

### WAIT-008: Split order by seat
- **Setup**: Group order
- **Action**: Mark items per seat
- **Expected**: Items grouped by seat

---

## Kitchen Order Processing

### KITCH-001: New order sent to kitchen
- **Setup**: Create order via POS/Waiter
- **Expected**: Kitchen sees new order

### KITCH-002: Order routing to correct station
- **Setup**: Item belongs to station "Grill"
- **Expected**: Sent to Grill station only

### KITCH-003: Kitchen station management
- **Setup**: Create/edit stations
- **Expected**: Stations configured correctly

### KITCH-004: Item-level cooking status
- **Setup**: Order with multiple items
- **Expected**: Each item tracked separately

### KITCH-005: Kitchen completion triggers serving
- **Setup**: All items done
- **Expected**: Order ready for serving

---

## Kitchen Order API

### KITCH-API-001: Get pending kitchen orders
- **URL**: `/api/v2/kitchen/orders`
- **Expected**: Returns pending orders
- **Test File**: `tests/Feature/Api/V2/KDSApiTest.php::test_can_get_pending_orders`

### KITCH-API-002: Update kitchen order item status
- **Action**: PUT `/api/v2/kitchen/orders/{id}/items/{item_id}`
- **Input**: `{"status": "cooking"}`
- **Expected**: Status updated
- **Test File**: `tests/Feature/Api/V2/KDSApiTest.php::test_can_update_item_status`

### KITCH-API-003: Complete kitchen order
- **Action**: PUT `/api/v2/kitchen/orders/{id}/complete`
- **Expected**: All items completed

---

## Integration Tests

### INT-001: POS -> Kitchen -> Waiter -> Served flow
- **Flow**:
  1. Waiter takes order → Sent to kitchen
  2. Kitchen cooks → Marks items done
  3. Waiter notified → Serves table
- **Test File**: `tests/Feature/Api/V1/KitchenOrderIntegrationTest.php::test_full_kitchen_flow`

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V2/KDSApiTest.php` | KDS API tests |
| `tests/Feature/Api/V2/WaiterApiTest.php` | Waiter API tests |
| `tests/Feature/Api/V1/KitchenOrderIntegrationTest.php` | Kitchen integration tests |
