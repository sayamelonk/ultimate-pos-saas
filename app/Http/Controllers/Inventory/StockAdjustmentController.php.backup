<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = StockAdjustment::where('tenant_id', $user->tenant_id)
            ->with(['outlet', 'createdBy', 'approvedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('adjustment_number', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('adjustment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('adjustment_date', '<=', $request->to_date);
        }

        $adjustments = $query->orderBy('adjustment_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.stock-adjustments.index', compact('adjustments'));
    }

    public function create(): View
    {
        $user = auth()->user();

        return view('inventory.stock-adjustments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'adjustment_date' => ['required', 'date'],
            'type' => ['required', 'in:stock_take,correction,opening_balance'],
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.current_qty' => ['required', 'numeric'],
            'items.*.actual_qty' => ['required', 'numeric'],
        ]);

        $lastAdjustment = StockAdjustment::where('tenant_id', $user->tenant_id)
            ->orderBy('id', 'desc')
            ->first();

        $adjustmentNumber = 'ADJ-'.date('Ymd').'-'.str_pad((($lastAdjustment->id ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['adjustment_number'] = $adjustmentNumber;
        $validated['status'] = StockAdjustment::STATUS_DRAFT;
        $validated['created_by'] = $user->id;

        $items = $validated['items'];
        unset($validated['items']);

        $adjustment = StockAdjustment::create($validated);

        foreach ($items as $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);
            $stock = InventoryStock::where('outlet_id', $validated['outlet_id'])
                ->where('inventory_item_id', $item['inventory_item_id'])
                ->first();

            $currentQty = $stock->quantity ?? 0;
            $actualQty = $item['actual_qty'];

            if ($currentQty != $actualQty) {
                $costPrice = $inventoryItem->cost_price ?? 0;

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'current_qty' => $currentQty,
                    'actual_qty' => $actualQty,
                    'difference' => $actualQty - $currentQty,
                    'cost_price' => $costPrice,
                    'value_difference' => ($actualQty - $currentQty) * $costPrice,
                ]);
            }
        }

        return redirect()->route('inventory.stock-adjustments.show', $adjustment)
            ->with('success', 'Stock adjustment created successfully.');
    }

    public function show(StockAdjustment $adjustment): View
    {
        $this->authorizeAdjustment($adjustment);
        $adjustment->load(['outlet', 'createdBy', 'approvedBy', 'items.inventoryItem']);

        return view('inventory.stock-adjustments.show', compact('adjustment'));
    }

    public function approve(StockAdjustment $adjustment): RedirectResponse
    {
        $this->authorizeAdjustment($adjustment);

        if (! $adjustment->isDraft()) {
            return back()->with('error', 'Only draft adjustments can be approved.');
        }

        $user = auth()->user();
        $adjustment->load('items');

        foreach ($adjustment->items as $item) {
            $this->stockService->adjustStock(
                outletId: $adjustment->outlet_id,
                inventoryItemId: $item->inventory_item_id,
                newQuantity: $item->actual_qty,
                userId: $user->id,
                referenceType: 'stock_adjustment',
                referenceId: $adjustment->id,
                notes: 'Adjustment: '.$adjustment->adjustment_number,
            );
        }

        $adjustment->update([
            'status' => StockAdjustment::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Stock adjustment approved successfully. Stock updated.');
    }

    public function cancel(StockAdjustment $adjustment): RedirectResponse
    {
        $this->authorizeAdjustment($adjustment);

        if (! $adjustment->isDraft()) {
            return back()->with('error', 'Cannot cancel approved adjustment.');
        }

        $adjustment->update(['status' => StockAdjustment::STATUS_CANCELLED]);

        return back()->with('success', 'Stock adjustment cancelled successfully.');
    }

    private function authorizeAdjustment(StockAdjustment $adjustment): void
    {
        $user = auth()->user();

        if ($adjustment->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
