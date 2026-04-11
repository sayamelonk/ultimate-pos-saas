# Manual Testing Checklist

Gunakan checklist ini untuk melakukan testing manual pada aplikasi.

## Pre-Testing Setup

- [ ] Database fresh/clean state
- [ ] PHP artisan serve running
- [ ] Test data seeded
- [ ] Browser cleared cache (if testing web)

---

## 1. Landing & Registration

### Landing Page
- [ ] Landing page loads at `/`
- [ ] Pricing page shows all plans at `/pricing`
- [ ] Login page accessible at `/login`
- [ ] Register page accessible at `/register`

### Registration
- [ ] Can register with valid data
- [ ] Shows error for invalid email
- [ ] Shows error for short password
- [ ] Shows error for duplicate email
- [ ] Redirects to email verification after register

### Email Verification
- [ ] Unverified user redirected from `/admin/dashboard`
- [ ] Verified user can access `/admin/dashboard`

---

## 2. Authentication

- [ ] Can login with valid credentials
- [ ] Shows error for wrong password
- [ ] Shows error for non-existent email
- [ ] Demo login works

---

## 3. Onboarding

- [ ] New user redirected to `/onboarding`
- [ ] Can complete business settings
- [ ] Can create first outlet
- [ ] Can create first category
- [ ] Can create first product
- [ ] Can create payment method
- [ ] Onboarding completion redirects to POS

---

## 4. POS Operations

### Session Management
- [ ] Can open POS session
- [ ] Can view current session
- [ ] Can close POS session
- [ ] Session report generated

### Held Orders
- [ ] Can save order as held
- [ ] Can recall held order
- [ ] Can delete held order
- [ ] Held orders persist across sessions

### Cash Drawer
- [ ] Cash drawer shows correct balance
- [ ] Cash in recorded
- [ ] Cash out recorded
- [ ] Close session matches expected cash

---

## 5. Transactions

### Checkout
- [ ] Can checkout with cash payment
- [ ] Can checkout with card payment
- [ ] Can checkout with multiple payments
- [ ] Change calculated correctly
- [ ] Transaction number format correct

### Discounts
- [ ] Item discount applied
- [ ] Order discount applied
- [ ] Discount code works
- [ ] Invalid discount code rejected

### Tax
- [ ] Exclusive tax calculated correctly
- [ ] Inclusive tax calculated correctly
- [ ] Tax breakdown shown on receipt

### Void & Refund
- [ ] Can void transaction
- [ ] Void requires authorization
- [ ] Can refund item
- [ ] Partial refund works
- [ ] Full refund works

### Receipt
- [ ] Receipt generated
- [ ] Receipt has all required info
- [ ] Print receipt works

---

## 6. Inventory

- [ ] Can create inventory item
- [ ] Can add stock (receive)
- [ ] Can transfer stock between outlets
- [ ] Can adjust stock
- [ ] Low stock alert shown
- [ ] Stock valuation correct

### Recipe (Professional+)
- [ ] Can create recipe
- [ ] Recipe cost calculated
- [ ] Auto-deduct on sale works

---

## 7. Menu Management

### Categories
- [ ] Can create category
- [ ] Can edit category
- [ ] Can delete empty category

### Products
- [ ] Can create product
- [ ] Can create product with variants
- [ ] Can create product with modifiers
- [ ] Can create combo product
- [ ] Can edit product
- [ ] Can toggle product active/inactive
- [ ] Outlet-specific pricing works

### Modifiers
- [ ] Can create modifier group
- [ ] Modifiers affect price

---

## 8. QR Order (Growth+)

- [ ] Can generate QR code for table
- [ ] QR code loads menu
- [ ] Can add items to cart
- [ ] Can place order
- [ ] Order appears in management
- [ ] Can pay at counter
- [ ] Online payment works

---

## 9. KDS & Waiter (Professional+)

### KDS
- [ ] KDS shows pending orders
- [ ] Can mark item in-progress
- [ ] Can mark item done
- [ ] Completed order notification sent

### Waiter
- [ ] Waiter sees assigned tables
- [ ] Can take order from table
- [ ] Can update order
- [ ] Notified when order ready

---

## 10. Reports

- [ ] Daily sales report works
- [ ] Sales by category report
- [ ] Sales by product report
- [ ] Transaction report
- [ ] Cash drawer report
- [ ] Export to PDF works

---

## 11. Admin Panel

### Tenant Management
- [ ] Can view all tenants
- [ ] Can create tenant
- [ ] Can suspend tenant

### User Management
- [ ] Can view all users
- [ ] Can create user
- [ ] Can reset PIN

### Subscription
- [ ] Can view all subscriptions
- [ ] Can view subscription details
- [ ] Can manually activate subscription

### Invoices
- [ ] Can view invoices
- [ ] Can resend invoice

---

## 12. Feature Gating

- [ ] Starter: Can only create 1 outlet
- [ ] Starter: Cannot access QR Order
- [ ] Growth: Can access QR Order
- [ ] Growth: Cannot access KDS
- [ ] Professional: Can access KDS
- [ ] Enterprise: Can access API

---

## 13. Tax Settings

- [ ] Can set exclusive tax
- [ ] Can set inclusive tax
- [ ] Can set tax per outlet
- [ ] Service charge toggle works

---

## 14. Multi Outlet

- [ ] Can switch between outlets
- [ ] Data updates per outlet
- [ ] Transaction linked to correct outlet
- [ ] Product prices per outlet

---

## Bug Report Template

```
Title: [SHORT DESCRIPTION]

Environment:
- Browser:
- OS:
- Date:

Steps to Reproduce:
1.
2.
3.

Expected Result:


Actual Result:


Screenshots (if any):
```

---

## Test Sign-Off

| Area | Tester | Date | Status |
|------|--------|------|--------|
| Landing & Registration | | | |
| Authentication | | | |
| Onboarding | | | |
| POS Operations | | | |
| Transactions | | | |
| Inventory | | | |
| Menu Management | | | |
| QR Order | | | |
| KDS & Waiter | | | |
| Reports | | | |
| Admin Panel | | | |
| Feature Gating | | | |
| Tax Settings | | | |
| Multi Outlet | | | |

**Overall Status:** [ ] PASS / [ ] FAIL
