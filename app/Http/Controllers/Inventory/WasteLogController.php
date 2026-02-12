<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\WasteLog;
use App\Services\Inventory\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WasteLogController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = WasteLog::where('tenant_id', $tenantId)
            ->with(['inventoryItem.unit', 'outlet', 'loggedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('inventoryItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('waste_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('waste_date', '<=', $request->date_to);
        }

        $wasteLogs = $query->latest('waste_date')->paginate(20)->withQueryString();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Calculate totals for selected period
        $totalQuery = WasteLog::where('tenant_id', $tenantId);
        if ($request->filled('outlet_id')) {
            $totalQuery->where('outlet_id', $request->outlet_id);
        }
        if ($request->filled('date_from')) {
            $totalQuery->whereDate('waste_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $totalQuery->whereDate('waste_date', '<=', $request->date_to);
        }
        $totalWasteValue = $totalQuery->sum('total_cost');

        return view('inventory.waste-logs.index', compact('wasteLogs', 'outlets', 'totalWasteValue'));
    }

    public function create(): View
    {
        $tenantId = $this->getTenantId();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        return view('inventory.waste-logs.create', compact('outlets', 'inventoryItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'in:expired,damaged,spoiled,overproduction,quality_issue,other'],
            'waste_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $inventoryItem = InventoryItem::find($validated['inventory_item_id']);
        $unitCost = $inventoryItem->cost_price;
        $totalCost = $validated['quantity'] * $unitCost;

        $wasteLog = WasteLog::create([
            'tenant_id' => $tenantId,
            'outlet_id' => $validated['outlet_id'],
            'inventory_item_id' => $validated['inventory_item_id'],
            'unit_id' => $inventoryItem->unit_id,
            'quantity' => $validated['quantity'],
            'cost_price' => $unitCost,
            'total_cost' => $totalCost,
            'reason' => $validated['reason'],
            'waste_date' => $validated['waste_date'],
            'notes' => $validated['notes'],
            'logged_by' => auth()->id(),
        ]);

        // Issue stock for the waste
        try {
            $this->stockService->issueStock(
                outletId: $validated['outlet_id'],
                inventoryItemId: $validated['inventory_item_id'],
                quantity: $validated['quantity'],
                userId: auth()->id(),
                reason: 'Waste: '.$validated['reason'],
                referenceType: 'waste_log',
                referenceId: $wasteLog->id
            );
        } catch (\Exception $e) {
            // If stock deduction fails, still log the waste but note the issue
            $wasteLog->update(['notes' => ($wasteLog->notes ?? '').' [Stock deduction failed: '.$e->getMessage().']']);
        }

        return redirect()->route('inventory.waste-logs.index')
            ->with('success', 'Waste log recorded successfully.');
    }

    public function show(WasteLog $wasteLog): View
    {
        $this->authorizeWasteLog($wasteLog);
        $wasteLog->load(['inventoryItem.unit', 'outlet', 'loggedBy']);

        return view('inventory.waste-logs.show', compact('wasteLog'));
    }

    public function destroy(WasteLog $wasteLog): RedirectResponse
    {
        $this->authorizeWasteLog($wasteLog);

        // Only allow deletion of recent waste logs (within 24 hours)
        if ($wasteLog->created_at->diffInHours(now()) > 24) {
            return back()->with('error', 'Cannot delete waste logs older than 24 hours.');
        }

        $wasteLog->delete();

        return redirect()->route('inventory.waste-logs.index')
            ->with('success', 'Waste log deleted successfully.');
    }

    public function report(Request $request): View
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth();

        $query = WasteLog::where('tenant_id', $tenantId)
            ->whereBetween('waste_date', [$dateFrom, $dateTo]);

        if (! empty($validated['outlet_id'])) {
            $query->where('outlet_id', $validated['outlet_id']);
        }

        // Summary by reason
        $wasteByReason = (clone $query)
            ->selectRaw('reason, COUNT(*) as count, SUM(quantity) as total_quantity, SUM(total_cost) as total_cost')
            ->groupBy('reason')
            ->get();

        // Summary by item
        $wasteByItem = (clone $query)
            ->with('inventoryItem.unit')
            ->selectRaw('inventory_item_id, SUM(quantity) as total_quantity, SUM(total_cost) as total_cost')
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_cost')
            ->limit(20)
            ->get();

        // Daily trend
        $dailyTrend = (clone $query)
            ->selectRaw('DATE(waste_date) as date, SUM(total_cost) as total_cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalWasteValue = $query->sum('total_cost');

        return view('inventory.waste-logs.report', compact(
            'wasteByReason',
            'wasteByItem',
            'dailyTrend',
            'outlets',
            'totalWasteValue',
            'dateFrom',
            'dateTo'
        ));
    }

    private function authorizeWasteLog(WasteLog $wasteLog): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($wasteLog->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
