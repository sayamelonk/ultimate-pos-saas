<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $outletId = $request->outlet_id ?? $user->outlet_id;

        $query = InventoryStock::where('outlet_id', $outletId)
            ->with(['inventoryItem.category', 'inventoryItem.unit']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('inventoryItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('inventoryItem', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'low') {
                $query->whereColumn('quantity', '<=', 'inventory_items.reorder_point');
            } elseif ($request->status === 'out') {
                $query->where('quantity', '<=', 0);
            } elseif ($request->status === 'available') {
                $query->whereColumn('quantity', '>', 'inventory_items.reorder_point');
            }
        }

        $stocks = $query->orderBy('id')->paginate(20)->withQueryString();

        return view('inventory.stocks.index', compact('stocks', 'outletId'));
    }

    public function show(InventoryStock $stock): View
    {
        $user = auth()->user();
        $this->authorizeStock($stock, $user->tenant_id);

        $stock->load([
            'inventoryItem.category',
            'inventoryItem.unit',
            'outlet',
            'stockBatches' => function ($q) {
                $q->where('remaining_quantity', '>', 0)
                    ->orderBy('expiry_date');
            },
            'stockMovements' => function ($q) {
                $q->latest()->limit(50);
            },
        ]);

        return view('inventory.stocks.show', compact('stock'));
    }

    public function movements(Request $request): View
    {
        $user = auth()->user();
        $outletId = $request->outlet_id ?? $user->outlet_id;

        $query = InventoryStock::where('outlet_id', $outletId);

        $movements = \App\Models\StockMovement::whereHas('stockBatch', function ($q) use ($outletId) {
            $q->whereHas('inventoryStock', function ($sq) use ($outletId) {
                $sq->where('outlet_id', $outletId);
            });
        })
            ->with(['inventoryStock.inventoryItem', 'inventoryStock.outlet'])
            ->when($request->filled('item_id'), function ($q) use ($request) {
                return $q->whereHas('inventoryStock', function ($sq) use ($request) {
                    $sq->where('inventory_item_id', $request->item_id);
                });
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                return $q->where('movement_type', $request->type);
            })
            ->when($request->filled('from_date'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->to_date);
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $items = InventoryItem::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return view('inventory.stocks.movements', compact('movements', 'items', 'outletId'));
    }

    public function lowStock(Request $request): View
    {
        $user = auth()->user();
        $outletId = $request->outlet_id ?? $user->outlet_id;

        $stocks = InventoryStock::where('outlet_id', $outletId)
            ->with(['inventoryItem.category', 'inventoryItem.unit'])
            ->whereHas('inventoryItem', function ($q) {
                $q->whereColumn('inventory_stocks.quantity', '<=', 'inventory_items.reorder_point');
            })
            ->get()
            ->sortBy(fn ($stock) => $stock->inventoryItem->reorder_point - $stock->quantity);

        return view('inventory.stocks.low_stock', compact('stocks', 'outletId'));
    }

    public function valuation(Request $request): View
    {
        $user = auth()->user();
        $outletId = $request->outlet_id ?? $user->outlet_id;

        $stocks = InventoryStock::where('outlet_id', $outletId)
            ->with(['inventoryItem.category'])
            ->get();

        $totalValue = $stocks->sum(fn ($stock) => $stock->quantity * $stock->avg_cost);
        $totalQuantity = $stocks->sum('quantity');
        $itemCount = $stocks->count();

        $byCategory = $stocks->groupBy(fn ($stock) => $stock->inventoryItem->category?->name ?? 'Uncategorized')
            ->map(fn ($group) => [
                'quantity' => $group->sum('quantity'),
                'value' => $group->sum(fn ($stock) => $stock->quantity * $stock->avg_cost),
                'items' => $group->count(),
            ])
            ->sortByDesc('value');

        return view('inventory.stocks.valuation', compact(
            'stocks',
            'outletId',
            'totalValue',
            'totalQuantity',
            'itemCount',
            'byCategory'
        ));
    }

    private function authorizeStock(InventoryStock $stock, string $tenantId): void
    {
        if ($stock->inventoryItem->tenant_id !== $tenantId) {
            abort(403, 'Access denied.');
        }
    }
}
