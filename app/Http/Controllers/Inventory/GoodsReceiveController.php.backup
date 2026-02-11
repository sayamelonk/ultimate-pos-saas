<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoodsReceiveController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = GoodsReceive::where('tenant_id', $user->tenant_id)
            ->with(['supplier', 'outlet', 'receivedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('gr_number', 'like', "%{$search}%");
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
            $query->whereDate('receive_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('receive_date', '<=', $request->to_date);
        }

        $goodsReceives = $query->orderBy('receive_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.goods-receives.index', compact('goodsReceives'));
    }

    public function create(?PurchaseOrder $purchaseOrder): View
    {
        if ($purchaseOrder) {
            $this->authorizePurchaseOrder($purchaseOrder);

            if (! $purchaseOrder->canBeReceived()) {
                return back()->with('error', 'Purchase order cannot be received.');
            }

            $purchaseOrder->load(['items.inventoryItem', 'supplier', 'outlet']);
        }

        return view('inventory.goods-receives.create', compact('purchaseOrder'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'receive_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['nullable', 'exists:purchase_order_items,id'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:50'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $lastGr = GoodsReceive::where('tenant_id', $user->tenant_id)
            ->orderBy('id', 'desc')
            ->first();

        $grNumber = 'GR-'.date('Ymd').'-'.str_pad((($lastGr->id ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['gr_number'] = $grNumber;
        $validated['status'] = GoodsReceive::STATUS_DRAFT;
        $validated['received_by'] = $user->id;
        $validated['subtotal'] = 0;
        $validated['tax_amount'] = 0;
        $validated['discount_amount'] = 0;
        $validated['total'] = 0;

        $items = $validated['items'];
        unset($validated['items']);

        $goodsReceive = GoodsReceive::create($validated);

        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['cost_price'];
            $taxAmount = $lineTotal * (($item['tax_percent'] ?? 0) / 100);
            $discountAmount = $lineTotal * (($item['discount_percent'] ?? 0) / 100);

            GoodsReceiveItem::create([
                'goods_receive_id' => $goodsReceive->id,
                'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
                'cost_price' => $item['cost_price'],
                'batch_number' => $item['batch_number'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
                'tax_percent' => $item['tax_percent'] ?? 0,
                'tax_amount' => $taxAmount,
                'discount_percent' => $item['discount_percent'] ?? 0,
                'discount_amount' => $discountAmount,
                'total' => $lineTotal + $taxAmount - $discountAmount,
            ]);
        }

        $goodsReceive->load('items');
        $goodsReceive->calculateTotals();
        $goodsReceive->save();

        return redirect()->route('inventory.goods-receives.show', $goodsReceive)
            ->with('success', 'Goods receive created successfully.');
    }

    public function show(GoodsReceive $goodsReceive): View
    {
        $this->authorizeGoodsReceive($goodsReceive);
        $goodsReceive->load(['supplier', 'outlet', 'receivedBy', 'items.inventoryItem', 'items.unit']);

        return view('inventory.goods-receives.show', compact('goodsReceive'));
    }

    public function complete(GoodsReceive $goodsReceive): RedirectResponse
    {
        $this->authorizeGoodsReceive($goodsReceive);

        if (! $goodsReceive->isDraft()) {
            return back()->with('error', 'Only draft goods receives can be completed.');
        }

        $goodsReceive->load('items');

        foreach ($goodsReceive->items as $item) {
            $this->stockService->receiveStock(
                outletId: $goodsReceive->outlet_id,
                inventoryItemId: $item->inventory_item_id,
                quantity: $item->quantity,
                costPrice: $item->cost_price,
                userId: auth()->id(),
                grItem: $item,
                batchNumber: $item->batch_number,
                expiryDate: $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date) : null,
            );

            if ($item->purchase_order_item_id) {
                $poItem = PurchaseOrderItem::find($item->purchase_order_item_id);
                if ($poItem) {
                    $poItem->received_qty += $item->quantity;
                    $poItem->save();
                }
            }
        }

        if ($goodsReceive->purchase_order_id) {
            $purchaseOrder = PurchaseOrder::find($goodsReceive->purchase_order_id);
            if ($purchaseOrder) {
                $purchaseOrder->load('items');

                if ($purchaseOrder->isFullyReceived()) {
                    $purchaseOrder->update(['status' => PurchaseOrder::STATUS_RECEIVED]);
                } else {
                    $purchaseOrder->update(['status' => PurchaseOrder::STATUS_PARTIAL]);
                }
            }
        }

        $goodsReceive->update(['status' => GoodsReceive::STATUS_COMPLETED]);

        return back()->with('success', 'Goods received completed successfully. Stock updated.');
    }

    public function cancel(GoodsReceive $goodsReceive): RedirectResponse
    {
        $this->authorizeGoodsReceive($goodsReceive);

        if ($goodsReceive->isCompleted()) {
            return back()->with('error', 'Cannot cancel completed goods receive.');
        }

        $goodsReceive->update(['status' => GoodsReceive::STATUS_CANCELLED]);

        return back()->with('success', 'Goods receive cancelled successfully.');
    }

    private function authorizeGoodsReceive(GoodsReceive $goodsReceive): void
    {
        $user = auth()->user();

        if ($goodsReceive->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }

    private function authorizePurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();

        if ($purchaseOrder->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
