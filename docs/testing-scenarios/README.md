# Testing Scenarios Documentation

Dokumentasi ini berisi skenario pengujian lengkap untuk aplikasi Ultimate POS SaaS.

## Folder Structure

```
docs/testing-scenarios/
├── README.md                    # Dokumentasi utama
├── 01-landing-registration.md  # Landing page & Registrasi
├── 02-authentication.md        # Login, Logout, Session
├── 03-subscription.md           # Subscription & Plan
├── 04-onboarding.md             # Onboarding Flow
├── 05-pos-operation.md          # POS Operations
├── 06-transaction.md            # Transaction & Checkout
├── 07-inventory.md              # Inventory Management
├── 08-menu-management.md        # Menu & Product Management
├── 09-pricing.md                # Pricing & Discounts
├── 10-kds-waiter.md             # KDS & Waiter System
├── 11-qr-order.md               # QR Order System
├── 12-reports.md                # Reports & Analytics
├── 13-admin-panel.md            # Admin Panel Features
├── 14-feature-gating.md         # Feature Gating per Tier
├── 15-multi-outlet.md           # Multi Outlet Management
├── 16-tax-settings.md           # Tax Settings
├── 17-api-v1.md                 # API V1 Endpoints
├── 18-api-v2.md                 # API V2 Endpoints
└── test-checklist.md            # Checklist untuk manual testing
```

## Cara Menggunakan

1. Baca skenario yang ingin diuji
2. Jalankan test dengan: `php artisan test`
3. Atau jalankan spesifik: `php artisan test --filter=TestName`
4. Untuk manual testing, gunakan `test-checklist.md`

## Status Test Files yang Ada

### Feature Tests
- `tests/Feature/UserJourneyTest.php` ✅
- `tests/Feature/TierStarterJourneyTest.php` ✅
- `tests/Feature/TierGrowthJourneyTest.php` ✅
- `tests/Feature/TierProfessionalJourneyTest.php` ✅
- `tests/Feature/TierEnterpriseJourneyTest.php` ✅
- `tests/Feature/Api/V1/RegisterTest.php` ✅
- `tests/Feature/Api/V1/SessionOpenCloseTest.php` ✅
- `tests/Feature/Api/V1/SubscriptionApiTest.php` ✅
- `tests/Feature/Api/V1/SubscriptionPlanApiTest.php` ✅
- `tests/Feature/Api/V1/TransactionCheckoutTest.php` ✅
- `tests/Feature/Api/V1/TransactionVoidRefundTest.php` ✅
- `tests/Feature/Api/V1/HeldOrderTest.php` ✅
- `tests/Feature/Api/V1/CategoryApiTest.php` ✅
- `tests/Feature/Api/V1/ProductApiTest.php` ✅
- `tests/Feature/Api/V1/MobileSyncMasterTest.php` ✅
- `tests/Feature/Api/V1/KitchenOrderIntegrationTest.php` ✅
- `tests/Feature/Api/V2/SessionApiTest.php` ✅
- `tests/Feature/Api/V2/OrderApiTest.php` ✅
- `tests/Feature/Api/V2/OrderPayTest.php` ✅
- `tests/Feature/Api/V2/OrderTaxModeTest.php` ✅
- `tests/Feature/Api/V2/CashDrawerApiTest.php` ✅
- `tests/Feature/Api/V2/InventoryApiTest.php` ✅
- `tests/Feature/Api/V2/KDSApiTest.php` ✅
- `tests/Feature/Api/V2/WaiterApiTest.php` ✅
- `tests/Feature/Api/V2/ReportsApiTest.php` ✅
- `tests/Feature/Api/V2/SyncApiTest.php` ✅
- `tests/Feature/Api/V2/SyncProductCompletenessTest.php` ✅
- `tests/Feature/Api/V2/SettingsApiTest.php` ✅
- `tests/Feature/Api/V2/SettingsTaxTest.php` ✅
- `tests/Feature/QrOrder/QrOrderMenuTest.php` ✅
- `tests/Feature/QrOrder/QrOrderManagementTest.php` ✅
- `tests/Feature/QrOrder/QrOrderCheckoutTest.php` ✅
- `tests/Feature/QrOrder/QrOrderWebhookTest.php` ✅
- `tests/Feature/DashboardFeatureGatingTest.php` ✅
- `tests/Feature/SidebarFeatureGatingTest.php` ✅
- `tests/Feature/OutletSwitchTest.php` ✅
- `tests/Feature/TaxInclusiveTest.php` ✅
- `tests/Feature/TaxSettingsTest.php` ✅
- `tests/Feature/Admin/AdminInvoiceControllerTest.php` ✅
- `tests/Feature/Admin/AdminSubscriptionControllerTest.php` ✅
- `tests/Feature/Admin/SubscriptionPlanControllerTest.php` ✅

### Unit Tests
- `tests/Unit/Models/*.php` - Model tests
- `tests/Unit/Services/StockServiceTest.php` - Service tests
- `tests/Unit/TranslationTest.php` - Translation tests

## Cara Menjalankan Test

```bash
# Semua test
php artisan test

# Feature tests saja
php artisan test --testsuite=Feature

# Unit tests saja
php artisan test --testsuite=Unit

# Test spesifik file
php artisan test tests/Feature/UserJourneyTest.php

# Test dengan filter
php artisan test --filter=test_landing_page

# Test dengan coverage (memerlukan phpunit.xml update)
php artisan test --coverage
```
