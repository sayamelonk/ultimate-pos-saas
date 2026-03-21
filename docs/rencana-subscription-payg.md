# Rencana Implementasi Sistem Subscription & Pay-As-You-Go

## 1. Overview

Dokumen ini berisi rencana implementasi sistem billing untuk Ultimate POS SaaS yang mencakup dua model:
- **Subscription** - Langganan bulanan/tahunan dengan batas fitur
- **Pay-As-You-Go** - Pembayaran berdasarkan penggunaan aktual

---

## 2. Model Billing yang Didukung

### 2.1 Subscription (Prepaid)
- **Monthly** - Pembayaran per bulan
- **Yearly** - Pembayaran per tahun (biasanya lebih murah)

### 2.2 Pay-As-You-Go (Postpaid)
- **Per Transaction** - Pembayaran berdasarkan jumlah transaksi
- **Per Outlet** - Pembayaran berdasarkan jumlah outlet aktif
- **Per User** - Pembayaran berdasarkan jumlah user aktif

### 2.3 Hybrid (Combo)
- Subscription dasar + add-on usage (misal: quota transaksi tambahan)

### 2.4 Topup (Prepaid Credits) - PRIORITAS
- Customer bisa topup kredit terlebih dahulu
- Saat transaksi, kredit otomatis terpotong
- Jika kredit habis, transaksi diblok/di-stop
- Kasir bisa pilih: biaya ditanggung customer atau owner

---

## 3. Fitur Tambahan: POS Transaction dengan PAYG Fee

### 3.1 Konsep Dasar
Di POS, kasir bisa memilih siapa yang menanggung biaya PAYG per transaksi:

| Opsi | Deskripsi | Contoh |
|------|-----------|--------|
| **Customer Bayar** | Biaya PAYG ditambahkan ke total struk | Admin fee Rp 150/transaksi |
| **Owner Bayar** | Biaya PAYG ditanggung owner (tidak muncul di struk) | Biaya admin ditanggung toko |

### 3.2 Alur di POS
```
1. Customer checkout di POS
2. Kasir pilih metode pembayaran
3. Kasir pilih "PAYG Fee":
   - [ ] Tambah ke struk (customer bayar)
   - [ ] Tanpa biaya (owner bayar)
4. Jika "Tambah ke struk":
   - Biaya PAYG ditambahkan ke total
   - Customer bayar total + admin fee
5. Jika "Tanpa biaya":
   - Total biasa (owner tanggung)
```

### 3.3 POS UI Mockup
```text
┌─────────────────────────────────────────┐
│           CHECKOUT                      │
├─────────────────────────────────────────┤
│ Items: 3 items         Rp 75.000       │
│ Discount:              Rp 5.000        │
│─────────────────────────────────────────│
│ SUBTOTAL:            Rp 70.000         │
│                                             │
│ PAYG Fee:           [Customer Bayar ▼]  │
│    ○ Tambah ke struk (+Rp 150)          │
│    ● Owner tanggung (Rp 0)              │
│─────────────────────────────────────────│
│ TOTAL:              Rp 70.000           │
│                                             │
│ [BAYAR]                                   │
└─────────────────────────────────────────┘
```

### 3.4 Tabel untuk Track Biaya per Transaksi
```php
// Modifikasi tabel transactions
$table->decimal('payg_fee', 12, 2)->default(0);  // Biaya PAYG
$table->enum('payg_paid_by', ['customer', 'owner'])->default('owner'); // Siapa bayar
```

---

## 4. Subscription → PAYG Migration

### 4.1 Skenario
Tenant yang sudah berlangganan subscription inginSwitch ke Pay-As-You-Go.

### 4.2 Alur Migration
```
1. Tenant request migration ( dari dashboard )
2. Admin/ Sistem approve request
3. Hitung sisa masa aktif subscription:
   - Contoh: 15 hari tersisa dari 30 hari
   - Nilai = (15/30) × harga bulanan
4. Konversi ke kredit/kuota PAYG:
   - Nilai sisa bisa digunakan untuk transaksi
   - Atau refund ke customer (opsional)
5. Cancel subscription lama
6. Activate PAYG plan baru
7. Mulai tracking usage untuk PAYG
```

### 4.3 Database Perubahan
```php
// migrations/xxxx_xx_xx_xxxxxx_add_migration_fields.php

// Di tabel subscriptions
$table->timestamp('migrated_to_payg_at')->nullable();
$table->decimal('remaining_credits', 12, 2)->nullable(); // Sisa kredit dari subscription
$table->string('migration_notes')->nullable();
```

---

## 5. Database Structure

### 5.1 Tabel yang Diperlukan

```sql
-- Subscription Plans (sudah ada di reference)
subscription_plans
├── id
├── name (Starter, Professional, Enterprise, Pay-As-You-Go)
├── slug
├── description
├── price_monthly
├── price_yearly
├── max_outlets
├── max_users
├── features (JSON)
├── is_active
├── sort_order
└── timestamps

-- Subscriptions
subscriptions
├── id
├── tenant_id (UUID, FK)
├── subscription_plan_id (FK)
├── billing_type (subscription | pay_as_you_go)
├── billing_cycle (monthly | yearly) -- untuk subscription
├── status (active | cancelled | expired | pending | past_due)
├── starts_at
├── ends_at
├── cancelled_at
├── cancellation_reason
└── timestamps

-- Subscription Invoices
subscription_invoices
├── id
├── tenant_id (UUID, FK)
├── subscription_id (FK, nullable)
├── subscription_plan_id (FK)
├── invoice_number
├── xendit_invoice_id
├── xendit_invoice_url
├── amount
├── tax_amount
├── total_amount
├── currency
├── billing_cycle (monthly | yearly) -- untuk subscription
├── type (subscription | usage) -- invoice type
├── status (pending | paid | expired | cancelled | failed)
├── payment_method
├── payment_channel
├── paid_at
├── expired_at
├── xendit_response (JSON)
├── notes
└── timestamps

-- Usage Records (BARU - untuk Pay-As-You-Go)
usage_records
├── id
├── tenant_id (UUID, FK)
├── subscription_id (FK, nullable)
├── period_month (YYYY-MM)
├── total_transactions
├── transaction_cost
├── average_transaction_cost
├── outlet_count
├── outlet_cost
├── user_count
├── user_cost
├── total_amount
├── status (pending | billed | paid)
├── billed_at
└── timestamps

-- Topup Credits (BARU - Prepaid Credits System)
topup_credits
├── id
├── tenant_id (UUID, FK)
├── customer_id (FK, nullable) -- bisa null kalau bukan per customer
├── amount -- jumlah kredit
├── balance_remaining -- sisa kredit
├── source (topup | subscription_refund | bonus)
├── reference_type (XenditPayment, dll)
├── reference_id
├── status (active | used | expired | cancelled)
├── expires_at
├── purchased_at
└── timestamps

-- Topup Transactions (History pemotongan kredit)
topup_transactions
├── id
├── topup_credit_id (FK)
├── transaction_id (FK, nullable)
├── amount_deducted
├── balance_before
├── balance_after
├── notes
└── timestamps
```

### 5.2 Penjelasan Field Baru

| Tabel | Field | Deskripsi |
|-------|-------|-----------|
| subscriptions | billing_type | 'subscription' atau 'pay_as_you_go' |
| subscription_invoices | type | 'subscription' (tagihan rutin) atau 'usage' (tagihan usage) |
| usage_records | - | Track penggunaan untuk Pay-As-You-Go |
| transactions | payg_fee | Biaya PAYG per transaksi |
| transactions | payg_paid_by | 'customer' atau 'owner' |
| subscriptions | migrated_to_payg_at | Tanggal migrasi ke PAYG |
| subscriptions | remaining_credits | Sisa kredit dari subscription |

---

## 6. Topup (Prepaid Credits) System - PRIORITAS

### 6.1 Konsep
Customer/owner bisa topup kredit terlebih dahulu. Saat transaksi, kredit otomatis terpotong.

### 6.2 Tipe Topup
| Tipe | Deskripsi |
|------|-----------|
| **Manual Topup** | Customer isi kredit manual via payment gateway |
| **Bonus Credit** | Kredit bonus dari promo/diskon |
| **Subscription Refund** | Sisa kredit dari migrasi subscription |

### 6.3 Alur Topup
```
1. Customer/Owner akses halaman topup
2. Pilih nominal topup (Rp 100.000, Rp 250.000, Rp 500.000, custom)
3. Pilih metode pembayaran (Xendit)
4. Payment berhasil → kredit masuk
5. Saat transaksi → kredit otomatis terpotong
```

### 6.4 Alur Transaksi dengan Kredit
```
1. Customer checkout di POS
2. Kasir pilih "Gunakan Kredit"
3. Sistem cek saldo kredit:
   - Jika cukup → potong dari kredit
   - Jika tidak → minta tambahan payment
4. Transaksi selesai
5. Update balance kredit
```

### 6.5 Credit Balance Display
```text
┌─────────────────────────────────────────┐
│           CUSTOMER INFO                  │
├─────────────────────────────────────────┤
│ Nama: John Doe                          │
│ Kredit Tersedia: Rp 250.000            │
│ Berlaku hingga: 31 Mar 2026            │
│                                          │
│ [TOPUP KREDIT]                          │
└─────────────────────────────────────────┘
```

---

## 7. Feature Matrix per Plan

| Feature | Starter (Subscription) | Professional (Subscription) | Enterprise (Subscription) | Pay-As-You-Go |
|---------|----------------------|----------------------------|--------------------------|---------------|
| **Harga** | Rp 99.000/bln | Rp 299.000/bln | Rp 899.000/bln | Per transaksi |
| **Outlet** | 1 | 3 | Unlimited | Per outlet |
| **User** | 2 | 10 | Unlimited | Per user |
| **Transaksi/bln** | 500 | 2.000 | Unlimited | Unlimited |
| **Inventory** | Basic | Advanced | Full | Full |
| **Reports** | Basic | Advanced | Full | Full |
| **Support** | Email | Email + Chat | Priority | Email |
| **API Access** | - | ✅ | ✅ | ✅ |

---

## 5. Implementation Steps

### Phase 1: Subscription System (Copy dari Reference)

#### 1.1 Install Dependencies
```bash
composer require xendit/xendit-php
```

#### 1.2 Create Migrations
- `2026_01_31_012850_create_subscription_plans_table.php` (sudah ada)
- `2026_01_31_012851_create_subscriptions_table.php` (sudah ada, perlu modifikasi)
- `2026_01_31_012852_create_subscription_invoices_table.php` (sudah ada)
- `xxxx_xx_xx_xxxxxx_create_usage_records_table.php` (BARU)
- Modifikasi `subscriptions` table - add `billing_type` column

#### 1.3 Create Models
- `app/Models/SubscriptionPlan.php` (sudah ada)
- `app/Models/Subscription.php` (sudah ada, perlu modifikasi)
- `app/Models/SubscriptionInvoice.php` (sudah ada)
- `app/Models/UsageRecord.php` (BARU)

#### 1.4 Create Services
- `app/Services/XenditService.php` (sudah ada - perlu copy)
- `app/Services/BillingService.php` (BARU - logic untuk hitung usage)
- `app/Services/UsageTrackerService.php` (BARU - track penggunaan)

#### 1.5 Create Controllers
- `app/Http/Controllers/SubscriptionController.php` (sudah ada)
- `app/Http/Controllers/UsageController.php` (BARU)
- `app/Http/Controllers/WebhookController.php` (perlu modifikasi untuk handle usage)

#### 1.6 Create Middleware
- `app/Http/Middleware/EnsureActiveSubscription.php` (sudah ada, perlu modifikasi untuk PAYG)

#### 1.7 Create Views
- `resources/views/subscription/` (sudah ada)
- `resources/views/usage/` (BARU)
- `resources/views/admin/subscription/` (BARU - untuk management di admin)

#### 1.8 Routes
```php
// Subscription
Route::get('/subscription', [SubscriptionController::class, 'index']);
Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);
Route::post('/subscription/subscribe/{plan}', [SubscriptionController::class, 'subscribe']);
Route::post('/subscription/renew', [SubscriptionController::class, 'renew']);
Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);

// Usage (PAYG)
Route::get('/usage', [UsageController::class, 'index']);
Route::get('/usage/current', [UsageController::class, 'current']);
Route::get('/usage/history', [UsageController::class, 'history']);

// Admin
Route::prefix('admin/subscription')->group(function () {
    Route::get('/plans', [AdminSubscriptionController::class, 'plans']);
    Route::post('/plans', [AdminSubscriptionController::class, 'storePlan']);
    Route::put('/plans/{plan}', [AdminSubscriptionController::class, 'updatePlan']);
    Route::delete('/plans/{plan}', [AdminSubscriptionController::class, 'deletePlan']);
    Route::get('/tenants/{tenant}', [AdminSubscriptionController::class, 'tenantSubscription']);
});
```

### Phase 2: Pay-As-You-Go Implementation

#### 2.1 Usage Tracking
- Tambahkan field `subscription_id` ke `transactions` table
- Create job untuk track daily usage
- Create command untuk generate monthly usage report

#### 2.2 Usage Calculation Logic
```php
// Contoh logika hitung biaya PAYG
$transactionCost = $usage->total_transactions * $plan->cost_per_transaction;
$outletCost = $usage->outlet_count * $plan->cost_per_outlet;
$userCost = $usage->user_count * $plan->cost_per_user;
$totalCost = $transactionCost + $outletCost + $userCost;
```

#### 2.3 Usage Alert
- Kirim notifikasi jika usage > 80% quota (untuk subscription)
- Kirim notifikasi bulanan untuk PAYG usage summary

### Phase 2.5: POS Integration (Kasir Pilih Siapa Bayar)

#### 2.5.1 Modifikasi Transaction Table
```bash
php artisan make:migration add_payg_fields_to_transactions_table
```

Fields to add:
- `payg_fee` - decimal(12,2) - biaya PAYG per transaksi
- `payg_paid_by` - enum('customer', 'owner') - siapa bayar

#### 2.5.2 POS Checkout Flow
- Tambahkan UI selector di checkout page
- Update calculateTotal() untuk include/exclude PAYG fee
- Update receipt printing untuk menampilkan PAYG fee

### Phase 2.7: Topup (Prepaid Credits) - PRIORITAS

#### 2.7.1 Create Migrations
```bash
php artisan make:migration create_topup_credits_table
php artisan make:migration create_topup_transactions_table
```

#### 2.7.2 Create Models
- `app/Models/TopupCredit.php`
- `app/Models/TopupTransaction.php`

#### 2.7.3 Create Services
- `app/Services/TopupService.php` - handle topup logic
- `app/Services/CreditDeductionService.php` - potong kredit saat transaksi

#### 2.7.4 Create Controllers
- `app/Http/Controllers/TopupController.php`
  - `index` - lihat daftar topup
  - `create` - form topup
  - `store` - proses topup (Xendit)
  - `history` - history topup & penggunaan

#### 2.7.5 POS Integration
- Update checkout untuk gunakan kredit
- Tampilkan saldo kredit di customer info
- Auto-deduct kredit saat transaksi

### Phase 2.9: Subscription → PAYG Migration

#### 2.9.1 Migration Controller
- `app/Http/Controllers/SubscriptionMigrationController.php`
  - `requestMigration()` - tenant request switch ke PAYG
  - `approveMigration()` - admin approve
  - `calculateRemainingValue()` - hitung nilai sisa subscription

#### 2.9.2 Migration Logic
```php
// Contoh logika migrasi
$subscription = $tenant->activeSubscription;
$daysRemaining = now()->diffInDays($subscription->ends_at);
$valuePerDay = $subscription->plan->price_monthly / 30;
$remainingValue = $daysRemaining * $valuePerDay;

// Convert ke kredit PAYG
$subscription->update([
    'status' => 'cancelled',
    'cancelled_at' => now(),
    'migrated_to_payg_at' => now(),
    'remaining_credits' => $remainingValue,
]);

// Activate PAYG
$tenant->activatePayg($remainingValue);
```

#### 2.9.3 Routes
```php
Route::post('/subscription/migration/request', [SubscriptionMigrationController::class, 'request']);
Route::post('/subscription/migration/approve/{tenant}', [SubscriptionMigrationController::class, 'approve']);
Route::get('/subscription/migration/history', [SubscriptionMigrationController::class, 'history']);
```

### Phase 3: Landing Page & User Flow

#### 3.1 Landing Page
- Tampilkan pricing plans (subscription vs PAYG)
- Highlight perbedaan kedua model
- CTA untuk register

#### 3.2 User Registration Flow
1. Register tenant
2. Pilih billing model (Subscription / PAYG)
3. Jika Subscription: pilih plan → payment → activated
4. Jika PAYG: langsung aktivasi dengan credit system

#### 3.3 Dashboard Enhancement
- Tampilkan current subscription status
- Tampilkan usage statistics
- Tampilkan billing history

---

## 6. Configuration

### 6.1 Environment Variables
```env
# Xendit
XENDIT_API_KEY=your_api_key
XENDIT_CALLBACK_TOKEN=your_callback_token
XENDIT_IS_PRODUCTION=false

# Billing
BILLING_DEFAULT_CURRENCY=IDR
BILLING_TAX_PERCENTAGE=11
BILLING_PAYG_COST_PER_TRANSACTION=150
BILLING_PAYG_COST_PER_OUTLET=50000
BILLING_PAYG_COST_PER_USER=25000
```

### 6.2 Config File
```php
// config/billing.php
return [
    'default_currency' => env('BILLING_DEFAULT_CURRENCY', 'IDR'),
    'tax_percentage' => env('BILLING_TAX_PERCENTAGE', 11),

    'payg' => [
        'cost_per_transaction' => env('BILLING_PAYG_COST_PER_TRANSACTION', 150),
        'cost_per_outlet' => env('BILLING_PAYG_COST_PER_OUTLET', 50000),
        'cost_per_user' => env('BILLING_PAYG_COST_PER_USER', 25000),
        'billing_cycle' => 'monthly', // atau 'weekly'
        'invoice_threshold' => 10000, // minimal tagihan
    ],

    'subscription' => [
        'default_trial_days' => 14,
        'grace_period_days' => 3,
    ],
];
```

---

## 7. Admin Management

### 7.1 Super Admin Features
- Create/Edit/Delete subscription plans
- View all tenant subscriptions
- Manual activate/deactivate tenant
- Override billing for specific tenant
- View billing reports

### 7.2 Tenant Admin Features
- View current subscription
- Upgrade/Downgrade plan
- Cancel subscription
- View invoice history
- Download invoice PDF

---

## 8. Payment Flow

### 8.1 Subscription Flow
```
1. User pilih plan
2. Create invoice → Xendit
3. Redirect ke Xendit payment page
4. User完成 payment
5. Xendit webhook → update invoice status
6. Subscription activated
7. Email confirmation sent
```

### 8.2 Pay-As-You-Go Flow
```
1. Tenant created with PAYG plan
2. System track daily usage
3. End of month: calculate total usage
4. Generate usage invoice
5. Send invoice to tenant
6. Tenant pay within due date
7. Continue service or suspend if unpaid
```

---

## 9. Files yang Perlu Dibuat/Modifikasi

### 9.1 Files Baru
```
# Subscription & Usage
app/Models/UsageRecord.php
app/Services/BillingService.php
app/Services/UsageTrackerService.php
app/Http/Controllers/UsageController.php
app/Http/Controllers/AdminSubscriptionController.php

# Topup (Prepaid Credits) - PRIORITAS
app/Models/TopupCredit.php
app/Models/TopupTransaction.php
app/Services/TopupService.php
app/Services/CreditDeductionService.php
app/Http/Controllers/TopupController.php

# Commands
app/Console/Commands/CalculateMonthlyUsage.php
app/Console/Commands/CheckExpiredSubscriptions.php

# Migrations
database/migrations/xxxx_xx_xx_xxxxxx_create_usage_records_table.php
database/migrations/xxxx_xx_xx_xxxxxx_create_topup_credits_table.php
database/migrations/xxxx_xx_xx_xxxxxx_create_topup_transactions_table.php
database/migrations/xxxx_xx_xx_xxxxxx_add_payg_fields_to_transactions_table.php
database/migrations/xxxx_xx_xx_xxxxxx_add_migration_fields_to_subscriptions_table.php

# Config
config/billing.php

# Views
resources/views/usage/index.blade.php
resources/views/usage/current.blade.php
resources/views/topup/index.blade.php
resources/views/topup/create.blade.php
resources/views/topup/history.blade.php
resources/views/admin/subscription/plans.blade.php
resources/views/admin/subscription/tenants.blade.php
resources/views/admin/subscription/migration.blade.php
resources/views/landing.blade.php
```

### 9.2 Files Modifikasi
```
app/Models/Tenant.php - add subscription relationship
app/Models/Subscription.php - add billing_type, migration fields
app/Models/SubscriptionInvoice.php - add type field
app/Models/Transaction.php - add payg_fee, payg_paid_by
app/Providers/AppServiceProvider.php - register billing service
bootstrap/app.php - add EnsureActiveSubscription middleware
routes/web.php - add usage, topup, migration routes
database/seeders/DatabaseSeeder.php - seed default plans
.env.example - add billing env vars

# POS Modifications
resources/views/pos/checkout.blade.php - add PAYG selector
app/Http/Controllers/PosController.php - handle credit deduction
```

---

## 10. Testing Plan

### 10.1 Unit Tests
- BillingService calculation
- UsageTrackerService tracking
- SubscriptionPlan model

### 10.2 Feature Tests
- Subscribe to plan
- Cancel subscription
- Process Xendit webhook
- Calculate usage

### 10.3 Integration Tests
- Full payment flow
- Subscription upgrade/downgrade
- Usage limit enforcement

---

## 11. Timeline Estimasi

| Phase | Task | Estimasi |
|-------|------|----------|
| 1 | Setup + Copy Subscription | 1-2 hari |
| 2 | PAYG Core Logic | 2-3 hari |
| 2.5 | POS Integration (Kasir Pilih) | 1-2 hari |
| 2.7 | **Topup System (PRIORITAS)** | 2-3 hari |
| 2.9 | Subscription → PAYG Migration | 1 hari |
| 3 | Usage Tracking & Alerts | 1-2 hari |
| 4 | Admin Management | 1-2 hari |
| 5 | Landing Page | 1 hari |
| 6 | Testing & Bug Fix | 2-3 hari |
| **Total** | | **12-19 hari** |

> **Catatan**: Phase 2.7 (Topup) adalah prioritas utama sesuai request.

---

## 12. Reference

- Project dengan subscription: `web/3-sourcecode-update-1-feb-2026`
- Xendit API: https://docs.xendit.co.id/
- Laravel 12 Billing best practices

---

## 13. Notes

- Pay-As-You-Go lebih cocok untuk bisnis kecil yang baru mulai
- Subscription memberikan predictable revenue
- Hybrid model bisa jadi pilihan: subscription dasar + add-on usage
- Perlu backup plan jika payment gateway unavailable
