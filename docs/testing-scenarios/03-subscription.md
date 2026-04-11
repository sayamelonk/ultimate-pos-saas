# 03 - Subscription & Plan Testing

## Subscription Plans

### SUB-001: View subscription plans
- **URL**: `/subscription/plans`
- **Expected**: Shows all available plans (Starter, Growth, Professional, Enterprise)
- **Test File**: `tests/Feature/UserJourneyTest.php`

### SUB-002: Plan pricing displayed correctly
- **Expected**:
  - Starter: Rp 99,000
  - Growth: Rp 299,000
  - Professional: Rp 599,000
  - Enterprise: Rp 1,499,000

---

## Trial Subscription

### TRIAL-001: New user gets 14-day trial
- **Setup**: Register new user
- **Expected**: Subscription status = 'trial', expires in 14 days
- **Test File**: `tests/Feature/UserJourneyTest.php::test_new_user_gets_trial`

### TRIAL-002: Trial user has full Professional access
- **Setup**: New user with trial
- **Expected**: All Professional features accessible
- **Test File**: `tests/Feature/TierStarterJourneyTest.php`

### TRIAL-003: Trial expiry notification sent
- **Setup**: Trial expiring soon (1-3 days before)
- **Expected**: Email notification sent
- **Test File**: `tests/Feature/UserJourneyTest.php`

### TRIAL-004: Trial expired user cannot transact
- **Setup**: Trial expired
- **Action**: Try to checkout
- **Expected**: Blocked with subscription message

---

## Subscription Lifecycle

### SUB-LIFE-001: Subscribe to paid plan
- **Setup**: Trial user or expired user
- **Action**: Choose plan, pay via Xendit
- **Expected**: Subscription activated
- **Test File**: `tests/Feature/Api/V1/SubscriptionApiTest.php::test_can_subscribe_to_plan`

### SUB-LIFE-002: Subscription auto-renews
- **Setup**: Active subscription
- **Expected**: Renews automatically on expiry date

### SUB-LIFE-003: Subscription cancelled (downgrade flow)
- **Setup**: Active subscription
- **Action**: Cancel subscription
- **Expected**: Access continues until expiry, then downgrade

### SUB-LIFE-004: Upgrade subscription (proration)
- **Setup**: Starter plan
- **Action**: Upgrade to Growth
- **Expected**: Prorated charge, immediate access to new features
- **Test File**: `tests/Feature/Api/V1/SubscriptionApiTest.php::test_can_upgrade_plan`

---

## Payment Integration (Xendit)

### PAY-001: Create payment via Xendit
- **Setup**: Subscribe to plan
- **Expected**: Xendit invoice created, redirect URL returned

### PAY-002: Payment success callback
- **Webhook**: Xendit payment success
- **Expected**: Subscription activated, invoice marked paid

### PAY-003: Payment failed callback
- **Webhook**: Xendit payment failed
- **Expected**: Invoice marked failed, notification sent

### PAY-004: View invoice
- **URL**: `/subscription/invoice/{id}`
- **Expected**: Shows invoice details

---

## Feature Gating by Tier

### GATE-001: Starter plan limits
- **Setup**: Starter subscription
- **Expected**:
  - Max 1 outlet
  - Max 100 products
  - No QR Order
  - No KDS
  - No API Access
- **Test File**: `tests/Feature/TierStarterJourneyTest.php`

### GATE-002: Growth plan features
- **Setup**: Growth subscription
- **Expected**:
  - Max 3 outlets
  - Max 500 products
  - QR Order enabled
  - Basic Inventory
  - No KDS
- **Test File**: `tests/Feature/TierGrowthJourneyTest.php`

### GATE-003: Professional plan features
- **Setup**: Professional subscription
- **Expected**:
  - Max 10 outlets
  - Max 2000 products
  - QR Order enabled
  - Advanced Inventory
  - KDS enabled
- **Test File**: `tests/Feature/TierProfessionalJourneyTest.php`

### GATE-004: Enterprise plan features
- **Setup**: Enterprise subscription
- **Expected**:
  - Unlimited outlets
  - Unlimited products
  - All features enabled
  - API Access enabled
- **Test File**: `tests/Feature/TierEnterpriseJourneyTest.php`

---

## Grace Period & Freeze

### FREEZE-001: Grace period after expiry
- **Setup**: Subscription expired (1 day ago)
- **Expected**: User can still transact (grace period)

### FREEZE-002: Account frozen after grace period
- **Setup**: Subscription expired (2+ days ago)
- **Expected**:
  - Account read-only
  - Cannot transact
  - Warning message shown

### FREEZE-003: Reactivate frozen account
- **Setup**: Frozen account
- **Action**: Subscribe to plan
- **Expected**: Account unfrozen, full access restored

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V1/SubscriptionApiTest.php` | Subscription CRUD |
| `tests/Feature/Api/V1/SubscriptionPlanApiTest.php` | Plan listing |
| `tests/Feature/TierStarterJourneyTest.php` | Starter tier tests |
| `tests/Feature/TierGrowthJourneyTest.php` | Growth tier tests |
| `tests/Feature/TierProfessionalJourneyTest.php` | Professional tier tests |
| `tests/Feature/TierEnterpriseJourneyTest.php` | Enterprise tier tests |
| `tests/Feature/Admin/AdminSubscriptionControllerTest.php` | Admin subscription |
| `tests/Feature/Admin/SubscriptionPlanControllerTest.php` | Admin plans |
| `tests/Feature/DashboardFeatureGatingTest.php` | Feature gating |
| `tests/Feature/SidebarFeatureGatingTest.php` | Sidebar gating |
