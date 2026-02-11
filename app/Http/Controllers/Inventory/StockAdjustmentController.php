<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockAdjustment;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(private StockAdjustmentService $adjustmentService) {}

    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = StockAdjustment::where('tenant_id', $tenantId)
            ->with(['outlet', 'createdBy', 'approvedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('adjustment_number', 'like', "%{$search}%");
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $adjustments = $query->latest()->paginate(15)->withQueryString();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.stock-adjustments.index', compact('adjustments', 'outlets'));
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

        return view('inventory.stock-adjustments.create', compact('outlets', 'inventoryItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'type' => ['required', 'in:stock_take,correction,damage,loss,found'],
            'adjustment_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.system_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $adjustment = $this->adjustmentService->createAdjustment(
            tenantId: $tenantId,
            outletId: $validated['outlet_id'],
            userId: auth()->id(),
            type: $validated['type'],
            items: $validated['items'],
            adjustmentDate: $validated['adjustment_date'],
            reason: $validated['reason'] ?? null,
            notes: $validated['notes'] ?? null
        );

        return redirect()->route('inventory.stock-adjustments.show', $adjustment)
            ->with('success', 'Stock adjustment created successfully.');
    }

    public function show(StockAdjustment $stockAdjustment): View
    {
        $this->authorizeAdjustment($stockAdjustment);
        $stockAdjustment->load([
            'outlet',
            'createdBy',
            'approvedBy',
            'items.inventoryItem.unit',
        ]);

        return view('inventory.stock-adjustments.show', compact('stockAdjustment'));
    }

    public function edit(StockAdjustment $stockAdjustment): View
    {
        $this->authorizeAdjustment($stockAdjustment);

        if ($stockAdjustment->status !== 'draft') {
            return redirect()->route('inventory.stock-adjustments.show', $stockAdjustment)
                ->with('error', 'Only draft adjustments can be edited.');
        }

        $tenantId = $this->getTenantId();
        $stockAdjustment->load('items.inventoryItem.unit');

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        return view('inventory.stock-adjustments.edit', compact('stockAdjustment', 'outlets', 'inventoryItems'));
    }

    public function update(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorizeAdjustment($stockAdjustment);

        if ($stockAdjustment->status !== 'draft') {
            return back()->with('error', 'Only draft adjustments can be updated.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:stock_take,correction,damage,loss,found'],
            'adjustment_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.system_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Delete existing items and recreate
        $stockAdjustment->items()->delete();

        $totalVariance = 0;
        foreach ($validated['items'] as $item) {
            $varianceQty = $item['actual_quantity'] - $item['system_quantity'];
            $totalVariance += abs($varianceQty);

            $stockAdjustment->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'system_quantity' => $item['system_quantity'],
                'actual_quantity' => $item['actual_quantity'],
                'variance_quantity' => $varianceQty,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $stockAdjustment->update([
            'type' => $validated['type'],
            'adjustment_date' => $validated['adjustment_date'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'],
            'total_variance' => $totalVariance,
        ]);

        return redirect()->route('inventory.stock-adjustments.show', $stockAdjustment)
            ->with('success', 'Stock adjustment updated successfully.');
    }

    public function destroy(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorizeAdjustment($stockAdjustment);

        if ($stockAdjustment->status !== 'draft') {
            return back()->with('error', 'Only draft adjustments can be deleted.');
        }

        $stockAdjustment->items()->delete();
        $stockAdjustment->delete();

        return redirect()->route('inventory.stock-adjustments.index')
            ->with('success', 'Stock adjustment deleted successfully.');
    }

    public function approve(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorizeAdjustment($stockAdjustment);
        $tenantId = $this->getTenantId();

        try {
            $this->adjustmentService->approveAdjustment($stockAdjustment, auth()->id());

            return redirect()->route('inventory.stock-adjustments.show', $stockAdjustment)
                ->with('success', 'Stock adjustment approved and stock updated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorizeAdjustment($stockAdjustment);

        if ($stockAdjustment->status !== 'pending') {
            return back()->with('error', 'Only pending adjustments can be rejected.');
        }

        $stockAdjustment->update(['status' => 'rejected']);

        return redirect()->route('inventory.stock-adjustments.show', $stockAdjustment)
            ->with('success', 'Stock adjustment rejected.');
    }

    public function stockTake(): View
    {
        $tenantId = $this->getTenantId();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.stock-adjustments.stock-take', compact('outlets', 'categories'));
    }

    public function getStockForOutlet(Request $request): \Illuminate\Http\JsonResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
        ]);

        // Verify outlet belongs to user's tenant
        $outlet = Outlet::where('tenant_id', $tenantId)
            ->where('id', $validated['outlet_id'])
            ->firstOrFail();

        $query = InventoryStock::where('outlet_id', $outlet->id)
            ->with(['inventoryItem.unit', 'inventoryItem.category']);

        // Filter by category if provided
        if (! empty($validated['category_id'])) {
            $query->whereHas('inventoryItem', function ($q) use ($validated) {
                $q->where('category_id', $validated['category_id']);
            });
        }

        $stocks = $query->get()
            ->map(function ($stock) {
                return [
                    'inventory_item_id' => $stock->inventory_item_id,
                    'name' => $stock->inventoryItem->name,
                    'sku' => $stock->inventoryItem->sku,
                    'unit' => $stock->inventoryItem->unit->abbreviation ?? '',
                    'system_quantity' => $stock->quantity,
                ];
            });

        return response()->json($stocks);
    }

    private function authorizeAdjustment(StockAdjustment $stockAdjustment): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($stockAdjustment->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
