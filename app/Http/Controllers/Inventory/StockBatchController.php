<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\BatchSetting;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\StockBatch;
use App\Models\StockBatchMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockBatchController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = StockBatch::where('tenant_id', $tenantId)
            ->with(['inventoryItem.unit', 'outlet']);

        // Filters
        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('item_id')) {
            $query->where('inventory_item_id', $request->item_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('expiry_filter')) {
            $settings = BatchSetting::getForTenant($tenantId);

            switch ($request->expiry_filter) {
                case 'expired':
                    $query->expired();
                    break;
                case 'critical':
                    $query->critical($settings->expiry_critical_days);
                    break;
                case 'warning':
                    $query->expiringSoon($settings->expiry_warning_days);
                    break;
                case 'no_expiry':
                    $query->whereNull('expiry_date');
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhere('supplier_batch_number', 'like', "%{$search}%")
                    ->orWhereHas('inventoryItem', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        $batches = $query->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $outlets = Outlet::where('tenant_id', $tenantId)->get();
        $items = InventoryItem::where('tenant_id', $tenantId)
            ->where('track_batches', true)
            ->get();
        $settings = BatchSetting::getForTenant($tenantId);

        // Stats
        $stats = [
            'total_batches' => StockBatch::where('tenant_id', $tenantId)->active()->count(),
            'expiring_soon' => StockBatch::where('tenant_id', $tenantId)
                ->expiringSoon($settings->expiry_warning_days)->count(),
            'critical' => StockBatch::where('tenant_id', $tenantId)
                ->critical($settings->expiry_critical_days)->count(),
            'expired' => StockBatch::where('tenant_id', $tenantId)
                ->where('status', StockBatch::STATUS_ACTIVE)
                ->expired()->count(),
        ];

        return view('inventory.batches.index', compact(
            'batches',
            'outlets',
            'items',
            'settings',
            'stats'
        ));
    }

    public function show(StockBatch $batch): View
    {
        $batch->load(['inventoryItem.unit', 'outlet', 'goodsReceiveItem.goodsReceive']);

        $movements = StockBatchMovement::where('stock_batch_id', $batch->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('inventory.batches.show', compact('batch', 'movements'));
    }

    public function create(Request $request): View
    {
        $tenantId = $this->getTenantId();

        $outlets = Outlet::where('tenant_id', $tenantId)->get();
        $items = InventoryItem::where('tenant_id', $tenantId)
            ->where('track_batches', true)
            ->where('is_active', true)
            ->with('unit')
            ->get();
        $settings = BatchSetting::getForTenant($tenantId);

        $selectedOutlet = $request->outlet_id;
        $selectedItem = $request->item_id;

        return view('inventory.batches.create', compact(
            'outlets',
            'items',
            'settings',
            'selectedOutlet',
            'selectedItem'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'production_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:production_date'],
            'initial_quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'supplier_batch_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $tenantId = $this->getTenantId();
        $settings = BatchSetting::getForTenant($tenantId);

        // Generate batch number if not provided
        $batchNumber = $request->batch_number;
        if (! $batchNumber && $settings->auto_generate_batch) {
            $batchNumber = StockBatch::generateBatchNumber(
                $request->outlet_id,
                $settings->batch_prefix
            );
        }

        $batch = StockBatch::create([
            'tenant_id' => $tenantId,
            'outlet_id' => $request->outlet_id,
            'inventory_item_id' => $request->inventory_item_id,
            'batch_number' => $batchNumber,
            'production_date' => $request->production_date,
            'expiry_date' => $request->expiry_date,
            'initial_quantity' => $request->initial_quantity,
            'current_quantity' => $request->initial_quantity,
            'unit_cost' => $request->unit_cost ?? 0,
            'supplier_batch_number' => $request->supplier_batch_number,
            'notes' => $request->notes,
            'status' => StockBatch::STATUS_ACTIVE,
        ]);

        // Create initial movement record
        StockBatchMovement::create([
            'tenant_id' => $tenantId,
            'outlet_id' => $request->outlet_id,
            'stock_batch_id' => $batch->id,
            'inventory_item_id' => $request->inventory_item_id,
            'type' => StockBatchMovement::TYPE_RECEIVE,
            'quantity' => $request->initial_quantity,
            'balance_before' => 0,
            'balance_after' => $request->initial_quantity,
            'reference_number' => 'Manual Entry',
            'notes' => 'Initial batch creation',
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('inventory.batches.index')
            ->with('success', __('inventory.batch_created'));
    }

    public function adjust(Request $request, StockBatch $batch): RedirectResponse
    {
        $request->validate([
            'adjustment_type' => ['required', 'in:add,subtract'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $tenantId = $this->getTenantId();
        $quantity = $request->adjustment_type === 'subtract'
            ? -$request->quantity
            : $request->quantity;

        $balanceBefore = $batch->current_quantity;
        $balanceAfter = $balanceBefore + $quantity;

        if ($balanceAfter < 0) {
            return back()->with('error', 'Cannot reduce quantity below zero.');
        }

        $batch->current_quantity = $balanceAfter;

        if ($batch->current_quantity <= 0) {
            $batch->status = StockBatch::STATUS_DEPLETED;
        }

        $batch->save();

        StockBatchMovement::create([
            'tenant_id' => $tenantId,
            'outlet_id' => $batch->outlet_id,
            'stock_batch_id' => $batch->id,
            'inventory_item_id' => $batch->inventory_item_id,
            'type' => StockBatchMovement::TYPE_ADJUSTMENT,
            'quantity' => $quantity,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_number' => 'ADJ-'.now()->format('YmdHis'),
            'notes' => $request->reason,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Batch quantity adjusted successfully.');
    }

    public function markExpired(StockBatch $batch): RedirectResponse
    {
        $tenantId = $this->getTenantId();
        $balanceBefore = $batch->current_quantity;

        // Create movement for the expired quantity
        if ($batch->current_quantity > 0) {
            StockBatchMovement::create([
                'tenant_id' => $tenantId,
                'outlet_id' => $batch->outlet_id,
                'stock_batch_id' => $batch->id,
                'inventory_item_id' => $batch->inventory_item_id,
                'type' => StockBatchMovement::TYPE_EXPIRED,
                'quantity' => -$batch->current_quantity,
                'balance_before' => $balanceBefore,
                'balance_after' => 0,
                'reference_number' => 'EXP-'.now()->format('YmdHis'),
                'notes' => 'Marked as expired',
                'user_id' => auth()->id(),
            ]);
        }

        $batch->current_quantity = 0;
        $batch->status = StockBatch::STATUS_EXPIRED;
        $batch->save();

        return back()->with('success', 'Batch marked as expired.');
    }

    public function dispose(StockBatch $batch): RedirectResponse
    {
        $tenantId = $this->getTenantId();
        $balanceBefore = $batch->current_quantity;

        if ($batch->current_quantity > 0) {
            StockBatchMovement::create([
                'tenant_id' => $tenantId,
                'outlet_id' => $batch->outlet_id,
                'stock_batch_id' => $batch->id,
                'inventory_item_id' => $batch->inventory_item_id,
                'type' => StockBatchMovement::TYPE_WASTE,
                'quantity' => -$batch->current_quantity,
                'balance_before' => $balanceBefore,
                'balance_after' => 0,
                'reference_number' => 'DSP-'.now()->format('YmdHis'),
                'notes' => 'Disposed',
                'user_id' => auth()->id(),
            ]);
        }

        $batch->current_quantity = 0;
        $batch->status = StockBatch::STATUS_DISPOSED;
        $batch->save();

        return back()->with('success', 'Batch disposed successfully.');
    }

    public function expiryReport(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $settings = BatchSetting::getForTenant($tenantId);

        $daysAhead = $request->get('days', $settings->expiry_warning_days);

        $query = StockBatch::where('tenant_id', $tenantId)
            ->where('status', StockBatch::STATUS_ACTIVE)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($daysAhead))
            ->where('current_quantity', '>', 0)
            ->with(['inventoryItem.unit', 'outlet']);

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        $batches = $query->orderBy('expiry_date', 'asc')->get();

        // Group by expiry status
        $expired = $batches->filter(fn ($b) => $b->isExpired());
        $critical = $batches->filter(fn ($b) => ! $b->isExpired() && $b->isCritical($settings->expiry_critical_days));
        $warning = $batches->filter(fn ($b) => ! $b->isExpired() && ! $b->isCritical($settings->expiry_critical_days));

        $outlets = Outlet::where('tenant_id', $tenantId)->get();

        // Calculate total value at risk
        $totalValue = $batches->sum(fn ($b) => $b->current_quantity * $b->unit_cost);

        return view('inventory.batches.expiry-report', compact(
            'batches',
            'expired',
            'critical',
            'warning',
            'outlets',
            'settings',
            'daysAhead',
            'totalValue'
        ));
    }
}
