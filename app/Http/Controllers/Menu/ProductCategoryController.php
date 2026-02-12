<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = ProductCategory::where('tenant_id', $tenantId)
            ->with('parent', 'children')
            ->withCount('products');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        $parentCategories = ProductCategory::where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu.categories.index', compact('categories', 'parentCategories'));
    }

    public function create(): View
    {
        $tenantId = $this->getTenantId();

        $parentCategories = ProductCategory::where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu.categories.create', compact('parentCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:product_categories,id'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'show_in_pos' => ['boolean'],
            'show_in_menu' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_in_pos'] = $request->boolean('show_in_pos', true);
        $validated['show_in_menu'] = $request->boolean('show_in_menu', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // Ensure unique slug
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (ProductCategory::where('tenant_id', $tenantId)->where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug.'-'.$counter++;
        }

        ProductCategory::create($validated);

        return redirect()->route('menu.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(ProductCategory $category): View
    {
        $this->authorizeCategory($category);
        $category->load(['parent', 'children', 'products' => function ($q) {
            $q->orderBy('sort_order')->limit(10);
        }]);

        return view('menu.categories.show', compact('category'));
    }

    public function edit(ProductCategory $category): View
    {
        $this->authorizeCategory($category);
        $tenantId = $this->getTenantId();

        $parentCategories = ProductCategory::where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category);
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:product_categories,id'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'show_in_pos' => ['boolean'],
            'show_in_menu' => ['boolean'],
        ]);

        // Prevent setting self as parent
        if (isset($validated['parent_id']) && $validated['parent_id'] === $category->id) {
            return back()->with('error', 'Category cannot be its own parent.');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_in_pos'] = $request->boolean('show_in_pos', true);
        $validated['show_in_menu'] = $request->boolean('show_in_menu', true);

        // Update slug if name changed
        if ($validated['name'] !== $category->name) {
            $validated['slug'] = Str::slug($validated['name']);
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (ProductCategory::where('tenant_id', $tenantId)
                ->where('slug', $validated['slug'])
                ->where('id', '!=', $category->id)
                ->exists()) {
                $validated['slug'] = $baseSlug.'-'.$counter++;
            }
        }

        $category->update($validated);

        return redirect()->route('menu.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category);

        // Check for products
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with products. Move or delete products first.');
        }

        // Check for children
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories.');
        }

        $category->delete();

        return redirect()->route('menu.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'exists:product_categories,id'],
            'categories.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['categories'] as $item) {
            ProductCategory::where('id', $item['id'])
                ->where('tenant_id', $tenantId)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Categories reordered successfully.');
    }

    private function authorizeCategory(ProductCategory $category): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($category->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
