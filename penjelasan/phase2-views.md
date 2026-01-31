# Phase 2: Blade Views - Inventory Management UI

## Overview

Phase 2 views menyediakan user interface untuk inventory management menggunakan Laravel Blade dan Tailwind CSS v4. Views ini mengikuti design system yang elegan dan professional.

---

## View Structure

### Folder Organization
```
resources/views/
├── layouts/
│   └── app.blade.php              # Main layout
├── inventory/
│   ├── items/
│   │   ├── index.blade.php        # List items
│   │   ├── create.blade.php       # Create item form
│   │   ├── edit.blade.php         # Edit item form
│   │   └── show.blade.php         # Item details
│   ├── categories/
│   │   ├── index.blade.php        # List categories
│   │   ├── create.blade.php       # Create category
│   │   └── edit.blade.php         # Edit category
│   ├── stocks/
│   │   ├── index.blade.php        # Stock overview
│   │   └── low-stock.blade.php    # Low stock alerts
│   ├── stock-adjustments/
│   │   └── stock-take.blade.php   # Stock take form
│   ├── stock-transfers/
│   │   ├── index.blade.php        # Transfer history
│   │   └── create.blade.php       # New transfer
│   ├── recipes/
│   │   ├── index.blade.php        # Recipe list
│   │   ├── create.blade.php       # Create recipe
│   │   └── edit.blade.php         # Edit recipe
│   └── reports/
│       ├── stock-valuation.blade.php
│       └── stock-movement.blade.php
└── components/                     # Reusable components
    ├── button.blade.php
    ├── input.blade.php
    ├── table.blade.php
    └── ...
```

---

## Design Principles

### 1. Component-Based
Gunakan reusable components untuk consistency:

```blade
<x-button variant="primary" icon="plus">
    Add Item
</x-button>
```

### 2. Tailwind CSS v4
Menggunakan Tailwind CSS v4 dengan theme colors:

```css
@import "tailwindcss";

@theme {
  --color-primary: #1E3A5F;
  --color-secondary: #64748B;
  --color-accent: #0EA5E9;
  --color-success: #10B981;
  --color-warning: #F59E0B;
  --color-danger: #EF4444;
}
```

### 3. Responsive Design
All views harus mobile-friendly:

```blade
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Responsive: 1 col mobile, 3 cols desktop -->
</div>
```

### 4. Alpine.js Integration
Gunakan Alpine.js untuk interactivity tanpa JavaScript complexity:

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

---

## 1. Inventory Items Views

### index.blade.php
List semua inventory items dengan search dan filter.

**Key Features:**
- Search bar (name, SKU, barcode)
- Category filter dropdown
- Status filter (active/inactive)
- Data table dengan pagination
- Actions dropdown (view, edit, delete)

**Code Structure:**
```blade
@extends('layouts.app')

@section('title', 'Inventory Items')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Inventory Items</h1>
            <p class="text-gray-600">Manage your inventory items</p>
        </div>
        <a href="{{ route('inventory.items.create') }}" class="...">
            <x-button variant="primary" icon="plus">Add Item</x-button>
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <x-input
                    type="search"
                    name="search"
                    placeholder="Search by name, SKU, or barcode..."
                    value="{{ request('search') }}"
                />
            </div>

            <!-- Category Filter -->
            <div>
                <x-select name="category_id">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <!-- Status Filter -->
            <div>
                <x-select name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                        Active
                    </option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                        Inactive
                    </option>
                </x-select>
            </div>

            <!-- Search Button -->
            <div class="flex items-end">
                <x-button variant="secondary" class="w-full">
                    Search
                </x-button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <x-table>
            <x-thead>
                <x-tr>
                    <x-th>SKU</x-th>
                    <x-th>Name</x-th>
                    <x-th>Category</x-th>
                    <x-th>Unit</x-th>
                    <x-th>Cost Price</x-th>
                    <x-th>Status</x-th>
                    <x-th class="text-right">Actions</x-th>
                </x-tr>
            </x-thead>
            <x-tbody>
                @forelse($items as $item)
                    <x-tr>
                        <x-td>
                            <span class="font-mono text-sm">{{ $item->sku }}</span>
                        </x-td>
                        <x-td>
                            <div class="font-medium text-gray-900">{{ $item->name }}</div>
                            @if($item->barcode)
                                <div class="text-sm text-gray-500">{{ $item->barcode }}</div>
                            @endif
                        </x-td>
                        <x-td>{{ $item->category?->name ?? '-' }}</x-td>
                        <x-td>{{ $item->unit->abbreviation }}</x-td>
                        <x-td>
                            {{ number_format($item->cost_price, 0, ',', '.') }}
                        </x-td>
                        <x-td>
                            <x-badge variant="{{ $item->is_active ? 'success' : 'secondary' }}">
                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </x-td>
                        <x-td class="text-right">
                            <x-dropdown>
                                <x-dropdown-item href="{{ route('inventory.items.show', $item) }}">
                                    View
                                </x-dropdown-item>
                                <x-dropdown-item href="{{ route('inventory.items.edit', $item) }}">
                                    Edit
                                </x-dropdown-item>
                                <x-dropdown-item
                                    wire:click="confirmDelete({{ $item->id }})"
                                    variant="danger"
                                >
                                    Delete
                                </x-dropdown-item>
                            </x-dropdown>
                        </x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="7">
                            <x-empty-state
                                title="No items found"
                                description="Get started by creating your first inventory item."
                                actionUrl="{{ route('inventory.items.create') }}"
                                actionText="Add Item"
                            />
                        </x-td>
                    </x-tr>
                @endforelse
            </x-tbody>
        </x-table>

        <!-- Pagination -->
        {{ $items->appends(request()->query())->links() }}
    </div>
</div>
@endsection
```

**UI Components Used:**
- `<x-button>` - Button dengan variants
- `<x-input>` - Input field
- `<x-select>` - Select dropdown
- `<x-table>`, `<x-thead>`, `<x-tr>`, `<x-th>`, `<x-td>`, `<x-tbody>` - Table components
- `<x-badge>` - Status badge
- `<x-dropdown>` - Actions dropdown
- `<x-empty-state>` - Empty state display

---

### create.blade.php & edit.blade.php
Form untuk create/edit inventory item.

**Form Sections:**

#### 1. Basic Information
```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <x-input
            type="text"
            name="sku"
            label="SKU"
            placeholder="ITEM-001"
            required
            value="{{ old('sku', $item->sku ?? '') }}"
        />
    </div>

    <div>
        <x-input
            type="text"
            name="barcode"
            label="Barcode"
            placeholder="1234567890123"
            value="{{ old('barcode', $item->barcode ?? '') }}"
        />
    </div>

    <div class="md:col-span-2">
        <x-input
            type="text"
            name="name"
            label="Item Name"
            placeholder="Enter item name"
            required
            value="{{ old('name', $item->name ?? '') }}"
        />
    </div>

    <div class="md:col-span-2">
        <x-textarea
            name="description"
            label="Description"
            rows="3"
            placeholder="Item description..."
        >{{ old('description', $item->description ?? '') }}</x-textarea>
    </div>
</div>
```

#### 2. Categorization
```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <x-select name="category_id" label="Category" required>
            <option value="">Select Category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}"
                        {{ old('category_id', $item->category_id ?? '') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-select>
    </div>

    <div>
        <x-select name="unit_id" label="Stock Unit" required>
            <option value="">Select Unit</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}"
                        {{ old('unit_id', $item->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                    {{ $unit->name }} ({{ $unit->abbreviation }})
                </option>
            @endforeach
        </x-select>
    </div>
</div>
```

#### 3. Pricing & Stock
```blade
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
        <x-input
            type="number"
            name="cost_price"
            label="Cost Price"
            step="0.01"
            required
            value="{{ old('cost_price', $item->cost_price ?? '') }}"
        />
    </div>

    <div>
        <x-input
            type="number"
            name="reorder_point"
            label="Reorder Point"
            step="0.01"
            placeholder="0.00"
            value="{{ old('reorder_point', $item->reorder_point ?? '') }}"
        />
    </div>

    <div>
        <x-input
            type="number"
            name="reorder_qty"
            label="Reorder Quantity"
            step="0.01"
            placeholder="0.00"
            value="{{ old('reorder_qty', $item->reorder_qty ?? '') }}"
        />
    </div>
</div>
```

#### 4. Settings
```blade
<div class="space-y-4">
    <div>
        <x-checkbox
            name="track_batches"
            label="Track Batches"
            hint="Enable batch/expiry tracking for this item"
            :checked="old('track_batches', $item->track_batches ?? false)"
        />
    </div>

    <div>
        <x-input
            type="number"
            name="shelf_life_days"
            label="Shelf Life (Days)"
            placeholder="e.g., 180"
            value="{{ old('shelf_life_days', $item->shelf_life_days ?? '') }}"
        />
    </div>

    <div>
        <x-checkbox
            name="is_active"
            label="Active"
            :checked="old('is_active', $item->is_active ?? true)"
        />
    </div>
</div>
```

---

### show.blade.php
Display item details dengan stock information.

**Sections:**

#### 1. Header
```blade
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $item->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $item->sku }}</p>
            @if($item->barcode)
                <p class="text-sm text-gray-500">Barcode: {{ $item->barcode }}</p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.items.edit', $item) }}">
                <x-button variant="secondary">Edit</x-button>
            </a>
            <x-button variant="danger">Delete</x-button>
        </div>
    </div>

    @if($item->description)
        <p class="mt-4 text-gray-700">{{ $item->description }}</p>
    @endif
</div>
```

#### 2. Stock Overview
```blade
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    @foreach($item->stocks as $stock)
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $stock->outlet->name }}
                    </h3>
                    <p class="text-sm text-gray-500">Current Stock</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-gray-900">
                        {{ number_format($stock->quantity, 2) }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $item->unit->abbreviation }}</p>
                </div>
            </div>

            @if($stock->isLowStock())
                <div class="mt-4 p-3 bg-warning-light rounded-lg">
                    <p class="text-sm text-warning-dark">
                        ⚠️ Low Stock - Reorder at {{ $stock->inventoryItem->reorder_point }}
                    </p>
                </div>
            @endif
        </x-card>
    @endforeach
</div>
```

#### 3. Active Batches (if track_batches)
```blade
@if($item->track_batches && $item->stockBatches->count() > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Active Batches</h2>

        <x-table>
            <x-thead>
                <x-tr>
                    <x-th>Batch Number</x-th>
                    <x-th>Expiry Date</x-th>
                    <x-th>Quantity</x-th>
                    <x-th>Status</x-th>
                </x-tr>
            </x-thead>
            <x-tbody>
                @foreach($item->stockBatches as $batch)
                    <x-tr>
                        <x-td>{{ $batch->batch_number }}</x-td>
                        <x-td>{{ $batch->expiry_date?->format('M d, Y') }}</x-td>
                        <x-td>{{ number_format($batch->current_qty, 2) }}</x-td>
                        <x-td>
                            <x-badge
                                :variant="$batch->isExpiringSoon() ? 'warning' : 'success'"
                            >
                                {{ $batch->status }}
                            </x-badge>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-tbody>
        </x-table>
    </div>
@endif
```

---

## 2. Stock Management Views

### stocks/low-stock.blade.php
Display low stock items dengan alerts.

**Features:**
- Filter by outlet
- Sort by urgency (days until stockout)
- Bulk reorder action

```blade
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Low Stock Alerts</h1>

    <!-- Outlet Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <x-select name="outlet_id" label="Filter by Outlet">
            <option value="">All Outlets</option>
            @foreach(auth()->user()->outlets as $outlet)
                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
            @endforeach
        </x-select>
    </div>

    <!-- Low Stock Items -->
    <div class="space-y-4">
        @foreach($lowStockItems as $stock)
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ $stock->inventoryItem->name }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ $stock->outlet->name }} • SKU: {{ $stock->inventoryItem->sku }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-2xl font-bold text-danger">
                            {{ number_format($stock->quantity, 2) }}
                        </p>
                        <p class="text-sm text-gray-500">
                            Reorder at: {{ number_format($stock->inventoryItem->reorder_point, 2) }}
                        </p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-danger h-2 rounded-full"
                            style="width: {{ ($stock->quantity / $stock->inventoryItem->reorder_point) * 100 }}%"
                        ></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
```

---

### stock-adjustments/stock-take.blade.php
Stock take form dengan barcode scanning support.

**Features:**
- Outlet selection
- Search item by SKU/barcode
- Quick entry with quantity input
- Auto-calculation of differences

```blade
<div x-data="stockTake()">
    <form method="POST" action="{{ route('inventory.stock.adjust') }}">
        @csrf

        <!-- Outlet Selection -->
        <div class="mb-6">
            <x-select name="outlet_id" label="Outlet" required>
                @foreach(auth()->user()->outlets as $outlet)
                    <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                @endforeach
            </x-select>
        </div>

        <!-- Search -->
        <div class="mb-6">
            <x-input
                type="search"
                x-model="search"
                @keyup.enter="findItem()"
                placeholder="Scan barcode or enter SKU..."
                autofocus
            />
        </div>

        <!-- Item Details (hidden until found) -->
        <div x-show="item" class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold" x-text="item?.name"></h3>
            <p class="text-sm text-gray-500">SKU: <span x-text="item?.sku"></span></p>

            <!-- Current Stock -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">System Stock</p>
                <p class="text-2xl font-bold" x-text="currentStock"></p>
            </div>

            <!-- Actual Stock Input -->
            <div class="mt-4">
                <x-input
                    type="number"
                    x-model="actualStock"
                    label="Actual Stock Count"
                    step="0.01"
                    required
                />
            </div>

            <!-- Difference -->
            <div class="mt-4 p-4 rounded-lg" :class="difference < 0 ? 'bg-danger-light' : 'bg-success-light'">
                <p class="text-sm text-gray-600">Difference</p>
                <p class="text-2xl font-bold" x-text="difference"></p>
            </div>

            <!-- Notes -->
            <div class="mt-4">
                <x-textarea name="notes" label="Notes" rows="2" />
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" name="inventory_item_id" x-model="item?.id">
            <input type="hidden" name="new_quantity" x-model="actualStock">

            <x-button type="submit" variant="primary" class="mt-4">
                Save Adjustment
            </x-button>
        </div>
    </form>
</div>

<script>
function stockTake() {
    return {
        search: '',
        item: null,
        currentStock: 0,
        actualStock: 0,

        get difference() {
            return (this.actualStock - this.currentStock).toFixed(2);
        },

        async findItem() {
            const response = await fetch(`/inventory/items/find?sku=${this.search}`);
            this.item = await response.json();
            this.currentStock = this.item.stocks[0]?.quantity || 0;
            this.actualStock = this.currentStock;
        }
    }
}
</script>
```

---

## 3. Recipe Management Views

### recipes/create.blade.php
Recipe builder dengan dynamic ingredient list.

**Features:**
- Dynamic add/remove ingredients
- Unit conversion calculator
- Live cost calculation
- Waste percentage input

```blade
<div x-data="recipeBuilder()">
    <form method="POST" action="{{ route('inventory.recipes.store') }}">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Recipe Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input
                    type="text"
                    name="name"
                    label="Recipe Name"
                    required
                    x-model="name"
                />

                <x-input
                    type="number"
                    name="yield_qty"
                    label="Yield Quantity"
                    step="0.01"
                    required
                    x-model="yieldQty"
                />

                <x-select name="yield_unit_id" label="Yield Unit" required>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </x-select>

                <div></div> <!-- Spacer -->
            </div>

            <x-textarea name="instructions" label="Instructions" rows="4" />
        </div>

        <!-- Ingredients -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Ingredients</h2>
                <x-button type="button" @click="addIngredient()" variant="secondary">
                    Add Ingredient
                </x-button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Item</th>
                            <th class="px-4 py-2 text-left">Quantity</th>
                            <th class="px-4 py-2 text-left">Unit</th>
                            <th class="px-4 py-2 text-left">Waste %</th>
                            <th class="px-4 py-2 text-left">Cost</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(ing, index) in ingredients" :key="index">
                            <tr>
                                <td class="px-4 py-2">
                                    <select x-model="ing.inventory_item_id" class="w-full">
                                        <option value="">Select Item</option>
                                        @foreach($inventoryItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" x-model="ing.quantity" step="0.01" class="w-full">
                                </td>
                                <td class="px-4 py-2">
                                    <select x-model="ing.unit_id" class="w-full">
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->abbreviation }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" x-model="ing.waste_percentage" class="w-20" placeholder="0">
                                </td>
                                <td class="px-4 py-2" x-text="calculateItemCost(ing)"></td>
                                <td class="px-4 py-2">
                                    <button type="button" @click="removeIngredient(index)" class="text-danger">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Total Cost -->
            <div class="mt-6 p-4 bg-primary-light rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold">Total Recipe Cost:</span>
                    <span class="text-2xl font-bold" x-text="formatCurrency(totalCost)"></span>
                </div>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-sm">Cost per Unit:</span>
                    <span class="font-semibold" x-text="formatCurrency(costPerUnit)"></span>
                </div>
            </div>
        </div>

        <x-button type="submit" variant="primary" class="w-full">
            Save Recipe
        </x-button>
    </form>
</div>

<script>
function recipeBuilder() {
    return {
        name: '',
        yieldQty: 1,
        ingredients: [],

        get totalCost() {
            return this.ingredients.reduce((sum, ing) => sum + this.calculateItemCost(ing), 0);
        },

        get costPerUnit() {
            return this.totalCost / this.yieldQty;
        },

        addIngredient() {
            this.ingredients.push({
                inventory_item_id: '',
                quantity: 0,
                unit_id: '',
                waste_percentage: 0
            });
        },

        removeIngredient(index) {
            this.ingredients.splice(index, 1);
        },

        calculateItemCost(ingredient) {
            // Calculate cost based on quantity, waste%, and item cost price
            // This would need to fetch item data
            return 0;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(value);
        }
    }
}
</script>
```

---

## Component Library

### Key Blade Components

#### Button Component
```blade
<x-button variant="primary|secondary|success|danger" icon="heroicon">
    Button Text
</x-button>
```

#### Input Component
```blade
<x-input
    type="text|email|number|..."
    name="field_name"
    label="Field Label"
    placeholder="Placeholder..."
    required
    value="..."
/>
```

#### Table Component
```blade
<x-table>
    <x-thead>
        <x-tr>
            <x-th>Header 1</x-th>
            <x-th>Header 2</x-th>
        </x-tr>
    </x-thead>
    <x-tbody>
        <x-tr>
            <x-td>Data 1</x-td>
            <x-td>Data 2</x-td>
        </x-tr>
    </x-tbody>
</x-table>
```

#### Badge Component
```blade
<x-badge variant="success|warning|danger|secondary">
    Label
</x-badge>
```

#### Card Component
```blade
<x-card>
    <h3>Card Title</h3>
    <p>Card content...</p>
</x-card>
```

---

## Best Practices

### 1. Always Use Components
```blade
<!-- ❌ Bad -->
<button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
    Click
</button>

<!-- ✅ Good -->
<x-button variant="primary">Click</x-button>
```

### 2. Responsive Classes
```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <!-- Mobile: 1 col, Tablet: 2 cols, Desktop: 3 cols -->
</div>
```

### 3. Conditional Rendering
```blade
@if($condition)
    <!-- Blade directive for server-side -->
@endif

<div x-show="clientCondition">
    <!-- Alpine.js for client-side -->
</div>
```

### 4. CSRF Protection
```blade
<form method="POST">
    @csrf
    <!-- Form fields -->
</form>
```

### 5. Error Messages
```blade
<x-input
    name="field_name"
    :error="$errors->first('field_name')"
/>
```

---

## Next Steps

Lihat dokumentasi berikutnya:
- [Phase 2: Reports - Inventory](./phase2-report-inventory.md) - Inventory reporting views
