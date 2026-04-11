# 02 - Authentication & Session Testing

## Login

### AUTH-001: User can login with valid credentials
- **Input**: valid email + password
- **Expected**: Login successful, redirected to dashboard
- **Test File**: `tests/Feature/UserJourneyTest.php`

### AUTH-002: Login fails with wrong password
- **Input**: valid email + wrong password
- **Expected**: Error "These credentials do not match our records"

### AUTH-003: Login fails with non-existent email
- **Input**: non-existent email
- **Expected**: Error "These credentials do not match our records"

### AUTH-004: Login fails with empty fields
- **Input**: empty email or password
- **Expected**: Validation error

### AUTH-005: Demo login works for demo user
- **Input**: demo credentials
- **Expected**: Login successful, demo tenant loaded

---

## Session Management

### SESS-001: User can open POS session
- **Setup**: Login, create outlet
- **Action**: POST `/api/v2/sessions/open`
- **Expected**: Session created, cash drawer initialized
- **Test File**: `tests/Feature/Api/V1/SessionOpenCloseTest.php::test_can_open_session`

### SESS-002: User can close POS session
- **Setup**: Open session first
- **Action**: POST `/api/v2/sessions/{id}/close`
- **Expected**: Session closed, closing balance recorded
- **Test File**: `tests/Feature/Api/V1/SessionOpenCloseTest.php::test_can_close_session`

### SESS-003: Cannot open session without cash
- **Setup**: Open session with no cash
- **Expected**: Validation error if required

### SESS-004: Session report generated on close
- **Setup**: Open session, make transactions
- **Action**: Close session
- **Expected**: Report shows all transactions

### SESS-005: Session persists across requests
- **Setup**: Login and open session
- **Action**: Make transaction
- **Expected**: Session ID maintained

---

## API Authentication (Sanctum)

### API-AUTH-001: API works with valid token
- **Setup**: Create user with Sanctum token
- **Header**: `Authorization: Bearer {token}`
- **Expected**: Request successful

### API-AUTH-002: API fails without token
- **Header**: No Authorization header
- **Expected**: 401 Unauthenticated

### API-AUTH-003: API fails with invalid token
- **Header**: `Authorization: Bearer invalid-token`
- **Expected**: 401 Unauthenticated

### API-AUTH-004: Token can be revoked
- **Setup**: Create and revoke token
- **Action**: Use revoked token
- **Expected**: 401 Unauthenticated

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V1/SessionOpenCloseTest.php` | All session tests |
| `tests/Feature/Api/V2/SessionApiTest.php` | API V2 session tests |
| `tests/Feature/Api/V2/CashDrawerApiTest.php` | Cash drawer session tests |
