<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InventoryCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->with('parent');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        $categories = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('inventory.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $parentCategories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('inventory.categories.create', compact('parentCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:inventory_categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        InventoryCategory::create($validated);

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(InventoryCategory $category): View
    {
        $this->authorizeCategory($category);
        $category->load(['parent', 'children', 'inventoryItems' => function ($q) {
            $q->orderBy('name')->take(20);
        }]);

        return view('inventory.categories.show', compact('category'));
    }

    public function edit(InventoryCategory $category): View
    {
        $this->authorizeCategory($category);
        $user = auth()->user();

        $parentCategories = InventoryCategory::where('tenant_id', $user->tenant_id)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('inventory.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, InventoryCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:inventory_categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        // Prevent category from being its own parent
        if ($validated['parent_id'] === $category->id) {
            return back()->with('error', 'Category cannot be its own parent.');
        }

        $category->update($validated);

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(InventoryCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category);

        // Check for child categories
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories.');
        }

        // Check for inventory items
        if ($category->inventoryItems()->exists()) {
            return back()->with('error', 'Cannot delete category with inventory items.');
        }

        $category->delete();

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function authorizeCategory(InventoryCategory $category): void
    {
        $user = auth()->user();

        if ($category->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
