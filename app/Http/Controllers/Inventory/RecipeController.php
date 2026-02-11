<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Models\Unit;
use App\Services\Inventory\RecipeCostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function __construct(private RecipeCostService $recipeCostService) {}

    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = Recipe::where('tenant_id', $tenantId)
            ->with(['yieldUnit', 'product.category', 'items']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $recipes = $query->orderBy('name')->paginate(15)->withQueryString();

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.recipes.index', compact('recipes', 'categories'));
    }

    public function create(): View
    {
        $tenantId = $this->getTenantId();

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Products will be available in Phase 3 - for now use empty collection
        $products = collect();

        return view('inventory.recipes.create', compact('inventoryItems', 'units', 'categories', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'yield_qty' => ['required', 'numeric', 'min:0.001'],
            'yield_unit_id' => ['required', 'exists:units,id'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['nullable', 'integer', 'min:0'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.waste_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['is_active'] = $request->boolean('is_active');

        $recipe = Recipe::create($validated);

        // Create recipe items
        foreach ($validated['items'] as $index => $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);

            $recipe->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $inventoryItem->unit_id,
                'waste_percentage' => $item['waste_percentage'] ?? 0,
                'notes' => $item['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }

        // Calculate and update recipe cost
        $this->recipeCostService->updateRecipeCost($recipe);

        return redirect()->route('inventory.recipes.show', $recipe)
            ->with('success', 'Recipe created successfully.');
    }

    public function show(Recipe $recipe): View
    {
        $this->authorizeRecipe($recipe);
        $recipe->load(['yieldUnit', 'product.category', 'items.inventoryItem.unit', 'items.unit']);

        $costBreakdown = $this->recipeCostService->getCostBreakdown($recipe);

        return view('inventory.recipes.show', compact('recipe', 'costBreakdown'));
    }

    public function edit(Recipe $recipe): View
    {
        $this->authorizeRecipe($recipe);
        $tenantId = $this->getTenantId();
        $recipe->load('items.inventoryItem.unit');

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Products will be available in Phase 3 - for now use empty collection
        $products = collect();

        return view('inventory.recipes.edit', compact('recipe', 'inventoryItems', 'units', 'categories', 'products'));
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorizeRecipe($recipe);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'yield_qty' => ['required', 'numeric', 'min:0.001'],
            'yield_unit_id' => ['required', 'exists:units,id'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['nullable', 'integer', 'min:0'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.waste_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $recipe->update($validated);

        // Delete existing items and recreate
        $recipe->items()->delete();

        foreach ($validated['items'] as $index => $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);

            $recipe->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $inventoryItem->unit_id,
                'waste_percentage' => $item['waste_percentage'] ?? 0,
                'notes' => $item['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }

        // Recalculate cost
        $this->recipeCostService->updateRecipeCost($recipe);

        return redirect()->route('inventory.recipes.show', $recipe)
            ->with('success', 'Recipe updated successfully.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorizeRecipe($recipe);

        // Check if recipe is linked to a product
        if ($recipe->product_id) {
            return back()->with('error', 'Cannot delete recipe linked to a product.');
        }

        $recipe->items()->delete();
        $recipe->delete();

        return redirect()->route('inventory.recipes.index')
            ->with('success', 'Recipe deleted successfully.');
    }

    public function duplicate(Recipe $recipe): RedirectResponse
    {
        $this->authorizeRecipe($recipe);
        $recipe->load('items');

        $newRecipe = $recipe->replicate();
        $newRecipe->name = $recipe->name.' (Copy)';
        $newRecipe->product_id = null;
        $newRecipe->save();

        foreach ($recipe->items as $item) {
            $newRecipe->items()->create([
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => $item->quantity,
                'unit_id' => $item->unit_id,
                'waste_percentage' => $item->waste_percentage ?? 0,
                'notes' => $item->notes,
                'sort_order' => $item->sort_order,
            ]);
        }

        // Recalculate cost for the new recipe
        $this->recipeCostService->updateRecipeCost($newRecipe);

        return redirect()->route('inventory.recipes.edit', $newRecipe)
            ->with('success', 'Recipe duplicated successfully. Please update the details.');
    }

    public function recalculateCost(Recipe $recipe): RedirectResponse
    {
        $this->authorizeRecipe($recipe);

        $this->recipeCostService->updateRecipeCost($recipe);

        return back()->with('success', 'Recipe cost recalculated.');
    }

    public function costAnalysis(): View
    {
        $tenantId = $this->getTenantId();

        $recipes = Recipe::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['yieldUnit', 'items.inventoryItem.unit', 'items.unit', 'product'])
            ->orderBy('name')
            ->get();

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.recipes.cost-analysis', compact('recipes', 'categories'));
    }

    private function authorizeRecipe(Recipe $recipe): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($recipe->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
