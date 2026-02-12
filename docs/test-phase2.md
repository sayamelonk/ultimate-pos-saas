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
| 2 | - | Kolom: name, abbreviation, base unit, conversion factor, status, actions |

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
| 4 | Base Unit: biarkan "None (This is a base unit)" | Tidak memilih base unit |
| 5 | Conversion Factor: 1 | Default value |
| 6 | Klik "Save" | Unit ter-create sebagai base unit, redirect dengan success message |
0
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
| 2 | - | Kolom: code, name, contact person, phone, city, status, actions |

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
| 2 | Input Supplier Code: "SUP001" | Field terisi (required) |
| 3 | Input Supplier Name: "Fresh Foods Co." | Field terisi (required) |
| 4 | Input Contact Person: "John Doe" | Field terisi (optional) |
| 5 | Input Email: "supplier@email.com" | Field terisi (optional) |
| 6 | Input Phone Number: "+62 812 3456 7890" | Field terisi (optional) |
| 7 | Input City: "Jakarta" | Field terisi (optional) |
| 8 | Input Address: "Jl. Raya No. 123" | Field terisi (optional) |
| 9 | Input Tax Number (NPWP): "01.234.567.8-901.000" | Field terisi (optional) |
| 10 | Input Payment Terms (Days): "30" | Field terisi (optional) |
| 11 | Input Notes | Field terisi (optional) |
| 12 | Centang "Active" | Checkbox tercentang (default checked) |
| 13 | Klik "Create Supplier" | Supplier ter-create, redirect dengan success message |

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
| 2 | - | Kolom: code, name, parent, items count, status, actions |

---

### 3.2 Create Category

#### TC-CAT-002: Buat kategori parent
**Precondition:** Login dengan permission
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/categories/create` | Form ditampilkan |
| 2 | Input Category Code: "RAW-FOOD" | Field terisi (required) |
| 3 | Input Category Name: "Bahan Baku" | Field terisi (required) |
| 4 | Parent Category: biarkan "None (Root Category)" | Parent null |
| 5 | Input Description: "Kategori untuk bahan baku" | Field terisi (optional) |
| 6 | Centang "Active" | Checkbox tercentang (default checked) |
| 7 | Klik "Create Category" | Kategori ter-create sebagai parent, redirect dengan success message |

#### TC-CAT-003: Buat sub-kategori
**Precondition:** Kategori parent "Bahan Baku" sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/categories/create` | Form ditampilkan |
| 2 | Input Category Code: "VEG" | Field terisi |
| 3 | Input Category Name: "Sayuran" | Field terisi |
| 4 | Pilih Parent Category: "Bahan Baku" | Parent terpilih |
| 5 | Input Description: "Berbagai jenis sayuran" | Field terisi (optional) |
| 6 | Centang "Active" | Checkbox tercentang |
| 7 | Klik "Create Category" | Sub-kategori ter-create dengan parent |

#### TC-CAT-003b: Validasi form category
**Precondition:** -
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Klik "Create Category" tanpa mengisi apapun | Error validation: code required, name required |

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
| 2 | - | Kolom: item (name + SKU), category, unit, type, cost price, stock level, status, actions |

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
| 1 | Navigasi ke `/inventory/items/create` | Form ditampilkan dengan 3 card: Basic Information, Stock Settings, dan Active checkbox |
| 2 | **Basic Information:** | |
| 3 | Input SKU: "RAW-BEEF-001" | Field terisi (required) |
| 4 | Input Barcode: "8991234567890" | Field terisi (optional) |
| 5 | Input Item Name: "Beef Sirloin" | Field terisi (required) |
| 6 | Pilih Category: "Bahan Baku" | Category terpilih (required) |
| 7 | Pilih Unit of Measure: "Kilogram (kg)" | Unit terpilih (required) |
| 8 | Pilih Type: "Raw Material" | Type terpilih (required) |
| 9 | Input Cost Price: "150000" | Field terisi (required) |
| 10 | Input Description | Field terisi (optional) |
| 11 | **Stock Settings:** | |
| 12 | Input Reorder Level: "10" | Alert ketika stock di bawah nilai ini |
| 13 | Input Reorder Quantity: "50" | Jumlah yang disarankan untuk order |
| 14 | Input Max Stock Level: "200" | Maksimum stock yang disimpan |
| 15 | Input Shelf Life (Days): "7" | Masa simpan dalam hari |
| 16 | Input Storage Location: "Cold Storage A" | Lokasi penyimpanan |
| 17 | Centang "Perishable" | Item dapat kadaluarsa |
| 18 | Centang "Track Batches" | Aktifkan batch/lot tracking |
| 19 | Centang "Active" | Item aktif (default checked) |
| 20 | Klik "Create Item" | Item ter-create, redirect dengan success message |

#### TC-ITEM-006: Buat item finished goods
**Precondition:** Raw materials sudah ada
| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigasi ke `/inventory/items/create` | Form ditampilkan |
| 2 | Input SKU: "FG-FRIED-001" | Field terisi |
| 3 | Input Item Name: "Ayam Goreng" | Field terisi |
| 4 | Pilih Type: "Finished Good" | Type terpilih |
| 5 | Pilih Category dan Unit | Fields terpilih |
| 6 | Input Cost Price | Field terisi |
| 7 | Klik "Create Item" | Item ter-create |

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

## Summary

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
1. PHP 8.3+
2. Laravel 12
3. Database MySQL/PostgreSQL
4. Node.js (untuk build assets)
5. Phase 1 testing completed

### Database Seeding
```bash
php artisan migrate:fresh --seed
```

### Test Data Requirements
- Multiple outlets (at least 2)
- Units (base and derived)
- Suppliers (at least 3)
- Categories (parent and children)
- Inventory items (raw materials and finished goods)
- Recipes with ingredients
- Customers with points
- Payment methods (cash, card, etc.)
- Active discounts

### Test Users
| Role | Email | Password | Permissions |
|------|-------|----------|-------------|
| Super Admin | super@admin.com | password | All |
| Tenant Owner | owner@tenant.com | password | All tenant operations |
| Manager | manager@tenant.com | password | Inventory, POS, Reports |
| Cashier | cashier@tenant.com | password | POS only |
| Inventory Staff | inventory@tenant.com | password | Inventory operations |

---

## Notes
- Semua test case harus dijalankan dalam environment testing
- Pastikan database ter-seed dengan data yang sesuai sebelum testing
- Test POS memerlukan session aktif
- Test stock movements harus memeriksa balance setelah setiap operasi
- Screenshot evidence diperlukan untuk bug reporting
- Test multi-outlet scenarios untuk stock transfers
