# 14 - Feature Gating Testing

## Feature Gating by Tier

### GATE-001: Starter tier - max outlets enforced
- **Setup**: Starter subscription
- **Action**: Try to create 2nd outlet
- **Expected**: Blocked with upgrade prompt
- **Test File**: `tests/Feature/TierStarterJourneyTest.php::test_cannot_create_multiple_outlets`

### GATE-002: Starter tier - max products enforced
- **Setup**: Starter subscription (limit: 100 products)
- **Action**: Try to create 101st product
- **Expected**: Blocked with upgrade prompt

### GATE-003: Growth tier - QR Order accessible
- **Setup**: Growth subscription
- **Action**: Access QR Order
- **Expected**: Feature accessible
- **Test File**: `tests/Feature/TierGrowthJourneyTest.php::test_can_access_qr_order`

### GATE-004: Growth tier - KDS not accessible
- **Setup**: Growth subscription
- **Action**: Try to access KDS
- **Expected**: Feature gated/blocked
- **Test File**: `tests/Feature/TierGrowthJourneyTest.php::test_cannot_access_kds`

### GATE-005: Professional tier - KDS accessible
- **Setup**: Professional subscription
- **Action**: Access KDS
- **Expected**: Feature accessible
- **Test File**: `tests/Feature/TierProfessionalJourneyTest.php::test_can_access_kds`

### GATE-006: Professional tier - Advanced Inventory accessible
- **Setup**: Professional subscription
- **Action**: Access Recipe/BOM features
- **Expected**: Feature accessible
- **Test File**: `tests/Feature/TierProfessionalJourneyTest.php::test_can_access_advanced_inventory`

### GATE-007: Enterprise tier - API access
- **Setup**: Enterprise subscription
- **Action**: Access API settings
- **Expected**: Feature accessible
- **Test File**: `tests/Feature/TierEnterpriseJourneyTest.php::test_can_access_api`

### GATE-008: Enterprise tier - unlimited outlets
- **Setup**: Enterprise subscription
- **Action**: Create multiple outlets
- **Expected**: No limit enforced

---

## Sidebar Feature Visibility

### SIDEBAR-001: Starter sidebar
- **Setup**: Starter user
- **Expected**: Only core features shown
- **Test File**: `tests/Feature/SidebarFeatureGatingTest.php::test_starter_sees_limited_menu`

### SIDEBAR-002: Growth sidebar
- **Setup**: Growth user
- **Expected**: QR Order shown, KDS hidden
- **Test File**: `tests/Feature/SidebarFeatureGatingTest.php::test_growth_sees_qr_order`

### SIDEBAR-003: Professional sidebar
- **Setup**: Professional user
- **Expected**: KDS shown
- **Test File**: `tests/Feature/SidebarFeatureGatingTest.php::test_professional_sees_kds`

### SIDEBAR-004: Enterprise sidebar
- **Setup**: Enterprise user
- **Expected**: All features shown

---

## Dashboard Widgets

### DASH-001: Starter dashboard
- **Setup**: Starter user
- **Expected**: Basic stats only
- **Test File**: `tests/Feature/DashboardFeatureGatingTest.php::test_starter_dashboard`

### DASH-002: Growth dashboard
- **Setup**: Growth user
- **Expected**: QR Order stats shown

### DASH-003: Professional dashboard
- **Setup**: Professional user
- **Expected**: KDS stats shown

### DASH-004: Enterprise dashboard
- **Setup**: Enterprise user
- **Expected**: All stats and API section

---

## API Feature Gating

### API-GATE-001: API endpoints gated
- **Setup**: Starter subscription
- **Action**: Call API requiring Growth+
- **Expected**: 403 Forbidden with upgrade message

### API-GATE-002: API key for Enterprise
- **Setup**: Enterprise subscription
- **Action**: Generate API key
- **Expected**: API key generated

---

## Trial vs Paid Feature Access

### TRIAL-GATE-001: Trial has full features
- **Setup**: Trial subscription (14 days, Professional-level)
- **Expected**: All Professional features accessible
- **Test File**: `tests/Feature/UserJourneyTest.php`

### TRIAL-GATE-002: Trial expired reverts to gated
- **Setup**: Trial expired
- **Action**: Try to access Professional feature
- **Expected**: Blocked

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/TierStarterJourneyTest.php` | Starter tier tests |
| `tests/Feature/TierGrowthJourneyTest.php` | Growth tier tests |
| `tests/Feature/TierProfessionalJourneyTest.php` | Professional tier tests |
| `tests/Feature/TierEnterpriseJourneyTest.php` | Enterprise tier tests |
| `tests/Feature/DashboardFeatureGatingTest.php` | Dashboard gating |
| `tests/Feature/SidebarFeatureGatingTest.php` | Sidebar gating |
