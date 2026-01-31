<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = StockTransfer::where('tenant_id', $user->tenant_id)
            ->with(['fromOutlet', 'toOutlet', 'createdBy', 'approvedBy', 'receivedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('transfer_number', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_outlet_id')) {
            $query->where('from_outlet_id', $request->from_outlet_id);
        }

        if ($request->filled('to_outlet_id')) {
            $query->where('to_outlet_id', $request->to_outlet_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('transfer_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transfer_date', '<=', $request->to_date);
        }

        $transfers = $query->orderBy('transfer_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.stock-transfers.index', compact('transfers'));
    }

    public function create(): View
    {
        $user = auth()->user();

        return view('inventory.stock-transfers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'from_outlet_id' => ['required', 'exists:outlets,id', 'different:to_outlet_id'],
            'to_outlet_id' => ['required', 'exists:outlets,id'],
            'transfer_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $lastTransfer = StockTransfer::where('tenant_id', $user->tenant_id)
            ->orderBy('id', 'desc')
            ->first();

        $transferNumber = 'TF-'.date('Ymd').'-'.str_pad((($lastTransfer->id ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['transfer_number'] = $transferNumber;
        $validated['status'] = StockTransfer::STATUS_DRAFT;
        $validated['created_by'] = $user->id;

        $items = $validated['items'];
        unset($validated['items']);

        $transfer = StockTransfer::create($validated);

        foreach ($items as $item) {
            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'cost_price' => $item['cost_price'],
            ]);
        }

        return redirect()->route('inventory.stock-transfers.show', $transfer)
            ->with('success', 'Stock transfer created successfully.');
    }

    public function show(StockTransfer $transfer): View
    {
        $this->authorizeTransfer($transfer);
        $transfer->load(['fromOutlet', 'toOutlet', 'createdBy', 'approvedBy', 'receivedBy', 'items.inventoryItem']);

        return view('inventory.stock-transfers.show', compact('transfer'));
    }

    public function submit(StockTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($transfer);

        if (! $transfer->isDraft()) {
            return back()->with('error', 'Only draft transfers can be submitted.');
        }

        $transfer->update(['status' => StockTransfer::STATUS_PENDING]);

        return back()->with('success', 'Stock transfer submitted successfully.');
    }

    public function approve(StockTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($transfer);

        if (! $transfer->canBeApproved()) {
            return back()->with('error', 'Cannot approve this transfer.');
        }

        $user = auth()->user();
        $transfer->load('items');

        foreach ($transfer->items as $item) {
            $this->stockService->transferStock(
                fromOutletId: $transfer->from_outlet_id,
                toOutletId: $transfer->to_outlet_id,
                inventoryItemId: $item->inventory_item_id,
                quantity: $item->quantity,
                userId: $user->id,
                transferId: $transfer->id,
            );
        }

        $transfer->update([
            'status' => StockTransfer::STATUS_IN_TRANSIT,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Stock transfer approved and stock moved.');
    }

    public function receive(StockTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($transfer);

        if (! $transfer->canBeReceived()) {
            return back()->with('error', 'Cannot receive this transfer.');
        }

        $user = auth()->user();

        $transfer->update([
            'status' => StockTransfer::STATUS_RECEIVED,
            'received_by' => $user->id,
            'received_at' => now(),
        ]);

        return back()->with('success', 'Stock transfer received successfully.');
    }

    public function cancel(StockTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($transfer);

        if ($transfer->isReceived()) {
            return back()->with('error', 'Cannot cancel received transfer.');
        }

        if ($transfer->isInTransit()) {
            return back()->with('error', 'Cannot cancel transfer that is already in transit. Please contact admin.');
        }

        $transfer->update(['status' => StockTransfer::STATUS_CANCELLED]);

        return back()->with('success', 'Stock transfer cancelled successfully.');
    }

    private function authorizeTransfer(StockTransfer $transfer): void
    {
        $user = auth()->user();

        if ($transfer->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
