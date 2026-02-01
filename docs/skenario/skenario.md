# POS SaaS Multi Merchant - Testing Scenarios
## Phase 1 & Phase 2 Complete Test Cases

---

# Phase 1 Testing Scenarios
## Foundation - Blade Components, Authentication & Admin CRUD

---

## 1. Authentication Module

### 1.1 Login

#### TC-AUTH-001: Login dengan kredensial valid
**Precondition:** User sudah terdaftar dan aktif
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/login` | Form login ditampilkan |
| 2 | Input email valid | Field terisi |
| 3 | Input password valid | Field terisi (masked) |
| 4 | Klik tombol "Login" | Redirect ke dashboard |

#### TC-AUTH-002: Login dengan kredensial invalid
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/login` | Form login ditampilkan |
| 2 | Input email yang tidak terdaftar | Field terisi |
| 3 | Input password sembarang | Field terisi |
| 4 | Klik tombol "Login" | Error message: "The provided credentials do not match our records." |

#### TC-AUTH-003: Login dengan akun yang dinonaktifkan
**Precondition:** User terdaftar tapi `is_active = false`
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/login` | Form login ditampilkan |
| 2 | Input email user yang dinonaktifkan | Field terisi |
| 3 | Input password yang benar | Field terisi |
| 4 | Klik tombol "Login" | Error message: "Your account has been deactivated." |

#### TC-AUTH-004: Login dengan remember me
**Precondition:** User sudah terdaftar dan aktif
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/login` | Form login ditampilkan |
| 2 | Input kredensial valid | Field terisi |
| 3 | Centang "Remember me" | Checkbox tercentang |
| 4 | Klik tombol "Login" | Redirect ke dashboard, remember token tersimpan |

#### TC-AUTH-005: Validasi form login
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/login` | Form login ditampilkan |
| 2 | Klik tombol "Login" tanpa mengisi apapun | Error validation: email required, password required |
| 3 | Input email dengan format salah (misal: "test") | Error: email harus format email valid |

---

### 1.2 Registration

#### TC-REG-001: Registrasi dengan data valid
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/register` | Form registrasi ditampilkan |
| 2 | Input nama: "John Doe" | Field terisi |
| 3 | Input email: "john@example.com" | Field terisi |
| 4 | Input business name: "John's Store" | Field terisi |
| 5 | Input phone: "08123456789" | Field terisi |
| 6 | Input password: "Password123" | Field terisi |
| 7 | Input password confirmation | Field terisi |
| 8 | Klik tombol "Register" | - User ter-create dengan tenant baru<br>- Outlet default "Main Outlet" ter-create<br>- User assigned role "tenant-owner"<br>- User assigned ke outlet default<br>- Redirect ke dashboard dengan success message |

#### TC-REG-002: Registrasi dengan email sudah terdaftar
**Precondition:** Email "existing@example.com" sudah terdaftar
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/register` | Form registrasi ditampilkan |
| 2 | Input email: "existing@example.com" | Field terisi |
| 3 | Input data lainnya dengan valid | Fields terisi |
| 4 | Klik tombol "Register" | Error: "The email has already been taken." |

#### TC-REG-003: Validasi password strength
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/register` | Form registrasi ditampilkan |
| 2 | Input semua field dengan valid | Fields terisi |
| 3 | Input password lemah: "123" | Field terisi |
| 4 | Klik tombol "Register" | Error validasi password (minimum requirements) |

#### TC-REG-004: Password confirmation tidak cocok
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka halaman `/register` | Form registrasi ditampilkan |
| 2 | Input password: "Password123" | Field terisi |
| 3 | Input password confirmation: "DifferentPass" | Field terisi |
| 4 | Klik tombol "Register" | Error: "The password confirmation does not match." |

---

### 1.3 Logout

#### TC-LOGOUT-001: Logout dari sistem
**Precondition:** User sudah login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik tombol "Logout" | POST request ke `/logout` |
| 2 | - | Session invalidated |
| 3 | - | Redirect ke halaman login |
| 4 | Coba akses halaman dashboard | Redirect ke login (unauthorized) |

---

## 2. Tenant Management (Super Admin Only)

### 2.1 List Tenants

#### TC-TENANT-001: Akses halaman tenant list sebagai Super Admin
**Precondition:** Login sebagai Super Admin
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants` | Halaman list tenant ditampilkan |
| 2 | - | Semua tenant ditampilkan dengan pagination (15 per page) |
| 3 | - | Kolom: name, code, email, outlets count, users count, status |

#### TC-TENANT-002: Akses halaman tenant sebagai Tenant Owner
**Precondition:** Login sebagai Tenant Owner (bukan Super Admin)
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants` | Error 403: "Access denied. Super Admin only." |

#### TC-TENANT-003: Search tenant
**Precondition:** Login sebagai Super Admin, beberapa tenant sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants` | List tenant ditampilkan |
| 2 | Input search: "restaurant" | Tenant dengan nama/code/email mengandung "restaurant" ditampilkan |

#### TC-TENANT-004: Filter tenant by status
**Precondition:** Login sebagai Super Admin
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants` | List tenant ditampilkan |
| 2 | Pilih filter status: "active" | Hanya tenant aktif yang ditampilkan |
| 3 | Pilih filter status: "inactive" | Hanya tenant nonaktif yang ditampilkan |

---

### 2.2 Create Tenant

#### TC-TENANT-005: Buat tenant baru
**Precondition:** Login sebagai Super Admin
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants/create` | Form create tenant ditampilkan |
| 2 | Input name: "New Restaurant" | Field terisi |
| 3 | Input email: "new@restaurant.com" (opsional) | Field terisi |
| 4 | Centang "is_active" | Checkbox tercentang |
| 5 | Klik "Save" | - Tenant ter-create dengan code auto-generated<br>- Redirect ke list dengan success message |

#### TC-TENANT-006: Buat tenant dengan email duplikat
**Precondition:** Tenant dengan email "existing@test.com" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants/create` | Form ditampilkan |
| 2 | Input email: "existing@test.com" | Field terisi |
| 3 | Klik "Save" | Error: email sudah digunakan |

---

### 2.3 Edit Tenant

#### TC-TENANT-007: Edit tenant
**Precondition:** Login sebagai Super Admin, tenant "ABC Store" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants/{id}/edit` | Form edit dengan data tenant ditampilkan |
| 2 | Ubah name: "ABC Store Updated" | Field berubah |
| 3 | Klik "Update" | - Data tenant terupdate<br>- Redirect ke list dengan success message |

#### TC-TENANT-008: Nonaktifkan tenant
**Precondition:** Login sebagai Super Admin, tenant aktif sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants/{id}/edit` | Form edit ditampilkan |
| 2 | Uncheck "is_active" | Checkbox tidak tercentang |
| 3 | Klik "Update" | Tenant menjadi nonaktif |

---

### 2.4 Delete Tenant

#### TC-TENANT-009: Hapus tenant tanpa users/outlets
**Precondition:** Tenant tanpa users dan outlets
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke list tenant | List ditampilkan |
| 2 | Klik "Delete" pada tenant target | Confirm dialog muncul |
| 3 | Konfirmasi delete | - Tenant terhapus<br>- Success message ditampilkan |

#### TC-TENANT-010: Hapus tenant dengan existing users/outlets
**Precondition:** Tenant memiliki users atau outlets
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada tenant | Confirm dialog muncul |
| 2 | Konfirmasi delete | Error: "Cannot delete tenant with existing users or outlets." |

---

## 3. Outlet Management

### 3.1 List Outlets

#### TC-OUTLET-001: Akses outlet list sebagai Super Admin
**Precondition:** Login sebagai Super Admin
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/outlets` | Semua outlet dari semua tenant ditampilkan |

#### TC-OUTLET-002: Akses outlet list sebagai Tenant Owner
**Precondition:** Login sebagai Tenant Owner
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/outlets` | Hanya outlet milik tenant sendiri yang ditampilkan |

#### TC-OUTLET-003: Search outlet
**Precondition:** Login, beberapa outlet sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/outlets` | List outlet ditampilkan |
| 2 | Input search: "main" | Outlet dengan nama/code/address mengandung "main" ditampilkan |

#### TC-OUTLET-004: Filter outlet by status
**Precondition:** Login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/outlets` | List outlet ditampilkan |
| 2 | Pilih filter status: "active" | Hanya outlet aktif yang ditampilkan |

---

### 3.2 Create Outlet

#### TC-OUTLET-005: Buat outlet baru
**Precondition:** Login sebagai Tenant Owner
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/outlets/create` | Form create outlet ditampilkan |
| 2 | Input name: "Branch 1" | Field terisi |
| 3 | Input code: "BR1" | Field terisi |
| 4 | Input address: "Jl. Test No. 1" | Field terisi |
| 5 | Input phone: "08123456789" | Field terisi |
| 6 | Input email: "branch1@test.com" | Field terisi |
| 7 | Centang "is_active" | Checkbox tercentang |
| 8 | Klik "Save" | - Outlet ter-create dengan tenant_id dari user login<br>- Redirect ke list dengan success message |

#### TC-OUTLET-006: Validasi form outlet
**Precondition:** Login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/outlets/create` | Form ditampilkan |
| 2 | Klik "Save" tanpa mengisi apapun | Error validation: name required, code required |

---

### 3.3 Edit Outlet

#### TC-OUTLET-007: Edit outlet sendiri
**Precondition:** Login sebagai Tenant Owner
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/outlets/{id}/edit` | Form edit outlet ditampilkan |
| 2 | Ubah name: "Branch 1 Updated" | Field berubah |
| 3 | Klik "Update" | Data outlet terupdate, redirect dengan success message |

#### TC-OUTLET-008: Edit outlet tenant lain (unauthorized)
**Precondition:** Login sebagai Tenant Owner, mencoba edit outlet dari tenant lain
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/outlets/{id}/edit` (outlet tenant lain) | Error 403: "Access denied." |

---

### 3.4 Delete Outlet

#### TC-OUTLET-009: Hapus outlet tanpa assigned users
**Precondition:** Outlet tanpa users terkait
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada outlet | Confirm dialog |
| 2 | Konfirmasi delete | Outlet terhapus, success message |

#### TC-OUTLET-010: Hapus outlet dengan assigned users
**Precondition:** Outlet memiliki users terkait
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada outlet | Confirm dialog |
| 2 | Konfirmasi delete | Error: "Cannot delete outlet with assigned users." |

---

## 4. User Management

### 4.1 List Users

#### TC-USER-001: Akses user list sebagai Super Admin
**Precondition:** Login sebagai Super Admin
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users` | Semua user dari semua tenant ditampilkan |
| 2 | - | Kolom: name, email, tenant, roles, outlets, status |

#### TC-USER-002: Akses user list sebagai Tenant Owner
**Precondition:** Login sebagai Tenant Owner
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users` | Hanya user milik tenant sendiri yang ditampilkan |

#### TC-USER-003: Search user
**Precondition:** Login, beberapa user sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users` | List user ditampilkan |
| 2 | Input search: "john" | User dengan nama/email mengandung "john" ditampilkan |

#### TC-USER-004: Filter user by status
**Precondition:** Login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Filter status: "active" | Hanya user aktif yang ditampilkan |
| 2 | Filter status: "inactive" | Hanya user nonaktif yang ditampilkan |

#### TC-USER-005: Filter user by role
**Precondition:** Login, beberapa user dengan role berbeda
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Filter role: "cashier" | Hanya user dengan role cashier yang ditampilkan |

---

### 4.2 Create User

#### TC-USER-006: Buat user baru dengan role dan outlet
**Precondition:** Login sebagai Tenant Owner
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users/create` | Form create user ditampilkan |
| 2 | Input name: "New Cashier" | Field terisi |
| 3 | Input email: "cashier@test.com" | Field terisi |
| 4 | Input password dan confirmation | Fields terisi |
| 5 | Pilih role: "Cashier" | Role terpilih |
| 6 | Pilih outlet: "Main Outlet" | Outlet terpilih |
| 7 | Centang "is_active" | Checkbox tercentang |
| 8 | Klik "Save" | - User ter-create<br>- Role ter-attach<br>- Outlet ter-attach (first outlet = default)<br>- Success message |

#### TC-USER-007: Buat user tanpa role (validation error)
**Precondition:** Login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users/create` | Form ditampilkan |
| 2 | Input data tanpa memilih role | Fields terisi |
| 3 | Klik "Save" | Error validation: roles required |

#### TC-USER-008: Buat user dengan email duplikat
**Precondition:** User dengan email "existing@test.com" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users/create` | Form ditampilkan |
| 2 | Input email: "existing@test.com" | Field terisi |
| 3 | Klik "Save" | Error: "The email has already been taken." |

---

### 4.3 Edit User

#### TC-USER-009: Edit user - update basic info
**Precondition:** Login, user target sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users/{id}/edit` | Form edit dengan data user ditampilkan |
| 2 | Ubah name: "Updated Name" | Field berubah |
| 3 | Klik "Update" | Data terupdate, success message |

#### TC-USER-010: Edit user - change password
**Precondition:** Login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users/{id}/edit` | Form edit ditampilkan |
| 2 | Input new password + confirmation | Fields terisi |
| 3 | Klik "Update" | Password ter-hash dan terupdate |

#### TC-USER-011: Edit user - change roles
**Precondition:** Login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users/{id}/edit` | Form edit ditampilkan |
| 2 | Ubah role dari "Cashier" ke "Manager" | Role terpilih |
| 3 | Klik "Update" | Role ter-sync |

#### TC-USER-012: Edit user - change outlets
**Precondition:** Login, multiple outlets tersedia
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users/{id}/edit` | Form edit ditampilkan |
| 2 | Tambah outlet assignment | Outlet terpilih |
| 3 | Klik "Update" | Outlets ter-sync, first outlet menjadi default |

#### TC-USER-013: Edit user tenant lain (unauthorized)
**Precondition:** Login sebagai Tenant Owner, mencoba edit user dari tenant lain
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users/{id}/edit` (user tenant lain) | Error 403: "Access denied." |

---

### 4.4 Delete User

#### TC-USER-014: Hapus user
**Precondition:** Login, user target bukan diri sendiri
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada user | Confirm dialog |
| 2 | Konfirmasi delete | - Roles detached<br>- Outlets detached<br>- User deleted<br>- Success message |

#### TC-USER-015: Hapus diri sendiri (prevented)
**Precondition:** Login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada akun sendiri | Error: "You cannot delete your own account." |

#### TC-USER-016: Hapus Super Admin (prevented)
**Precondition:** Login sebagai Super Admin
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada user Super Admin | Error: "Cannot delete super admin user." |

---

## 5. Role & Permission Management

### 5.1 List Roles

#### TC-ROLE-001: Akses role list sebagai Super Admin
**Precondition:** Login sebagai Super Admin
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/roles` | Semua role ditampilkan |
| 2 | - | Kolom: name, description, users count, permissions count, is_system |

#### TC-ROLE-002: Akses role list sebagai Tenant Owner
**Precondition:** Login sebagai Tenant Owner
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/roles` | System roles + tenant's custom roles ditampilkan |

#### TC-ROLE-003: Search role
**Precondition:** Login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input search: "manager" | Roles dengan nama mengandung "manager" ditampilkan |

---

### 5.2 Create Role

#### TC-ROLE-004: Buat custom role baru
**Precondition:** Login sebagai Tenant Owner
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/roles/create` | Form create role ditampilkan |
| 2 | Input name: "Shift Leader" | Field terisi |
| 3 | Input description: "Lead cashier" | Field terisi |
| 4 | Klik "Save" | - Role ter-create dengan slug auto-generated<br>- is_system = false<br>- tenant_id = user's tenant<br>- Redirect ke halaman permissions |

#### TC-ROLE-005: Buat role dengan nama duplikat
**Precondition:** Role "Manager" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/roles/create` | Form ditampilkan |
| 2 | Input name: "Manager" | Field terisi |
| 3 | Klik "Save" | Error: "A role with this name already exists." |

---

### 5.3 Edit Role

#### TC-ROLE-006: Edit custom role
**Precondition:** Login, custom role (non-system) sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/roles/{id}/edit` | Form edit ditampilkan |
| 2 | Ubah name dan description | Fields berubah |
| 3 | Klik "Update" | Data terupdate, success message |

#### TC-ROLE-007: Edit system role (prevented)
**Precondition:** Login, mencoba edit system role
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/roles/{id}/edit` (system role) | Redirect dengan error: "System roles cannot be edited." |

---

### 5.4 Role Permissions

#### TC-ROLE-008: Assign permissions ke role
**Precondition:** Login, role sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/roles/{id}/permissions` | Halaman permissions dengan grouped modules |
| 2 | Pilih beberapa permissions | Checkboxes tercentang |
| 3 | Klik "Save Permissions" | Permissions ter-sync, success message |

#### TC-ROLE-009: Remove all permissions dari role
**Precondition:** Role memiliki beberapa permissions
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/roles/{id}/permissions` | Permissions ditampilkan |
| 2 | Uncheck semua permissions | Semua checkbox tidak tercentang |
| 3 | Klik "Save Permissions" | Semua permissions di-detach |

---

### 5.5 Delete Role

#### TC-ROLE-010: Hapus custom role tanpa users
**Precondition:** Custom role tanpa users terkait
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada role | Confirm dialog |
| 2 | Konfirmasi delete | - Permissions detached<br>- Role deleted<br>- Success message |

#### TC-ROLE-011: Hapus system role (prevented)
**Precondition:** Mencoba hapus system role
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada system role | Error: "System roles cannot be deleted." |

#### TC-ROLE-012: Hapus role dengan assigned users (prevented)
**Precondition:** Role memiliki users terkait
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada role | Error: "Cannot delete role with assigned users." |

---

## 6. Blade Components

### 6.1 Form Components

#### TC-COMP-001: Input component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-input>` component | Input field rendered dengan styling konsisten |
| 2 | Test dengan type: text, email, password | Sesuai type |
| 3 | Test dengan error attribute | Error message ditampilkan |

#### TC-COMP-002: Select component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-select>` component | Dropdown rendered |
| 2 | Test dengan options | Options ditampilkan |
| 3 | Test dengan selected value | Default value terpilih |

#### TC-COMP-003: Checkbox component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-checkbox>` component | Checkbox rendered dengan label |
| 2 | Test checked state | Checkbox tercentang jika checked=true |

#### TC-COMP-004: Radio component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-radio>` component | Radio button rendered |
| 2 | Test group selection | Hanya satu yang terpilih dalam group |

#### TC-COMP-005: Textarea component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-textarea>` component | Textarea rendered |
| 2 | Test dengan rows attribute | Ukuran sesuai |

---

### 6.2 Button & Navigation Components

#### TC-COMP-006: Button component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-button>` component | Button rendered |
| 2 | Test variant: primary, secondary, danger | Warna sesuai variant |
| 3 | Test size: sm, md, lg | Ukuran sesuai |
| 4 | Test disabled state | Button disabled, tidak bisa diklik |

#### TC-COMP-007: Dropdown component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-dropdown>` component | Dropdown trigger rendered |
| 2 | Klik trigger | Menu dropdown muncul |
| 3 | Test dropdown items | Items rendered dengan benar |

---

### 6.3 Display Components

#### TC-COMP-008: Card component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-card>` component | Card container rendered |
| 2 | Test dengan header dan body | Sections rendered |

#### TC-COMP-009: Alert component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-alert type="success">` | Green alert rendered |
| 2 | Render `<x-alert type="error">` | Red alert rendered |
| 3 | Render `<x-alert type="warning">` | Yellow alert rendered |
| 4 | Render `<x-alert type="info">` | Blue alert rendered |

#### TC-COMP-010: Badge component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-badge>` component | Badge rendered |
| 2 | Test dengan variants | Warna sesuai variant |

#### TC-COMP-011: Stat Card component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-stat-card>` component | Stat card rendered |
| 2 | Test dengan value dan label | Value dan label ditampilkan |

#### TC-COMP-012: Empty State component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-empty-state>` component | Empty state message rendered |

---

### 6.4 Table Components

#### TC-COMP-013: Table component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-table>` component | Table rendered dengan styling |
| 2 | Test dengan `<x-th>` dan `<x-td>` | Cells rendered dengan benar |

#### TC-COMP-014: Pagination component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render `<x-pagination>` component | Pagination links rendered |
| 2 | Test navigasi halaman | Link ke halaman yang benar |

---

### 6.5 Modal & Overlay Components

#### TC-COMP-015: Modal component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Trigger modal open | Modal muncul dengan overlay |
| 2 | Test close button | Modal tertutup |
| 3 | Test close on overlay click | Modal tertutup |

#### TC-COMP-016: Confirm Modal component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Trigger confirm modal | Confirmation dialog muncul |
| 2 | Klik "Confirm" | Action dieksekusi |
| 3 | Klik "Cancel" | Modal tertutup tanpa action |

#### TC-COMP-017: Toast component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Trigger toast notification | Toast muncul |
| 2 | Test auto-dismiss | Toast menghilang setelah waktu tertentu |
| 3 | Test manual dismiss | Toast tertutup saat diklik close |

---

### 6.6 Layout Components

#### TC-COMP-018: App Layout component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render halaman dengan app-layout | Layout dengan sidebar dan header rendered |
| 2 | Test navigation | Navigation links berfungsi |
| 3 | Test responsive | Layout menyesuaikan di mobile |

#### TC-COMP-019: Guest Layout component
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Render halaman login/register | Guest layout rendered |
| 2 | Test centered content | Content di tengah |

---

## 7. Multi-Tenant Authorization

### 7.1 Data Isolation

#### TC-MT-001: Tenant data isolation - Users
**Precondition:** 2 tenant dengan users masing-masing
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login sebagai Tenant A Owner | Dashboard Tenant A |
| 2 | Navigasi ke `/admin/users` | Hanya user Tenant A yang tampil |
| 3 | Logout dan login sebagai Tenant B Owner | Dashboard Tenant B |
| 4 | Navigasi ke `/admin/users` | Hanya user Tenant B yang tampil |

#### TC-MT-002: Tenant data isolation - Outlets
**Precondition:** 2 tenant dengan outlets masing-masing
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login sebagai Tenant A Owner | Dashboard Tenant A |
| 2 | Navigasi ke `/admin/outlets` | Hanya outlet Tenant A yang tampil |

#### TC-MT-003: Cross-tenant access prevention
**Precondition:** Login sebagai Tenant A, mencoba akses resource Tenant B
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Coba akses `/admin/users/{tenant_b_user_id}` | Error 403 |
| 2 | Coba akses `/admin/outlets/{tenant_b_outlet_id}` | Error 403 |

---

### 7.2 Super Admin Access

#### TC-MT-004: Super Admin cross-tenant access
**Precondition:** Login sebagai Super Admin
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/users` | Semua users dari semua tenant ditampilkan |
| 2 | Navigasi ke `/admin/outlets` | Semua outlets dari semua tenant ditampilkan |
| 3 | Edit user dari tenant manapun | Berhasil tanpa error |

---

## 8. Security Testing

### 8.1 Session Security

#### TC-SEC-001: Session regeneration on login
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Catat session ID sebelum login | Session ID awal |
| 2 | Login | Session ID berubah (regenerated) |

#### TC-SEC-002: Session invalidation on logout
**Precondition:** User sudah login
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Catat session ID | Session ID tercatat |
| 2 | Logout | Session invalidated |
| 3 | Coba gunakan session ID lama | Tidak bisa akses |

---

### 8.2 CSRF Protection

#### TC-SEC-003: CSRF token on forms
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Inspect form login | Ada hidden field `_token` |
| 2 | Submit form tanpa token | Error 419 |

---

### 8.3 Password Security

#### TC-SEC-004: Password hashing
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat user baru dengan password "Test123" | User ter-create |
| 2 | Cek database | Password ter-hash (bukan plaintext) |

---

## Phase 1 Summary

| Module | Total Test Cases |
|--------|-----------------|
| Authentication | 9 |
| Tenant Management | 10 |
| Outlet Management | 10 |
| User Management | 16 |
| Role & Permission | 12 |
| Blade Components | 19 |
| Multi-Tenant | 4 |
| Security | 4 |
| **Total** | **84** |

---

# Phase 2 Testing Scenarios
## Inventory Management & POS System

---

## 1. Master Data - Units

### 1.1 List Units

#### TC-UNIT-001: Akses halaman unit list
**Precondition:** Login sebagai user dengan akses inventory
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/units` | Halaman list unit ditampilkan |
| 2 | - | Kolom: name, abbreviation, is_base, status, actions |

#### TC-UNIT-002: Search unit
**Precondition:** Beberapa unit sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input search: "kilogram" | Unit dengan nama/abbreviation mengandung "kilogram" ditampilkan |

---

### 1.2 Create Unit

#### TC-UNIT-003: Buat unit baru (base unit)
**Precondition:** Login dengan permission create unit
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/units/create` | Form create unit ditampilkan |
| 2 | Input name: "Kilogram" | Field terisi |
| 3 | Input abbreviation: "kg" | Field terisi |
| 4 | Centang "is_base" | Checkbox tercentang |
| 5 | Klik "Save" | Unit ter-create, redirect dengan success message |

#### TC-UNIT-004: Buat unit turunan (derived unit)
**Precondition:** Base unit "Kilogram" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/units/create` | Form ditampilkan |
| 2 | Input name: "Gram" | Field terisi |
| 3 | Input abbreviation: "g" | Field terisi |
| 4 | Pilih base unit: "Kilogram" | Base unit terpilih |
| 5 | Input conversion: "0.001" | Field terisi |
| 6 | Klik "Save" | Unit ter-create dengan konversi |

#### TC-UNIT-005: Validasi form unit
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Save" tanpa mengisi apapun | Error validation: name required, abbreviation required |

---

### 1.3 Edit & Delete Unit

#### TC-UNIT-006: Edit unit
**Precondition:** Unit sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik edit pada unit | Form edit ditampilkan |
| 2 | Ubah name | Field berubah |
| 3 | Klik "Update" | Data terupdate, success message |

#### TC-UNIT-007: Hapus unit tanpa item terkait
**Precondition:** Unit tanpa inventory items terkait
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada unit | Confirm dialog muncul |
| 2 | Konfirmasi delete | Unit terhapus, success message |

#### TC-UNIT-008: Hapus unit dengan item terkait
**Precondition:** Unit memiliki inventory items terkait
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada unit | Confirm dialog |
| 2 | Konfirmasi delete | Error: "Cannot delete unit with existing items." |

---

## 2. Master Data - Suppliers

### 2.1 List Suppliers

#### TC-SUPP-001: Akses halaman supplier list
**Precondition:** Login dengan akses inventory
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/suppliers` | List supplier ditampilkan |
| 2 | - | Kolom: name, code, contact, phone, email, status |

#### TC-SUPP-002: Search dan filter supplier
**Precondition:** Beberapa supplier sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input search: "PT" | Supplier dengan nama/code mengandung "PT" ditampilkan |
| 2 | Filter status: "active" | Hanya supplier aktif yang ditampilkan |

---

### 2.2 Create Supplier

#### TC-SUPP-003: Buat supplier baru
**Precondition:** Login dengan permission create supplier
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/suppliers/create` | Form create supplier ditampilkan |
| 2 | Input name: "PT Supplier ABC" | Field terisi |
| 3 | Input code: "SUP001" | Field terisi |
| 4 | Input contact person: "John Doe" | Field terisi |
| 5 | Input phone: "08123456789" | Field terisi |
| 6 | Input email: "supplier@abc.com" | Field terisi |
| 7 | Input address | Field terisi |
| 8 | Centang "is_active" | Checkbox tercentang |
| 9 | Klik "Save" | Supplier ter-create, redirect dengan success message |

#### TC-SUPP-004: Validasi supplier code unique
**Precondition:** Supplier dengan code "SUP001" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat supplier dengan code: "SUP001" | Error: "Supplier code already exists." |

---

### 2.3 Edit & Delete Supplier

#### TC-SUPP-005: Edit supplier
**Precondition:** Supplier sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik edit pada supplier | Form edit ditampilkan dengan data |
| 2 | Ubah contact dan phone | Fields berubah |
| 3 | Klik "Update" | Data terupdate |

#### TC-SUPP-006: Hapus supplier tanpa PO terkait
**Precondition:** Supplier tanpa purchase orders
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada supplier | Confirm dialog |
| 2 | Konfirmasi delete | Supplier terhapus |

#### TC-SUPP-007: Hapus supplier dengan PO terkait
**Precondition:** Supplier memiliki purchase orders
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada supplier | Error: "Cannot delete supplier with existing purchase orders." |

---

## 3. Master Data - Categories

### 3.1 List Categories

#### TC-CAT-001: Akses halaman category list
**Precondition:** Login dengan akses inventory
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/categories` | List kategori ditampilkan |
| 2 | - | Kolom: name, parent, items count, status |

---

### 3.2 Create Category

#### TC-CAT-002: Buat kategori parent
**Precondition:** Login dengan permission
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/categories/create` | Form ditampilkan |
| 2 | Input name: "Bahan Baku" | Field terisi |
| 3 | Tidak pilih parent (kosong) | Parent null |
| 4 | Klik "Save" | Kategori ter-create sebagai parent |

#### TC-CAT-003: Buat sub-kategori
**Precondition:** Kategori parent "Bahan Baku" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/categories/create` | Form ditampilkan |
| 2 | Input name: "Sayuran" | Field terisi |
| 3 | Pilih parent: "Bahan Baku" | Parent terpilih |
| 4 | Klik "Save" | Sub-kategori ter-create dengan parent |

---

### 3.3 Edit & Delete Category

#### TC-CAT-004: Edit kategori
**Precondition:** Kategori sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik edit pada kategori | Form edit ditampilkan |
| 2 | Ubah name dan parent | Fields berubah |
| 3 | Klik "Update" | Data terupdate |

#### TC-CAT-005: Hapus kategori tanpa items
**Precondition:** Kategori tanpa items dan sub-kategori
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada kategori | Confirm dialog |
| 2 | Konfirmasi delete | Kategori terhapus |

#### TC-CAT-006: Hapus kategori dengan sub-kategori
**Precondition:** Kategori memiliki sub-kategori
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada kategori | Error: "Cannot delete category with sub-categories." |

---

## 4. Inventory Items

### 4.1 List Items

#### TC-ITEM-001: Akses halaman inventory items
**Precondition:** Login dengan akses inventory
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/items` | List items ditampilkan |
| 2 | - | Kolom: image, name, SKU, category, unit, type, track stock, status |

#### TC-ITEM-002: Search items
**Precondition:** Beberapa items sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input search: "ayam" | Items dengan nama/SKU mengandung "ayam" ditampilkan |

#### TC-ITEM-003: Filter items by category
**Precondition:** Items dengan berbagai kategori
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Pilih filter kategori: "Bahan Baku" | Items di kategori "Bahan Baku" ditampilkan |

#### TC-ITEM-004: Filter items by type
**Precondition:** Items dengan berbagai tipe
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Filter type: "raw_material" | Hanya raw material ditampilkan |
| 2 | Filter type: "finished_goods" | Hanya finished goods ditampilkan |

---

### 4.2 Create Item

#### TC-ITEM-005: Buat item raw material
**Precondition:** Login dengan permission, kategori dan unit sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/items/create` | Form ditampilkan |
| 2 | Input name: "Daging Ayam" | Field terisi |
| 3 | Input SKU: "RM001" | Field terisi |
| 4 | Pilih category: "Bahan Baku" | Category terpilih |
| 5 | Pilih unit: "Kilogram" | Unit terpilih |
| 6 | Pilih type: "raw_material" | Type terpilih |
| 7 | Centang "track_stock" | Stock akan di-track |
| 8 | Input min_stock: 10 | Field terisi |
| 9 | Input max_stock: 100 | Field terisi |
| 10 | Klik "Save" | Item ter-create |

#### TC-ITEM-006: Buat item finished goods dengan recipe
**Precondition:** Raw materials sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/items/create` | Form ditampilkan |
| 2 | Input name: "Ayam Goreng" | Field terisi |
| 3 | Pilih type: "finished_goods" | Type terpilih |
| 4 | Centang "is_for_sale" | Item dapat dijual |
| 5 | Input harga jual (jika ada) | Field terisi |
| 6 | Klik "Save" | Item ter-create |

#### TC-ITEM-007: Validasi SKU unique
**Precondition:** Item dengan SKU "RM001" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat item dengan SKU: "RM001" | Error: "SKU already exists." |

---

### 4.3 Edit & Delete Item

#### TC-ITEM-008: Edit item
**Precondition:** Item sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik edit pada item | Form edit ditampilkan |
| 2 | Ubah name, min_stock, max_stock | Fields berubah |
| 3 | Klik "Update" | Data terupdate |

#### TC-ITEM-009: Hapus item tanpa stock
**Precondition:** Item tanpa stock records
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada item | Confirm dialog |
| 2 | Konfirmasi delete | Item terhapus |

#### TC-ITEM-010: Hapus item dengan stock
**Precondition:** Item memiliki stock records
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Delete" pada item | Error: "Cannot delete item with existing stock." |

---

## 5. Stock Management

### 5.1 Stock Overview

#### TC-STOCK-001: Lihat stock per outlet
**Precondition:** Login, items dengan stock sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stocks` | List stock ditampilkan |
| 2 | - | Kolom: item, outlet, quantity, unit, last updated |

#### TC-STOCK-002: Filter stock by outlet
**Precondition:** Multiple outlets dengan stock
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Pilih outlet: "Main Outlet" | Stock dari Main Outlet ditampilkan |

#### TC-STOCK-003: Lihat low stock items
**Precondition:** Items dengan qty < min_stock
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stocks-low` | Items dengan stock rendah ditampilkan |
| 2 | - | Badge warning/danger sesuai level |

#### TC-STOCK-004: Lihat expiring items
**Precondition:** Items dengan batch expiry date mendekati
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stocks-expiring` | Items dengan expiry mendekati ditampilkan |
| 2 | - | Sorted by expiry date ASC |

---

### 5.2 Stock Movements

#### TC-STOCK-005: Lihat stock movements
**Precondition:** Beberapa transaksi stock sudah terjadi
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stocks-movements` | List movements ditampilkan |
| 2 | - | Kolom: date, item, type, qty, reference, user |

#### TC-STOCK-006: Filter movements by date range
**Precondition:** Movements dalam rentang waktu berbeda
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Set date range: "01-01-2024" to "31-01-2024" | Movements dalam rentang ditampilkan |

#### TC-STOCK-007: Filter movements by type
**Precondition:** Various movement types exist
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Filter type: "purchase" | Hanya purchase movements |
| 2 | Filter type: "sale" | Hanya sale movements |
| 3 | Filter type: "adjustment" | Hanya adjustment movements |

---

### 5.3 Stock Batches

#### TC-STOCK-008: Lihat stock batches
**Precondition:** Items dengan batch tracking
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stocks-batches` | List batches ditampilkan |
| 2 | - | Kolom: batch number, item, qty, expiry date, cost |

---

## 6. Purchase Orders

### 6.1 List Purchase Orders

#### TC-PO-001: Akses halaman purchase orders
**Precondition:** Login dengan akses PO
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/purchase-orders` | List PO ditampilkan |
| 2 | - | Kolom: PO number, date, supplier, outlet, total, status |

#### TC-PO-002: Filter PO by status
**Precondition:** PO dengan berbagai status
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Filter status: "draft" | Hanya draft PO |
| 2 | Filter status: "approved" | Hanya approved PO |
| 3 | Filter status: "sent" | Hanya sent PO |

---

### 6.2 Create Purchase Order

#### TC-PO-003: Buat PO baru
**Precondition:** Login, supplier dan items sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/purchase-orders/create` | Form ditampilkan |
| 2 | Pilih supplier: "PT Supplier ABC" | Supplier terpilih |
| 3 | Pilih outlet: "Main Outlet" | Outlet terpilih |
| 4 | Input expected date | Date terpilih |
| 5 | Tambah item: "Daging Ayam", qty: 50, price: 50000 | Item ditambahkan ke list |
| 6 | Tambah item lain | Item ditambahkan |
| 7 | Klik "Save as Draft" | PO ter-create dengan status "draft" |

#### TC-PO-004: Validasi minimum items
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat PO tanpa items | Error: "At least one item is required." |

---

### 6.3 PO Workflow

#### TC-PO-005: Approve purchase order
**Precondition:** PO dengan status "draft"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail PO draft | Detail ditampilkan |
| 2 | Klik "Approve" | Confirm dialog |
| 3 | Konfirmasi approve | Status berubah ke "approved" |

#### TC-PO-006: Send purchase order ke supplier
**Precondition:** PO dengan status "approved"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail PO approved | Detail ditampilkan |
| 2 | Klik "Send to Supplier" | Status berubah ke "sent" |

#### TC-PO-007: Cancel purchase order
**Precondition:** PO dengan status "draft" atau "approved"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail PO | Detail ditampilkan |
| 2 | Klik "Cancel" | Confirm dialog |
| 3 | Konfirmasi cancel | Status berubah ke "cancelled" |

#### TC-PO-008: Cancel PO yang sudah received (prevented)
**Precondition:** PO dengan status "partially_received" atau "received"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Coba cancel PO | Error: "Cannot cancel PO that has been received." |

---

## 7. Goods Receive

### 7.1 List Goods Receive

#### TC-GR-001: Akses halaman goods receive
**Precondition:** Login dengan akses GR
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/goods-receives` | List GR ditampilkan |
| 2 | - | Kolom: GR number, date, PO reference, supplier, outlet, status |

---

### 7.2 Create Goods Receive

#### TC-GR-002: Buat GR dari PO
**Precondition:** PO dengan status "sent"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/goods-receives/create` | Form ditampilkan |
| 2 | Pilih PO reference | Items dari PO auto-load |
| 3 | Input received qty untuk setiap item | Qty terisi |
| 4 | Input batch number (jika track batch) | Batch terisi |
| 5 | Input expiry date (jika applicable) | Date terisi |
| 6 | Klik "Save" | GR ter-create dengan status "draft" |

#### TC-GR-003: Partial receive
**Precondition:** PO dengan 100 qty, hanya receive 50
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat GR dengan qty: 50 (dari 100 ordered) | GR ter-create |
| 2 | Complete GR | PO status berubah ke "partially_received" |

#### TC-GR-004: Full receive
**Precondition:** PO dengan 100 qty, receive semua
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat GR dengan full qty | GR ter-create |
| 2 | Complete GR | PO status berubah ke "received" |

---

### 7.3 GR Workflow

#### TC-GR-005: Complete goods receive
**Precondition:** GR dengan status "draft"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail GR | Detail ditampilkan |
| 2 | Klik "Complete" | Confirm dialog |
| 3 | Konfirmasi complete | - Status berubah ke "completed"<br>- Stock bertambah sesuai qty received<br>- Stock movement ter-create |

#### TC-GR-006: Cancel goods receive (before complete)
**Precondition:** GR dengan status "draft"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Cancel" | Confirm dialog |
| 2 | Konfirmasi cancel | Status berubah ke "cancelled" |

#### TC-GR-007: Cancel completed GR (prevented)
**Precondition:** GR dengan status "completed"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Coba cancel GR | Error: "Cannot cancel completed goods receive." |

---

## 8. Stock Adjustments

### 8.1 List Stock Adjustments

#### TC-ADJ-001: Akses halaman stock adjustments
**Precondition:** Login dengan akses adjustment
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stock-adjustments` | List adjustments ditampilkan |
| 2 | - | Kolom: adjustment number, date, outlet, type, items count, status |

---

### 8.2 Create Stock Adjustment

#### TC-ADJ-002: Buat adjustment increase
**Precondition:** Login, items dengan stock
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stock-adjustments/create` | Form ditampilkan |
| 2 | Pilih outlet: "Main Outlet" | Outlet terpilih |
| 3 | Pilih type: "increase" | Type terpilih |
| 4 | Input reason: "Found missing stock" | Field terisi |
| 5 | Tambah item: "Daging Ayam", qty: 5 | Item ditambahkan |
| 6 | Klik "Save" | Adjustment ter-create dengan status "pending" |

#### TC-ADJ-003: Buat adjustment decrease
**Precondition:** Items dengan stock > 0
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Pilih type: "decrease" | Type terpilih |
| 2 | Input reason: "Damaged goods" | Field terisi |
| 3 | Tambah item dengan qty | Item ditambahkan |
| 4 | Klik "Save" | Adjustment ter-create |

#### TC-ADJ-004: Validasi decrease tidak melebihi stock
**Precondition:** Item dengan stock: 10
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat decrease adjustment dengan qty: 15 | Error: "Adjustment quantity exceeds available stock." |

---

### 8.3 Adjustment Workflow

#### TC-ADJ-005: Approve stock adjustment
**Precondition:** Adjustment dengan status "pending"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail adjustment | Detail ditampilkan |
| 2 | Klik "Approve" | Confirm dialog |
| 3 | Konfirmasi approve | - Status berubah ke "approved"<br>- Stock berubah sesuai adjustment<br>- Stock movement ter-create |

#### TC-ADJ-006: Reject stock adjustment
**Precondition:** Adjustment dengan status "pending"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail adjustment | Detail ditampilkan |
| 2 | Klik "Reject" | Confirm dialog dengan input reason |
| 3 | Input rejection reason | Field terisi |
| 4 | Konfirmasi reject | Status berubah ke "rejected", stock tidak berubah |

---

### 8.4 Stock Take

#### TC-ADJ-007: Lakukan stock take
**Precondition:** Login, items dengan stock
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stock-take` | Form stock take ditampilkan |
| 2 | Pilih outlet | Outlet terpilih, current stock loaded |
| 3 | Input actual count untuk setiap item | Counts terisi |
| 4 | Sistem hitung variance | Variance ditampilkan (actual - system) |
| 5 | Klik "Create Adjustment" | Adjustment ter-create dari variance |

---

## 9. Stock Transfers

### 9.1 List Stock Transfers

#### TC-TRF-001: Akses halaman stock transfers
**Precondition:** Login dengan akses transfer
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stock-transfers` | List transfers ditampilkan |
| 2 | - | Kolom: transfer number, date, from outlet, to outlet, items, status |

---

### 9.2 Create Stock Transfer

#### TC-TRF-002: Buat transfer antar outlet
**Precondition:** 2+ outlets, items dengan stock di source outlet
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/stock-transfers/create` | Form ditampilkan |
| 2 | Pilih source outlet: "Main Outlet" | Source terpilih, available stock loaded |
| 3 | Pilih destination outlet: "Branch 1" | Destination terpilih |
| 4 | Tambah item: "Daging Ayam", qty: 20 | Item ditambahkan |
| 5 | Input notes (opsional) | Field terisi |
| 6 | Klik "Save" | Transfer ter-create dengan status "pending" |

#### TC-TRF-003: Validasi transfer qty tidak melebihi stock
**Precondition:** Item dengan stock: 10 di source outlet
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat transfer dengan qty: 15 | Error: "Transfer quantity exceeds available stock." |

#### TC-TRF-004: Validasi source dan destination berbeda
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Pilih source dan destination outlet yang sama | Error: "Source and destination must be different." |

---

### 9.3 Transfer Workflow

#### TC-TRF-005: Approve stock transfer
**Precondition:** Transfer dengan status "pending"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail transfer | Detail ditampilkan |
| 2 | Klik "Approve" | Status berubah ke "approved" |

#### TC-TRF-006: Ship stock transfer
**Precondition:** Transfer dengan status "approved"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Ship" | Confirm dialog |
| 2 | Konfirmasi ship | - Status berubah ke "shipped"<br>- Stock di source outlet berkurang<br>- Stock in transit ter-create |

#### TC-TRF-007: Receive stock transfer
**Precondition:** Transfer dengan status "shipped"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login sebagai user di destination outlet | Dashboard destination outlet |
| 2 | Buka detail transfer | Detail ditampilkan |
| 3 | Klik "Receive" | Confirm dialog |
| 4 | Konfirmasi receive | - Status berubah ke "received"<br>- Stock di destination outlet bertambah<br>- Stock movements ter-create |

#### TC-TRF-008: Cancel stock transfer
**Precondition:** Transfer dengan status "pending" atau "approved"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Cancel" | Confirm dialog |
| 2 | Konfirmasi cancel | Status berubah ke "cancelled" |

#### TC-TRF-009: Cancel shipped transfer (prevented)
**Precondition:** Transfer dengan status "shipped"
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Coba cancel transfer | Error: "Cannot cancel transfer that has been shipped." |

---

## 10. Recipes

### 10.1 List Recipes

#### TC-RCP-001: Akses halaman recipes
**Precondition:** Login dengan akses recipe
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/recipes` | List recipes ditampilkan |
| 2 | - | Kolom: name, output item, output qty, cost, margin, status |

---

### 10.2 Create Recipe

#### TC-RCP-002: Buat recipe baru
**Precondition:** Output item (finished goods) dan ingredients (raw materials) sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/recipes/create` | Form ditampilkan |
| 2 | Input name: "Recipe Ayam Goreng" | Field terisi |
| 3 | Pilih output item: "Ayam Goreng" | Output terpilih |
| 4 | Input output qty: 1 | Qty terisi |
| 5 | Tambah ingredient: "Daging Ayam", qty: 0.25 | Ingredient ditambahkan |
| 6 | Tambah ingredient: "Bumbu", qty: 0.05 | Ingredient ditambahkan |
| 7 | Klik "Save" | - Recipe ter-create<br>- Cost auto-calculated dari harga ingredients |

#### TC-RCP-003: Validasi minimum ingredients
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat recipe tanpa ingredients | Error: "At least one ingredient is required." |

---

### 10.3 Recipe Operations

#### TC-RCP-004: Duplicate recipe
**Precondition:** Recipe sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail recipe | Detail ditampilkan |
| 2 | Klik "Duplicate" | Confirm dialog |
| 3 | Konfirmasi duplicate | Recipe baru ter-create dengan nama "{original} (Copy)" |

#### TC-RCP-005: Recalculate recipe cost
**Precondition:** Recipe dengan ingredients, harga ingredient berubah
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail recipe | Detail ditampilkan |
| 2 | Klik "Recalculate Cost" | Cost diupdate berdasarkan harga terbaru |

#### TC-RCP-006: Lihat cost analysis
**Precondition:** Multiple recipes sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/recipes-cost-analysis` | Cost analysis ditampilkan |
| 2 | - | Perbandingan cost vs selling price, margin analysis |

---

## 11. Waste Logs

### 11.1 List Waste Logs

#### TC-WASTE-001: Akses halaman waste logs
**Precondition:** Login dengan akses waste
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/waste-logs` | List waste logs ditampilkan |
| 2 | - | Kolom: date, item, qty, reason, value, recorded by |

---

### 11.2 Create Waste Log

#### TC-WASTE-002: Catat waste
**Precondition:** Items dengan stock > 0
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/waste-logs/create` | Form ditampilkan |
| 2 | Pilih outlet | Outlet terpilih |
| 3 | Pilih item: "Daging Ayam" | Item terpilih |
| 4 | Input qty: 2 | Qty terisi |
| 5 | Pilih reason: "expired" | Reason terpilih |
| 6 | Input notes (opsional) | Field terisi |
| 7 | Klik "Save" | - Waste log ter-create<br>- Stock berkurang sesuai qty<br>- Stock movement ter-create |

#### TC-WASTE-003: Validasi waste qty tidak melebihi stock
**Precondition:** Item dengan stock: 5
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input waste qty: 10 | Error: "Waste quantity exceeds available stock." |

---

### 11.3 Waste Report

#### TC-WASTE-004: Lihat waste report
**Precondition:** Waste logs dalam periode tertentu
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/waste-report` | Report ditampilkan |
| 2 | Set date range | Report di-filter |
| 3 | - | Summary: total value, by reason, by item, trends |

---

## 12. Inventory Reports

### 12.1 Stock Valuation Report

#### TC-RPT-001: Lihat stock valuation
**Precondition:** Items dengan stock dan cost
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/reports/stock-valuation` | Report ditampilkan |
| 2 | Pilih outlet (atau semua) | Data sesuai outlet |
| 3 | - | Total value, breakdown by category, by item |

---

### 12.2 Stock Movement Report

#### TC-RPT-002: Lihat stock movement report
**Precondition:** Stock movements dalam periode
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/reports/stock-movement` | Report ditampilkan |
| 2 | Set date range dan filters | Data sesuai filter |
| 3 | - | In, out, balance per item |

---

### 12.3 COGS Report

#### TC-RPT-003: Lihat COGS (Cost of Goods Sold) report
**Precondition:** Transaksi penjualan dengan items yang punya cost
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/reports/cogs` | Report ditampilkan |
| 2 | Set periode | Data sesuai periode |
| 3 | - | COGS calculation, margin analysis |

---

### 12.4 Food Cost Report

#### TC-RPT-004: Lihat food cost report
**Precondition:** Recipes dengan cost, transaksi penjualan
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/reports/food-cost` | Report ditampilkan |
| 2 | Set periode | Data sesuai periode |
| 3 | - | Food cost percentage, ideal vs actual |

---

## 13. Customers

### 13.1 List Customers

#### TC-CUST-001: Akses halaman customers
**Precondition:** Login dengan akses customer
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/customers` | List customers ditampilkan |
| 2 | - | Kolom: name, phone, email, points, total transactions, status |

#### TC-CUST-002: Search customers
**Precondition:** Beberapa customers sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input search: "john" | Customers dengan nama/phone/email mengandung "john" |

---

### 13.2 Create Customer

#### TC-CUST-003: Buat customer baru
**Precondition:** Login dengan permission
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/customers/create` | Form ditampilkan |
| 2 | Input name: "John Doe" | Field terisi |
| 3 | Input phone: "08123456789" | Field terisi |
| 4 | Input email: "john@example.com" | Field terisi |
| 5 | Input address | Field terisi |
| 6 | Klik "Save" | Customer ter-create dengan points: 0 |

#### TC-CUST-004: Validasi phone unique
**Precondition:** Customer dengan phone "08123456789" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buat customer dengan phone yang sama | Error: "Phone number already exists." |

---

### 13.3 Customer Points

#### TC-CUST-005: Tambah points manual
**Precondition:** Customer sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka detail customer | Detail ditampilkan |
| 2 | Klik "Add Points" | Modal/form muncul |
| 3 | Input points: 100 | Field terisi |
| 4 | Input reason: "Bonus registration" | Field terisi |
| 5 | Klik "Add" | Points bertambah, history tercatat |

---

## 14. Pricing - Payment Methods

### 14.1 List Payment Methods

#### TC-PAY-001: Akses halaman payment methods
**Precondition:** Login dengan akses pricing
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pricing/payment-methods` | List payment methods ditampilkan |
| 2 | - | Kolom: name, type, fee, status |

---

### 14.2 Create Payment Method

#### TC-PAY-002: Buat payment method Cash
**Precondition:** Login dengan permission
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pricing/payment-methods/create` | Form ditampilkan |
| 2 | Input name: "Cash" | Field terisi |
| 3 | Pilih type: "cash" | Type terpilih |
| 4 | Fee: 0 | Field terisi |
| 5 | Klik "Save" | Payment method ter-create |

#### TC-PAY-003: Buat payment method dengan fee
**Precondition:** Login dengan permission
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input name: "Credit Card" | Field terisi |
| 2 | Pilih type: "card" | Type terpilih |
| 3 | Input fee_type: "percentage" | Fee type terpilih |
| 4 | Input fee_value: 2.5 | Fee terisi |
| 5 | Klik "Save" | Payment method ter-create dengan fee |

---

## 15. Pricing - Discounts

### 15.1 List Discounts

#### TC-DISC-001: Akses halaman discounts
**Precondition:** Login dengan akses pricing
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pricing/discounts` | List discounts ditampilkan |
| 2 | - | Kolom: name, type, value, valid period, status |

---

### 15.2 Create Discount

#### TC-DISC-002: Buat discount percentage
**Precondition:** Login dengan permission
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pricing/discounts/create` | Form ditampilkan |
| 2 | Input name: "Weekend Special" | Field terisi |
| 3 | Pilih type: "percentage" | Type terpilih |
| 4 | Input value: 10 | Value terisi |
| 5 | Set valid_from dan valid_until | Dates terpilih |
| 6 | Pilih applicable items/categories (opsional) | Items terpilih |
| 7 | Klik "Save" | Discount ter-create |

#### TC-DISC-003: Buat discount fixed amount
**Precondition:** Login dengan permission
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Pilih type: "fixed" | Type terpilih |
| 2 | Input value: 5000 | Value terisi |
| 3 | Klik "Save" | Discount ter-create |

---

## 16. Pricing - Price Management

### 16.1 Price List

#### TC-PRICE-001: Lihat price list
**Precondition:** Items dengan harga
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pricing/prices` | Price list ditampilkan |
| 2 | - | Kolom: item, unit, cost, selling price, margin |

---

### 16.2 Bulk Price Edit

#### TC-PRICE-002: Bulk edit prices
**Precondition:** Multiple items
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pricing/prices/bulk-edit` | Bulk edit form ditampilkan |
| 2 | Pilih items untuk edit | Items terpilih |
| 3 | Input adjustment type: "percentage increase" | Type terpilih |
| 4 | Input value: 10 | Value terisi |
| 5 | Klik "Apply" | Preview perubahan ditampilkan |
| 6 | Konfirmasi changes | Prices terupdate |

#### TC-PRICE-003: Copy prices antar outlet
**Precondition:** Multiple outlets dengan prices berbeda
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Pilih source outlet | Source terpilih |
| 2 | Pilih destination outlet | Destination terpilih |
| 3 | Klik "Copy Prices" | Confirm dialog |
| 4 | Konfirmasi copy | Prices di destination = prices di source |

---

## 17. POS - Session Management

### 17.1 Open Session

#### TC-SES-001: Buka session kasir
**Precondition:** Login sebagai cashier, tidak ada session aktif
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pos` atau `/pos/sessions/open` | Form open session ditampilkan |
| 2 | Input opening_cash: 500000 | Field terisi |
| 3 | Klik "Open Session" | - Session ter-create<br>- Status: "open"<br>- Redirect ke POS screen |

#### TC-SES-002: Cegah multiple active sessions
**Precondition:** User sudah punya session aktif
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Coba buka session baru | Error: "You already have an active session." |

---

### 17.2 Close Session

#### TC-SES-003: Tutup session kasir
**Precondition:** Session aktif dengan beberapa transaksi
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pos/sessions/{session}/close` | Form close session ditampilkan |
| 2 | - | System expected cash ditampilkan (opening + sales - refunds) |
| 3 | Input actual_cash: sesuai expected | Field terisi |
| 4 | Input notes (opsional) | Field terisi |
| 5 | Klik "Close Session" | - Session closed<br>- Variance calculated (actual - expected)<br>- Redirect ke session report |

#### TC-SES-004: Close session dengan variance
**Precondition:** Session aktif
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input actual_cash berbeda dari expected | Field terisi |
| 2 | Klik "Close Session" | - Session closed<br>- Variance recorded<br>- Alert jika variance signifikan |

---

### 17.3 Session Report

#### TC-SES-005: Lihat session report
**Precondition:** Session sudah closed
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pos/sessions/{session}/report` | Report ditampilkan |
| 2 | - | Opening cash, total sales, total refunds, expected cash, actual cash, variance |
| 3 | - | Breakdown by payment method |
| 4 | - | Transaction list |

---

## 18. POS - Main Screen

### 18.1 POS Interface

#### TC-POS-001: Akses POS screen
**Precondition:** Login dengan session aktif
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pos` | POS screen ditampilkan |
| 2 | - | Product grid/list, cart, customer section, payment section |

#### TC-POS-002: Load items by category
**Precondition:** Items dengan berbagai kategori
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik kategori "Makanan" | Items dalam kategori "Makanan" ditampilkan |
| 2 | Klik kategori "Minuman" | Items dalam kategori "Minuman" ditampilkan |

#### TC-POS-003: Search items di POS
**Precondition:** Items tersedia
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input search: "ayam" | Items mengandung "ayam" ditampilkan |

---

### 18.2 Cart Operations

#### TC-POS-004: Tambah item ke cart
**Precondition:** POS screen aktif
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik item "Ayam Goreng" | Item ditambahkan ke cart dengan qty: 1 |
| 2 | Klik item yang sama lagi | Qty bertambah menjadi 2 |
| 3 | - | Subtotal di-calculate |

#### TC-POS-005: Ubah qty di cart
**Precondition:** Item sudah ada di cart
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik +/- atau input qty langsung | Qty berubah |
| 2 | - | Subtotal di-recalculate |

#### TC-POS-006: Hapus item dari cart
**Precondition:** Items di cart
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik tombol hapus pada item | Item dihapus dari cart |
| 2 | - | Total di-recalculate |

#### TC-POS-007: Clear cart
**Precondition:** Items di cart
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Clear Cart" | Confirm dialog |
| 2 | Konfirmasi clear | Semua items dihapus dari cart |

---

### 18.3 Customer Selection

#### TC-POS-008: Pilih customer existing
**Precondition:** Customers tersedia
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Select Customer" | Customer search muncul |
| 2 | Search customer: "john" | Matching customers ditampilkan |
| 3 | Pilih customer | Customer terpilih, points ditampilkan |

#### TC-POS-009: Quick add customer baru dari POS
**Precondition:** POS screen aktif
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Add New Customer" | Quick add form muncul |
| 2 | Input name dan phone | Fields terisi |
| 3 | Klik "Save" | Customer ter-create dan terpilih |

---

### 18.4 Discount Application

#### TC-POS-010: Apply discount percentage
**Precondition:** Items di cart, discount aktif tersedia
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Apply Discount" | Available discounts ditampilkan |
| 2 | Pilih discount "Weekend Special (10%)" | Discount applied |
| 3 | - | Discount amount calculated, total updated |

#### TC-POS-011: Apply discount manual
**Precondition:** Items di cart, user punya permission manual discount
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Manual Discount" | Discount input form muncul |
| 2 | Input discount type dan value | Fields terisi |
| 3 | Klik "Apply" | Discount applied ke cart |

---

### 18.5 Price Calculation

#### TC-POS-012: Calculate with tax
**Precondition:** Items dengan tax configured
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Add items ke cart | Subtotal calculated |
| 2 | - | Tax calculated dan ditampilkan |
| 3 | - | Grand total = subtotal + tax - discount |

---

## 19. POS - Checkout

### 19.1 Payment Process

#### TC-CHK-001: Checkout dengan Cash
**Precondition:** Items di cart
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Checkout" | Payment screen/modal muncul |
| 2 | Pilih payment method: "Cash" | Cash selected |
| 3 | Input amount received: 100000 | Amount terisi |
| 4 | - | Change calculated dan ditampilkan |
| 5 | Klik "Complete Payment" | - Transaction ter-create<br>- Stock berkurang (jika track_stock)<br>- Receipt ditampilkan |

#### TC-CHK-002: Checkout dengan Card
**Precondition:** Items di cart, payment method Card tersedia
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Pilih payment method: "Credit Card" | Card selected |
| 2 | - | Fee ditampilkan jika ada |
| 3 | Klik "Complete Payment" | Transaction ter-create dengan payment method card |

#### TC-CHK-003: Split payment
**Precondition:** Items di cart, total: 100000
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Add payment: Cash 50000 | Partial payment recorded |
| 2 | Add payment: Card 50000 | Full payment completed |
| 3 | Klik "Complete Payment" | Transaction ter-create dengan multiple payments |

#### TC-CHK-004: Validasi payment amount
**Precondition:** Items di cart, total: 100000
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Input amount kurang dari total (non-cash) | Error: "Payment amount insufficient." |

---

### 19.2 Receipt

#### TC-CHK-005: Print receipt
**Precondition:** Transaction completed
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Print Receipt" | Receipt preview/print dialog |
| 2 | - | Receipt berisi: outlet info, transaction number, date, items, subtotal, discount, tax, total, payment method, change |

#### TC-CHK-006: Reprint receipt
**Precondition:** Past transaction
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/pos/receipt/{transaction}` | Receipt ditampilkan |
| 2 | Klik "Print" | Receipt can be reprinted |

---

## 20. Transactions

### 20.1 Transaction List

#### TC-TRX-001: Lihat transaction list
**Precondition:** Login dengan akses transactions
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/transactions` | List transactions ditampilkan |
| 2 | - | Kolom: transaction number, date, outlet, customer, total, payment, status |

#### TC-TRX-002: Filter transactions by date
**Precondition:** Transactions dalam periode berbeda
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Set date range | Transactions dalam range ditampilkan |

#### TC-TRX-003: Filter transactions by status
**Precondition:** Transactions dengan berbagai status
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Filter status: "completed" | Hanya completed transactions |
| 2 | Filter status: "refunded" | Hanya refunded transactions |
| 3 | Filter status: "voided" | Hanya voided transactions |

---

### 20.2 Transaction Detail

#### TC-TRX-004: Lihat transaction detail
**Precondition:** Transaction sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik transaction number | Detail ditampilkan |
| 2 | - | Items, quantities, prices, discounts, payments, customer info |

---

### 20.3 Refund

#### TC-TRX-005: Refund transaction (full)
**Precondition:** Completed transaction
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka transaction detail | Detail ditampilkan |
| 2 | Klik "Refund" | Refund form ditampilkan |
| 3 | Select semua items untuk refund | Items terpilih |
| 4 | Input refund reason | Field terisi |
| 5 | Klik "Process Refund" | - Transaction status: "refunded"<br>- Stock dikembalikan (jika track_stock)<br>- Refund recorded di session |

#### TC-TRX-006: Refund transaction (partial)
**Precondition:** Completed transaction dengan multiple items
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Refund" | Refund form ditampilkan |
| 2 | Select hanya beberapa items | Items terpilih |
| 3 | Input qty refund | Qty terisi |
| 4 | Klik "Process Refund" | - Transaction status: "partially_refunded"<br>- Stock dikembalikan sesuai qty refund |

#### TC-TRX-007: Refund dengan permission check
**Precondition:** User tanpa permission refund
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Coba akses refund | Error 403: "You don't have permission to refund." |

---

### 20.4 Void

#### TC-TRX-008: Void transaction
**Precondition:** Recent transaction (within allowed time window)
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Buka transaction detail | Detail ditampilkan |
| 2 | Klik "Void" | Confirm dialog dengan reason input |
| 3 | Input void reason | Field terisi |
| 4 | Konfirmasi void | - Transaction status: "voided"<br>- Stock dikembalikan<br>- Void recorded |

#### TC-TRX-009: Void transaction setelah time window
**Precondition:** Transaction older than allowed void window
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Coba void transaction | Error: "Transaction cannot be voided. Time limit exceeded." |

---

## Phase 2 Summary

| Module | Total Test Cases |
|--------|-----------------|
| Units | 8 |
| Suppliers | 7 |
| Categories | 6 |
| Inventory Items | 10 |
| Stock Management | 8 |
| Purchase Orders | 8 |
| Goods Receive | 7 |
| Stock Adjustments | 7 |
| Stock Transfers | 9 |
| Recipes | 6 |
| Waste Logs | 4 |
| Inventory Reports | 4 |
| Customers | 5 |
| Payment Methods | 3 |
| Discounts | 3 |
| Price Management | 3 |
| POS Sessions | 5 |
| POS Main Screen | 12 |
| POS Checkout | 6 |
| Transactions | 9 |
| **Total** | **130** |

---

## Test Environment Setup

### Prerequisites
1. PHP 8.4+
2. Laravel 12
3. Database MySQL/PostgreSQL
4. Node.js (untuk build assets)

### Database Seeding
```bash
php artisan migrate:fresh --seed
```

### Test Users
| Role | Email | Password |
|------|-------|----------|
| Super Admin | super@admin.com | password |
| Tenant Owner | owner@tenant.com | password |
| Manager | manager@tenant.com | password |
| Cashier | cashier@tenant.com | password |

---

## Notes
- Semua test case harus dijalankan dalam environment testing
- Pastikan database ter-seed dengan data yang sesuai sebelum testing
- Gunakan browser modern (Chrome, Firefox, Safari) untuk UI testing
- Screenshot evidence diperlukan untuk bug reporting
- Test multi-outlet scenarios untuk stock transfers
