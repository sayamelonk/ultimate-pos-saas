# 📚 Ultimate POS SaaS - Testing Guide

> Complete testing documentation for Ultimate POS SaaS Multi-Merchant project

## Table of Contents

1. [Quick Start](#quick-start)
2. [Project Setup](#project-setup)
3. [Running Multiple Projects](#running-multiple-projects)
4. [Testing the POS Application](#testing-the-pos-application)
5. [Testing Common Features](#testing-common-features)
6. [Troubleshooting](#troubleshooting)
7. [Pre-Production Checklist](#pre-production-checklist)

---

## Quick Start

### Access URLs

| Project | URL | Database | Session Cookie |
|---------|-----|----------|----------------|
| **Main Project** | http://localhost:8000 | ultimate-pos-saas-db | ultimate_pos_current_session |
| **Master-2** | http://localhost:8001 | ultimate-pos-saas-master2-db | ultimate_pos_master2_session |

### Demo Credentials

Both projects use the same database credentials:

```
Email: superadmin@ultimatepos.com
Password: password
```

**Other Demo Users:**
- owner@demo.com
- manager@demo.com
- cashier@demo.com
- kitchen@demo.com
- waiter@demo.com (master-2 only)

---

## Project Setup

### Initial Setup (First Time)

If you just cloned the repository, follow these steps:

```bash
# 1. Navigate to project
cd "/Users/rikihikmianto/FlutterProjects/POS SaaS Multi Merchant - Bangun Dari Nol hingga Rilis di Play Store/ultimate-pos-saas"

# 2. Install PHP dependencies
composer install --no-interaction

# 3. Install NPM dependencies
npm install

# 4. Copy environment file
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Run database migrations
php artisan migrate:fresh --seed

# 7. Clear caches
php artisan optimize:clear
```

### After Git Pull (Updating Existing Project)

```bash
# 1. Pull latest changes
git pull origin main

# 2. Install/update dependencies
composer install
npm install

# 3. Clear caches
php artisan optimize:clear

# 4. Run any new migrations
php artisan migrate

# 5. Clear config cache
php artisan config:clear
```

---

## Running Multiple Projects

### Prerequisites

Both projects can run simultaneously because they use:
- ✅ **Different ports** (8000 and 8001)
- ✅ **Different databases** (ultimate-pos-saas-db and ultimate-pos-saas-master2-db)
- ✅ **Different session cookies** (ultimate_pos_current_session and ultimate_pos_master2_session)
- ✅ **Different Vite ports** (5173 and production build)

### Start Both Projects

**Terminal 1 - Main Project (Port 8000):**
```bash
cd "/Users/rikihikmianto/FlutterProjects/POS SaaS Multi Merchant - Bangun Dari Nol hingga Rilis di Play Store/ultimate-pos-saas"

# Start Laravel server (background)
php artisan serve --host=localhost --port=8000 > /tmp/project-8000.log 2>&1 &
echo $! > /tmp/project-8000.pid

# Start Vite dev server (background)
npm run dev > /tmp/vite-dev-8000.log 2>&1 &
echo $! > /tmp/vite-dev-8000.pid
```

**Terminal 2 - Master-2 (Port 8001):**
```bash
cd "/Users/rikihikmianto/FlutterProjects/POS SaaS Multi Merchant - Bangun Dari Nol hingga Rilis di Play Store/web/2-ultimate-pos-saas-master"

# Start Laravel server (background)
php artisan serve --host=localhost --port=8001

# Note: Master-2 uses production build, no Vite dev server needed
```

### Verify Both Servers

```bash
# Check server status
echo "=== Server Status ==="
lsof -ti:8000 >/dev/null 2>&1 && echo "Main Project (8000): RUNNING" || echo "Main Project: STOPPED"
lsof -ti:8001 >/dev/null 2>&1 && echo "Master-2 (8001): RUNNING" || echo "Master-2: STOPPED"

# Test HTTP responses
curl -s http://localhost:8000/pos -o /dev/null -w "Main: %{http_code}\n"
curl -s http://localhost:8001/pos -o /dev/null -w "Master-2: %{http_code}\n"
```

**Expected Output:**
```
Main Project: RUNNING
Master-2 (8001): RUNNING
Main: 200 OK
Master-2: 200 OK
```

---

## Testing the POS Application

### 1. Login Testing

**Test Steps:**

1. Open browser to http://localhost:8000
2. Navigate to `/login` (should redirect automatically)
3. Enter credentials:
   - Email: `superadmin@ultimatepos.com`
   - Password: `password`
4. Click "Login" button
5. Verify successful redirect to `/pos`
6. Verify session is active (check browser dev tools → Application → Cookies)

**Expected Result:**
- ✅ Redirect to `/pos` page
- ✅ Dashboard/POS interface loads
- ✅ No 419 Page Expired errors
- ✅ No session conflicts

**Common Login Issues:**

| Issue | Symptom | Solution |
|-------|---------|----------|
| **419 Page Expired** | Session timeout or CSRF mismatch | Clear browser cache and cookies, refresh page |
| **Database connection error** | SQLSTATE connections failed | Verify database exists in MySQL: `CREATE DATABASE ultimate_pos_saas_db;` |
| **Wrong credentials** | "These credentials do not match" | Check email and password, verify user exists in database |
| **Tenant not found** | "No query results for this model" | Run: `php artisan tinker --execute='\App\Models\Tenant::first();'` |

### 2. POS Interface Testing

**Test Checklist:**

- [ ] **Categories & Products**
  - [ ] Create new product category
  - [ ] Create new product with variant
  - [ ] Add product to cart
  - [ ] Search for existing product
  - [ ] Edit product price
  - [ ] Delete product

- [ ] **Cart & Checkout**
  - [ ] Add single item to cart
  - [ ] Add multiple items
  - [ ] Update quantity
  - [ ] Remove item from cart
  - [ ] Apply discount
  - [ ] Calculate total

- [ ] **Customer Management**
  - [ ] Select existing customer
  - [ ] Search customer by name
  - [ ] Create new customer
  - [ ] View customer details
  - [ ] Assign customer to order

- [ ] **Payment Processing**
  - [ ] Select payment method (Cash, Card, E-Wallet)
  - [ ] Enter payment amount
  - [ ] Calculate change
  - [ ] Complete transaction
  - [ ] Print receipt
  - [ ] Open cash drawer

- [ ] **Order Management**
  - [ ] Hold current order
  - [ ] Recall held order
  - [ ] Create new order
  - [ ] View order history
  - [ ] Split order

- [ ] **Session Management**
  - [ ] Open new shift/session
  - [ ] Close current shift
  - [ ] Count drawer cash
  - [ ] View shift reports

### Testing Different Projects

**Main Project (Current):**
- Uses latest development code
- May have new features not yet in Master-2
- Test with: http://localhost:8000

**Master-2 (Reference):**
- More stable/complete version
- All features fully implemented
- Test with: http://localhost:8001

### Compare Feature Sets

| Feature | Main Project | Master-2 |
|---------|--------------|-----------|
| Hold Order | ❌ Not implemented | ✅ Available |
| Recall Order | ❌ Not implemented | ✅ Available |
| Cash Drawer | ❌ Not implemented | ✅ Available |
| Customer Search | Full UI | Compact dropdown |
| Cart Panel | Right side (empty) | Hidden (line 97-99) |

---

## Testing Common Features

### 1. Product Management Test

**Test Script:**
```php
// In Tinker: Create test product
$product = \App\Models\Product::factory()->create([
    'name' => 'Test Product ' . now(),
    'sku' => 'TEST-001',
    'selling_price' => 15000,
    'cost_price' => 10000,
    'category_id' => \App\Models\ProductCategory::first()->id,
    'is_active' => true,
]);

echo "Product created: " . $product->name . " (ID: " . $product->id . ")\n";

// Verify product appears in POS
$products = \App\Models\Product::where('is_active', true)->get();
echo "Total active products: " . $products->count() . "\n";
```

**Expected Output:**
```
Product created: Test Product 2025-02-11 (ID: uuid-here)
Total active products: 42
```

### 2. Order Processing Test

**Test Script:**
```php
// In Tinker: Create test order
$order = \App\Models\HeldOrder::factory()->create([
    'customer_id' => \App\Models\Customer::first()->id,
    'tenant_id' => auth()->user()->tenant_id,
    'status' => 'held',
    'notes' => 'Test order from tinker',
]);

echo "Held order created: " . $order->id . "\n";
```

**Expected Output:**
```
Held order created: uuid-here
```

### 3. Multi-Tenancy Test

**Verify Tenant Isolation:**
```php
// In Tinker: Check tenant
$tenant = auth()->user()->tenant;
echo "Current tenant: " . $tenant->name . " (ID: " . $tenant->id . ")\n";

// Check database connection
\DB::connection()->getDatabaseName();
// Should output: ultimate-pos-saas-db (for Main) or ultimate-pos-saas-master2-db (for Master-2)
```

### 4. Payment Methods Test

**Verify Payment Methods:**
```bash
# Check available payment methods
mysql -u root -e "USE ultimate_pos_saas_db; SELECT id, name FROM payment_methods WHERE is_active = 1;"

# Expected output:
# +uuid-here | Cash | 1 | BCA | 2 | GoPay | 3 |
```

---

## Troubleshooting

### Common Issues

#### Issue: "419 Page Expired"
**Cause:** CSRF token mismatch or session expired

**Solutions:**
```bash
# Clear all caches
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear

# Clear browser data
1. Open DevTools (F12)
2. Right-click → Clear browsing data
3. Hard refresh (Ctrl+Shift+R)
```

#### Issue: "Vite manifest not found"
**Cause:** Production build not created

**Solutions:**
```bash
# Build assets
npm run build

# Or run dev server
npm run dev
```

#### Issue: "Database connection refused"
**Cause:** MySQL server not running or wrong port

**Solutions:**
```bash
# Check MySQL status
brew services list | grep mysql
# Or check port
mysql -u root -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```

#### Issue: "Port already in use"
**Cause:** Previous server still running

**Solutions:**
```bash
# Kill existing servers
lsof -ti:8000 | xargs kill -9
lsof -ti:8001 | xargs kill -9
lsof -ti:5173 | xargs kill -9

# Restart servers
php artisan serve --host=localhost --port=8000
npm run dev
```

#### Issue: "Session conflicts between projects"
**Cause:** Both projects using same session cookie name

**Solutions:**
```bash
# Check .env files
grep SESSION_COOKIE .env
grep SESSION_COOKIE /Users/rikihikmianto/FlutterProjects/POS\ SaaS\ Multi\ Merchant\ -\ Bangun\ Dari\ Nol\ hingga\ Rilis\ di\ Play\ Store/web/2-ultimate-pos-saas-master/.env

# Should output different values:
# Main: ultimate_pos_current_session
# Master-2: ultimate_pos_master2_session
```

---

## Pre-Production Checklist

### Before Deploying to Production

- [ ] **Environment Configuration**
  - [ ] Set `APP_ENV=production` in `.env`
  - [ ] Set `APP_DEBUG=false`
  - [ ] Configure `APP_URL` to production domain
  - [ ] Update database credentials for production

- [ ] **Security**
  - [ ] Change all default passwords
  - [ ] Generate secure `APP_KEY`
  - [ ] Configure HTTPS/SSL certificate
  - [ ] Set up firewall rules

- [ ] **Database**
  - [ ] Create production database
  - [ ] Run migrations: `php artisan migrate --force`
  - [ ] Seed production data: `php artisan db:seed --class=ProductionSeeder`
  - [ ] Optimize database: `php artisan db:optimize`

- [ ] **Assets**
  - [ ] Build for production: `npm run build`
  - [ ] Verify `public/build/` contains compiled assets
  - [ ] Run `php artisan view:cache`
  - [ ] Test all asset loading

- [ ] **Performance**
  - [ ] Enable OPcache
  - [ ] Configure queue driver (Redis/Database)
  - [ ] Set up Laravel Horizon for queue monitoring
  - [ ] Enable gzip compression
  - [ ] Configure CDN for static assets

- [ ] **Monitoring**
  - [ ] Set up logging (Monolog/CloudWatch)
  - [ ] Configure error tracking (Sentry/Bugsnag)
  - [ ] Set up uptime monitoring
  - [ ] Configure backup strategy

- [ ] **Testing**
  - [ ] Run full test suite: `php artisan test`
  - [ ] Load test all payment gateways
  - [ ] Test concurrent users
  - [ ] Test multi-tenant isolation
  - [ ] Performance testing (load testing)
  - [ ] Security audit

- [ ] **Documentation**
  - [ ] Update README with deployment instructions
  - [ ] Create API documentation
  - [ ] Document cron jobs
  - [ ] Create user manual
  - [ ] Document environment variables

---

## Quick Commands Reference

### Database Operations

```bash
# Fresh database with seeding
php artisan migrate:fresh --seed

# Run migrations only
php artisan migrate

# Seed data only
php artisan db:seed

# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback
```

### Cache Operations

```bash
# Clear all caches
php artisan optimize:clear

# Clear specific cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Re-cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Tinker Commands

```bash
# Quick database check
php artisan tinker --execute="DB::connection()->getDatabaseName();"

# Create test user
php artisan tinker --execute="
\$user = \App\Models\User::factory()->create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);
echo \$user->id;
"

# Check tenant
php artisan tinker --execute="auth()->user()->tenant->name;"

# Count products
php artisan tinker --execute="echo \App\Models\Product::count();"
```

### Server Operations

```bash
# Start development server
php artisan serve --host=localhost --port=8000

# Start with specific host
php artisan serve --host=127.0.0.1 --port=8000

# Production build
npm run build

# Development build with HMR
npm run dev
```

---

## Testing Checklist

Use this checklist when testing the application:

### Phase 1: Basic Functionality
- [ ] User can login successfully
- [ ] Dashboard loads without errors
- [ ] POS interface displays correctly
- [ ] Categories are visible and filterable
- [ ] Products display with images and prices
- [ ] Cart functionality works (add/remove items)
- [ ] Customer selection works
- [ ] Payment can be processed
- [ ] Order can be completed
- [ ] Receipt is generated
- [ ] Session persists across page refresh

### Phase 2: Advanced Features
- [ ] Product search works (name/SKU/barcode)
- [ ] Product variants display correctly
- [ ] Modifiers can be added to products
- [ ] Combos are available
- [ ] Discounts apply correctly
- [ ] Tax calculation works
- [ ] Multiple payment methods functional
- [ ] Cash drawer opens and counts money
- [ ] Held orders can be recalled
- [ ] Shift management works (open/close)
- [ ] Reports generate correctly

### Phase 3: Multi-Tenancy
- [ ] Tenants are isolated (data separation)
- [ ] Users belong to correct tenants
- [ ] Tenant scope works in queries
- [ ] Each tenant has own settings
- [ ] No cross-tenant data leakage

### Phase 4: Performance & Reliability
- [ ] Page load time is acceptable (<2s)
- [ ] Assets load without delay
- [ ] No console errors in browser
- [ ] API responses are fast (<500ms)
- [ ] Database queries are optimized
- [ ] Caching is working where needed
- [ ] No memory leaks in long-running processes

### Phase 5: Security
- [ ] SQL injection protection works
- [ ] XSS protection is enabled
- [ ] CSRF tokens are validated
- [ ] File uploads are validated
- [ ] Rate limiting works
- [ ] Authentication is secure
- [ ] Session hijacking prevented

### Phase 6: Compatibility
- [ ] Works in Chrome (latest)
- [ ] Works in Firefox (latest)
- [ ] Works in Safari (latest)
- [ ] Works on mobile devices
- [ ] Responsive design works
- [ ] Touch interactions work

---

## Browser DevTools Usage

### Checking Network Requests

1. Open DevTools (F12)
2. Go to **Network** tab
3. Filter by "Fetch/XHR"
4. Click request to view details
5. Check:
   - Status code (200, 422, 500, etc.)
   - Response time
   - Request payload
   - Response data
   - Headers (cookies, authorization)

### Checking Console Errors

1. Open DevTools (F12)
2. Go to **Console** tab
3. Look for:
   - Red errors
   - Failed promises
   - Network errors
   - JavaScript errors
   - Laravel Livewire errors

### Checking Application State

1. Open DevTools (F12)
2. Go to **Application** tab
3. In Console, type:
   ```javascript
   // Check Livewire app state
   Livewire.first();

   // Check auth state
   localStorage.getItem('auth_token');

   // Check cart state
   localStorage.getItem('cart');
   ```

---

## Notes

### Project Differences

**Main Project (ultimate-pos-saas):**
- Latest development branch
- May have incomplete features
- Uses Vite dev server for HMR
- Active development

**Master-2 (2-ultimate-pos-saas-master):**
- More complete/stable
- All features implemented
- Uses production build
- Reference for comparison

### Best Practices

1. **Always test in both environments** (local and production)
2. **Check browser compatibility** (Chrome, Firefox, Safari)
3. **Test with real data**, not just dummy data
4. **Verify multi-tenancy isolation** (data doesn't leak)
5. **Test payment flows** thoroughly (critical for POS)
6. **Monitor performance** (load times, response times)
7. **Security testing** (SQL injection, XSS, CSRF)
8. **Test concurrent users** (multiple cashiers)
9. **Test error handling** (network failures, timeouts)
10. **Document all test results**

---

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Livewire Documentation](https://livewire.laravel.com)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Tailwind CSS](https://tailwindcss.com/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

---

**Last Updated:** 2026-02-11
**Version:** 1.0.0
**Status:** ✅ Ready for Testing
