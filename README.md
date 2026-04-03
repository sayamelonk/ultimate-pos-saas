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
| **Payment** | Xendit | - |

---

## Platform Overview

| Platform | Technology | Description |
|----------|------------|-------------|
| **Web Admin** | Laravel + Blade | Dashboard, master data, reporting, settings |
| **POS Web** | Laravel + Alpine.js | Browser-based POS for cashier |
| **POS Mobile** | Flutter | Native mobile POS app |
| **Waiter App** | Flutter | Order taking, table management |
| **KDS** | Web (PWA) | Kitchen Display System |
| **QR Order** | Web (PWA) | Customer self-order |

---

## Daftar Fitur

### 1. Multi-Tenancy & Outlet Management
- Arsitektur multi-tenant (mendukung banyak brand/franchise)
- Manajemen tenant (brand, logo, kontak, subscription plan)
- Manajemen outlet per tenant (alamat, jam operasional, koordinat GPS)
- Pengaturan pajak & service charge per outlet
- **Tax Mode**: Inclusive (harga sudah termasuk pajak) & Exclusive (pajak ditambahkan)
- Kustomisasi receipt (header, footer, logo)

### 2. User & Access Control
- Multi-role: Super Admin, Tenant Owner, Outlet Manager, Cashier, Waiter, Kitchen Staff
- Custom roles per tenant
- Fine-grained permissions per modul
- PIN login untuk akses cepat POS
- User-outlet assignment

### 3. Inventory & Stock Management
- Manajemen unit (kg, g, L, pcs, pack, dll)
- Manajemen supplier & pricing
- Kategori inventory (bahan baku, semi-finished, packaging)
- Tracking stock level (min, max, reorder point)
- Expiry tracking
- Recipe/BOM (Bill of Materials) - auto stock deduction

### 4. Product & Menu Management
- Produk single, variant, dan combo/bundle
- Kategori hierarki dengan icon & color
- Variant groups (Size, Ice Level, Temperature)
- Modifier/add-on (Extra cheese, toppings, dll)
- Harga berbeda per outlet (Product-Outlet Assignment)
- Kitchen station assignment

### 5. POS Core - Order & Transaction
- Shift management (open/close, cash in/out)
- Order types: dine-in, takeaway, delivery, QR order
- Multi-item order dengan variant & modifier
- Discount per order/item (persentase/nominal)
- Promo code support
- **Tax Calculation**: Support inclusive & exclusive mode
- **Service Charge**: Configurable per outlet
- Order status tracking real-time
- Void/cancel dengan reason
- Held orders dengan auto-expiry

### 6. Payment Management
- Multi-payment method (Cash, Card, E-Wallet, QRIS, Transfer)
- Split payment
- Payment gateway integration (Xendit)
- Refund management
- Digital receipt (print, WhatsApp)

### 7. Table Management & Floor Plan
- Multi-floor support
- Drag-drop floor plan editor
- Table status (available, occupied, reserved, billing, cleaning)
- Table merge & transfer
- Reservasi meja
- QR code unik per meja

### 8. Kitchen Display System (KDS)
- Multi kitchen station (Bar, Food, Dessert)
- Real-time order queue
- Color coding berdasarkan waktu tunggu
- Bump system (mark as ready)
- Priority/rush order flag
- Sound alert untuk new order
- Thermal printer support (ESC/POS)

### 9. QR Order (Self-Order Customer)
- Scan QR untuk auto detect outlet & meja
- Browsing menu dengan gambar & deskripsi
- Pilih variant & modifier
- Cart management
- Order tracking real-time
- Call waiter / request bill

### 10. Waiter App (Mobile)
- PIN-based login
- Visual table status dengan timer
- Order taking langsung dari meja
- Kirim order ke kitchen
- Split bill (equal, by item, custom)
- Real-time notification (order ready, customer call)
- Offline mode dengan auto-sync

### 11. Reporting & Analytics
- Sales summary (daily/weekly/monthly)
- Sales by category & product
- Sales by payment method
- Sales by hour (peak hour analysis)
- Sales by staff
- Shift report & cash reconciliation
- Discount & void report
- Table turnover analysis
- Export ke Excel/PDF

---

## Subscription Tiers

| Feature | Starter | Growth | Professional | Enterprise |
|---------|:-------:|:------:|:------------:|:----------:|
| **Price** | Rp 99K | Rp 299K | Rp 599K | Rp 1.499K |
| POS Core | ✅ | ✅ | ✅ | ✅ |
| Outlets | 1 | 3 | 10 | Unlimited |
| Users | 3 | 10 | 25 | Unlimited |
| Products | 100 | 500 | Unlimited | Unlimited |
| Product Variants | ❌ | ✅ | ✅ | ✅ |
| Modifiers | ❌ | ✅ | ✅ | ✅ |
| Product Combos | ❌ | ✅ | ✅ | ✅ |
| Inventory Basic | ❌ | ✅ | ✅ | ✅ |
| Inventory Advanced | ❌ | ❌ | ✅ | ✅ |
| Recipe/BOM | ❌ | ❌ | ✅ | ✅ |
| Waiter App | ❌ | ❌ | ✅ | ✅ |
| QR Order | ❌ | ❌ | ✅ | ✅ |
| KDS | ❌ | ❌ | ❌ | ✅ |
| API Access | ❌ | ❌ | ❌ | ✅ |

**Trial:** 14 hari full akses Professional tier, tanpa kartu kredit.

---

## API Documentation

### Swagger UI

Interactive API documentation tersedia di:

**🔗 https://saas.jagofullstack.com/api/documentation**

### Authentication

Semua API endpoints menggunakan **Laravel Sanctum** dengan Bearer Token:

```http
Authorization: Bearer {your_api_token}
X-Outlet-Id: {outlet_uuid}
```

### API Versioning

| Version | Base URL | Status |
|---------|----------|--------|
| **v2** | `/api/v2/` | ✅ Active (Recommended) |
| v1 | `/api/v1/` | ⚠️ Deprecated |

---

## Mobile App API Reference

API endpoints untuk pengembangan POS Mobile App dan Waiter App.

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
| `GET` | `/api/v2/sync/master` | Sync all master data (products, categories, modifiers, payment methods) |
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

**Response Structure `/api/v2/settings/outlet`:**
```json
{
  "data": {
    "outlet_id": "uuid",
    "outlet_name": "Main Store",
    "outlet_code": "MAIN",
    "address": "Jl. Sudirman No. 123",
    "city": "Jakarta",
    "phone": "021-1234567",
    "email": "main@store.com",
    "tax_enabled": true,
    "tax_mode": "exclusive",
    "tax_percentage": 10.0,
    "service_charge_enabled": true,
    "service_charge_percentage": 5.0,
    "opening_time": "08:00",
    "closing_time": "22:00",
    "currency": "IDR",
    "timezone": "Asia/Jakarta"
  }
}
```

**Tax Mode Explanation:**
| Mode | Description | Calculation |
|------|-------------|-------------|
| `exclusive` | Tax added on top of price | `total = subtotal + (subtotal × tax%)` |
| `inclusive` | Price already includes tax | `subtotal = total / (1 + tax%)` |

### POS Sessions

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/sessions/current` | Get current active session |
| `POST` | `/api/v2/sessions/open` | Open new POS session |
| `POST` | `/api/v2/sessions/close` | Close current session |
| `GET` | `/api/v2/sessions/history` | Get session history |
| `GET` | `/api/v2/sessions/{id}/report` | Get session report |

**Open Session Request:**
```json
{
  "opening_cash": 500000,
  "notes": "Opening shift pagi"
}
```

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

**Calculate Cart Request:**
```json
{
  "items": [
    {
      "product_id": "uuid",
      "variant_id": null,
      "quantity": 2,
      "modifiers": [
        {
          "id": "uuid",
          "name": "Extra Cheese",
          "price": 5000,
          "quantity": 1
        }
      ],
      "discount_amount": 0,
      "notes": "Pedas level 2"
    }
  ],
  "discount_type": "percentage",
  "discount_value": 10
}
```

**Calculate Cart Response:**
```json
{
  "data": {
    "items": [...],
    "items_count": 2,
    "subtotal": 90000,
    "discount_amount": 9000,
    "after_discount": 81000,
    "tax_enabled": true,
    "tax_mode": "exclusive",
    "tax_percentage": 10.0,
    "tax_amount": 8100,
    "service_charge_enabled": true,
    "service_charge_percentage": 5.0,
    "service_charge_amount": 4050,
    "rounding": -50,
    "grand_total": 93100
  }
}
```

**Checkout Request:**
```json
{
  "items": [...],
  "order_type": "dine_in",
  "table_id": "uuid",
  "customer_id": "uuid",
  "discount_type": "percentage",
  "discount_value": 10,
  "payments": [
    {
      "payment_method_id": "uuid",
      "amount": 100000,
      "reference_number": null
    }
  ],
  "notes": "Customer request"
}
```

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
| `GET` | `/api/v2/inventory/stock-levels` | Get stock levels |
| `GET` | `/api/v2/inventory/products/{id}/stock` | Get product stock |
| `POST` | `/api/v2/inventory/adjustments` | Create stock adjustment |
| `GET` | `/api/v2/inventory/low-stock` | Get low stock alerts |
| `GET` | `/api/v2/inventory/history` | Get stock history |

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
┌─────────────────────────────┐
│  Status Bar (System)        │
├─────────────────────────────┤
│  App Bar / Header           │  48-56dp
├─────────────────────────────┤
│                             │
│  Content Area               │
│                             │
│                             │
├─────────────────────────────┤
│  Bottom Navigation          │  56dp
└─────────────────────────────┘
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
```

---

## Database Structure

### Core Entities
- `tenants` - Data tenant/brand
- `outlets` - Data outlet per tenant
- `users` - Data user dengan role
- `roles` & `permissions` - RBAC system

### Products
- `product_categories` - Kategori produk
- `products` - Data produk
- `product_variants` - Variant produk
- `product_outlets` - Product-outlet assignment & pricing
- `modifier_groups` & `modifiers` - Add-on produk
- `combos` & `combo_items` - Combo/bundle products

### Transactions
- `transactions` - Data transaksi
- `transaction_items` - Item dalam transaksi
- `transaction_payments` - Data pembayaran
- `pos_sessions` - Shift kasir
- `cash_drawer_logs` - Cash in/out logs
- `held_orders` - Held/pending orders

### Operations
- `floors` & `tables` - Manajemen meja
- `kitchen_stations` - Station dapur
- `reservations` - Reservasi meja

---

## API Error Codes

| HTTP Code | Error | Description |
|-----------|-------|-------------|
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthenticated | Missing or invalid token |
| 403 | Forbidden | Insufficient permissions |
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
