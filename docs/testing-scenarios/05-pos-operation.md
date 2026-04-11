# 05 - POS Operation Testing

## POS Dashboard

### POS-001: POS page loads for authorized user
- **URL**: `/pos` or `/pos/{outlet_id}`
- **Expected**: POS interface loads
- **Test File**: `tests/Feature/UserJourneyTest.php`

### POS-002: POS page requires open session
- **Setup**: No open session
- **Expected**: Prompt to open session

### POS-003: POS shows session info when session open
- **Setup**: Open session
- **Expected**: Shows session ID, start time, initial cash

### POS-004: POS shows today's transactions
- **Setup**: Open session with transactions
- **Expected**: Lists transactions made today

---

## Held Orders

### HELD-001: Create held order
- **Action**: Save current cart as held order
- **Input**: `{"items": [...], "note": "Meja 5"}`
- **Expected**: Order saved, cart cleared
- **Test File**: `tests/Feature/Api/V1/HeldOrderTest.php::test_can_create_held_order`

### HELD-002: Recall held order
- **Setup**: Create held order
- **Action**: Recall held order
- **Expected**: Cart populated with held order items

### HELD-003: Delete held order
- **Action**: Delete held order
- **Expected**: Order removed

### HELD-004: Held orders persist across sessions
- **Setup**: Create held order
- **Action**: Close and reopen session
- **Expected**: Held order still exists

### HELD-005: Multiple held orders
- **Setup**: Create 3 held orders
- **Expected**: All shown in held orders list

### HELD-006: Held order timeout (if implemented)
- **Setup**: Create held order
- **Action**: Wait for timeout
- **Expected**: Order auto-deleted or prompted

---

## Cash Drawer

### CASH-001: View cash drawer balance
- **Expected**: Shows current cash balance

### CASH-002: Cash drawer log - open session
- **Setup**: Open session
- **Expected**: Log entry created for opening balance

### CASH-003: Cash drawer log - cash received
- **Setup**: Transaction with cash payment
- **Expected**: Cash added to drawer

### CASH-004: Cash drawer log - cash out
- **Action**: Record cash payout
- **Expected**: Cash subtracted from drawer

### CASH-005: Cash drawer log - close session
- **Setup**: Close session
- **Expected**: Log entry for closing balance

### CASH-006: Cash drawer mismatch alert
- **Setup**: Expected vs actual cash differs
- **Expected**: Alert shown, difference recorded

---

## Authorization & PIN

### PIN-001: PIN required for certain actions
- **Action**: Void transaction
- **Expected**: PIN modal shown

### PIN-002: Correct PIN allows action
- **Input**: Correct PIN
- **Expected**: Action completed

### PIN-003: Wrong PIN blocks action
- **Input**: Wrong PIN (3 times)
- **Expected**: Blocked, attempt logged

### PIN-004: Manager PIN override
- **Setup**: Use manager PIN
- **Expected**: Action allowed

### PIN-005: View PIN attempts log
- **URL**: `/admin/authorization/logs`
- **Expected**: Shows all PIN attempt history

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V1/HeldOrderTest.php` | All held order tests |
| `tests/Feature/Api/V2/CashDrawerApiTest.php` | Cash drawer tests |
