# Phase 2: Inventory Reports - Analytics & Insights

## Overview

Phase 2 inventory reports menyediakan comprehensive analytics untuk inventory management, stock valuation, dan cost control. Reports ini membantu dalam decision making dan inventory optimization.

---

## Struktur Folder

```
app/Http/Controllers/Inventory/
└── ReportController.php

resources/views/inventory/reports/
├── stock-valuation.blade.php
├── stock-movement.blade.php
├── cogs.blade.php
└── food-cost.blade.php
```

---

## ReportController ⭐

**File:** `app/Http/Controllers/Inventory/ReportController.php`

Controller utama untuk semua inventory reports dengan 4 report utama: Stock Valuation, Stock Movement, COGS, dan Food Cost Analysis.

### Full Source Code

```php
<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\Recipe;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\WasteLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Stock Valuation Report
     */
    public function stockValuation(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $outletId = $request->get('outlet_id');
        $categoryId = $request->get('category_id');

        $query = Stock::query()
            ->with(['inventoryItem.category', 'inventoryItem.unit', 'outlet'])
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId));

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($categoryId) {
            $query->whereHas('inventoryItem', fn ($q) => $q->where('category_id', $categoryId));
        }

        $stocks = $query->get();

        // Calculate valuations
        $valuationData = $stocks->map(function ($stock) {
            $avgCost = $stock->inventoryItem->cost_price ?? 0;
            $value = $stock->quantity * $avgCost;

            return [
                'item' => $stock->inventoryItem,
                'outlet' => $stock->outlet,
                'quantity' => $stock->quantity,
                'avg_cost' => $avgCost,
                'value' => $value,
            ];
        });

        // Group by category
        $byCategory = $valuationData->groupBy(fn ($item) => $item['item']->category?->name ?? 'Uncategorized')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'quantity' => $items->sum('quantity'),
                'value' => $items->sum('value'),
            ]);

        // Group by outlet
        $byOutlet = $valuationData->groupBy(fn ($item) => $item['outlet']->name)
            ->map(fn ($items) => [
                'count' => $items->count(),
                'quantity' => $items->sum('quantity'),
                'value' => $items->sum('value'),
            ]);

        $totalValue = $valuationData->sum('value');
        $totalItems = $valuationData->count();

        $outlets = Outlet::where('tenant_id', $tenantId)->orderBy('name')->get();
        $categories = InventoryCategory::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('inventory.reports.stock-valuation', compact(
            'valuationData',
            'byCategory',
            'byOutlet',
            'totalValue',
            'totalItems',
            'outlets',
            'categories'
        ));
    }

    /**
     * Stock Movement Report
     */
    public function stockMovement(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $outletId = $request->get('outlet_id');
        $itemId = $request->get('item_id');
        $movementType = $request->get('movement_type');

        $query = StockMovement::query()
            ->with(['inventoryItem.unit', 'outlet', 'user'])
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($itemId) {
            $query->where('inventory_item_id', $itemId);
        }

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        $movements = $query->latest()->paginate(50);

        // Summary by type
        $summaryQuery = StockMovement::query()
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        if ($outletId) {
            $summaryQuery->where('outlet_id', $outletId);
        }

        $summaryByType = $summaryQuery->clone()
            ->selectRaw('movement_type, SUM(quantity) as total_qty, COUNT(*) as count')
            ->groupBy('movement_type')
            ->get()
            ->keyBy('movement_type');

        // Daily trend
        $dailyTrend = StockMovement::query()
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->selectRaw('DATE(created_at) as date, movement_type, SUM(quantity) as total')
            ->groupBy('date', 'movement_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(fn ($items) => $items->keyBy('movement_type'));

        $outlets = Outlet::where('tenant_id', $tenantId)->orderBy('name')->get();
        $items = InventoryItem::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('inventory.reports.stock-movement', compact(
            'movements',
            'summaryByType',
            'dailyTrend',
            'outlets',
            'items',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * COGS Report
     */
    public function cogs(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $outletId = $request->get('outlet_id');

        // Get sale movements (stock deductions for sales)
        $query = StockMovement::query()
            ->with(['inventoryItem.category', 'inventoryItem.unit', 'outlet'])
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('movement_type', 'sale')
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $saleMovements = $query->get();

        // Calculate COGS by item
        $cogsByItem = $saleMovements->groupBy('inventory_item_id')->map(function ($movements) {
            $item = $movements->first()->inventoryItem;
            $totalQty = $movements->sum('quantity');
            $totalCost = $movements->sum(fn ($m) => abs($m->quantity) * ($m->unit_cost ?? $item->cost_price ?? 0));

            return [
                'item' => $item,
                'quantity' => abs($totalQty),
                'total_cost' => $totalCost,
                'avg_cost' => $totalQty != 0 ? $totalCost / abs($totalQty) : 0,
            ];
        })->sortByDesc('total_cost');

        // COGS by category
        $cogsByCategory = $cogsByItem->groupBy(fn ($data) => $data['item']->category?->name ?? 'Uncategorized')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'quantity' => $items->sum('quantity'),
                'total_cost' => $items->sum('total_cost'),
            ])
            ->sortByDesc('total_cost');

        // COGS by outlet
        $cogsByOutlet = $saleMovements->groupBy('outlet_id')->map(function ($movements) {
            $outlet = $movements->first()->outlet;
            $totalCost = $movements->sum(fn ($m) => abs($m->quantity) * ($m->unit_cost ?? $m->inventoryItem->cost_price ?? 0));

            return [
                'outlet' => $outlet,
                'total_cost' => $totalCost,
            ];
        })->sortByDesc('total_cost');

        // Daily COGS trend
        $dailyCogs = StockMovement::query()
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('movement_type', 'sale')
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->selectRaw('DATE(created_at) as date, SUM(ABS(quantity) * COALESCE(unit_cost, 0)) as total_cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalCogs = $cogsByItem->sum('total_cost');

        $outlets = Outlet::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('inventory.reports.cogs', compact(
            'cogsByItem',
            'cogsByCategory',
            'cogsByOutlet',
            'dailyCogs',
            'totalCogs',
            'outlets',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Food Cost Analysis Report
     */
    public function foodCost(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Get all recipes with their costs
        $recipes = Recipe::query()
            ->with(['ingredients.inventoryItem', 'product', 'category', 'yieldUnit'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        // Calculate food cost metrics
        $recipeAnalysis = $recipes->map(function ($recipe) {
            $unitCost = $recipe->yield_quantity > 0 ? $recipe->total_cost / $recipe->yield_quantity : 0;
            $sellingPrice = $recipe->product?->price ?? 0;
            $grossProfit = $sellingPrice - $unitCost;
            $foodCostPercent = $sellingPrice > 0 ? ($unitCost / $sellingPrice) * 100 : 0;
            $grossMarginPercent = $sellingPrice > 0 ? ($grossProfit / $sellingPrice) * 100 : 0;

            return [
                'recipe' => $recipe,
                'unit_cost' => $unitCost,
                'selling_price' => $sellingPrice,
                'gross_profit' => $grossProfit,
                'food_cost_percent' => $foodCostPercent,
                'gross_margin_percent' => $grossMarginPercent,
            ];
        });

        // Summary statistics
        $avgFoodCost = $recipeAnalysis->where('selling_price', '>', 0)->avg('food_cost_percent');
        $avgMargin = $recipeAnalysis->where('selling_price', '>', 0)->avg('gross_margin_percent');

        // High cost items (food cost > 35%)
        $highCostItems = $recipeAnalysis->filter(fn ($r) => $r['food_cost_percent'] > 35 && $r['selling_price'] > 0);

        // Get waste data for the period
        $wasteTotal = WasteLog::query()
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereBetween('waste_date', [$dateFrom, $dateTo])
            ->sum('value');

        // Get purchase data (from GR completed)
        $purchaseTotal = StockMovement::query()
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('movement_type', 'goods_receive')
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as total')
            ->value('total') ?? 0;

        // Food cost by category
        $costByCategory = $recipeAnalysis
            ->where('selling_price', '>', 0)
            ->groupBy(fn ($r) => $r['recipe']->category?->name ?? 'Uncategorized')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'avg_food_cost' => $items->avg('food_cost_percent'),
                'avg_margin' => $items->avg('gross_margin_percent'),
                'total_potential_profit' => $items->sum('gross_profit'),
            ]);

        $categories = InventoryCategory::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('inventory.reports.food-cost', compact(
            'recipeAnalysis',
            'avgFoodCost',
            'avgMargin',
            'highCostItems',
            'wasteTotal',
            'purchaseTotal',
            'costByCategory',
            'categories',
            'dateFrom',
            'dateTo'
        ));
    }
}
```

---

## 1. Stock Valuation Report

**Route:** `GET /inventory/reports/stock-valuation`

### Purpose
Menampilkan total nilai stok berdasarkan cost price (weighted average cost) dengan grouping per category dan outlet.

### Key Metrics

1. **Total Inventory Value** - Σ(quantity × cost_price)
2. **Value by Category** - Breakdown per kategori
3. **Value by Outlet** - Perbandingan antar outlet
4. **Item Details** - Cost per unit dan total value per item

### View Data Structure

```php
[
    'valuationData' => Collection,  // Individual item valuations
    'byCategory' => Collection,      // Grouped by category
    'byOutlet' => Collection,        // Grouped by outlet
    'totalValue' => float,           // Total inventory value
    'totalItems' => int,             // Total stock records
    'outlets' => Collection,         // Filter options
    'categories' => Collection,      // Filter options
]
```

### Calculation Logic

```php
// Per item valuation
$avgCost = $stock->inventoryItem->cost_price ?? 0;
$value = $stock->quantity * $avgCost;

// Group by category
$byCategory = $valuationData->groupBy(fn ($item) =>
    $item['item']->category?->name ?? 'Uncategorized'
)->map(fn ($items) => [
    'count' => $items->count(),
    'quantity' => $items->sum('quantity'),
    'value' => $items->sum('value'),
]);

// Total
$totalValue = $valuationData->sum('value');
```

### Filter Options

- **outlet_id** - Filter by specific outlet
- **category_id** - Filter by specific category

### Usage Example

```php
// In controller
public function stockValuation(Request $request)
{
    $report = app(ReportController::class);
    return $report->stockValuation($request);
}

// In view
@foreach($byCategory as $category => $data)
    <div>
        <h3>{{ $category }}</h3>
        <p>Items: {{ $data['count'] }}</p>
        <p>Value: Rp {{ number_format($data['value'], 0, ',', '.') }}</p>
    </div>
@endforeach
```

---

## 2. Stock Movement Report

**Route:** `GET /inventory/reports/stock-movement`

### Purpose
Melacak semua pergerakan stok dalam periode tertentu dengan summary statistics dan daily trend.

### Key Features

1. **Movement List** - Paginated list semua movements (50 per page)
2. **Summary by Type** - Total quantity per movement type
3. **Daily Trend** - Timeline pergerakan harian
4. **Advanced Filtering** - Date range, outlet, item, type

### View Data Structure

```php
[
    'movements' => LengthAwarePaginator,  // 50 per page
    'summaryByType' => Collection,         // Stats per type
    'dailyTrend' => Collection,            // Daily timeline
    'outlets' => Collection,
    'items' => Collection,
    'dateFrom' => string,                  // Filter value
    'dateTo' => string,                    // Filter value
]
```

### Movement Types

Dari `StockMovement` model:
- `in` - Stock masuk (purchase, return, transfer in)
- `out` - Stock keluar (sale, waste, transfer out)
- `adjustment` - Penyesuaian stok
- `transfer_in` - Transfer masuk
- `transfer_out` - Transfer keluar
- `waste` - Pembuangan

### Summary by Type Logic

```php
$summaryByType = StockMovement::query()
    ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
    ->selectRaw('movement_type, SUM(quantity) as total_qty, COUNT(*) as count')
    ->groupBy('movement_type')
    ->get()
    ->keyBy('movement_type');

// Result:
// [
//     'in' => ['total_qty' => 500, 'count' => 10],
//     'out' => ['total_qty' => -200, 'count' => 25],
//     'adjustment' => ['total_qty' => 50, 'count' => 3],
// ]
```

### Daily Trend Logic

```php
$dailyTrend = StockMovement::query()
    ->selectRaw('DATE(created_at) as date, movement_type, SUM(quantity) as total')
    ->groupBy('date', 'movement_type')
    ->orderBy('date')
    ->get()
    ->groupBy('date')
    ->map(fn ($items) => $items->keyBy('movement_type'));

// Result:
// [
//     '2025-01-15' => [
//         'in' => ['total' => 100],
//         'out' => ['total' => -50],
//     ],
//     '2025-01-16' => [
//         'in' => ['total' => 75],
//         'out' => ['total' => -30],
//     ],
// ]
```

### Filter Options

- **date_from** - Start date (default: start of month)
- **date_to** - End date (default: today)
- **outlet_id** - Filter by outlet
- **item_id** - Filter by specific inventory item
- **movement_type** - Filter by movement type

---

## 3. COGS Report (Cost of Goods Sold)

**Route:** `GET /inventory/reports/cogs`

### Purpose
Menghitung dan menganalisis Cost of Goods Sold berdasarkan sale movements dengan breakdown per item, category, dan outlet.

### Key Features

1. **COGS by Item** - Top items by cost
2. **COGS by Category** - Category analysis
3. **COGS by Outlet** - Outlet comparison
4. **Daily COGS Trend** - Time series analysis

### View Data Structure

```php
[
    'cogsByItem' => Collection,       // Sorted by cost desc
    'cogsByCategory' => Collection,   // Grouped by category
    'cogsByOutlet' => Collection,     // Grouped by outlet
    'dailyCogs' => Collection,        // Daily totals
    'totalCogs' => float,             // Grand total
    'outlets' => Collection,
    'dateFrom' => string,
    'dateTo' => string,
]
```

### COGS Calculation Logic

```php
// Get sale movements (stock out for sales)
$saleMovements = StockMovement::query()
    ->where('movement_type', 'sale')
    ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
    ->get();

// Calculate COGS by item
$cogsByItem = $saleMovements->groupBy('inventory_item_id')->map(function ($movements) {
    $item = $movements->first()->inventoryItem;
    $totalQty = $movements->sum('quantity');  // Negative for sales
    $totalCost = $movements->sum(fn ($m) =>
        abs($m->quantity) * ($m->unit_cost ?? $item->cost_price ?? 0)
    );

    return [
        'item' => $item,
        'quantity' => abs($totalQty),
        'total_cost' => $totalCost,
        'avg_cost' => $totalQty != 0 ? $totalCost / abs($totalQty) : 0,
    ];
})->sortByDesc('total_cost');
```

### Important Notes

1. **Movement Type** - Filter hanya `movement_type = 'sale'`
2. **Absolute Quantity** - Gunakan `abs()` untuk quantity (karena sale movements negatif)
3. **Unit Cost Priority** - Gunakan `unit_cost` dari movement, fallback ke `cost_price` dari item
4. **Date Range** - Default ke current month

### Daily COGS Trend

```php
$dailyCogs = StockMovement::query()
    ->where('movement_type', 'sale')
    ->selectRaw('DATE(created_at) as date, SUM(ABS(quantity) * COALESCE(unit_cost, 0)) as total_cost')
    ->groupBy('date')
    ->orderBy('date')
    ->get();

// Result:
// [
//     ['date' => '2025-01-15', 'total_cost' => 5000000],
//     ['date' => '2025-01-16', 'total_cost' => 6500000],
// ]
```

---

## 4. Food Cost Analysis Report

**Route:** `GET /inventory/reports/food-cost`

### Purpose
Menganalisis food cost percentage untuk semua recipes (menu items) dan identify items dengan food cost terlalu tinggi.

### Key Metrics

1. **Food Cost %** - (Cost / Selling Price) × 100
2. **Gross Margin %** - (Profit / Selling Price) × 100
3. **Average Food Cost** - Rata-rata semua recipes
4. **High Cost Items** - Items dengan food cost > 35%
5. **Waste Impact** - Total waste value untuk periode
6. **Purchase Total** - Total pembelian untuk periode

### Industry Standards

| Metric | Ideal | Acceptable | Warning |
|--------|--------|------------|---------|
| Food Cost % | 25-35% | 35-40% | >40% |
| Gross Margin % | 60-75% | 60-65% | <60% |

### View Data Structure

```php
[
    'recipeAnalysis' => Collection,      // All recipes with metrics
    'avgFoodCost' => float,             // Average food cost %
    'avgMargin' => float,               // Average margin %
    'highCostItems' => Collection,      // Food cost > 35%
    'wasteTotal' => float,              // Total waste value
    'purchaseTotal' => float,           // Total purchases
    'costByCategory' => Collection,      // By recipe category
    'categories' => Collection,          // Filter options
    'dateFrom' => string,
    'dateTo' => string,
]
```

### Food Cost Calculations

```php
$recipeAnalysis = $recipes->map(function ($recipe) {
    // Unit cost per portion
    $unitCost = $recipe->yield_quantity > 0
        ? $recipe->total_cost / $recipe->yield_quantity
        : 0;

    $sellingPrice = $recipe->product?->price ?? 0;
    $grossProfit = $sellingPrice - $unitCost;

    // Food cost percentage
    $foodCostPercent = $sellingPrice > 0
        ? ($unitCost / $sellingPrice) * 100
        : 0;

    // Gross margin percentage
    $grossMarginPercent = $sellingPrice > 0
        ? ($grossProfit / $sellingPrice) * 100
        : 0;

    return [
        'recipe' => $recipe,
        'unit_cost' => $unitCost,
        'selling_price' => $sellingPrice,
        'gross_profit' => $grossProfit,
        'food_cost_percent' => $foodCostPercent,
        'gross_margin_percent' => $grossMarginPercent,
    ];
});
```

### High Cost Items Detection

```php
$highCostItems = $recipeAnalysis->filter(fn ($r) =>
    $r['food_cost_percent'] > 35 && $r['selling_price'] > 0
);

// Items yang perlu attention:
// - Turunkan portion size
// - Naikkan harga jual
// - Cari supplier lebih murah
// - Optimalkan recipe
```

### Waste Impact Calculation

```php
$wasteTotal = WasteLog::query()
    ->whereBetween('waste_date', [$dateFrom, $dateTo])
    ->sum('value');

// Impact on food cost:
// Waste increases actual food cost beyond recipe standard
```

### Category Analysis

```php
$costByCategory = $recipeAnalysis
    ->where('selling_price', '>', 0)
    ->groupBy(fn ($r) => $r['recipe']->category?->name ?? 'Uncategorized')
    ->map(fn ($items) => [
        'count' => $items->count(),
        'avg_food_cost' => $items->avg('food_cost_percent'),
        'avg_margin' => $items->avg('gross_margin_percent'),
        'total_potential_profit' => $items->sum('gross_profit'),
    ]);
```

---

## Route Definitions

**routes/web.php**

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('stock-valuation', [ReportController::class, 'stockValuation'])
                ->name('stock-valuation');

            Route::get('stock-movement', [ReportController::class, 'stockMovement'])
                ->name('stock-movement');

            Route::get('cogs', [ReportController::class, 'cogs'])
                ->name('cogs');

            Route::get('food-cost', [ReportController::class, 'foodCost'])
                ->name('food-cost');
        });
    });
});
```

---

## Usage Examples

### Example 1: Generate Stock Valuation Report

```php
// In controller
public function generateReport(Request $request, ReportController $reportCtrl)
{
    return $reportCtrl->stockValuation($request);
}

// Access URL
// /inventory/reports/stock-valuation?outlet_id=xxx&category_id=yyy
```

### Example 2: Get COGS for Current Month

```php
// Automatic default: current month
$cogs = app(ReportController::class)->cogs(request());

// Returns COGS from start of month to today
```

### Example 3: Analyze Food Cost by Category

```php
$reportCtrl = app(ReportController::class);
$response = $reportCtrl->foodCost(request());

$data = $response->getData();
foreach($data['costByCategory'] as $category => $metrics) {
    if ($metrics['avg_food_cost'] > 40) {
        // Warning: category has high food cost
        echo "{$category}: {$metrics['avg_food_cost']}% (WARNING)\n";
    }
}
```

---

## Best Practices

### 1. Eager Loading
Selalu eager load relationships untuk N+1 prevention:

```php
// ✅ Good
Stock::with(['inventoryItem.category', 'inventoryItem.unit', 'outlet'])->get();

// ❌ Bad - N+1 problem
Stock::get()->each(function ($stock) {
    echo $stock->inventoryItem->category->name;
});
```

### 2. Date Range Defaults

Gunakan sensible defaults untuk date filters:

```php
$dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
$dateTo = $request->get('date_to', now()->format('Y-m-d'));
```

### 3. Aggregation Pipeline

Gunakan collection aggregation untuk efficiency:

```php
// Group by multiple fields
$byCategory = $data->groupBy(fn ($item) => $item['category'])
    ->map(fn ($items) => [
        'count' => $items->count(),
        'sum' => $items->sum('value'),
    ]);
```

### 4. NULL Handling

Selalu handle NULL values dalam calculations:

```php
$avgCost = $stock->inventoryItem->cost_price ?? 0;
$categoryName = $item->category?->name ?? 'Uncategorized';
```

### 5. Absolute Values

Gunakan `abs()` untuk sale quantities (karena movements negatif):

```php
$totalCost = $movements->sum(fn ($m) =>
    abs($m->quantity) * $m->unit_cost
);
```

---

## Performance Optimization

### 1. Database Indexes

Pastikan indexes ada untuk query yang sering dipakai:

```sql
-- Stock movements
CREATE INDEX idx_stock_movements_date ON stock_movements(created_at);
CREATE INDEX idx_stock_movements_type ON stock_movements(movement_type);
CREATE INDEX idx_stock_movements_item_date ON stock_movements(inventory_item_id, created_at);

-- Stocks
CREATE INDEX idx_stocks_outlet ON inventory_stocks(outlet_id);
```

### 2. Query Chunking

Untuk large datasets, gunakan chunking:

```php
StockMovement::whereBetween('created_at', [$from, $to])
    ->chunk(1000, function ($movements) {
        foreach ($movements as $movement) {
            // Process
        }
    });
```

### 3. Pagination

Gunakan pagination untuk list views:

```php
$movements = StockMovement::query()
    ->latest()
    ->paginate(50);  // 50 per page
```

---

## Testing Reports

### Example Test

```php
<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_valuation_calculates_correctly()
    {
        $user = $this->createUserWithStock();

        $response = $this->actingAs($user)
            ->get(route('inventory.reports.stock-valuation'));

        $response->assertStatus(200);
        $response->assertViewHas('totalValue');
        $response->assertViewHas('byCategory');
    }

    public function test_cogs_filters_by_date_range()
    {
        $user = $this->createUserWithMovements();

        $response = $this->actingAs($user)
            ->get(route('inventory.reports.cogs', [
                'date_from' => '2025-01-01',
                'date_to' => '2025-01-31',
            ]));

        $response->assertStatus(200);
        $this->assertNotNull($response->viewData('dateFrom'));
        $this->assertNotNull($response->viewData('dateTo'));
    }

    public function test_food_cost_identifies_high_cost_items()
    {
        $user = $this->createUserWithRecipes();

        $response = $this->actingAs($user)
            ->get(route('inventory.reports.food-cost'));

        $response->assertStatus(200);
        $response->assertViewHas('highCostItems');

        $highCostItems = $response->viewData('highCostItems');
        $this->assertTrue($highCostItems->every(fn ($item) =>
            $item['food_cost_percent'] > 35
        ));
    }
}
```

---

## Export Reports (Optional Enhancement)

Untuk menambah export functionality:

### CSV Export

```php
public function exportStockValuation(Request $request)
{
    $controller = new ReportController();
    $viewData = $controller->stockValuation($request)->getData();

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="stock-valuation.csv"',
    ];

    $callback = function () use ($viewData) {
        $file = fopen('php://output', 'w');
        fputcsv($file, ['Item', 'Category', 'Outlet', 'Quantity', 'Unit Cost', 'Total Value']);

        foreach ($viewData['valuationData'] as $item) {
            fputcsv($file, [
                $item['item']->name,
                $item['item']->category->name ?? 'N/A',
                $item['outlet']->name,
                $item['quantity'],
                $item['avg_cost'],
                $item['value'],
            ]);
        }

        fclose($file);
    };

    return Response::stream($callback, 200, $headers);
}
```

---

## Next Steps

Lihat dokumentasi berikutnya:
- [Phase 2: Views](./phase2-views.md) - Blade views implementation
- [Phase 2: Services](./phase2-services.md) - Business logic layer
- [Phase 2: Controllers](./phase2-controllers.md) - HTTP request handlers
