<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockTransfer;
use App\Services\Inventory\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function __construct(private StockTransferService $transferService) {}

    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = StockTransfer::where('tenant_id', $tenantId)
            ->with(['fromOutlet', 'toOutlet', 'createdBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('transfer_number', 'like', "%{$search}%");
        }

        if ($request->filled('source_outlet_id')) {
            $query->where('from_outlet_id', $request->source_outlet_id);
        }

        if ($request->filled('destination_outlet_id')) {
            $query->where('to_outlet_id', $request->destination_outlet_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->latest()->paginate(15)->withQueryString();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.stock-transfers.index', compact('transfers', 'outlets'));
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

        return view('inventory.stock-transfers.create', compact('outlets', 'inventoryItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'from_outlet_id' => ['required', 'exists:outlets,id'],
            'to_outlet_id' => ['required', 'exists:outlets,id', 'different:from_outlet_id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $transfer = $this->transferService->createTransfer(
            tenantId: $tenantId,
            sourceOutletId: $validated['from_outlet_id'],
            destinationOutletId: $validated['to_outlet_id'],
            userId: auth()->id(),
            items: $validated['items'],
            transferDate: $validated['transfer_date'],
            notes: $validated['notes'] ?? null
        );

        return redirect()->route('inventory.stock-transfers.show', $transfer)
            ->with('success', 'Stock transfer created successfully.');
    }

    public function show(StockTransfer $stockTransfer): View
    {
        $this->authorizeTransfer($stockTransfer);
        $stockTransfer->load([
            'fromOutlet',
            'toOutlet',
            'createdBy',
            'approvedBy',
            'receivedBy',
            'items.inventoryItem.unit',
        ]);

        return view('inventory.stock-transfers.show', compact('stockTransfer'));
    }

    public function edit(StockTransfer $stockTransfer): View
    {
        $this->authorizeTransfer($stockTransfer);

        if ($stockTransfer->status !== 'draft') {
            return redirect()->route('inventory.stock-transfers.show', $stockTransfer)
                ->with('error', 'Only draft transfers can be edited.');
        }

        $tenantId = $this->getTenantId();
        $stockTransfer->load('items.inventoryItem.unit');

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        return view('inventory.stock-transfers.edit', compact('stockTransfer', 'outlets', 'inventoryItems'));
    }

    public function update(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorizeTransfer($stockTransfer);

        if ($stockTransfer->status !== 'draft') {
            return back()->with('error', 'Only draft transfers can be updated.');
        }

        $validated = $request->validate([
            'from_outlet_id' => ['required', 'exists:outlets,id'],
            'to_outlet_id' => ['required', 'exists:outlets,id', 'different:from_outlet_id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Delete existing items and recreate
        $stockTransfer->items()->delete();

        foreach ($validated['items'] as $item) {
            $stockTransfer->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $stockTransfer->update([
            'from_outlet_id' => $validated['from_outlet_id'],
            'to_outlet_id' => $validated['to_outlet_id'],
            'transfer_date' => $validated['transfer_date'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('inventory.stock-transfers.show', $stockTransfer)
            ->with('success', 'Stock transfer updated successfully.');
    }

    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorizeTransfer($stockTransfer);

        if ($stockTransfer->status !== 'draft') {
            return back()->with('error', 'Only draft transfers can be deleted.');
        }

        $stockTransfer->items()->delete();
        $stockTransfer->delete();

        return redirect()->route('inventory.stock-transfers.index')
            ->with('success', 'Stock transfer deleted successfully.');
    }

    public function approve(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorizeTransfer($stockTransfer);
        $tenantId = $this->getTenantId();

        try {
            $this->transferService->approveTransfer($stockTransfer, auth()->id());

            return redirect()->route('inventory.stock-transfers.show', $stockTransfer)
                ->with('success', 'Stock transfer approved. Items reserved from source outlet.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ship(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorizeTransfer($stockTransfer);

        if ($stockTransfer->status !== 'approved') {
            return back()->with('error', 'Only approved transfers can be shipped.');
        }

        $stockTransfer->update([
            'status' => 'in_transit',
            'shipped_at' => now(),
        ]);

        return redirect()->route('inventory.stock-transfers.show', $stockTransfer)
            ->with('success', 'Stock transfer marked as shipped.');
    }

    public function receive(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorizeTransfer($stockTransfer);
        $tenantId = $this->getTenantId();

        try {
            $this->transferService->receiveTransfer($stockTransfer, auth()->id());

            return redirect()->route('inventory.stock-transfers.show', $stockTransfer)
                ->with('success', 'Stock transfer received. Stock added to destination outlet.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorizeTransfer($stockTransfer);
        $tenantId = $this->getTenantId();

        try {
            $this->transferService->cancelTransfer($stockTransfer, auth()->id());

            return redirect()->route('inventory.stock-transfers.show', $stockTransfer)
                ->with('success', 'Stock transfer cancelled.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function getSourceStock(Request $request): \Illuminate\Http\JsonResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
        ]);

        // Verify outlet belongs to user's tenant
        $outlet = Outlet::where('tenant_id', $tenantId)
            ->where('id', $validated['outlet_id'])
            ->firstOrFail();

        $stocks = InventoryStock::where('outlet_id', $outlet->id)
            ->where('quantity', '>', 0)
            ->with(['inventoryItem.unit'])
            ->get()
            ->map(function ($stock) {
                return [
                    'inventory_item_id' => $stock->inventory_item_id,
                    'name' => $stock->inventoryItem->name,
                    'sku' => $stock->inventoryItem->sku,
                    'unit' => $stock->inventoryItem->unit->abbreviation ?? '',
                    'available_quantity' => $stock->quantity - ($stock->reserved_qty ?? 0),
                ];
            });

        return response()->json($stocks);
    }

    private function authorizeTransfer(StockTransfer $stockTransfer): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($stockTransfer->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
