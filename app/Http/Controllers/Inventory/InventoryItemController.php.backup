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
        $user = auth()->user();
        $query = InventoryItem::where('tenant_id', $user->tenant_id)
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

        $categories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return view('inventory.items.index', compact('items', 'categories'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $categories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.items.create', compact('categories', 'units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

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

        $validated['tenant_id'] = $user->tenant_id;
        $validated['is_perishable'] = $request->boolean('is_perishable', false);
        $validated['track_batches'] = $request->boolean('track_batches', false);
        $validated['is_active'] = $request->boolean('is_active', true);

        InventoryItem::create($validated);

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
                $q->where('remaining_quantity', '>', 0)->orderBy('expiry_date');
            },
        ]);

        return view('inventory.items.show', compact('item'));
    }

    public function edit(InventoryItem $item): View
    {
        $this->authorizeItem($item);
        $user = auth()->user();

        $categories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $user->tenant_id)
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

        $validated['is_perishable'] = $request->boolean('is_perishable', false);
        $validated['track_batches'] = $request->boolean('track_batches', false);
        $validated['is_active'] = $request->boolean('is_active', true);

        $item->update($validated);

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

        if ($item->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
