# 17 - API V1 Endpoints Testing

## Base URL
```
/api/v1/
```

## Authentication

### POST /auth/register
- **Description**: Register new user
- **Test File**: `tests/Feature/Api/V1/RegisterTest.php::test_user_can_register`
- **Input**:
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Expected**: 201 Created, user object, token

### POST /auth/login
- **Description**: Login user
- **Input**:
  ```json
  {
    "email": "john@example.com",
    "password": "password123"
  }
  ```
- **Expected**: 200 OK, token

### POST /auth/logout
- **Description**: Logout user
- **Headers**: `Authorization: Bearer {token}`
- **Expected**: 200 OK

---

## Products

### GET /products
- **Description**: List products
- **Headers**: `Authorization: Bearer {token}`, `X-Outlet-Id: {outlet_id}`
- **Test File**: `tests/Feature/Api/V1/ProductApiTest.php::test_can_get_products`
- **Expected**: 200 OK, paginated products

### POST /products
- **Description**: Create product
- **Test File**: `tests/Feature/Api/V1/ProductApiTest.php::test_can_create_product`
- **Expected**: 201 Created

### GET /products/{id}
- **Description**: Get single product
- **Expected**: 200 OK, product object

### PUT /products/{id}
- **Description**: Update product
- **Expected**: 200 OK

### DELETE /products/{id}
- **Description**: Delete product
- **Expected**: 204 No Content

---

## Categories

### GET /categories
- **Description**: List categories
- **Test File**: `tests/Feature/Api/V1/CategoryApiTest.php::test_can_get_categories`
- **Expected**: 200 OK, categories

### POST /categories
- **Description**: Create category
- **Test File**: `tests/Feature/Api/V1/CategoryApiTest.php::test_can_create_category`
- **Expected**: 201 Created

### PUT /categories/{id}
- **Description**: Update category
- **Expected**: 200 OK

### DELETE /categories/{id}
- **Description**: Delete category
- **Expected**: 204 No Content

---

## Transactions

### POST /transactions/checkout
- **Description**: Process checkout
- **Headers**: `Authorization: Bearer {token}`, `X-Outlet-Id: {outlet_id}`
- **Test File**: `tests/Feature/Api/V1/TransactionCheckoutTest.php::test_can_checkout_with_cash`
- **Expected**: 201 Created, transaction object

### POST /transactions/{id}/void
- **Description**: Void transaction
- **Test File**: `tests/Feature/Api/V1/TransactionVoidRefundTest.php::test_can_void_transaction`
- **Expected**: 200 OK

### POST /transactions/{id}/refund
- **Description**: Refund transaction
- **Test File**: `tests/Feature/Api/V1/TransactionVoidRefundTest.php::test_can_refund_item`
- **Expected**: 200 OK

---

## Sessions

### POST /sessions/open
- **Description**: Open POS session
- **Test File**: `tests/Feature/Api/V1/SessionOpenCloseTest.php::test_can_open_session`
- **Expected**: 201 Created, session object

### POST /sessions/{id}/close
- **Description**: Close POS session
- **Test File**: `tests/Feature/Api/V1/SessionOpenCloseTest.php::test_can_close_session`
- **Expected**: 200 OK

---

## Held Orders

### GET /held-orders
- **Description**: List held orders
- **Test File**: `tests/Feature/Api/V1/HeldOrderTest.php::test_can_get_held_orders`
- **Expected**: 200 OK

### POST /held-orders
- **Description**: Create held order
- **Test File**: `tests/Feature/Api/V1/HeldOrderTest.php::test_can_create_held_order`
- **Expected**: 201 Created

### DELETE /held-orders/{id}
- **Description**: Delete held order
- **Expected**: 204 No Content

---

## Mobile Sync

### GET /mobile/sync
- **Description**: Sync all data for mobile app
- **Test File**: `tests/Feature/Api/V1/MobileSyncMasterTest.php::test_mobile_sync_returns_products`
- **Expected**: 200 OK, full data payload

---

## Subscription

### GET /subscription
- **Description**: Get current subscription
- **Test File**: `tests/Feature/Api/V1/SubscriptionApiTest.php::test_can_get_subscription`
- **Expected**: 200 OK

### POST /subscription/subscribe
- **Description**: Subscribe to plan
- **Test File**: `tests/Feature/Api/V1/SubscriptionApiTest.php::test_can_subscribe_to_plan`
- **Expected**: 200 OK

### POST /subscription/upgrade
- **Description**: Upgrade subscription
- **Test File**: `tests/Feature/Api/V1/SubscriptionApiTest.php::test_can_upgrade_plan`
- **Expected**: 200 OK

### GET /subscription/plans
- **Description**: Get available plans
- **Test File**: `tests/Feature/Api/V1/SubscriptionPlanApiTest.php::test_can_get_subscription_plans`
- **Expected**: 200 OK

---

## Customers

### GET /customers
- **Description**: List customers
- **Expected**: 200 OK

### POST /customers
- **Description**: Create customer
- **Expected**: 201 Created

---

## Outlets

### GET /outlets
- **Description**: List outlets
- **Expected**: 200 OK

### POST /outlets
- **Description**: Create outlet
- **Expected**: 201 Created

---

## API V1 Test Coverage

| Endpoint | Test File |
|----------|-----------|
| POST /auth/register | `tests/Feature/Api/V1/RegisterTest.php` |
| GET /products | `tests/Feature/Api/V1/ProductApiTest.php` |
| POST /products | `tests/Feature/Api/V1/ProductApiTest.php` |
| GET /categories | `tests/Feature/Api/V1/CategoryApiTest.php` |
| POST /categories | `tests/Feature/Api/V1/CategoryApiTest.php` |
| POST /transactions/checkout | `tests/Feature/Api/V1/TransactionCheckoutTest.php` |
| POST /transactions/{id}/void | `tests/Feature/Api/V1/TransactionVoidRefundTest.php` |
| POST /transactions/{id}/refund | `tests/Feature/Api/V1/TransactionVoidRefundTest.php` |
| POST /sessions/open | `tests/Feature/Api/V1/SessionOpenCloseTest.php` |
| POST /sessions/{id}/close | `tests/Feature/Api/V1/SessionOpenCloseTest.php` |
| GET /held-orders | `tests/Feature/Api/V1/HeldOrderTest.php` |
| POST /held-orders | `tests/Feature/Api/V1/HeldOrderTest.php` |
| GET /mobile/sync | `tests/Feature/Api/V1/MobileSyncMasterTest.php` |
| GET /subscription | `tests/Feature/Api/V1/SubscriptionApiTest.php` |
| POST /subscription/subscribe | `tests/Feature/Api/V1/SubscriptionApiTest.php` |
| POST /subscription/upgrade | `tests/Feature/Api/V1/SubscriptionApiTest.php` |
| GET /subscription/plans | `tests/Feature/Api/V1/SubscriptionPlanApiTest.php` |
