<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Unit;
use App\Models\WasteLog;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WasteLogController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = WasteLog::where('tenant_id', $user->tenant_id)
            ->with(['inventoryItem', 'outlet', 'unit', 'loggedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('inventoryItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('item_id')) {
            $query->where('inventory_item_id', $request->item_id);
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('waste_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('waste_date', '<=', $request->to_date);
        }

        $wasteLogs = $query->orderBy('waste_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $items = InventoryItem::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        $reasons = WasteLog::getReasons();

        return view('inventory.waste-logs.index', compact('wasteLogs', 'items', 'reasons'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $items = InventoryItem::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $reasons = WasteLog::getReasons();

        return view('inventory.waste-logs.create', compact('items', 'units', 'reasons'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'batch_id' => ['nullable', 'exists:stock_batches,id'],
            'waste_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_id' => ['required', 'exists:units,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'in:expired,spoiled,damaged,preparation,overproduction,other'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['total_cost'] = $validated['quantity'] * $validated['cost_price'];
        $validated['logged_by'] = $user->id;

        $wasteLog = WasteLog::create($validated);

        $this->stockService->issueStock(
            outletId: $validated['outlet_id'],
            inventoryItemId: $validated['inventory_item_id'],
            quantity: $validated['quantity'],
            type: \App\Models\StockMovement::TYPE_WASTE,
            userId: $user->id,
            referenceType: 'waste_log',
            referenceId: $wasteLog->id,
            notes: 'Waste: '.$validated['reason'],
        );

        return redirect()->route('inventory.waste-logs.index')
            ->with('success', 'Waste logged successfully.');
    }

    public function show(WasteLog $wasteLog): View
    {
        $this->authorizeWasteLog($wasteLog);
        $wasteLog->load(['inventoryItem', 'outlet', 'batch', 'unit', 'loggedBy']);

        return view('inventory.waste-logs.show', compact('wasteLog'));
    }

    public function report(Request $request): View
    {
        $user = auth()->user();

        $query = WasteLog::where('tenant_id', $user->tenant_id)
            ->with(['inventoryItem', 'outlet']);

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('waste_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('waste_date', '<=', $request->to_date);
        }

        $wasteLogs = $query->orderBy('waste_date')->get();

        $totalCost = $wasteLogs->sum('total_cost');
        $totalQuantity = $wasteLogs->sum('quantity');

        $byReason = $wasteLogs->groupBy('reason')
            ->map(fn ($group) => [
                'quantity' => $group->sum('quantity'),
                'cost' => $group->sum('total_cost'),
                'count' => $group->count(),
                'reason_label' => WasteLog::getReasons()[$group->first()->reason] ?? $group->first()->reason,
            ])
            ->sortByDesc('cost');

        $byItem = $wasteLogs->groupBy('inventory_item_id')
            ->map(fn ($group) => [
                'item_name' => $group->first()->inventoryItem->name,
                'quantity' => $group->sum('quantity'),
                'cost' => $group->sum('total_cost'),
                'count' => $group->count(),
            ])
            ->sortByDesc('cost');

        return view('inventory.waste-logs.report', compact(
            'wasteLogs',
            'totalCost',
            'totalQuantity',
            'byReason',
            'byItem'
        ));
    }

    private function authorizeWasteLog(WasteLog $wasteLog): void
    {
        $user = auth()->user();

        if ($wasteLog->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
