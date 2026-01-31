<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Recipe::where('tenant_id', $user->tenant_id)
            ->with(['product', 'yieldUnit']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $recipes = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('inventory.recipes.index', compact('recipes'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $units = Unit::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.recipes.create', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'yield_qty' => ['required', 'numeric', 'min:0'],
            'yield_unit_id' => ['required', 'exists:units,id'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['estimated_cost'] = 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $recipe = Recipe::create($validated);

        foreach ($items as $item) {
            $recipe->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
            ]);
        }

        $recipe->calculateCost();
        $recipe->update(['estimated_cost' => $recipe->calculateCost()]);

        return redirect()->route('inventory.recipes.index')
            ->with('success', 'Recipe created successfully.');
    }

    public function show(Recipe $recipe): View
    {
        $this->authorizeRecipe($recipe);
        $recipe->load(['product', 'yieldUnit', 'items.inventoryItem', 'items.unit']);

        return view('inventory.recipes.show', compact('recipe'));
    }

    public function edit(Recipe $recipe): View
    {
        $this->authorizeRecipe($recipe);
        $user = auth()->user();

        $units = Unit::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recipe->load(['items']);

        return view('inventory.recipes.edit', compact('recipe', 'units'));
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorizeRecipe($recipe);

        $validated = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'yield_qty' => ['required', 'numeric', 'min:0'],
            'yield_unit_id' => ['required', 'exists:units,id'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $recipe->update($validated);

        $recipe->items()->delete();

        foreach ($items as $item) {
            $recipe->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
            ]);
        }

        $recipe->update(['estimated_cost' => $recipe->calculateCost()]);

        return redirect()->route('inventory.recipes.index')
            ->with('success', 'Recipe updated successfully.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorizeRecipe($recipe);

        $recipe->items()->delete();
        $recipe->delete();

        return redirect()->route('inventory.recipes.index')
            ->with('success', 'Recipe deleted successfully.');
    }

    public function calculateCost(Recipe $recipe): RedirectResponse
    {
        $this->authorizeRecipe($recipe);

        $recipe->load('items');
        $cost = $recipe->calculateCost();

        $recipe->update(['estimated_cost' => $cost]);

        return back()->with('success', 'Recipe cost recalculated: '.number_format($cost, 2));
    }

    private function authorizeRecipe(Recipe $recipe): void
    {
        $user = auth()->user();

        if ($recipe->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
