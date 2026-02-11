<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ModifierGroup;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOutlet;
use App\Models\Recipe;
use App\Models\VariantGroup;
use App\Services\Menu\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = Product::where('tenant_id', $tenantId)
            ->with(['category', 'variants']);

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

        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $products = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $tenantId = $this->getTenantId();

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recipes = Recipe::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $variantGroups = VariantGroup::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('activeOptions')
            ->orderBy('name')
            ->get();

        $modifierGroups = ModifierGroup::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('activeModifiers')
            ->orderBy('name')
            ->get();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu.products.create', compact(
            'categories',
            'recipes',
            'inventoryItems',
            'variantGroups',
            'modifierGroups',
            'outlets'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'recipe_id' => ['nullable', 'exists:recipes,id'],
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'product_type' => ['required', 'in:single,variant,combo'],
            'track_stock' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'show_in_pos' => ['boolean'],
            'show_in_menu' => ['boolean'],
            'allow_notes' => ['boolean'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'tags' => ['nullable', 'array'],
            'allergens' => ['nullable', 'array'],
            'variant_groups' => ['nullable', 'array'],
            'variant_groups.*' => ['exists:variant_groups,id'],
            'modifier_groups' => ['nullable', 'array'],
            'modifier_groups.*' => ['exists:modifier_groups,id'],
            'outlets' => ['nullable', 'array'],
            'outlets.*.outlet_id' => ['exists:outlets,id'],
            'outlets.*.is_available' => ['boolean'],
            'outlets.*.custom_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['slug'] = Str::slug($validated['name']);
        $validated['track_stock'] = $request->boolean('track_stock', true);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured', false);
        $validated['show_in_pos'] = $request->boolean('show_in_pos', true);
        $validated['show_in_menu'] = $request->boolean('show_in_menu', true);
        $validated['allow_notes'] = $request->boolean('allow_notes', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // Ensure unique slug
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Product::where('tenant_id', $tenantId)->where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug.'-'.$counter++;
        }

        $product = Product::create($validated);

        // Attach variant groups
        if (! empty($validated['variant_groups'])) {
            foreach ($validated['variant_groups'] as $index => $groupId) {
                $product->variantGroups()->attach($groupId, [
                    'id' => Str::uuid(),
                    'sort_order' => $index,
                ]);
            }
        }

        // Attach modifier groups
        if (! empty($validated['modifier_groups'])) {
            foreach ($validated['modifier_groups'] as $index => $groupId) {
                $product->modifierGroups()->attach($groupId, [
                    'id' => Str::uuid(),
                    'sort_order' => $index,
                ]);
            }
        }

        // Setup outlet availability
        if (! empty($validated['outlets'])) {
            foreach ($validated['outlets'] as $outletData) {
                ProductOutlet::create([
                    'product_id' => $product->id,
                    'outlet_id' => $outletData['outlet_id'],
                    'is_available' => $outletData['is_available'] ?? true,
                    'custom_price' => $outletData['custom_price'] ?? null,
                ]);
            }
        }

        // Generate variants if variant product
        if ($product->product_type === Product::TYPE_VARIANT && $product->variantGroups->isNotEmpty()) {
            $this->productService->generateVariants($product);
        }

        return redirect()->route('menu.products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        $this->authorizeProduct($product);
        $product->load([
            'category',
            'recipe',
            'inventoryItem',
            'variants',
            'variantGroups.options',
            'modifierGroups.modifiers',
            'productOutlets.outlet',
        ]);

        return view('menu.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorizeProduct($product);
        $tenantId = $this->getTenantId();

        $product->load([
            'variantGroups',
            'modifierGroups',
            'productOutlets',
        ]);

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recipes = Recipe::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $variantGroups = VariantGroup::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('activeOptions')
            ->orderBy('name')
            ->get();

        $modifierGroups = ModifierGroup::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('activeModifiers')
            ->orderBy('name')
            ->get();

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu.products.edit', compact(
            'product',
            'categories',
            'recipes',
            'inventoryItems',
            'variantGroups',
            'modifierGroups',
            'outlets'
        ));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'recipe_id' => ['nullable', 'exists:recipes,id'],
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'product_type' => ['required', 'in:single,variant,combo'],
            'track_stock' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'show_in_pos' => ['boolean'],
            'show_in_menu' => ['boolean'],
            'allow_notes' => ['boolean'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'tags' => ['nullable', 'array'],
            'allergens' => ['nullable', 'array'],
            'variant_groups' => ['nullable', 'array'],
            'modifier_groups' => ['nullable', 'array'],
            'outlets' => ['nullable', 'array'],
        ]);

        $validated['track_stock'] = $request->boolean('track_stock', true);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured', false);
        $validated['show_in_pos'] = $request->boolean('show_in_pos', true);
        $validated['show_in_menu'] = $request->boolean('show_in_menu', true);
        $validated['allow_notes'] = $request->boolean('allow_notes', true);

        // Update slug if name changed
        if ($validated['name'] !== $product->name) {
            $validated['slug'] = Str::slug($validated['name']);
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Product::where('tenant_id', $tenantId)
                ->where('slug', $validated['slug'])
                ->where('id', '!=', $product->id)
                ->exists()) {
                $validated['slug'] = $baseSlug.'-'.$counter++;
            }
        }

        $product->update($validated);

        // Update variant groups (detach all, then attach with UUID)
        if (isset($validated['variant_groups'])) {
            $product->variantGroups()->detach();
            foreach ($validated['variant_groups'] as $index => $groupId) {
                $product->variantGroups()->attach($groupId, [
                    'id' => Str::uuid(),
                    'sort_order' => $index,
                ]);
            }
        }

        // Update modifier groups (detach all, then attach with UUID)
        if (isset($validated['modifier_groups'])) {
            $product->modifierGroups()->detach();
            foreach ($validated['modifier_groups'] as $index => $groupId) {
                $product->modifierGroups()->attach($groupId, [
                    'id' => Str::uuid(),
                    'sort_order' => $index,
                ]);
            }
        }

        // Update outlet settings
        if (isset($validated['outlets'])) {
            $product->productOutlets()->delete();
            foreach ($validated['outlets'] as $outletData) {
                ProductOutlet::create([
                    'product_id' => $product->id,
                    'outlet_id' => $outletData['outlet_id'],
                    'is_available' => $outletData['is_available'] ?? true,
                    'custom_price' => $outletData['custom_price'] ?? null,
                ]);
            }
        }

        return redirect()->route('menu.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);

        // Soft delete
        $product->delete();

        return redirect()->route('menu.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);

        $newProduct = $this->productService->duplicate($product);

        return redirect()->route('menu.products.edit', $newProduct)
            ->with('success', 'Product duplicated. Please update the details.');
    }

    public function generateVariants(Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);

        if ($product->product_type !== Product::TYPE_VARIANT) {
            return back()->with('error', 'This product is not a variant type.');
        }

        $this->productService->generateVariants($product);

        return back()->with('success', 'Variants generated successfully.');
    }

    public function bulkUpdatePrices(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'products' => ['required', 'array'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.base_price' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($validated['products'] as $item) {
            Product::where('id', $item['id'])
                ->where('tenant_id', $tenantId)
                ->update(['base_price' => $item['base_price']]);
        }

        return back()->with('success', 'Prices updated successfully.');
    }

    private function authorizeProduct(Product $product): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($product->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
