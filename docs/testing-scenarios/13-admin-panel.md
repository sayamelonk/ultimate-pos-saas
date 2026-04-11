# 13 - Admin Panel Testing

## Admin Dashboard

### ADMIN-001: Admin dashboard loads
- **URL**: `/admin/dashboard`
- **Expected**: Dashboard with stats
- **Test File**: `tests/Feature/UserJourneyTest.php::test_admin_dashboard_loads`

### ADMIN-002: Dashboard shows correct stats
- **Expected**:
  - Total tenants
  - Active subscriptions
  - Monthly revenue

---

## Tenant Management (Super Admin)

### TENANT-001: List all tenants
- **URL**: `/admin/tenants`
- **Expected**: Lists all tenants
- **Test File**: `tests/Feature/Admin/TenantControllerTest.php`

### TENANT-002: View tenant details
- **URL**: `/admin/tenants/{id}`
- **Expected**: Shows tenant info, subscription, usage

### TENANT-003: Create tenant manually
- **Action**: Create tenant
- **Expected**: Tenant created

### TENANT-004: Edit tenant
- **Action**: Edit tenant info
- **Expected**: Updated

### TENANT-005: Suspend tenant
- **Action**: Suspend tenant
- **Expected**: Tenant cannot login

### TENANT-006: Delete tenant
- **Action**: Delete tenant
- **Expected**: Tenant and all data deleted

---

## User Management

### USER-001: List users
- **URL**: `/admin/users`
- **Expected**: Lists all users
- **Test File**: `tests/Feature/Admin/UserControllerTest.php`

### USER-002: View user details
- **URL**: `/admin/users/{id}`
- **Expected**: Shows user info, roles, outlets

### USER-003: Create user
- **Action**: Create new user
- **Expected**: User created

### USER-004: Edit user
- **Action**: Update user
- **Expected**: Updated

### USER-005: Delete user
- **Action**: Delete user
- **Expected**: User deleted

### USER-006: Reset user PIN
- **Action**: Reset PIN
- **Expected**: PIN reset to default

---

## Outlet Management

### OUTLET-001: List outlets
- **URL**: `/admin/outlets`
- **Expected**: Lists all outlets
- **Test File**: `tests/Feature/Admin/OutletControllerTest.php`

### OUTLET-002: Create outlet
- **Action**: Create outlet
- **Expected**: Outlet created

### OUTLET-003: Edit outlet
- **Action**: Update outlet
- **Expected**: Updated

### OUTLET-004: Assign users to outlet
- **Action**: Assign/unassign users
- **Expected**: Permissions updated

---

## Role & Permission Management

### ROLE-001: List roles
- **URL**: `/admin/roles`
- **Expected**: Lists all roles
- **Test File**: `tests/Feature/Admin/RoleControllerTest.php`

### ROLE-002: Create role
- **Action**: Create role with permissions
- **Expected**: Role created

### ROLE-003: Edit role permissions
- **Action**: Update permissions
- **Expected**: Permissions updated

### ROLE-004: Delete role
- **Action**: Delete role
- **Expected**: Cannot delete if users assigned

---

## Subscription Plan Management

### PLAN-001: List subscription plans
- **URL**: `/admin/subscription-plans`
- **Expected**: Lists all plans
- **Test File**: `tests/Feature/Admin/SubscriptionPlanControllerTest.php`

### PLAN-002: Create subscription plan
- **Action**: Create plan
- **Input**:
  ```json
  {
    "name": "Custom Plan",
    "price": 199000,
    "features": {...}
  }
  ```
- **Expected**: Plan created

### PLAN-003: Edit subscription plan
- **Action**: Update plan
- **Expected**: Updated (with proration warning)

### PLAN-004: Set plan as default
- **Action**: Set as default trial plan
- **Expected**: New signups get this plan

---

## Subscription Management

### SUB-ADMIN-001: List all subscriptions
- **URL**: `/admin/subscriptions`
- **Expected**: Lists all subscriptions
- **Test File**: `tests/Feature/Admin/AdminSubscriptionControllerTest.php`

### SUB-ADMIN-002: View subscription details
- **URL**: `/admin/subscriptions/{id}`
- **Expected**: Shows plan, status, usage, invoices

### SUB-ADMIN-003: Manually activate subscription
- **Action**: Activate for customer
- **Expected**: Subscription activated

### SUB-ADMIN-004: Cancel subscription
- **Action**: Cancel subscription
- **Expected**: Access continues until expiry

### SUB-ADMIN-005: Extend subscription
- **Action**: Add days to subscription
- **Expected**: Expiry date extended

---

## Invoice Management

### INV-001: List invoices
- **URL**: `/admin/invoices`
- **Expected**: Lists all invoices
- **Test File**: `tests/Feature/Admin/AdminInvoiceControllerTest.php`

### INV-002: View invoice details
- **URL**: `/admin/invoices/{id}`
- **Expected**: Shows invoice with line items

### INV-003: Manual invoice creation
- **Action**: Create manual invoice
- **Expected**: Invoice created

### INV-004: Resend invoice email
- **Action**: Resend invoice
- **Expected**: Invoice email sent

---

## Authorization Logs

### AUTH-LOG-001: View authorization logs
- **URL**: `/admin/authorization/logs`
- **Expected**: Shows PIN attempts, actions taken
- **Test File**: `tests/Feature/UserJourneyTest.php::test_can_view_authorization_logs`

### AUTH-LOG-002: Filter logs by date
- **Expected**: Filtered by date range

### AUTH-LOG-003: Filter logs by user
- **Expected**: Logs for specific user

---

## System Settings

### SYS-001: View system settings
- **URL**: `/admin/settings`
- **Expected**: Shows configurable settings

### SYS-002: Update system settings
- **Action**: Update settings
- **Expected**: Settings saved

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Admin/TenantControllerTest.php` | Tenant tests |
| `tests/Feature/Admin/UserControllerTest.php` | User tests |
| `tests/Feature/Admin/OutletControllerTest.php` | Outlet tests |
| `tests/Feature/Admin/RoleControllerTest.php` | Role tests |
| `tests/Feature/Admin/SubscriptionPlanControllerTest.php` | Plan tests |
| `tests/Feature/Admin/AdminSubscriptionControllerTest.php` | Subscription tests |
| `tests/Feature/Admin/AdminInvoiceControllerTest.php` | Invoice tests |
