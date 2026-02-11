<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrderService $purchaseOrderService) {}

    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = PurchaseOrder::where('tenant_id', $tenantId)
            ->with(['supplier', 'outlet']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchaseOrders = $query->latest()->paginate(15)->withQueryString();

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.purchase-orders.index', compact('purchaseOrders', 'suppliers', 'outlets'));
    }

    public function create(): View
    {
        $tenantId = $this->getTenantId();

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $items = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        return view('inventory.purchase-orders.create', compact('suppliers', 'outlets', 'items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $purchaseOrder = $this->purchaseOrderService->createPurchaseOrder(
            tenantId: $tenantId,
            supplierId: $validated['supplier_id'],
            outletId: $validated['outlet_id'],
            userId: auth()->id(),
            items: $validated['items'],
            expectedDate: $validated['expected_date'] ?? null,
            notes: $validated['notes'] ?? null
        );

        return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizePurchaseOrder($purchaseOrder);
        $purchaseOrder->load([
            'supplier',
            'outlet',
            'createdBy',
            'approvedBy',
            'items.inventoryItem.unit',
            'goodsReceives.items',
        ]);

        return view('inventory.purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft purchase orders can be edited.');
        }

        $tenantId = $this->getTenantId();
        $purchaseOrder->load('items.inventoryItem.unit');

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $items = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        return view('inventory.purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'outlets', 'items'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft purchase orders can be updated.');
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Delete existing items and recreate
        $purchaseOrder->items()->delete();

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $lineTotal;

            $purchaseOrder->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $lineTotal,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $purchaseOrder->update([
            'supplier_id' => $validated['supplier_id'],
            'outlet_id' => $validated['outlet_id'],
            'expected_date' => $validated['expected_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'subtotal' => $subtotal,
            'total_amount' => $subtotal, // Can add tax calculation later
        ]);

        return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated successfully.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft purchase orders can be deleted.');
        }

        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return redirect()->route('inventory.purchase-orders.index')
            ->with('success', 'Purchase order deleted successfully.');
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);
        $tenantId = $this->getTenantId();

        try {
            $this->purchaseOrderService->approvePurchaseOrder($purchaseOrder, auth()->id());

            return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if (! in_array($purchaseOrder->status, ['draft', 'approved', 'sent'])) {
            return back()->with('error', 'This purchase order cannot be cancelled.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order cancelled successfully.');
    }

    public function send(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if ($purchaseOrder->status !== 'approved') {
            return back()->with('error', 'Only approved purchase orders can be sent.');
        }

        $purchaseOrder->update(['status' => 'sent']);

        return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order marked as sent.');
    }

    private function authorizePurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($purchaseOrder->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
