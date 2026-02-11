<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModifierGroupController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = ModifierGroup::where('tenant_id', $tenantId)
            ->withCount(['modifiers', 'products']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $modifierGroups = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('menu.modifier-groups.index', compact('modifierGroups'));
    }

    public function create(): View
    {
        $tenantId = $this->getTenantId();

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu.modifier-groups.create', compact('inventoryItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'selection_type' => ['required', 'in:single,multiple'],
            'min_selections' => ['nullable', 'integer', 'min:0'],
            'max_selections' => ['nullable', 'integer', 'min:1'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'modifiers' => ['nullable', 'array'],
            'modifiers.*.name' => ['required', 'string', 'max:255'],
            'modifiers.*.display_name' => ['nullable', 'string', 'max:255'],
            'modifiers.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'modifiers.*.price' => ['nullable', 'numeric', 'min:0'],
            'modifiers.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'modifiers.*.quantity_used' => ['nullable', 'numeric', 'min:0'],
            'modifiers.*.is_default' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['is_required'] = $request->boolean('is_required', false);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['min_selections'] = $validated['min_selections'] ?? 0;

        $modifierGroup = ModifierGroup::create($validated);

        // Create modifiers
        if (! empty($validated['modifiers'])) {
            foreach ($validated['modifiers'] as $index => $modifierData) {
                Modifier::create([
                    'modifier_group_id' => $modifierGroup->id,
                    'inventory_item_id' => $modifierData['inventory_item_id'] ?? null,
                    'name' => $modifierData['name'],
                    'display_name' => $modifierData['display_name'] ?? null,
                    'price' => $modifierData['price'] ?? 0,
                    'cost_price' => $modifierData['cost_price'] ?? 0,
                    'quantity_used' => $modifierData['quantity_used'] ?? 1,
                    'is_default' => $modifierData['is_default'] ?? false,
                    'is_active' => true,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('menu.modifier-groups.show', $modifierGroup)
            ->with('success', 'Modifier group created successfully.');
    }

    public function show(ModifierGroup $modifierGroup): View
    {
        $this->authorizeModifierGroup($modifierGroup);
        $modifierGroup->load([
            'modifiers' => fn ($q) => $q->orderBy('sort_order'),
            'modifiers.inventoryItem',
            'products',
        ]);

        return view('menu.modifier-groups.show', compact('modifierGroup'));
    }

    public function edit(ModifierGroup $modifierGroup): View
    {
        $this->authorizeModifierGroup($modifierGroup);
        $tenantId = $this->getTenantId();

        $modifierGroup->load(['modifiers' => fn ($q) => $q->orderBy('sort_order')]);

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu.modifier-groups.edit', compact('modifierGroup', 'inventoryItems'));
    }

    public function update(Request $request, ModifierGroup $modifierGroup): RedirectResponse
    {
        $this->authorizeModifierGroup($modifierGroup);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'selection_type' => ['required', 'in:single,multiple'],
            'min_selections' => ['nullable', 'integer', 'min:0'],
            'max_selections' => ['nullable', 'integer', 'min:1'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'modifiers' => ['nullable', 'array'],
            'modifiers.*.id' => ['nullable', 'exists:modifiers,id'],
            'modifiers.*.name' => ['required', 'string', 'max:255'],
            'modifiers.*.display_name' => ['nullable', 'string', 'max:255'],
            'modifiers.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'modifiers.*.price' => ['nullable', 'numeric', 'min:0'],
            'modifiers.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'modifiers.*.quantity_used' => ['nullable', 'numeric', 'min:0'],
            'modifiers.*.is_default' => ['boolean'],
            'modifiers.*.is_active' => ['boolean'],
        ]);

        $validated['is_required'] = $request->boolean('is_required', false);
        $validated['is_active'] = $request->boolean('is_active');

        $modifierGroup->update($validated);

        // Update modifiers
        if (isset($validated['modifiers'])) {
            $existingIds = [];

            foreach ($validated['modifiers'] as $index => $modifierData) {
                if (! empty($modifierData['id'])) {
                    // Update existing
                    Modifier::where('id', $modifierData['id'])
                        ->where('modifier_group_id', $modifierGroup->id)
                        ->update([
                            'inventory_item_id' => $modifierData['inventory_item_id'] ?? null,
                            'name' => $modifierData['name'],
                            'display_name' => $modifierData['display_name'] ?? null,
                            'price' => $modifierData['price'] ?? 0,
                            'cost_price' => $modifierData['cost_price'] ?? 0,
                            'quantity_used' => $modifierData['quantity_used'] ?? 1,
                            'is_default' => $modifierData['is_default'] ?? false,
                            'is_active' => $modifierData['is_active'] ?? true,
                            'sort_order' => $index,
                        ]);
                    $existingIds[] = $modifierData['id'];
                } else {
                    // Create new
                    $modifier = Modifier::create([
                        'modifier_group_id' => $modifierGroup->id,
                        'inventory_item_id' => $modifierData['inventory_item_id'] ?? null,
                        'name' => $modifierData['name'],
                        'display_name' => $modifierData['display_name'] ?? null,
                        'price' => $modifierData['price'] ?? 0,
                        'cost_price' => $modifierData['cost_price'] ?? 0,
                        'quantity_used' => $modifierData['quantity_used'] ?? 1,
                        'is_default' => $modifierData['is_default'] ?? false,
                        'is_active' => true,
                        'sort_order' => $index,
                    ]);
                    $existingIds[] = $modifier->id;
                }
            }

            // Delete removed modifiers
            $modifierGroup->modifiers()->whereNotIn('id', $existingIds)->delete();
        }

        return redirect()->route('menu.modifier-groups.show', $modifierGroup)
            ->with('success', 'Modifier group updated successfully.');
    }

    public function destroy(ModifierGroup $modifierGroup): RedirectResponse
    {
        $this->authorizeModifierGroup($modifierGroup);

        // Check if used by products
        if ($modifierGroup->products()->exists()) {
            return back()->with('error', 'Cannot delete modifier group used by products.');
        }

        $modifierGroup->modifiers()->delete();
        $modifierGroup->delete();

        return redirect()->route('menu.modifier-groups.index')
            ->with('success', 'Modifier group deleted successfully.');
    }

    private function authorizeModifierGroup(ModifierGroup $modifierGroup): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($modifierGroup->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
