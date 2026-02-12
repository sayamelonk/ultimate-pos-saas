<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceive;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoodsReceiveController extends Controller
{
    public function __construct(private PurchaseOrderService $purchaseOrderService) {}

    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = GoodsReceive::where('tenant_id', $tenantId)
            ->with(['purchaseOrder.supplier', 'outlet', 'receivedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gr_number', 'like', "%{$search}%")
                    ->orWhereHas('purchaseOrder', function ($sq) use ($search) {
                        $sq->where('po_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $goodsReceives = $query->latest()->paginate(15)->withQueryString();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.goods-receives.index', compact('goodsReceives', 'outlets'));
    }

    public function create(Request $request): View
    {
        $tenantId = $this->getTenantId();

        $purchaseOrders = PurchaseOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['approved', 'sent', 'partial'])
            ->with(['supplier', 'outlet', 'items.inventoryItem.unit'])
            ->latest()
            ->get();

        $selectedPO = null;
        if ($request->filled('purchase_order_id')) {
            $selectedPO = $purchaseOrders->firstWhere('id', $request->purchase_order_id);
        }

        return view('inventory.goods-receives.create', compact('purchaseOrders', 'selectedPO'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'receive_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:50'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $purchaseOrder = PurchaseOrder::where('tenant_id', $tenantId)
            ->findOrFail($validated['purchase_order_id']);

        $goodsReceive = $this->purchaseOrderService->createGoodsReceive(
            purchaseOrder: $purchaseOrder,
            userId: auth()->id(),
            items: $validated['items'],
            invoiceNumber: $validated['invoice_number'] ?? null,
            receiveDate: $validated['receive_date'],
            notes: $validated['notes'] ?? null
        );

        return redirect()->route('inventory.goods-receives.show', $goodsReceive)
            ->with('success', 'Goods receive created successfully.');
    }

    public function show(string $goodsReceive): View
    {
        $user = auth()->user();

        $query = GoodsReceive::query();
        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $goodsReceive = $query->with([
            'purchaseOrder.supplier',
            'outlet',
            'receivedBy',
            'items.purchaseOrderItem.inventoryItem.unit',
        ])->findOrFail($goodsReceive);

        return view('inventory.goods-receives.show', compact('goodsReceive'));
    }

    public function edit(GoodsReceive $goodsReceive): View
    {
        $this->authorizeGoodsReceive($goodsReceive);

        if ($goodsReceive->status !== 'draft') {
            return redirect()->route('inventory.goods-receives.show', $goodsReceive)
                ->with('error', 'Only draft goods receives can be edited.');
        }

        $goodsReceive->load([
            'purchaseOrder.items.inventoryItem.unit',
            'items.purchaseOrderItem.inventoryItem.unit',
        ]);

        return view('inventory.goods-receives.edit', compact('goodsReceive'));
    }

    public function update(Request $request, GoodsReceive $goodsReceive): RedirectResponse
    {
        $this->authorizeGoodsReceive($goodsReceive);

        if ($goodsReceive->status !== 'draft') {
            return back()->with('error', 'Only draft goods receives can be updated.');
        }

        $validated = $request->validate([
            'receive_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:goods_receive_items,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:50'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $goodsReceive->update([
            'receive_date' => $validated['receive_date'],
            'invoice_number' => $validated['invoice_number'],
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['items'] as $itemData) {
            $goodsReceive->items()->where('id', $itemData['id'])->update([
                'quantity_received' => $itemData['quantity_received'],
                'batch_number' => $itemData['batch_number'] ?? null,
                'expiry_date' => $itemData['expiry_date'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return redirect()->route('inventory.goods-receives.show', $goodsReceive)
            ->with('success', 'Goods receive updated successfully.');
    }

    public function destroy(GoodsReceive $goodsReceive): RedirectResponse
    {
        $this->authorizeGoodsReceive($goodsReceive);

        if ($goodsReceive->status !== 'draft') {
            return back()->with('error', 'Only draft goods receives can be deleted.');
        }

        $goodsReceive->items()->delete();
        $goodsReceive->delete();

        return redirect()->route('inventory.goods-receives.index')
            ->with('success', 'Goods receive deleted successfully.');
    }

    public function complete(GoodsReceive $goodsReceive): RedirectResponse
    {
        $this->authorizeGoodsReceive($goodsReceive);
        $tenantId = $this->getTenantId();

        try {
            $this->purchaseOrderService->completeGoodsReceive($goodsReceive, auth()->id());

            return redirect()->route('inventory.goods-receives.show', $goodsReceive)
                ->with('success', 'Goods receive completed and stock updated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(GoodsReceive $goodsReceive): RedirectResponse
    {
        $this->authorizeGoodsReceive($goodsReceive);

        if ($goodsReceive->status !== 'draft') {
            return back()->with('error', 'Only draft goods receives can be cancelled.');
        }

        $goodsReceive->update(['status' => 'cancelled']);

        return redirect()->route('inventory.goods-receives.show', $goodsReceive)
            ->with('success', 'Goods receive cancelled successfully.');
    }

    private function authorizeGoodsReceive(GoodsReceive $goodsReceive): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($goodsReceive->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
