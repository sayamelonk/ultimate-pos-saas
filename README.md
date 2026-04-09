# Ultimate POS SaaS

Sistem Point of Sale (POS) berbasis SaaS dengan arsitektur multi-tenant untuk restoran, cafe, dan F&B business.

## Production URLs

| Resource | URL |
|----------|-----|
| **Web Admin** | https://saas.jagofullstack.com |
| **API Base URL** | https://saas.jagofullstack.com/api/v2 |
| **Swagger UI** | https://saas.jagofullstack.com/api/documentation |
| **OpenAPI JSON** | https://saas.jagofullstack.com/docs/api-docs.json |

## Tech Stack

| Layer | Technology | Version |
|-------|------------|---------|
| **Backend** | Laravel | 12.x |
| **Language** | PHP | 8.3 |
| **Database** | MySQL | 8.0+ |
| **Cache & Queue** | Redis | 7.x |
| **Frontend** | Blade + Tailwind CSS | 4.x |
| **JavaScript** | Alpine.js | 3.x |
| **Mobile App** | Flutter | 3.x |
| **API Auth** | Laravel Sanctum | 4.x |
| **API Docs** | L5-Swagger (OpenAPI 3.0) | 10.x |
| **Payment Gateway** | Xendit | - |

---

## Platform Overview

| Platform | Technology | Description |
|----------|------------|-------------|
| **Web Admin** | Laravel + Blade | Dashboard, master data, reporting, settings |
| **POS Web** | Laravel + Alpine.js | Browser-based POS for cashier |
| **POS Mobile** | Flutter | Native mobile POS app (offline-first) |
| **Waiter App** | Flutter | Order taking, table management |
| **KDS** | Web (PWA) | Kitchen Display System |
| **QR Order** | Web (PWA) | Customer self-order |

---

## Daftar Fitur

### 1. Multi-Tenancy & Outlet Management
- Arsitektur multi-tenant (mendukung banyak brand/franchise, white-label ready)
- Manajemen tenant (brand, logo, kontak, currency, timezone)
- Manajemen outlet per tenant (alamat, jam operasional, koordinat GPS)
- Pengaturan pajak & service charge per outlet (override dari tenant)
- **Tax Mode**: Inclusive (harga sudah termasuk pajak) & Exclusive (pajak ditambahkan)
- Kustomisasi receipt (header, footer, logo toggle)
- Switch outlet aktif

### 2. Subscription & Billing
- 4 tier subscription: Starter, Growth, Professional, Enterprise
- Billing cycle: Monthly & Yearly
- Trial 14 hari (full akses Professional, tanpa kartu kredit)
- Grace period 1 hari setelah expire sebelum freeze
- Freeze mode (read-only, bisa lihat data, tidak bisa transaksi)
- Upgrade dengan proration (bayar selisih pro-rata)
- Downgrade: cancel dulu, freeze, subscribe tier baru
- Payment gateway Xendit (invoice webhook)
- Invoice management & history
- Automated lifecycle: trial expiry, grace period, freeze, data deletion warning
- Email notifications: trial reminder (7d, 3d, 1d), trial expired, subscription expiry, data deletion warning

### 3. Feature Gating (Per Subscription Tier)
- POS Core, Product Management, Basic Reports - semua tier
- Product Variant, Combo, Modifiers - Growth+
- Discount & Promo - Growth+
- Inventory Basic - Growth+
- Table Management - Growth+
- Inventory Advanced, Recipe/BOM, Stock Transfer - Professional+
- Waiter App, QR Order - Professional+
- Manager Authorization - Professional+
- Export Excel/PDF - Professional+
- KDS, API Access, Custom Branding, Loyalty Points - Enterprise
- Dedicated Support, SLA Uptime - Enterprise
- Middleware enforcement: `CheckSubscriptionFeature`, `BlockFrozenWrite`, `CheckSubscriptionStatus`

### 4. User & Access Control (RBAC)
- Multi-role: Super Admin, Tenant Owner, Outlet Manager, Cashier, Waiter, Kitchen Staff
- Custom roles per tenant
- Fine-grained permissions per modul
- PIN login untuk akses cepat POS/KDS/Waiter
- User-outlet assignment (pivot table)
- Email verification wajib (link valid 24 jam)
- Multi-language support (locale switcher)

### 5. Onboarding Wizard
- Multi-step onboarding untuk tenant baru:
  - Update business profile
  - Tambah produk pertama
  - Setup payment methods
  - Invite staff
  - Complete / Skip

### 6. Product & Menu Management
- Produk single, variant, dan combo/bundle
- Kategori hierarki dengan icon & color (reorder drag-drop)
- Variant groups (Size, Ice Level, Temperature) dengan price modifier
- Modifier/add-on groups (Extra cheese, toppings, dll)
- Harga berbeda per outlet (Product-Outlet Assignment)
- Bulk update harga & copy harga antar outlet
- Kitchen station assignment per produk
- Barcode support & product search
- Duplicate product
- Auto-generate product variants
- Link/unlink inventory recipe (BOM)
- Product flags: `show_in_pos`, `show_in_menu`, `is_featured`, `track_stock`, `allow_notes`
- Product metadata: tags, allergens, nutritional info, prep time

### 7. POS Core - Order & Transaction
- Shift/session management (open/close dengan cash balance)
- Order types: dine-in, takeaway, delivery
- Multi-item order dengan variant & modifier
- Discount per order/item (persentase, nominal, buy X get Y)
- Promo code validation & auto-apply discounts
- **Tax Calculation**: Support inclusive & exclusive mode
- **Service Charge**: Configurable per outlet
- Real-time cart calculation (preview sebelum checkout)
- Void/cancel dengan reason & manager authorization
- Refund management
- Held orders (simpan & recall)
- Transaction number format: `{OUTLET_CODE}-{YYYYMMDD}-{SEQ}`
- Receipt view (print-ready)

### 8. Payment Management
- Multi-payment method (Cash, Card, E-Wallet, QRIS, Transfer)
- Split payment (multiple payment methods per transaksi)
- Payment gateway integration (Xendit)
- Charge/surcharge per payment method
- Refund management
- Digital receipt

### 9. Cash Drawer Management
- Cash drawer status tracking
- Cash In / Cash Out operations
- Cash balance monitoring
- Cash drawer logs & report
- Open cash drawer command

### 10. Manager Authorization (PIN Security)
- Configurable actions yang memerlukan manager PIN (void, refund, discount override, dll)
- PIN verification untuk sensitive operations
- List available managers untuk PIN entry
- Authorization log (audit trail)
- Lockout protection (failed PIN attempts)
- Admin panel untuk konfigurasi authorization settings

### 11. Table Management & Floor Plan
- Multi-floor support dengan sort order
- Drag-drop floor plan editor (update table positions)
- Table status: available, occupied, reserved, cleaning
- Open table (start session dengan guest count)
- Close table (end session)
- Move table (physical relocation)
- Table sessions history
- QR code unik per meja

### 12. Kitchen Display System (KDS)
- PIN-based login (tanpa email/password)
- Multi kitchen station (Grill, Fry, Cold, Bar, dll) dengan color coding
- Real-time order queue (filter by status & station)
- Order status flow: pending -> preparing -> ready -> served / cancelled
- Priority system: normal, rush, VIP (VIP sorted first)
- Bump system (mark as ready, next status)
- Per-item status tracking (start & ready individual items)
- Recall served orders
- Cancel dengan reason
- Auto-create kitchen order saat POS checkout
- KDS Statistics: pending/preparing/ready counts, served today, avg prep time, orders by hour
- Kitchen station CRUD (code, color, sort order, active/inactive)

### 13. Waiter App (Mobile API)
- PIN-based login & outlet selection
- List floors & tables (filter by status, floor)
- Get table detail dengan current order
- Open/close table (session management)
- Update table status
- Browse menu (filter by category, search)
- Create new order (dine-in, takeaway) dengan items, variants, modifiers
- Add items to existing order
- Send order to kitchen (creates KitchenOrder)
- Mark order as picked up (served)
- List orders (filter by status, kitchen status, scope: my orders / outlet-wide)

### 14. Customer Management & Loyalty
- Full CRUD customer (code, name, email, phone, address, birth date, gender)
- Membership levels: regular, silver, gold, platinum
- Loyalty points tracking (total points, total spent, total visits)
- Points earn saat checkout (otomatis)
- Points redemption di POS
- Manual add points
- Membership expiry date
- Transaction history per customer
- Customer search di POS

### 15. Discount & Promo Management
- Tipe diskon: percentage, fixed amount, buy X get Y
- Scope: order level & item level
- Discount code / promo code
- Auto-apply discounts
- Minimum purchase & minimum quantity
- Maximum discount cap
- Usage limit (total & per customer)
- Member-only discounts (by membership level)
- Outlet-specific discounts
- Item-specific discounts
- Date validity (valid from & valid until)
- Validate discount code via API

### 16. Inventory & Stock Management
- **Units**: Full CRUD untuk measurement units (kg, g, L, pcs, pack, dll)
- **Suppliers**: Full CRUD, supplier items catalog (SKU & harga per supplier)
- **Inventory Categories**: Full CRUD dengan category codes
- **Inventory Items**: Full CRUD, linked ke products untuk stock tracking
- **Stock Levels**: Current quantities, min/max/reorder point
- **Stock Movements**: Full movement log & history
- **Low Stock Alerts**: Otomatis detect items di bawah reorder point
- **Expiry Tracking**: Expiring items view & alerts

### 17. Purchase Orders
- Full CRUD purchase orders
- Status workflow: draft -> approved -> sent -> received -> cancelled
- Approve, send to supplier, cancel PO
- Purchase order items dengan quantity & pricing

### 18. Goods Receive
- Full CRUD goods receive (dari purchase order)
- Complete goods receive (auto update stock)
- Cancel goods receive

### 19. Stock Adjustments & Stock Take
- Full CRUD stock adjustments
- Approve / Reject adjustments
- Stock take (physical count)
- Per-outlet stock query

### 20. Stock Transfers (Multi-Outlet)
- Full CRUD stock transfers antar outlet
- Status workflow: draft -> approved -> shipped -> received -> cancelled
- Approve, ship, receive, cancel transfer

### 21. Recipe / Bill of Materials (BOM)
- Full CRUD recipes
- Duplicate recipe
- Recipe items (ingredients dengan quantity)
- Recalculate cost
- Cost analysis report
- Link recipe ke products (auto stock deduction saat checkout)

### 22. Stock Batches
- Batch receiving & management
- Batch settings (FIFO/FEFO/LIFO)
- Expiry report per batch
- Batch detail, adjust, mark expired, dispose

### 23. Waste Logs
- Create waste log entries
- List, view, delete waste logs
- Waste report

### 24. Inventory Reports
- Stock valuation report
- Stock movement report
- COGS (Cost of Goods Sold) report
- Food cost report

### 25. Reporting & Analytics
- Sales summary (daily/weekly/monthly): total sales, transactions, average order value, discount total, refund total
- Sales by category & product
- Sales by payment method
- Sales by hour (peak hour analysis)
- Daily sales breakdown
- Shift report & cash reconciliation
- Session report
- Export ke Excel/PDF

### 26. Offline-First Mobile Sync
- Master sync: full data dump (categories, products, variants, modifiers, payment methods, discounts, floors, tables, outlet settings, subscription features)
- Delta sync: incremental changes since last sync timestamp
- Bulk upload offline transactions
- Sync POS session state
- Customer search offline-first

### 27. Super Admin Panel
- Dashboard overview
- Tenant management (CRUD, switch/impersonate tenants)
- User management (CRUD, outlet assignment)
- Role & permission management
- Subscription plans management (create/edit plans with feature flags JSON)
- Subscriptions management (view all, update status)
- Invoice management (view all, update payment status)
- User PIN management (set/edit/delete PIN)
- Authorization settings & logs

### 28. Public Pages
- Landing page
- Pricing page (live plans dari database)
- Terms of service
- Privacy policy

---

## Subscription Tiers

| Feature | Starter | Growth | Professional | Enterprise |
|---------|:-------:|:------:|:------------:|:----------:|
| **Harga** | Rp 99K | Rp 299K | Rp 599K | Rp 1.499K |
| POS Core | ✅ | ✅ | ✅ | ✅ |
| Outlets | 1 | 3 | 10 | Unlimited |
| Users | 3 | 10 | 25 | Unlimited |
| Products | 100 | 500 | Unlimited | Unlimited |
| Product Variants | ❌ | ✅ | ✅ | ✅ |
| Modifiers | ❌ | ✅ | ✅ | ✅ |
| Product Combos | ❌ | ✅ | ✅ | ✅ |
| Discount & Promo | ❌ | ✅ | ✅ | ✅ |
| Inventory Basic | ❌ | ✅ | ✅ | ✅ |
| Table Management | ❌ | ✅ | ✅ | ✅ |
| Inventory Advanced | ❌ | ❌ | ✅ | ✅ |
| Recipe/BOM | ❌ | ❌ | ✅ | ✅ |
| Stock Transfer | ❌ | ❌ | ✅ | ✅ |
| Manager Authorization | ❌ | ❌ | ✅ | ✅ |
| Export Excel/PDF | ❌ | ❌ | ✅ | ✅ |
| Waiter App | ❌ | ❌ | ✅ | ✅ |
| QR Order | ❌ | ❌ | ✅ | ✅ |
| KDS | ❌ | ❌ | ❌ | ✅ |
| API Access | ❌ | ❌ | ❌ | ✅ |
| Loyalty Points | ❌ | ❌ | ❌ | ✅ |
| Custom Branding | ❌ | ❌ | ❌ | ✅ |
| Dedicated Support | ❌ | ❌ | ❌ | ✅ |
| SLA Uptime | ❌ | ❌ | ❌ | ✅ |

**Trial:** 14 hari full akses Professional tier, tanpa kartu kredit.

---

## API Documentation

### Swagger UI

Interactive API documentation tersedia di:

https://saas.jagofullstack.com/api/documentation

### Authentication

Semua API endpoints menggunakan **Laravel Sanctum** dengan Bearer Token:

```http
Authorization: Bearer {your_api_token}
X-Outlet-Id: {outlet_uuid}
```

### API Versioning

| Version | Base URL | Status |
|---------|----------|--------|
| **v2** | `/api/v2/` | Active (Recommended) |
| v1 | `/api/v1/` | Deprecated |

---

## Mobile App API Reference

API endpoints untuk pengembangan POS Mobile App, Waiter App, dan KDS.

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v2/auth/login` | Login dengan email/password |
| `POST` | `/api/v2/auth/login-pin` | Login dengan PIN (untuk staff) |
| `POST` | `/api/v2/auth/logout` | Logout & revoke token |
| `GET` | `/api/v2/auth/me` | Get current user profile |

### Master Data Sync

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/sync/master` | Sync all master data (products, categories, modifiers, payment methods, floors, tables) |
| `GET` | `/api/v2/sync/delta?since={timestamp}` | Get changes since last sync |

**Response Structure `/api/v2/sync/master`:**
```json
{
  "data": {
    "products": [
      {
        "id": "uuid",
        "sku": "PRD001",
        "barcode": "8991234567890",
        "name": "Nasi Goreng Spesial",
        "image": "https://...",
        "category_id": "uuid",
        "category_name": "Main Course",
        "category_color": "#FF5733",
        "product_type": "single|variant|combo",
        "base_price": 35000,
        "price": 35000,
        "cost_price": 15000,
        "allow_notes": true,
        "has_variants": false,
        "has_modifiers": true,
        "is_combo": false,
        "variants": [],
        "modifier_groups": [
          {
            "id": "uuid",
            "name": "Topping",
            "display_name": "Pilih Topping",
            "selection_type": "single|multiple",
            "min_selections": 0,
            "max_selections": 3,
            "is_required": false,
            "modifiers": [
              {
                "id": "uuid",
                "name": "telur_ceplok",
                "display_name": "Telur Ceplok",
                "price": 5000,
                "is_default": false
              }
            ]
          }
        ],
        "combo_items": []
      }
    ],
    "categories": [...],
    "payment_methods": [...],
    "tables": [...],
    "customers": [...]
  },
  "synced_at": "2024-02-12T10:00:00Z"
}
```

### Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/settings` | Get all settings (bundled) |
| `GET` | `/api/v2/settings/outlet` | Get outlet settings |
| `GET` | `/api/v2/settings/pos` | Get POS operational settings |
| `GET` | `/api/v2/settings/features` | Get feature flags based on subscription |
| `GET` | `/api/v2/settings/authorization` | Get authorization settings (which actions need manager PIN) |
| `GET` | `/api/v2/settings/receipt` | Get receipt settings (header, footer, logo) |
| `GET` | `/api/v2/settings/printer` | Get printer settings |
| `GET` | `/api/v2/settings/subscription` | Get subscription info |

**Tax Mode Explanation:**
| Mode | Description | Calculation |
|------|-------------|-------------|
| `exclusive` | Tax added on top of price | `total = subtotal + (subtotal x tax%)` |
| `inclusive` | Price already includes tax | `tax = amount x (rate / (100 + rate))` |

### POS Sessions

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/sessions/current` | Get current active session |
| `POST` | `/api/v2/sessions/open` | Open new POS session |
| `POST` | `/api/v2/sessions/close` | Close current session |
| `GET` | `/api/v2/sessions/history` | Get session history |
| `GET` | `/api/v2/sessions/{id}/report` | Get session report |

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/orders` | List orders for current outlet |
| `POST` | `/api/v2/orders/calculate` | Calculate cart totals (preview) |
| `POST` | `/api/v2/orders/checkout` | Create order (checkout) |
| `GET` | `/api/v2/orders/{id}` | Get order detail |
| `POST` | `/api/v2/orders/{id}/void` | Void order |
| `POST` | `/api/v2/orders/{id}/refund` | Refund order |
| `GET` | `/api/v2/orders/{id}/receipt` | Get receipt data |

### Cash Drawer

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/cash-drawer/status` | Get cash drawer status |
| `GET` | `/api/v2/cash-drawer/logs` | Get cash drawer logs |
| `POST` | `/api/v2/cash-drawer/in` | Cash in |
| `POST` | `/api/v2/cash-drawer/out` | Cash out |
| `GET` | `/api/v2/cash-drawer/balance` | Get current balance |
| `POST` | `/api/v2/cash-drawer/open` | Open cash drawer |

### Held Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/held-orders` | List held orders |
| `POST` | `/api/v2/held-orders` | Create held order |
| `GET` | `/api/v2/held-orders/{id}` | Get held order detail |
| `POST` | `/api/v2/held-orders/{id}/recall` | Recall held order |
| `DELETE` | `/api/v2/held-orders/{id}` | Delete held order |

### KDS (Kitchen Display System)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v2/kds/auth/login` | KDS PIN login |
| `GET` | `/api/v2/kds/orders` | List kitchen orders (filter by status, station) |
| `GET` | `/api/v2/kds/orders/{id}` | Get kitchen order detail |
| `POST` | `/api/v2/kds/orders/{id}/start` | Start preparing |
| `POST` | `/api/v2/kds/orders/{id}/ready` | Mark order ready |
| `POST` | `/api/v2/kds/orders/{id}/served` | Mark order served |
| `POST` | `/api/v2/kds/orders/{id}/cancel` | Cancel order with reason |
| `POST` | `/api/v2/kds/orders/{id}/recall` | Recall served order |
| `POST` | `/api/v2/kds/orders/{id}/bump` | Bump to next status |
| `POST` | `/api/v2/kds/orders/{id}/priority` | Set priority (normal/rush/vip) |
| `POST` | `/api/v2/kds/orders/{id}/items/{item}/start` | Start individual item |
| `POST` | `/api/v2/kds/orders/{id}/items/{item}/ready` | Mark individual item ready |
| `GET` | `/api/v2/kds/stations` | List kitchen stations |
| `GET` | `/api/v2/kds/stats` | KDS statistics |

### Waiter App

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v2/waiter/auth/login` | Waiter PIN login |
| `POST` | `/api/v2/waiter/auth/logout` | Waiter logout |
| `GET` | `/api/v2/waiter/floors` | List floors |
| `GET` | `/api/v2/waiter/tables` | List tables (filter by status, floor) |
| `GET` | `/api/v2/waiter/tables/{id}` | Get table detail with current order |
| `POST` | `/api/v2/waiter/tables/{id}/open` | Open table (start session) |
| `POST` | `/api/v2/waiter/tables/{id}/close` | Close table (end session) |
| `PUT` | `/api/v2/waiter/tables/{id}/status` | Update table status |
| `GET` | `/api/v2/waiter/menu` | List menu (filter by category, search) |
| `GET` | `/api/v2/waiter/categories` | List categories |
| `GET` | `/api/v2/waiter/orders` | List orders (filter by status, kitchen status) |
| `GET` | `/api/v2/waiter/orders/{id}` | Get order detail |
| `POST` | `/api/v2/waiter/orders` | Create new order |
| `POST` | `/api/v2/waiter/orders/{id}/items` | Add items to existing order |
| `POST` | `/api/v2/waiter/orders/{id}/send` | Send order to kitchen |
| `POST` | `/api/v2/waiter/orders/{id}/pickup` | Mark order as picked up |

### Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/reports/sales-summary` | Sales summary |
| `GET` | `/api/v2/reports/sales-by-payment` | Sales by payment method |
| `GET` | `/api/v2/reports/sales-by-category` | Sales by category |
| `GET` | `/api/v2/reports/sales-by-product` | Sales by product |
| `GET` | `/api/v2/reports/hourly-sales` | Hourly sales |
| `GET` | `/api/v2/reports/daily-sales` | Daily sales |

### Inventory (Growth+ Tier)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/inventory/items` | List inventory items |
| `GET` | `/api/v2/inventory/items/{id}` | Get item detail |
| `GET` | `/api/v2/inventory/items/{id}/movements` | Get item movement history |
| `GET` | `/api/v2/inventory/stock-levels` | Get stock levels |
| `GET` | `/api/v2/inventory/products/{id}/stock` | Get product stock |
| `POST` | `/api/v2/inventory/adjustments` | Create stock adjustment |
| `GET` | `/api/v2/inventory/low-stock` | Get low stock alerts |
| `GET` | `/api/v2/inventory/history` | Get stock history |

### Subscription

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/subscription` | Get current subscription |
| `POST` | `/api/v2/subscription/trial` | Start trial |
| `POST` | `/api/v2/subscription/subscribe` | Subscribe to plan |
| `POST` | `/api/v2/subscription/upgrade` | Upgrade plan |
| `POST` | `/api/v2/subscription/cancel` | Cancel subscription |
| `POST` | `/api/v2/subscription/reactivate` | Reactivate subscription |
| `GET` | `/api/v2/subscription/upgrade-proration` | Calculate upgrade proration |
| `GET` | `/api/v2/subscription/invoices` | List invoices |
| `GET` | `/api/v2/subscription/features` | Check features per plan |

### Floor & Table Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/floors` | List floors |
| `POST` | `/api/v2/floors` | Create floor |
| `GET` | `/api/v2/floors/{id}/tables` | Get tables per floor |
| `GET` | `/api/v2/tables` | List all tables |
| `POST` | `/api/v2/tables` | Create table |
| `PUT` | `/api/v2/tables/{id}/position` | Update table position (drag-drop) |
| `PUT` | `/api/v2/tables/{id}/status` | Update table status |
| `POST` | `/api/v2/tables/{id}/open` | Open table (start session) |
| `POST` | `/api/v2/tables/{id}/close` | Close table (end session) |
| `POST` | `/api/v2/tables/{id}/move` | Move table |

---

## Design System

Panduan design untuk memastikan konsistensi UI/UX antara Web dan Mobile App.

### Color Palette

#### Primary Colors
| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Primary** | `#3B82F6` | `59, 130, 246` | Buttons, links, active states |
| **Primary Dark** | `#1D4ED8` | `29, 78, 216` | Hover states |
| **Primary Light** | `#DBEAFE` | `219, 234, 254` | Backgrounds, badges |

#### Secondary Colors
| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Secondary** | `#6B7280` | `107, 114, 128` | Secondary text, icons |
| **Secondary Dark** | `#374151` | `55, 65, 81` | Dark mode elements |
| **Secondary Light** | `#F3F4F6` | `243, 244, 246` | Backgrounds |

#### Status Colors
| Status | Hex | Usage |
|--------|-----|-------|
| **Success** | `#10B981` | Success messages, completed orders |
| **Warning** | `#F59E0B` | Warnings, pending states |
| **Error** | `#EF4444` | Errors, void, cancel |
| **Info** | `#3B82F6` | Information, tips |

#### Order Status Colors
| Status | Hex | Background |
|--------|-----|------------|
| `pending` | `#F59E0B` | `#FEF3C7` |
| `processing` | `#3B82F6` | `#DBEAFE` |
| `ready` | `#10B981` | `#D1FAE5` |
| `completed` | `#6B7280` | `#F3F4F6` |
| `voided` | `#EF4444` | `#FEE2E2` |

#### Table Status Colors
| Status | Hex | Background |
|--------|-----|------------|
| `available` | `#10B981` | `#D1FAE5` |
| `occupied` | `#EF4444` | `#FEE2E2` |
| `reserved` | `#8B5CF6` | `#EDE9FE` |
| `billing` | `#F59E0B` | `#FEF3C7` |
| `cleaning` | `#6B7280` | `#F3F4F6` |

### Typography

#### Font Family
| Platform | Font |
|----------|------|
| Web | `Inter, system-ui, sans-serif` |
| Mobile (iOS) | `SF Pro Text` |
| Mobile (Android) | `Roboto` |

#### Font Sizes
| Name | Size | Line Height | Usage |
|------|------|-------------|-------|
| `xs` | 12px | 16px | Captions, labels |
| `sm` | 14px | 20px | Body small, table cells |
| `base` | 16px | 24px | Body text |
| `lg` | 18px | 28px | Subheadings |
| `xl` | 20px | 28px | Headings |
| `2xl` | 24px | 32px | Page titles |
| `3xl` | 30px | 36px | Large displays |
| `4xl` | 36px | 40px | Hero text |

#### Font Weights
| Name | Weight | Usage |
|------|--------|-------|
| `normal` | 400 | Body text |
| `medium` | 500 | Buttons, labels |
| `semibold` | 600 | Headings, emphasis |
| `bold` | 700 | Strong emphasis |

### Spacing

Menggunakan 4px base unit:

| Name | Value | Usage |
|------|-------|-------|
| `0` | 0px | - |
| `1` | 4px | Tight spacing |
| `2` | 8px | Small gaps |
| `3` | 12px | Default padding |
| `4` | 16px | Card padding |
| `5` | 20px | Section spacing |
| `6` | 24px | Large gaps |
| `8` | 32px | Section margins |
| `10` | 40px | Large sections |
| `12` | 48px | Page margins |

### Border Radius

| Name | Value | Usage |
|------|-------|-------|
| `none` | 0px | - |
| `sm` | 4px | Small elements |
| `DEFAULT` | 8px | Buttons, inputs |
| `md` | 12px | Cards |
| `lg` | 16px | Modals |
| `xl` | 24px | Large cards |
| `full` | 9999px | Pills, avatars |

### Shadows

| Name | CSS Value | Usage |
|------|-----------|-------|
| `sm` | `0 1px 2px rgba(0,0,0,0.05)` | Subtle elevation |
| `DEFAULT` | `0 1px 3px rgba(0,0,0,0.1)` | Cards |
| `md` | `0 4px 6px rgba(0,0,0,0.1)` | Dropdowns |
| `lg` | `0 10px 15px rgba(0,0,0,0.1)` | Modals |
| `xl` | `0 20px 25px rgba(0,0,0,0.1)` | Popovers |

### Components

#### Buttons
```
Primary:    bg-blue-500 text-white hover:bg-blue-600
Secondary:  bg-gray-100 text-gray-700 hover:bg-gray-200
Success:    bg-green-500 text-white hover:bg-green-600
Danger:     bg-red-500 text-white hover:bg-red-600
Outline:    border border-gray-300 text-gray-700 hover:bg-gray-50
```

#### Input Fields
```
Default:    border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500
Error:      border-red-500 focus:ring-red-500
Disabled:   bg-gray-100 cursor-not-allowed
```

#### Cards
```
Default:    bg-white rounded-xl shadow-sm border border-gray-200 p-4
Hover:      hover:shadow-md transition-shadow
Selected:   ring-2 ring-blue-500
```

### Icons

Menggunakan **Heroicons** (https://heroicons.com):
- Outline variant untuk navigation
- Solid variant untuk actions dan status

### Mobile App Guidelines

#### Screen Structure
```
+-----------------------------+
|  Status Bar (System)        |
+-----------------------------+
|  App Bar / Header           |  48-56dp
+-----------------------------+
|                             |
|  Content Area               |
|                             |
|                             |
+-----------------------------+
|  Bottom Navigation          |  56dp
+-----------------------------+
```

#### Touch Targets
- Minimum touch target: 48x48dp
- Recommended spacing between targets: 8dp

#### Loading States
- Use skeleton loaders for content
- Use spinner for actions
- Always show loading indicator after 200ms

#### Error Handling
- Show inline errors for form validation
- Show toast/snackbar for API errors
- Show full-screen error for critical failures

---

## Database Structure

### Core (5 tables)
- `tenants` - Data tenant/brand
- `outlets` - Data outlet per tenant
- `users` - Data user dengan role
- `roles` & `permissions` - RBAC system

### Auth & PIN (2 tables)
- `user_pins` - PIN untuk akses cepat
- `pin_attempts` - Failed PIN attempt tracking

### Products (11 tables)
- `product_categories` - Kategori produk
- `products` - Data produk
- `product_variants` - Variant produk
- `variant_groups` & `variant_options` - Variant group management
- `product_outlets` - Product-outlet assignment & pricing
- `modifier_groups` & `modifiers` - Add-on produk
- `combos` & `combo_items` - Combo/bundle products
- `prices` - Price management

### Transactions (7 tables)
- `transactions` - Data transaksi
- `transaction_items` - Item dalam transaksi
- `transaction_payments` - Data pembayaran (split payment)
- `transaction_discounts` - Applied discounts
- `pos_sessions` - Shift kasir
- `cash_drawer_logs` - Cash in/out logs
- `held_orders` - Held/pending orders

### Kitchen (3 tables)
- `kitchen_orders` - Kitchen order queue
- `kitchen_order_items` - Items per kitchen order
- `kitchen_stations` - Station dapur (Grill, Fry, Cold, dll)

### Operations (3 tables)
- `floors` - Lantai/area restoran
- `tables` - Meja per lantai
- `table_sessions` - Session per meja (tracking occupancy)

### Customer & Loyalty (2 tables)
- `customers` - Data pelanggan & membership
- `customer_points` - Point transactions

### Pricing & Discounts (2 tables)
- `payment_methods` - Metode pembayaran
- `discounts` - Diskon & promo

### Inventory (9 tables)
- `inventory_categories` - Kategori inventory
- `inventory_items` - Item inventory
- `inventory_stocks` - Current stock levels
- `stock_movements` - Stock movement log
- `stock_batches` & `stock_batch_movements` - Batch tracking
- `units` - Measurement units
- `suppliers` & `supplier_items` - Supplier management

### Purchasing (4 tables)
- `purchase_orders` & `purchase_order_items` - Purchase orders
- `goods_receives` & `goods_receive_items` - Goods receive

### Stock Operations (5 tables)
- `stock_adjustments` & `stock_adjustment_items` - Stock adjustments
- `stock_transfers` & `stock_transfer_items` - Inter-outlet transfers
- `waste_logs` - Waste tracking

### Recipe (2 tables)
- `recipes` & `recipe_items` - Bill of Materials

### Subscription (3 tables)
- `subscription_plans` - Available plans
- `subscriptions` - Active subscriptions
- `subscription_invoices` - Payment invoices

### Authorization (3 tables)
- `authorization_settings` - Configurable actions
- `authorization_logs` - Audit trail
- `batch_settings` - Batch configuration

---

## Installation

### System Requirements

| Software | Minimum | Recommended |
|----------|---------|-------------|
| PHP | 8.2 | 8.3+ |
| Composer | 2.0 | Latest |
| Node.js | 18.x | 20.x LTS |
| npm | 9.x | 10.x |
| MySQL | 8.0 | 8.0+ |
| Redis | 6.x | 7.x |

### PHP Extensions Required

```
php-mbstring, php-xml, php-curl, php-mysql, php-redis,
php-bcmath, php-json, php-zip, php-gd, php-fileinfo
```

### Quick Setup

```bash
# Clone repository
git clone <repository-url>
cd ultimate-pos-saas

# One-liner setup
composer setup

# Or manual setup:
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build

# Run development server
composer run dev
```

### Environment Configuration

```env
APP_NAME="Ultimate POS"
APP_ENV=production
APP_URL=https://saas.jagofullstack.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=ultimate_pos_saas
DB_USERNAME=pos_user
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Swagger
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_CONST_HOST="${APP_URL}"
```

---

## Development Commands

```bash
# Development server
composer run dev

# Testing
php artisan test                    # Run all tests
php artisan test --filter=TestName  # Run specific test
php artisan test --compact          # Compact output

# Code formatting
vendor/bin/pint                     # Format all PHP files
vendor/bin/pint --dirty             # Format changed files only

# Database
php artisan migrate                 # Run migrations
php artisan migrate:fresh --seed    # Reset & seed
php artisan db:seed                 # Run seeders

# Cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# API Documentation
php artisan l5-swagger:generate     # Generate Swagger docs

# Logs
php artisan pail                    # Real-time log viewer

# Subscription Management
php artisan subscriptions:process-statuses       # Process trial/subscription lifecycle
php artisan subscriptions:send-trial-reminders   # Send trial expiry reminders
php artisan subscription:send-expiry-reminders   # Send subscription expiry reminders
php artisan subscription:process-frozen          # Process frozen accounts
php artisan pos:reset-floor                      # Reset floor/kitchen state (demo)
```

---

## API Error Codes

| HTTP Code | Error | Description |
|-----------|-------|-------------|
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthenticated | Missing or invalid token |
| 403 | Forbidden | Insufficient permissions or frozen account |
| 404 | Not Found | Resource not found |
| 422 | Validation Error | Request validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal server error |

**Error Response Format:**
```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

---

## Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Create Pull Request

### Commit Message Format

```
type(scope): description

Types: feat, fix, docs, style, refactor, test, chore
Example: feat(api): add tax mode support for orders
```

---

## License

Proprietary - All rights reserved.

---

## Support

- **Documentation**: https://saas.jagofullstack.com/api/documentation
- **Issues**: GitHub Issues
- **Email**: support@jagofullstack.com
