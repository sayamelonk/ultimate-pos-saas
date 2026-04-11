# 01 - Landing Page & Registration Testing

## Landing Page

### LP-001: Landing page loads successfully
- **URL**: `/`
- **Expected**: Page loads without error, shows pricing info
- **Test File**: `tests/Feature/UserJourneyTest.php::test_landing_page_loads_successfully`

### LP-002: Pricing page accessible
- **URL**: `/pricing`
- **Expected**: Shows all subscription plans with pricing

### LP-003: Login page accessible
- **URL**: `/login`
- **Expected**: Shows login form with email/password fields

### LP-004: Register page accessible
- **URL**: `/register`
- **Expected**: Shows registration form

---

## Registration

### REG-001: User can register with valid data
- **Input**: name, email, password, password_confirmation
- **Expected**:
  - User created in database
  - Email verification sent
  - Redirected to email verification notice
- **Test File**: `tests/Feature/Api/V1/RegisterTest.php::test_user_can_register`

### REG-002: Registration fails with invalid email
- **Input**: email = "invalid-email"
- **Expected**: Validation error message

### REG-003: Registration fails with short password
- **Input**: password = "123"
- **Expected**: Validation error "Password must be at least 8 characters"

### REG-004: Registration fails with duplicate email
- **Input**: email already exists
- **Expected**: Validation error "The email has already been taken"

### REG-005: Registration fails with missing required fields
- **Input**: missing name or email
- **Expected**: Validation error for missing field

---

## Email Verification

### VER-001: Unverified user redirected from protected routes
- **Setup**: Register new user, don't verify email
- **URL**: `/admin/dashboard`
- **Expected**: Redirect to `/email/verify`
- **Test File**: `tests/Feature/UserJourneyTest.php::test_unverified_user_redirected_from_dashboard`

### VER-002: Verified user can access protected routes
- **Setup**: Register and verify email
- **URL**: `/admin/dashboard`
- **Expected**: Page loads successfully

### VER-003: Email verification link works
- **Setup**: Register user
- **Action**: Click verification link in email
- **Expected**: Email verified, redirected to dashboard

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/UserJourneyTest.php` | `test_landing_page_loads_successfully()` |
| `tests/Feature/Api/V1/RegisterTest.php` | All registration tests |
