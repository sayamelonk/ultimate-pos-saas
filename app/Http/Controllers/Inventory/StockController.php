<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();

        // Get outlet IDs for this tenant
        $outletIds = Outlet::where('tenant_id', $tenantId)->pluck('id');

        $query = InventoryStock::whereIn('outlet_id', $outletIds)
            ->with(['inventoryItem.unit', 'outlet']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('inventoryItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'low') {
                $query->whereHas('inventoryItem', function ($q) {
                    $q->whereRaw('inventory_stocks.quantity <= inventory_items.reorder_point');
                });
            } elseif ($request->status === 'out') {
                $query->where('quantity', '<=', 0);
            }
        }

        $stocks = $query->orderBy('updated_at', 'desc')->paginate(15)->withQueryString();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.stocks.index', compact('stocks', 'outlets'));
    }

    public function show(InventoryStock $stock): View
    {
        $this->authorizeStock($stock);
        $stock->load(['inventoryItem.unit', 'outlet']);

        // Get batches for this item at this outlet
        $batches = StockBatch::where('inventory_item_id', $stock->inventory_item_id)
            ->where('outlet_id', $stock->outlet_id)
            ->where('current_qty', '>', 0)
            ->orderBy('expiry_date')
            ->get();

        // Get recent movements
        $movements = StockMovement::where('inventory_item_id', $stock->inventory_item_id)
            ->where('outlet_id', $stock->outlet_id)
            ->with(['createdBy'])
            ->latest()
            ->take(20)
            ->get();

        return view('inventory.stocks.show', compact('stock', 'batches', 'movements'));
    }

    public function movements(Request $request): View
    {
        $tenantId = $this->getTenantId();

        // Get outlet IDs for this tenant
        $outletIds = Outlet::where('tenant_id', $tenantId)->pluck('id');

        $query = StockMovement::whereIn('outlet_id', $outletIds)
            ->with(['inventoryItem.unit', 'outlet', 'createdBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('inventoryItem', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->latest()->paginate(20)->withQueryString();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.stocks.movements', compact('movements', 'outlets'));
    }

    public function batches(Request $request): View
    {
        $tenantId = $this->getTenantId();

        // Get outlet IDs for this tenant
        $outletIds = Outlet::where('tenant_id', $tenantId)->pluck('id');

        $query = StockBatch::whereIn('outlet_id', $outletIds)
            ->where('current_qty', '>', 0)
            ->with(['inventoryItem.unit', 'outlet']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('inventoryItem', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('expiry_status')) {
            $today = now();
            if ($request->expiry_status === 'expired') {
                $query->where('expiry_date', '<', $today);
            } elseif ($request->expiry_status === 'expiring_soon') {
                $query->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)]);
            }
        }

        $batches = $query->orderBy('expiry_date')->paginate(20)->withQueryString();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.stocks.batches', compact('batches', 'outlets'));
    }

    public function lowStock(): View
    {
        $tenantId = $this->getTenantId();

        $lowStockItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('reorder_point', '>', 0)
            ->with(['unit', 'stocks.outlet'])
            ->get()
            ->filter(function ($item) {
                $totalStock = $item->stocks->sum('quantity');

                return $totalStock <= $item->reorder_point;
            });

        return view('inventory.stocks.low-stock', compact('lowStockItems'));
    }

    public function expiringItems(): View
    {
        $tenantId = $this->getTenantId();
        $warningDate = now()->addDays(30);

        // Get outlet IDs for this tenant
        $outletIds = Outlet::where('tenant_id', $tenantId)->pluck('id');

        $expiringBatches = StockBatch::whereIn('outlet_id', $outletIds)
            ->where('current_quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $warningDate)
            ->with(['inventoryItem.unit', 'outlet'])
            ->orderBy('expiry_date')
            ->get();

        return view('inventory.stocks.expiring', compact('expiringBatches'));
    }

    private function authorizeStock(InventoryStock $stock): void
    {
        $tenantId = $this->getTenantId();
        $stock->load('outlet');

        if ($stock->outlet->tenant_id !== $tenantId) {
            abort(403, 'Access denied.');
        }
    }
}
