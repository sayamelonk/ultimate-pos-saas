<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->with(['category', 'unit']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return view('inventory.items.index', compact('items', 'categories'));
    }

    public function create(): View
    {
        $tenantId = $this->getTenantId();

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.items.create', compact('categories', 'units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:inventory_categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'type' => ['required', 'in:raw_material,finished_good,consumable,packaging'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'is_perishable' => ['boolean'],
            'track_batches' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // Map form fields to database fields
        $data = [
            'tenant_id' => $tenantId,
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'unit_id' => $validated['unit_id'],
            'cost_price' => $validated['cost_price'],
            'reorder_point' => $validated['reorder_level'] ?? 0,
            'reorder_qty' => $validated['reorder_quantity'] ?? 0,
            'max_stock' => $validated['max_stock_level'] ?? null,
            'shelf_life_days' => $validated['shelf_life_days'] ?? null,
            'storage_location' => $validated['storage_location'] ?? null,
            'is_perishable' => $request->boolean('is_perishable', false),
            'track_batches' => $request->boolean('track_batches', false),
            'is_active' => $request->boolean('is_active'),
        ];

        InventoryItem::create($data);

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item created successfully.');
    }

    public function show(InventoryItem $item): View
    {
        $this->authorizeItem($item);
        $item->load([
            'category',
            'unit',
            'supplierItems.supplier',
            'stocks.outlet',
            'stockBatches' => function ($q) {
                $q->where('current_quantity', '>', 0)->orderBy('expiry_date');
            },
        ]);

        return view('inventory.items.show', compact('item'));
    }

    public function edit(InventoryItem $item): View
    {
        $this->authorizeItem($item);
        $tenantId = $this->getTenantId();

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.items.edit', compact('item', 'categories', 'units'));
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:inventory_categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'type' => ['required', 'in:raw_material,finished_good,consumable,packaging'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'is_perishable' => ['boolean'],
            'track_batches' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // Map form fields to database fields
        $data = [
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'unit_id' => $validated['unit_id'],
            'cost_price' => $validated['cost_price'],
            'reorder_point' => $validated['reorder_level'] ?? 0,
            'reorder_qty' => $validated['reorder_quantity'] ?? 0,
            'max_stock' => $validated['max_stock_level'] ?? null,
            'shelf_life_days' => $validated['shelf_life_days'] ?? null,
            'storage_location' => $validated['storage_location'] ?? null,
            'is_perishable' => $request->boolean('is_perishable', false),
            'track_batches' => $request->boolean('track_batches', false),
            'is_active' => $request->boolean('is_active'),
        ];

        $item->update($data);

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        // Check for existing stock
        if ($item->stocks()->where('quantity', '>', 0)->exists()) {
            return back()->with('error', 'Cannot delete item with existing stock.');
        }

        // Check for recipes using this item
        if ($item->recipeItems()->exists()) {
            return back()->with('error', 'Cannot delete item used in recipes.');
        }

        $item->delete();

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item deleted successfully.');
    }

    private function authorizeItem(InventoryItem $item): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($item->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
