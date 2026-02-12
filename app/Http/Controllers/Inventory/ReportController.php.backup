<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Recipe;
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
        $user = auth()->user();
        $outletId = $request->outlet_id ?? $user->outlet_id;
        $categoryId = $request->category_id;

        $query = InventoryStock::query()
            ->with(['inventoryItem.category', 'inventoryItem.unit', 'outlet'])
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $user->tenant_id));

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($categoryId) {
            $query->whereHas('inventoryItem', fn ($q) => $q->where('category_id', $categoryId));
        }

        $stocks = $query->get();

        // Calculate valuations
        $valuationData = $stocks->map(function ($stock) {
            $avgCost = $stock->avg_cost ?? 0;
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

        $categories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        $outlets = \App\Models\Outlet::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return view('inventory.reports.stock-valuation', compact(
            'valuationData',
            'byCategory',
            'byOutlet',
            'totalValue',
            'totalItems',
            'outletId',
            'categories',
            'outlets'
        ));
    }

    /**
     * Stock Movement Report
     */
    public function stockMovement(Request $request): View
    {
        $user = auth()->user();
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');
        $outletId = $request->outlet_id;
        $itemId = $request->item_id;

        $query = StockMovement::query()
            ->with(['inventoryItem.category', 'inventoryItem.unit', 'outlet'])
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($itemId) {
            $query->where('inventory_item_id', $itemId);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->latest()->paginate(50)->withQueryString();

        // Summary by type
        $summaryQuery = StockMovement::query()
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        if ($outletId) {
            $summaryQuery->where('outlet_id', $outletId);
        }

        $summaryByType = $summaryQuery->clone()
            ->selectRaw('type, SUM(ABS(quantity)) as total_qty, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $items = InventoryItem::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        $outlets = \App\Models\Outlet::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        // Daily trend data
        $dailyTrend = StockMovement::query()
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->selectRaw('DATE(created_at) as date, type, SUM(ABS(quantity) * cost_price) as total')
            ->groupBy('date', 'type')
            ->get()
            ->groupBy('date');

        return view('inventory.reports.stock-movement', compact(
            'movements',
            'summaryByType',
            'items',
            'outlets',
            'dailyTrend',
            'outletId',
            'itemId',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * COGS Report
     */
    public function cogs(Request $request): View
    {
        $user = auth()->user();
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');
        $outletId = $request->outlet_id;

        // Get sale movements (stock deductions for sales)
        $query = StockMovement::query()
            ->with(['inventoryItem.category', 'inventoryItem.unit', 'outlet'])
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->where('type', 'sale')
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $saleMovements = $query->get();

        // Calculate COGS by item
        $cogsByItem = $saleMovements->groupBy('inventory_item_id')->map(function ($movements) {
            $item = $movements->first()->inventoryItem;
            $totalQty = $movements->sum('quantity');
            $totalCost = $movements->sum(fn ($m) => abs($m->quantity) * ($m->cost_price ?? $item->cost_price ?? 0));

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
            $totalCost = $movements->sum(fn ($m) => abs($m->quantity) * ($m->cost_price ?? $m->inventoryItem->cost_price ?? 0));

            return [
                'outlet' => $outlet,
                'total_cost' => $totalCost,
            ];
        })->sortByDesc('total_cost');

        // Daily COGS trend
        $dailyCogs = StockMovement::query()
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->where('type', 'sale')
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->selectRaw('DATE(created_at) as date, SUM(ABS(quantity) * cost_price) as total_cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalCogs = $cogsByItem->sum('total_cost');

        $outlets = \App\Models\Outlet::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return view('inventory.reports.cogs', compact(
            'cogsByItem',
            'cogsByCategory',
            'cogsByOutlet',
            'dailyCogs',
            'totalCogs',
            'outlets',
            'outletId',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Food Cost Analysis Report
     */
    public function foodCost(Request $request): View
    {
        $user = auth()->user();
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        // Get all recipes with their costs
        $recipes = Recipe::query()
            ->with(['items.inventoryItem', 'category', 'yieldUnit'])
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        // Calculate food cost metrics
        $recipeAnalysis = $recipes->map(function ($recipe) {
            $unitCost = $recipe->yield_qty > 0 ? $recipe->estimated_cost / $recipe->yield_qty : 0;
            $sellingPrice = 0; // Product will be added in Phase 3
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
            ->where('tenant_id', $user->tenant_id)
            ->whereBetween('waste_date', [$dateFrom, $dateTo])
            ->sum('total_cost');

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

        $categories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return view('inventory.reports.food-cost', compact(
            'recipeAnalysis',
            'avgFoodCost',
            'avgMargin',
            'highCostItems',
            'wasteTotal',
            'costByCategory',
            'categories',
            'dateFrom',
            'dateTo'
        ));
    }
}
