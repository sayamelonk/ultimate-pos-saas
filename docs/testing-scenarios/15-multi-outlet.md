# 15 - Multi Outlet Management Testing

## Outlet Switching

### OUT-001: Switch between outlets
- **Setup**: User assigned to multiple outlets
- **Action**: Switch outlet
- **Expected**: Data updated to selected outlet
- **Test File**: `tests/Feature/OutletSwitchTest.php::test_can_switch_outlet`

### OUT-002: Outlet switch persists
- **Setup**: Switch outlet
- **Action**: Reload page
- **Expected**: Same outlet selected

### OUT-003: POS loads correct outlet data
- **Setup**: Selected outlet
- **Action**: Open POS
- **Expected**: Products and prices for selected outlet

### OUT-004: Transaction for correct outlet
- **Setup**: Selected outlet
- **Action**: Checkout
- **Expected**: Transaction linked to selected outlet

### OUT-005: Cannot access unassigned outlet
- **Setup**: User assigned to outlet A only
- **Action**: Try to access outlet B
- **Expected**: Access denied

---

## Outlet-Specific Settings

### OUT-SET-001: Tax settings per outlet
- **Setup**: Different tax rates per outlet
- **Action**: Checkout at each outlet
- **Expected**: Correct tax applied
- **Test File**: `tests/Feature/OutletSwitchTest.php::test_outlet_tax_settings`

### OUT-SET-002: Operating hours per outlet
- **Setup**: Different hours per outlet
- **Expected**: POS shows open/closed status

### OUT-SET-003: Payment methods per outlet
- **Setup**: Different payment methods per outlet
- **Expected**: Only enabled methods shown

---

## Outlet Reports

### OUT-RPT-001: Report per outlet
- **Setup**: Select outlet
- **Action**: View sales report
- **Expected**: Data only for selected outlet

### OUT-RPT-002: Combined report all outlets
- **Expected**: Aggregate data from all outlets

### OUT-RPT-003: Outlet comparison
- **Expected**: Side-by-side outlet performance

---

## Product Availability per Outlet

### OUT-PROD-001: Enable/disable product per outlet
- **Action**: Toggle product availability
- **Expected**: Product shown/hidden in selected outlet

### OUT-PROD-002: Different price per outlet
- **Setup**: Set different prices per outlet
- **Expected**: Correct price shown per outlet

### OUT-PROD-003: Product sync to outlet
- **Setup**: New product
- **Action**: Sync to outlets
- **Expected**: Product available in all outlets

---

## User Permission per Outlet

### OUT-USER-001: Assign user to multiple outlets
- **Action**: Add outlets to user
- **Expected**: User can access all assigned outlets

### OUT-USER-002: User role per outlet
- **Setup**: Cashier at outlet A, Manager at outlet B
- **Expected**: Different permissions per outlet

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/OutletSwitchTest.php` | All outlet switch tests |
