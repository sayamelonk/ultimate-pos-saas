<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VariantGroupController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = VariantGroup::where('tenant_id', $tenantId)
            ->withCount(['options', 'products']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $variantGroups = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('menu.variant-groups.index', compact('variantGroups'));
    }

    public function create(): View
    {
        return view('menu.variant-groups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'display_type' => ['required', 'in:button,dropdown,color,image'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.display_name' => ['nullable', 'string', 'max:255'],
            'options.*.value' => ['nullable', 'string', 'max:255'],
            'options.*.price_adjustment' => ['nullable', 'numeric'],
            'options.*.is_default' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['is_required'] = $request->boolean('is_required', true);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $variantGroup = VariantGroup::create($validated);

        // Create options
        if (! empty($validated['options'])) {
            foreach ($validated['options'] as $index => $optionData) {
                VariantOption::create([
                    'variant_group_id' => $variantGroup->id,
                    'name' => $optionData['name'],
                    'display_name' => $optionData['display_name'] ?? null,
                    'value' => $optionData['value'] ?? null,
                    'price_adjustment' => $optionData['price_adjustment'] ?? 0,
                    'is_default' => $optionData['is_default'] ?? false,
                    'is_active' => true,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('menu.variant-groups.show', $variantGroup)
            ->with('success', 'Variant group created successfully.');
    }

    public function show(VariantGroup $variantGroup): View
    {
        $this->authorizeVariantGroup($variantGroup);
        $variantGroup->load(['options' => fn ($q) => $q->orderBy('sort_order'), 'products']);

        return view('menu.variant-groups.show', compact('variantGroup'));
    }

    public function edit(VariantGroup $variantGroup): View
    {
        $this->authorizeVariantGroup($variantGroup);
        $variantGroup->load(['options' => fn ($q) => $q->orderBy('sort_order')]);

        return view('menu.variant-groups.edit', compact('variantGroup'));
    }

    public function update(Request $request, VariantGroup $variantGroup): RedirectResponse
    {
        $this->authorizeVariantGroup($variantGroup);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'display_type' => ['required', 'in:button,dropdown,color,image'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*.id' => ['nullable', 'exists:variant_options,id'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.display_name' => ['nullable', 'string', 'max:255'],
            'options.*.value' => ['nullable', 'string', 'max:255'],
            'options.*.price_adjustment' => ['nullable', 'numeric'],
            'options.*.is_default' => ['boolean'],
            'options.*.is_active' => ['boolean'],
        ]);

        $validated['is_required'] = $request->boolean('is_required', true);
        $validated['is_active'] = $request->boolean('is_active');

        $variantGroup->update($validated);

        // Update options
        if (isset($validated['options'])) {
            $existingIds = [];

            foreach ($validated['options'] as $index => $optionData) {
                if (! empty($optionData['id'])) {
                    // Update existing
                    VariantOption::where('id', $optionData['id'])
                        ->where('variant_group_id', $variantGroup->id)
                        ->update([
                            'name' => $optionData['name'],
                            'display_name' => $optionData['display_name'] ?? null,
                            'value' => $optionData['value'] ?? null,
                            'price_adjustment' => $optionData['price_adjustment'] ?? 0,
                            'is_default' => $optionData['is_default'] ?? false,
                            'is_active' => $optionData['is_active'] ?? true,
                            'sort_order' => $index,
                        ]);
                    $existingIds[] = $optionData['id'];
                } else {
                    // Create new
                    $option = VariantOption::create([
                        'variant_group_id' => $variantGroup->id,
                        'name' => $optionData['name'],
                        'display_name' => $optionData['display_name'] ?? null,
                        'value' => $optionData['value'] ?? null,
                        'price_adjustment' => $optionData['price_adjustment'] ?? 0,
                        'is_default' => $optionData['is_default'] ?? false,
                        'is_active' => true,
                        'sort_order' => $index,
                    ]);
                    $existingIds[] = $option->id;
                }
            }

            // Delete removed options
            $variantGroup->options()->whereNotIn('id', $existingIds)->delete();
        }

        return redirect()->route('menu.variant-groups.show', $variantGroup)
            ->with('success', 'Variant group updated successfully.');
    }

    public function destroy(VariantGroup $variantGroup): RedirectResponse
    {
        $this->authorizeVariantGroup($variantGroup);

        // Check if used by products
        if ($variantGroup->products()->exists()) {
            return back()->with('error', 'Cannot delete variant group used by products.');
        }

        $variantGroup->options()->delete();
        $variantGroup->delete();

        return redirect()->route('menu.variant-groups.index')
            ->with('success', 'Variant group deleted successfully.');
    }

    private function authorizeVariantGroup(VariantGroup $variantGroup): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($variantGroup->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
