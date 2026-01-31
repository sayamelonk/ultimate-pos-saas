# Manual Verification Guide

## Cara Cek Database Secara Langsung

### 1. Gunakan Tinker
```bash
php artisan tinker
```

### 2. Cek Tenants
```php
// Lihat semua tenants
Tenant::withCount('outlets', 'users')->get();

// Cek tenant tertentu
Tenant::find('id');
```

### 3. Cek Users
```php
// Lihat user dengan roles dan outlets
User::with('roles', 'outlets')->get();

// Cek user tenant tertentu
User::where('tenant_id', 'tenant-id')->with('roles')->get();
```

### 4. Cek Outlets
```php
// Lihat outlets dengan tenant
Outlet::with('tenant')->get();

// Cek outlet per tenant
Outlet::where('tenant_id', 'tenant-id')->get();
```

### 5. Cek Roles
```php
// Lihat roles dengan permissions
Role::with('permissions')->get();

// Cek system roles
Role::where('is_system', true)->get();

// Cek custom roles per tenant
Role::where('tenant_id', 'tenant-id')->get();
```

## Browser Testing Checklist

### Authentication Flow
1. Buka http://localhost:8000
2. Klik "Register"
3. Isi:
   - Name: Test User
   - Email: test@example.com
   - Password: Password123
   - Confirm: Password123
   - Business Name: Test Business
4. Klik Register
5. ✅ Anda akan redirect ke dashboard
6. ✅ Cek database: Tenant, Outlet "Main Outlet", User ter-create

### Multi-Tenant Isolation
1. Register sebagai Tenant A
2. Logout
3. Register sebagai Tenant B
4. Login sebagai Tenant A
5. Buka /admin/users
6. ✅ Hanya user Tenant A yang muncul
7. ❌ User Tenant B tidak muncul

### Role & Permission
1. Login sebagai Tenant Owner
2. Buka /admin/roles
3. Klik "Create Role"
4. Buat role "Cashier"
5. Assign permissions (misal: "View POS", "Create Orders")
6. Simpan
7. ✅ Role ter-create
8. ✅ Permissions ter-assign

### Super Admin Features
1. Buat user super-admin via seeder
2. Login sebagai super-admin
3. Buka /admin/tenants
4. ✅ Bisa lihat semua tenants
5. Buka /admin/users
6. ✅ Bisa lihat semua users dari semua tenants
7. Buka /admin/outlets
8. ✅ Bisa lihat semua outlets
