# Phase 2: Controllers - Inventory Management

## Overview

Dokumentasi ini mencakup semua controllers untuk **Inventory Management Module** di Phase 2. Controllers ini menangani CRUD operations untuk master data inventory seperti Units, Suppliers, Categories, dan Items.

---

## Table of Contents

1. [UnitController](#1-unitcontroller-) - Manajemen satuan unit
2. [SupplierController](#2-suppliercontroller-) - Manajemen supplier
3. [InventoryCategoryController](#3-inventorycategorycontroller-) - Manajemen kategori inventory
4. [InventoryItemController](#4-inventoryitemcontroller-) - Manajemen item inventory

---

## 1. UnitController ⭐

**File:** `app/Http/Controllers/Inventory/UnitController.php`

Controller untuk mengelola satuan unit (kg, gram, pcs, liter, dll) dengan support untuk base unit dan derived units.

### Full Source Code

```php
<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Unit::where('tenant_id', $user->tenant_id)
            ->with('baseUnit');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            if ($request->type === 'base') {
                $query->whereNull('base_unit_id');
            } else {
                $query->whereNotNull('base_unit_id');
            }
        }

        $units = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('inventory.units.index', compact('units'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $baseUnits = Unit::where('tenant_id', $user->tenant_id)
            ->whereNull('base_unit_id')
            ->orderBy('name')
            ->get();

        return view('inventory.units.create', compact('baseUnits'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10'],
            'base_unit_id' => ['nullable', 'exists:units,id'],
            'conversion_factor' => ['required_with:base_unit_id', 'numeric', 'min:0.000001'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['conversion_factor'] = $validated['conversion_factor'] ?? 1;

        Unit::create($validated);

        return redirect()->route('inventory.units.index')
            ->with('success', 'Unit created successfully.');
    }

    public function show(Unit $unit): View
    {
        $this->authorizeUnit($unit);
        $unit->load(['baseUnit', 'derivedUnits']);

        return view('inventory.units.show', compact('unit'));
    }

    public function edit(Unit $unit): View
    {
        $this->authorizeUnit($unit);
        $user = auth()->user();

        $baseUnits = Unit::where('tenant_id', $user->tenant_id)
            ->whereNull('base_unit_id')
            ->where('id', '!=', $unit->id)
            ->orderBy('name')
            ->get();

        return view('inventory.units.edit', compact('unit', 'baseUnits'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorizeUnit($unit);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10'],
            'base_unit_id' => ['nullable', 'exists:units,id'],
            'conversion_factor' => ['required_with:base_unit_id', 'numeric', 'min:0.000001'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['conversion_factor'] = $validated['conversion_factor'] ?? 1;

        $unit->update($validated);

        return redirect()->route('inventory.units.index')
            ->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorizeUnit($unit);

        // Check if unit is used in inventory items
        if ($unit->inventoryItems()->exists()) {
            return back()->with('error', 'Cannot delete unit used by inventory items.');
        }

        // Check if unit has derived units
        if ($unit->derivedUnits()->exists()) {
            return back()->with('error', 'Cannot delete base unit with derived units.');
        }

        $unit->delete();

        return redirect()->route('inventory.units.index')
            ->with('success', 'Unit deleted successfully.');
    }

    private function authorizeUnit(Unit $unit): void
    {
        $user = auth()->user();

        if ($unit->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
```

### Routes

```php
// routes/web.php
Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::resource('units', UnitController::class);
});
```

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/inventory/units` | `inventory.units.index` | List all units with filters |
| GET | `/inventory/units/create` | `inventory.units.create` | Show create form |
| POST | `/inventory/units` | `inventory.units.store` | Store new unit |
| GET | `/inventory/units/{unit}` | `inventory.units.show` | Show unit details |
| GET | `/inventory/units/{unit}/edit` | `inventory.units.edit` | Show edit form |
| PUT/PATCH | `/inventory/units/{unit}` | `inventory.units.update` | Update unit |
| DELETE | `/inventory/units/{unit}` | `inventory.units.destroy` | Delete unit |

### Features

#### 1. Index - List & Filter
- **Search**: Search by name or abbreviation
- **Type Filter**: Filter base units or derived units
- **Pagination**: 15 items per page
- **Eager Loading**: Loads baseUnit relationship

```php
// Example: Search units
GET /inventory/units?search=kg

// Example: Filter only base units
GET /inventory/units?type=base

// Example: Filter only derived units
GET /inventory/units?type=derived
```

#### 2. Create & Store
- Select base unit (optional) for derived units
- Conversion factor auto-sets to 1 if no base unit
- Auto-assign tenant_id from authenticated user

```php
// Create base unit (kg)
POST /inventory/units
{
    "name": "Kilogram",
    "abbreviation": "kg",
    "is_active": true
}

// Create derived unit (gram)
POST /inventory/units
{
    "name": "Gram",
    "abbreviation": "g",
    "base_unit_id": "uuid-of-kg",
    "conversion_factor": 0.001,
    "is_active": true
}
```

#### 3. Show Details
- Loads baseUnit and derivedUnits relationships
- Shows conversion hierarchy

#### 4. Edit & Update
- Cannot set unit as its own parent
- Validates conversion factor if base unit selected

#### 5. Delete with Validation
- **Prevents deletion** if used by inventory items
- **Prevents deletion** if has derived units (for base units)

### Validation Rules

| Field | Rules | Description |
|-------|-------|-------------|
| `name` | required, string, max:50 | Unit name |
| `abbreviation` | required, string, max:10 | Short code (kg, g, L) |
| `base_unit_id` | nullable, exists:units,id | Parent unit |
| `conversion_factor` | required_with:base_unit_id, numeric, min:0.000001 | Conversion to base |
| `is_active` | boolean | Active status |

### Security

- **Tenant Isolation**: All queries scoped to tenant_id
- **Authorization**: `authorizeUnit()` checks ownership before edit/delete

---

## 2. SupplierController ⭐

**File:** `app/Http/Controllers/Inventory/SupplierController.php`

Controller untuk mengelola data supplier inventory.

### Full Source Code

```php
<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Supplier::where('tenant_id', $user->tenant_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $suppliers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('inventory.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('inventory.suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['is_active'] = $request->boolean('is_active', true);

        Supplier::create($validated);

        return redirect()->route('inventory.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier): View
    {
        $this->authorizeSupplier($supplier);
        $supplier->load(['supplierItems.inventoryItem', 'purchaseOrders' => function ($q) {
            $q->latest()->take(10);
        }]);

        return view('inventory.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorizeSupplier($supplier);

        return view('inventory.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeSupplier($supplier);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $supplier->update($validated);

        return redirect()->route('inventory.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorizeSupplier($supplier);

        // Check for open purchase orders
        if ($supplier->purchaseOrders()->whereNotIn('status', ['cancelled', 'received'])->exists()) {
            return back()->with('error', 'Cannot delete supplier with open purchase orders.');
        }

        $supplier->delete();

        return redirect()->route('inventory.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    private function authorizeSupplier(Supplier $supplier): void
    {
        $user = auth()->user();

        if ($supplier->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
```

### Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/inventory/suppliers` | `inventory.suppliers.index` | List suppliers |
| GET | `/inventory/suppliers/create` | `inventory.suppliers.create` | Create form |
| POST | `/inventory/suppliers` | `inventory.suppliers.store` | Store supplier |
| GET | `/inventory/suppliers/{supplier}` | `inventory.suppliers.show` | Show details |
| GET | `/inventory/suppliers/{supplier}/edit` | `inventory.suppliers.edit` | Edit form |
| PUT/PATCH | `/inventory/suppliers/{supplier}` | `inventory.suppliers.update` | Update supplier |
| DELETE | `/inventory/suppliers/{supplier}` | `inventory.suppliers.destroy` | Delete supplier |

### Features

#### 1. Index with Filters
- **Search**: Search name, code, contact person, email
- **Status Filter**: Active/Inactive

```php
// Search supplier
GET /inventory/suppliers?search=PT%20Food

// Filter active only
GET /inventory/suppliers?status=active
```

#### 2. Store & Update
- Complete supplier information
- Payment terms (NET 30, NET 60, etc.)
- Tax number for invoicing

```php
POST /inventory/suppliers
{
    "code": "SUP-001",
    "name": "PT Food Supplier",
    "contact_person": "John Doe",
    "email": "john@foodsupplier.com",
    "phone": "08123456789",
    "address": "Jl. Food No. 123",
    "city": "Jakarta",
    "tax_number": "01.234.567.8-901.000",
    "payment_terms": 30,
    "notes": "Preferred supplier for flour",
    "is_active": true
}
```

#### 3. Show Details
- Loads supplier items with inventory
- Shows last 10 purchase orders

#### 4. Delete Protection
- Prevents deletion if open purchase orders exist
- Only allows deletion if all orders are cancelled or received

---

## 3. InventoryCategoryController ⭐

**File:** `app/Http/Controllers/Inventory/InventoryCategoryController.php`

Controller untuk mengelola kategori inventory dengan support hierarchy (parent-child).

### Full Source Code

```php
<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InventoryCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->with('parent');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        $categories = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('inventory.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $parentCategories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('inventory.categories.create', compact('parentCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:inventory_categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        InventoryCategory::create($validated);

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(InventoryCategory $category): View
    {
        $this->authorizeCategory($category);
        $category->load(['parent', 'children', 'inventoryItems' => function ($q) {
            $q->orderBy('name')->take(20);
        }]);

        return view('inventory.categories.show', compact('category'));
    }

    public function edit(InventoryCategory $category): View
    {
        $this->authorizeCategory($category);
        $user = auth()->user();

        $parentCategories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('inventory.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, InventoryCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:inventory_categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        // Prevent category from being its own parent
        if ($validated['parent_id'] === $category->id) {
            return back()->with('error', 'Category cannot be its own parent.');
        }

        $category->update($validated);

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(InventoryCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category);

        // Check for child categories
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories.');
        }

        // Check for inventory items
        if ($category->inventoryItems()->exists()) {
            return back()->with('error', 'Cannot delete category with inventory items.');
        }

        $category->delete();

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function authorizeCategory(InventoryCategory $category): void
    {
        $user = auth()->user();

        if ($category->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
```

### Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/inventory/categories` | `inventory.categories.index` | List categories |
| GET | `/inventory/categories/create` | `inventory.categories.create` | Create form |
| POST | `/inventory/categories` | `inventory.categories.store` | Store category |
| GET | `/inventory/categories/{category}` | `inventory.categories.show` | Show details |
| GET | `/inventory/categories/{category}/edit` | `inventory.categories.edit` | Edit form |
| PUT/PATCH | `/inventory/categories/{category}` | `inventory.categories.update` | Update category |
| DELETE | `/inventory/categories/{category}` | `inventory.categories.destroy` | Delete category |

### Features

#### 1. Hierarchy Support
- Parent-child relationships
- Create root categories or subcategories
- Auto-generate slug from name

```php
// Create root category
POST /inventory/categories
{
    "code": "CAT-001",
    "name": "Proteins",
    "description": "Meat, poultry, seafood",
    "is_active": true
}

// Create subcategory
POST /inventory/categories
{
    "code": "CAT-002",
    "name": "Beef",
    "parent_id": "parent-uuid",
    "description": "All beef products",
    "is_active": true
}
```

#### 2. Index Filters
- **Search**: Name or code
- **Parent Filter**: Filter by parent or show root only

```php
// Show root categories only
GET /inventory/categories?parent_id=root

// Show subcategories of specific parent
GET /inventory/categories?parent_id=uuid-of-parent
```

#### 3. Show Details
- Loads parent, children, and inventory items
- Shows category hierarchy

#### 4. Delete Protection
- Cannot delete if has child categories
- Cannot delete if has inventory items
- Prevents self-parent assignment

---

## 4. InventoryItemController ⭐

**File:** `app/Http/Controllers/Inventory/InventoryItemController.php`

Controller untuk mengelola master data inventory items.

### Full Source Code

```php
<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = InventoryItem::where('tenant_id', $user->tenant_id)
            ->with(['category', 'unit']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();

        $categories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return view('inventory.items.index', compact('items', 'categories'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $categories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.items.create', compact('categories', 'units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:inventory_categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'type' => ['required', 'in:raw_material,finished_good,consumable,packaging'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'is_perishable' => ['boolean'],
            'track_batches' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['is_perishable'] = $request->boolean('is_perishable', false);
        $validated['track_batches'] = $request->boolean('track_batches', false);
        $validated['is_active'] = $request->boolean('is_active', true);

        InventoryItem::create($validated);

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item created successfully.');
    }

    public function show(InventoryItem $item): View
    {
        $this->authorizeItem($item);
        $item->load([
            'category',
            'unit',
            'supplierItems.supplier',
            'stocks.outlet',
            'stockBatches' => function ($q) {
                $q->where('remaining_quantity', '>', 0)->orderBy('expiry_date');
            },
        ]);

        return view('inventory.items.show', compact('item'));
    }

    public function edit(InventoryItem $item): View
    {
        $this->authorizeItem($item);
        $user = auth()->user();

        $categories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.items.edit', compact('item', 'categories', 'units'));
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:inventory_categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'type' => ['required', 'in:raw_material,finished_good,consumable,packaging'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'is_perishable' => ['boolean'],
            'track_batches' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_perishable'] = $request->boolean('is_perishable', false);
        $validated['track_batches'] = $request->boolean('track_batches', false);
        $validated['is_active'] = $request->boolean('is_active', true);

        $item->update($validated);

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        // Check for existing stock
        if ($item->stocks()->where('quantity', '>', 0)->exists()) {
            return back()->with('error', 'Cannot delete item with existing stock.');
        }

        // Check for recipes using this item
        if ($item->recipeItems()->exists()) {
            return back()->with('error', 'Cannot delete item used in recipes.');
        }

        $item->delete();

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item deleted successfully.');
    }

    private function authorizeItem(InventoryItem $item): void
    {
        $user = auth()->user();

        if ($item->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
```

### Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/inventory/items` | `inventory.items.index` | List items |
| GET | `/inventory/items/create` | `inventory.items.create` | Create form |
| POST | `/inventory/items` | `inventory.items.store` | Store item |
| GET | `/inventory/items/{item}` | `inventory.items.show` | Show details |
| GET | `/inventory/items/{item}/edit` | `inventory.items.edit` | Edit form |
| PUT/PATCH | `/inventory/items/{item}` | `inventory.items.update` | Update item |
| DELETE | `/inventory/items/{item}` | `inventory.items.destroy` | Delete item |

### Features

#### 1. Advanced Filtering
- **Search**: Name, SKU, barcode
- **Category Filter**: Filter by category
- **Type Filter**: raw_material, finished_good, consumable, packaging
- **Status Filter**: Active/Inactive

```php
// Search items
GET /inventory/items?search=flour

// Filter by category
GET /inventory/items?category_id=uuid

// Filter by type
GET /inventory/items?type=raw_material

// Combine filters
GET /inventory/items?search=flour&type=raw_material&status=active
```

#### 2. Create & Store
- Comprehensive item information
- Multiple item types supported
- Reorder management (level, quantity, max stock)
- Shelf life tracking for perishables
- Batch tracking option

```php
POST /inventory/items
{
    "sku": "FLOUR-001",
    "barcode": "899100210001",
    "name": "Tepung Terigu Premium",
    "description": "High quality wheat flour",
    "category_id": "category-uuid",
    "unit_id": "unit-uuid",
    "type": "raw_material",
    "cost_price": 85000,
    "reorder_level": 100,
    "reorder_quantity": 200,
    "max_stock_level": 500,
    "shelf_life_days": 180,
    "storage_location": "Warehouse A-1",
    "is_perishable": true,
    "track_batches": true,
    "is_active": true
}
```

#### 3. Show Details
- Loads category, unit, suppliers
- Shows stock across outlets
- Shows active batches (with remaining quantity)

#### 4. Delete Protection
- Cannot delete if has existing stock
- Cannot delete if used in recipes

### Item Types

| Type | Description | Example |
|------|-------------|---------|
| `raw_material` | Bahan baku mentah | Tepung, gula, telur |
| `finished_good` | Produk jadi | Kue siap saji |
| `consumable` | Barang pakai | Minyak goreng, gas |
| `packaging` | Kemasan | Box, plastik, cup |

---

## Common Patterns Across Controllers

### 1. Tenant Isolation

Semua controllers menggunakan pattern yang sama untuk tenant isolation:

```php
public function index(Request $request): View
{
    $user = auth()->user();
    $query = Model::where('tenant_id', $user->tenant_id);

    // ... filters ...

    return view('...', compact('models'));
}

public function store(Request $request): RedirectResponse
{
    $user = auth()->user();

    $validated = $request->validate([...]);
    $validated['tenant_id'] = $user->tenant_id;

    Model::create($validated);

    return redirect()->route('...')->with('success', '...');
}
```

### 2. Authorization

Semua controllers memiliki private method untuk authorization:

```php
private function authorizeModel(Model $model): void
{
    $user = auth()->user();

    if ($model->tenant_id !== $user->tenant_id) {
        abort(403, 'Access denied.');
    }
}

// Digunakan di show, edit, update, destroy
public function show(Model $model): View
{
    $this->authorizeModel($model);
    // ...
}
```

### 3. Delete Protection

Pattern untuk mencegah deletion jika ada related data:

```php
public function destroy(Model $model): RedirectResponse
{
    $this->authorizeModel($model);

    // Check related data
    if ($model->related()->exists()) {
        return back()->with('error', 'Cannot delete...');
    }

    $model->delete();

    return redirect()->route('...')
        ->with('success', '...');
}
```

### 4. Boolean Handling

Pattern untuk handle boolean input dari forms:

```php
// In validation
$validated['is_active'] = ['boolean'];

// In store/update
$validated['is_active'] = $request->boolean('is_active', true);
// Second parameter is default value
```

### 5. Query String Preservation

Untuk maintain filters saat pagination:

```php
$models = $query->paginate(15)->withQueryString();
```

---

## Route Definitions

### Complete Route Group

```php
// routes/web.php
Route::middleware(['auth'])->prefix('inventory')->name('inventory.')->group(function () {
    // Units
    Route::resource('units', UnitController::class);

    // Suppliers
    Route::resource('suppliers', SupplierController::class);

    // Categories
    Route::resource('categories', InventoryCategoryController::class)
        ->parameter('categories', 'category');

    // Items
    Route::resource('items', InventoryItemController::class)
        ->parameter('items', 'item');
});
```

---

## Views Structure

```
resources/views/inventory/
├── units/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── suppliers/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── categories/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
└── items/
    ├── index.blade.php
    ├── create.blade.php
    ├── show.blade.php
    └── edit.blade.php
```

---

## Testing

### Example Tests

```php
<?php

namespace Tests\Feature\Controllers\Inventory;

use App\Models\Unit;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_index_displays_units(): void
    {
        Unit::factory()->create(['tenant_id' => $this->user->tenant_id]);

        $response = $this->actingAs($this->user)
            ->get(route('inventory.units.index'));

        $response->assertStatus(200);
        $response->assertViewHas('units');
    }

    public function test_store_creates_unit(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('inventory.units.store'), [
                'name' => 'Kilogram',
                'abbreviation' => 'kg',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('inventory.units.index'));
        $this->assertDatabaseHas('units', [
            'name' => 'Kilogram',
            'abbreviation' => 'kg',
        ]);
    }

    public function test_update_modifies_unit(): void
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->user->tenant_id]);

        $response = $this->actingAs($this->user)
            ->put(route('inventory.units.update', $unit), [
                'name' => 'Updated Name',
                'abbreviation' => 'kg',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('inventory.units.index'));
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_destroy_deletes_unit(): void
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->user->tenant_id]);

        $response = $this->actingAs($this->user)
            ->delete(route('inventory.units.destroy', $unit));

        $response->assertRedirect(route('inventory.units.index'));
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_cannot_delete_unit_used_by_items(): void
    {
        $unit = Unit::factory()
            ->has(InventoryItem::factory())
            ->create(['tenant_id' => $this->user->tenant_id]);

        $response = $this->actingAs($this->user)
            ->delete(route('inventory.units.destroy', $unit));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    public function test_unauthorized_user_cannot_access_other_tenant_units(): void
    {
        $otherTenant = Tenant::factory()->create();
        $unit = Unit::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->user)
            ->get(route('inventory.units.show', $unit));

        $response->assertStatus(403);
    }
}
```

---

## Best Practices

### 1. Always Use Route Model Binding

```php
// Good
public function show(Unit $unit): View
{
    $this->authorizeUnit($unit);
    // ...
}

// Avoid
public function show($id): View
{
    $unit = Unit::findOrFail($id);
    // ...
}
```

### 2. Validate Then Assign

```php
// Good
$validated = $request->validate([...]);
$validated['tenant_id'] = $user->tenant_id;
Model::create($validated);

// Avoid
Model::create([
    'tenant_id' => $user->tenant_id,
    'name' => $request->name, // Not validated!
]);
```

### 3. Use Eager Loading for Index

```php
// Good
$items = InventoryItem::where('tenant_id', $user->tenant_id)
    ->with(['category', 'unit'])
    ->paginate(15);

// Avoid - N+1 query problem
$items = InventoryItem::where('tenant_id', $user->tenant_id)
    ->paginate(15);
```

### 4. Preserve Query String

```php
// Good
$models = $query->paginate(15)->withQueryString();

// Avoid - Filters reset on pagination
$models = $query->paginate(15);
```

---

## Next Steps

Lanjut ke dokumentasi berikutnya:
- [Phase 2: Models - Master Data](../phase2-models-1-master-data.md)
- [Phase 2: Services](../phase2-services.md)
- [Phase 2: Views](../phase2-views.md)
