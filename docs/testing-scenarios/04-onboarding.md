# 04 - Onboarding Flow Testing

## Onboarding Flow

### ONB-001: New user redirected to onboarding
- **Setup**: Register and verify email, no outlets created
- **URL**: `/onboarding`
- **Expected**: Onboarding page loads
- **Test File**: `tests/Feature/UserJourneyTest.php::test_new_user_sees_onboarding_page`

### ONB-002: Complete business settings
- **Setup**: Start onboarding
- **Action**: POST `/onboarding/business`
- **Input**:
  ```json
  {
    "name": "Warung Kopi",
    "timezone": "Asia/Jakarta",
    "currency": "IDR",
    "tax_percentage": 10,
    "service_charge_percentage": 5
  }
  ```
- **Expected**: Tenant settings saved
- **Test File**: `tests/Feature/UserJourneyTest.php::test_onboarding_step1_update_business_settings`

### ONB-003: Create first outlet
- **Setup**: Complete business settings
- **Action**: POST `/onboarding/outlet`
- **Input**:
  ```json
  {
    "name": "Outlet Utama",
    "address": "Jakarta",
    "phone": "08123456789"
  }
  ```
- **Expected**: First outlet created

### ONB-004: Create first category
- **Setup**: Create outlet
- **Action**: POST `/onboarding/category`
- **Input**: `{"name": "Makanan"}`
- **Expected**: Category created

### ONB-005: Create first product
- **Setup**: Create category
- **Action**: POST `/onboarding/product`
- **Input**:
  ```json
  {
    "name": "Nasi Goreng",
    "category_id": "uuid",
    "price": 25000
  }
  ```
- **Expected**: Product created

### ONB-006: Create payment method
- **Setup**: Create product
- **Action**: POST `/onboarding/payment-method`
- **Input**: `{"name": "Tunai", "type": "cash"}`
- **Expected**: Payment method created

### ONB-007: Complete onboarding
- **Setup**: All onboarding steps done
- **Action**: POST `/onboarding/complete`
- **Expected**:
  - `onboarding_completed_at` set
  - Redirected to POS
- **Test File**: `tests/Feature/UserJourneyTest.php::test_onboarding_complete`

### ONB-008: Completed onboarding user redirected from onboarding page
- **Setup**: Complete onboarding
- **URL**: `/onboarding`
- **Expected**: Redirect to dashboard or POS

### ONB-009: Onboarding validation - missing required fields
- **Action**: POST `/onboarding/business` with missing fields
- **Expected**: Validation error

### ONB-010: Onboarding validation - invalid timezone
- **Action**: POST `/onboarding/business` with invalid timezone
- **Expected**: Validation error

---

## Onboarding Status Check

### ONB-STATUS-001: Check onboarding progress
- **Expected**: Shows which steps are completed

### ONB-STATUS-002: Resume onboarding
- **Setup**: Half-complete onboarding
- **URL**: `/onboarding`
- **Expected**: Resume from incomplete step

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/UserJourneyTest.php` | `test_new_user_sees_onboarding_page()`, `test_onboarding_step1_update_business_settings()`, `test_onboarding_complete()` |
