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
| 3 | - | Kolom: code, name, email, phone, outlets count, users count, status |

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
| 2 | Input search: "restaurant" | Tenant dengan nama/slug/domain mengandung "restaurant" ditampilkan |

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
| 3 | Input email: "contact@newrestaurant.com" (opsional) | Field terisi |
| 4 | Input phone: "08123456789" (opsional) | Field terisi |
| 5 | Centang "is_active" | Checkbox tercentang |
| 6 | Klik "Save" | - Tenant ter-create dengan code auto-generated<br>- Redirect ke list dengan success message |

#### TC-TENANT-006: Validasi code unique (auto-generated)
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/admin/tenants/create` | Form ditampilkan |
| 2 | Input name: "Test Restaurant" | Field terisi |
| 3 | Klik "Save" | Tenant ter-create dengan code auto-generated yang unique |
| 4 | Buat tenant lain dengan nama sama | Code berbeda (karena ada random suffix) |

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

## Summary

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

## Test Environment Setup

### Prerequisites
1. PHP 8.3+
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
