<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = PurchaseOrder::where('tenant_id', $user->tenant_id)
            ->with(['supplier', 'outlet', 'createdBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('po_number', 'like', "%{$search}%");
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        $purchaseOrders = $query->orderBy('order_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $suppliers = Supplier::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.purchase-orders.index', compact('purchaseOrders', 'suppliers'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $suppliers = Supplier::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.purchase-orders.create', compact('suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after:order_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
        ]);

        $lastPo = PurchaseOrder::where('tenant_id', $user->tenant_id)
            ->orderBy('id', 'desc')
            ->first();

        $poNumber = 'PO-'.date('Ymd').'-'.str_pad((($lastPo->id ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['po_number'] = $poNumber;
        $validated['status'] = PurchaseOrder::STATUS_DRAFT;
        $validated['created_by'] = $user->id;
        $validated['subtotal'] = 0;
        $validated['tax_amount'] = 0;
        $validated['discount_amount'] = 0;
        $validated['total'] = 0;

        $items = $validated['items'];
        unset($validated['items']);

        $purchaseOrder = PurchaseOrder::create($validated);

        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['price'];
            $taxAmount = $lineTotal * (($item['tax_percent'] ?? 0) / 100);
            $discountAmount = $lineTotal * (($item['discount_percent'] ?? 0) / 100);

            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
                'price' => $item['price'],
                'tax_percent' => $item['tax_percent'] ?? 0,
                'tax_amount' => $taxAmount,
                'discount_percent' => $item['discount_percent'] ?? 0,
                'discount_amount' => $discountAmount,
                'total' => $lineTotal + $taxAmount - $discountAmount,
                'received_qty' => 0,
            ]);
        }

        $purchaseOrder->load('items');
        $purchaseOrder->calculateTotals();
        $purchaseOrder->save();

        return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizePurchaseOrder($purchaseOrder);
        $purchaseOrder->load(['supplier', 'outlet', 'createdBy', 'approvedBy', 'items.inventoryItem', 'items.unit', 'goodsReceives']);

        return view('inventory.purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if (! $purchaseOrder->isEditable()) {
            return back()->with('error', 'Cannot edit purchase order in current status.');
        }

        $user = auth()->user();

        $suppliers = Supplier::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $purchaseOrder->load('items.inventoryItem');

        return view('inventory.purchase-orders.edit', compact('purchaseOrder', 'suppliers'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if (! $purchaseOrder->isEditable()) {
            return back()->with('error', 'Cannot edit purchase order in current status.');
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after:order_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
        ]);

        $items = $validated['items'];
        unset($validated['items']);

        $purchaseOrder->update($validated);

        $purchaseOrder->items()->delete();

        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['price'];
            $taxAmount = $lineTotal * (($item['tax_percent'] ?? 0) / 100);
            $discountAmount = $lineTotal * (($item['discount_percent'] ?? 0) / 100);

            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
                'price' => $item['price'],
                'tax_percent' => $item['tax_percent'] ?? 0,
                'tax_amount' => $taxAmount,
                'discount_percent' => $item['discount_percent'] ?? 0,
                'discount_amount' => $discountAmount,
                'total' => $lineTotal + $taxAmount - $discountAmount,
                'received_qty' => 0,
            ]);
        }

        $purchaseOrder->load('items');
        $purchaseOrder->calculateTotals();
        $purchaseOrder->save();

        return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated successfully.');
    }

    public function submit(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if (! $purchaseOrder->isDraft()) {
            return back()->with('error', 'Only draft orders can be submitted.');
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_SUBMITTED]);

        return back()->with('success', 'Purchase order submitted successfully.');
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if (! $purchaseOrder->canBeApproved()) {
            return back()->with('error', 'Cannot approve this purchase order.');
        }

        $user = auth()->user();

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Purchase order approved successfully.');
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizePurchaseOrder($purchaseOrder);

        if ($purchaseOrder->status === PurchaseOrder::STATUS_RECEIVED) {
            return back()->with('error', 'Cannot cancel received purchase order.');
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return back()->with('success', 'Purchase order cancelled successfully.');
    }

    private function authorizePurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();

        if ($purchaseOrder->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
