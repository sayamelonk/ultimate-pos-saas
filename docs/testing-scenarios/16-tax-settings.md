# 16 - Tax Settings Testing

## Tax Modes

### TAX-001: Exclusive tax mode
- **Setup**: Tenant/Outlet with exclusive tax
- **Action**: Checkout with subtotal 100,000 + 10% tax
- **Expected**:
  - Subtotal: 100,000
  - Tax: 10,000
  - Grand Total: 110,000
- **Test File**: `tests/Feature/Api/V2/OrderTaxModeTest.php::test_checkout_tax_exclusive`

### TAX-002: Inclusive tax mode
- **Setup**: Tenant/Outlet with inclusive tax
- **Action**: Checkout with subtotal 100,000 (tax included)
- **Expected**:
  - Grand Total: 100,000
  - Tax: 9,091
  - Subtotal: 90,909
- **Test File**: `tests/Feature/Api/V2/OrderTaxModeTest.php::test_checkout_tax_inclusive`

### TAX-003: No tax mode
- **Setup**: Tax disabled
- **Expected**: No tax added

---

## Tax Rate Settings

### TAX-RATE-001: Set tax percentage
- **Action**: Set tax to 11%
- **Expected**: 11% applied to all transactions

### TAX-RATE-002: Different tax per outlet
- **Setup**: Outlet A = 10%, Outlet B = 11%
- **Action**: Checkout at each outlet
- **Expected**: Correct tax rate per outlet
- **Test File**: `tests/Feature/TaxSettingsTest.php`

### TAX-RATE-003: Tax rounding
- **Setup**: Tax calculation results in decimal
- **Expected**: Properly rounded (nearest integer or 2 decimal places)

---

## Tax-Exempt Items

### TAX-EX-001: Mark item as tax-exempt
- **Setup**: Product marked tax-exempt
- **Action**: Checkout with tax-exempt item
- **Expected**: No tax on exempt item

### TAX-EX-002: Mixed taxable and exempt items
- **Setup**: 1 taxable item, 1 exempt item
- **Expected**: Tax only on taxable item

---

## Tax Reporting

### TAX-REPORT-001: Tax collected report
- **URL**: `/reports/tax`
- **Expected**: Shows total tax collected

### TAX-REPORT-002: Tax breakdown
- **Expected**: Tax by rate (10%, 11%, exempt)

---

## Service Charge

### SVC-001: Service charge calculation
- **Setup**: 5% service charge enabled
- **Action**: Checkout subtotal 100,000
- **Expected**:
  - Subtotal: 100,000
  - Service Charge: 5,000
  - (Plus tax on appropriate mode)

### SVC-002: Service charge toggle
- **Setup**: Disable service charge
- **Expected**: No service charge added

### SVC-003: Service charge tax
- **Setup**: Service charge enabled with exclusive tax
- **Expected**: Tax applied to (subtotal + service charge) OR only subtotal (configurable)

---

## Tax Inclusive in Transaction Details

### TAX-TXN-001: Transaction shows tax breakdown
- **Action**: Complete transaction
- **Expected**: Receipt shows tax amount and rate

### TAX-TXN-002: Inclusive tax shown correctly
- **Expected**: Tax portion extracted and shown

---

## Tax Settings API

### TAX-API-001: Update tax settings
- **Action**: PUT `/api/v2/settings/tax`
- **Input**:
  ```json
  {
    "tax_mode": "exclusive",
    "tax_percentage": 11
  }
  ```
- **Expected**: Settings updated
- **Test File**: `tests/Feature/Api/V2/SettingsTaxTest.php::test_can_update_tax_settings`

### TAX-API-002: Get tax settings
- **Action**: GET `/api/v2/settings/tax`
- **Expected**: Returns current tax settings
- **Test File**: `tests/Feature/Api/V2/SettingsTaxTest.php::test_can_get_tax_settings`

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V2/OrderTaxModeTest.php` | Tax mode tests |
| `tests/Feature/TaxInclusiveTest.php` | Inclusive tax tests |
| `tests/Feature/TaxSettingsTest.php` | Tax settings tests |
| `tests/Feature/Api/V2/SettingsTaxTest.php` | Settings tax API tests |
