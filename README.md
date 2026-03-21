# Ultimate POS SaaS

Sistem Point of Sale (POS) berbasis SaaS dengan arsitektur multi-tenant untuk restoran, cafe, dan F&B business.

## Tech Stack

-   **Backend:** Laravel 12, PHP 8.3
-   **Database:** MySQL/PostgreSQL
-   **Frontend Admin:** Blade + Tailwind CSS
-   **Mobile App:** Flutter (Waiter App & POS App)
-   **Real-time:** WebSocket (Laravel Reverb/Pusher)
-   **Payment Gateway:** Xendit, Midtrans

## Daftar Fitur

### 1. Multi-Tenancy & Outlet Management

-   Arsitektur multi-tenant (mendukung banyak brand/franchise)
-   Manajemen tenant (brand, logo, kontak, subscription plan)
-   Manajemen outlet per tenant (alamat, jam operasional, koordinat GPS)
-   Pengaturan pajak & service charge per outlet
-   Kustomisasi receipt (header, footer, logo)

### 2. User & Access Control

-   Multi-role: Super Admin, Tenant Owner, Outlet Manager, Cashier, Waiter, Kitchen Staff
-   Custom roles per tenant
-   Fine-grained permissions per modul
-   PIN login untuk akses cepat POS
-   User-outlet assignment

### 3. Inventory & Stock Management

-   Manajemen unit (kg, g, L, pcs, pack, dll)
-   Manajemen supplier & pricing
-   Kategori inventory (bahan baku, semi-finished, packaging)
-   Tracking stock level (min, max, reorder point)
-   Expiry tracking
-   Recipe/BOM (Bill of Materials) - auto stock deduction

### 4. Product & Menu Management

-   Produk single, variant, dan combo/bundle
-   Kategori hierarki dengan icon & color
-   Variant groups (Size, Ice Level, Temperature)
-   Modifier/add-on (Extra cheese, toppings, dll)
-   Harga berbeda per outlet
-   Kitchen station assignment

### 5. POS Core - Order & Transaction

-   Shift management (open/close, cash in/out)
-   Order types: dine-in, takeaway, delivery, QR order
-   Multi-item order dengan variant & modifier
-   Discount per order/item (persentase/nominal)
-   Promo code support
-   Tax & service charge calculation
-   Order status tracking real-time
-   Void/cancel dengan reason

### 6. Payment Management

-   Multi-payment method (Cash, Card, E-Wallet, QRIS, Transfer)
-   Split payment
-   Payment gateway integration (Xendit, Midtrans)
-   Refund management
-   Digital receipt (print, WhatsApp)

### 7. Table Management & Floor Plan

-   Multi-floor support
-   Drag-drop floor plan editor
-   Table status (available, occupied, reserved, billing, cleaning)
-   Table merge & transfer
-   Reservasi meja
-   QR code unik per meja

### 8. Kitchen Display System (KDS)

-   Multi kitchen station (Bar, Food, Dessert)
-   Real-time order queue
-   Color coding berdasarkan waktu tunggu
-   Bump system (mark as ready)
-   Priority/rush order flag
-   Sound alert untuk new order
-   Thermal printer support (ESC/POS)

### 9. QR Order (Self-Order Customer)

-   Scan QR untuk auto detect outlet & meja
-   Browsing menu dengan gambar & deskripsi
-   Pilih variant & modifier
-   Cart management
-   Order tracking real-time
-   Call waiter / request bill

### 10. Waiter App (Mobile)

-   PIN-based login
-   Visual table status dengan timer
-   Order taking langsung dari meja
-   Kirim order ke kitchen
-   Split bill (equal, by item, custom)
-   Real-time notification (order ready, customer call)
-   Offline mode dengan auto-sync

### 11. Reporting & Analytics

-   Sales summary (daily/weekly/monthly)
-   Sales by category & product
-   Sales by payment method
-   Sales by hour (peak hour analysis)
-   Sales by staff
-   Shift report & cash reconciliation
-   Discount & void report
-   Table turnover analysis
-   Export ke Excel/PDF

### 12. Security & Technical

-   UUID-based primary keys
-   Role-based access control
-   Password hashing & PIN encryption
-   Tenant data isolation
-   RESTful API
-   WebSocket untuk real-time updates

## Platform

| Platform   | Teknologi       | Fungsi                            |
| ---------- | --------------- | --------------------------------- |
| Web Admin  | Laravel + Blade | Dashboard, master data, reporting |
| Waiter App | Flutter         | Order taking, table management    |
| POS App    | Flutter         | Kasir, pembayaran                 |
| KDS        | Web             | Kitchen display system            |
| QR Order   | Web (PWA)       | Customer self-order               |

## Instalasi

### Requirements

-   PHP 8.3+
-   Composer
-   Node.js 18+
-   MySQL 8.0+ / PostgreSQL 15+

### Setup

```bash
# Clone repository
git clone <repository-url>
cd ultimate-pos-saas

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Setup database
php artisan migrate --seed

# Build assets
npm run build

# Start development server
php artisan serve
```

### Development

```bash
# Run with hot reload
npm run dev

# Run tests
php artisan test

# Code formatting
vendor/bin/pint
```

## Struktur Database

### Core Entities

-   `tenants` - Data tenant/brand
-   `outlets` - Data outlet per tenant
-   `users` - Data user dengan role
-   `roles` & `permissions` - RBAC system

### Inventory

-   `inventory_categories` - Kategori bahan baku
-   `inventory_items` - Item inventory
-   `suppliers` - Data supplier
-   `recipes` - Resep/BOM produk

### Products

-   `categories` - Kategori produk
-   `products` - Data produk
-   `variant_groups` & `variant_options` - Variant produk
-   `modifier_groups` & `modifiers` - Add-on produk

### Orders

-   `orders` - Data order
-   `order_items` - Item dalam order
-   `payments` - Data pembayaran
-   `discounts` - Master diskon

### Operations

-   `floors` & `tables` - Manajemen meja
-   `shifts` - Data shift kasir
-   `kitchen_stations` - Station dapur
-   `kitchen_queues` - Antrian dapur

## API Documentation

API documentation tersedia di `/api/documentation` setelah aplikasi berjalan.

## Contributing

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## License

Proprietary - All rights reserved.
