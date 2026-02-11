<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ComboController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->getTenantId();
        $query = Product::where('tenant_id', $tenantId)
            ->where('product_type', Product::TYPE_COMBO)
            ->with(['category', 'combo.items.product']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $combos = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('menu.combos.index', compact('combos'));
    }

    public function create(): View
    {
        $tenantId = $this->getTenantId();

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('product_type', '!=', Product::TYPE_COMBO)
            ->orderBy('name')
            ->get();

        return view('menu.combos.create', compact('categories', 'products'));
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
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'show_in_pos' => ['boolean'],
            'show_in_menu' => ['boolean'],
            'pricing_type' => ['required', 'in:fixed,sum,discount_percent,discount_amount'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'allow_substitutions' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.category_id' => ['nullable', 'exists:product_categories,id'],
            'items.*.group_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.is_required' => ['boolean'],
            'items.*.allow_variant_selection' => ['boolean'],
            'items.*.price_adjustment' => ['nullable', 'numeric'],
        ]);

        // Create product first
        $productData = [
            'tenant_id' => $tenantId,
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'base_price' => $validated['base_price'],
            'product_type' => Product::TYPE_COMBO,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured', false),
            'show_in_pos' => $request->boolean('show_in_pos', true),
            'show_in_menu' => $request->boolean('show_in_menu', true),
            'track_stock' => false,
        ];

        // Ensure unique slug
        $baseSlug = $productData['slug'];
        $counter = 1;
        while (Product::where('tenant_id', $tenantId)->where('slug', $productData['slug'])->exists()) {
            $productData['slug'] = $baseSlug.'-'.$counter++;
        }

        $product = Product::create($productData);

        // Create combo
        $combo = Combo::create([
            'product_id' => $product->id,
            'pricing_type' => $validated['pricing_type'],
            'discount_value' => $validated['discount_value'] ?? 0,
            'allow_substitutions' => $request->boolean('allow_substitutions', false),
        ]);

        // Create combo items
        foreach ($validated['items'] as $index => $itemData) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'product_id' => $itemData['product_id'] ?? null,
                'category_id' => $itemData['category_id'] ?? null,
                'group_name' => $itemData['group_name'] ?? null,
                'quantity' => $itemData['quantity'],
                'is_required' => $itemData['is_required'] ?? true,
                'allow_variant_selection' => $itemData['allow_variant_selection'] ?? true,
                'price_adjustment' => $itemData['price_adjustment'] ?? 0,
                'sort_order' => $index,
            ]);
        }

        // Calculate cost price based on items
        $this->updateComboCost($product, $combo);

        return redirect()->route('menu.combos.show', $product)
            ->with('success', 'Combo created successfully.');
    }

    public function show(Product $combo): View
    {
        $this->authorizeCombo($combo);
        $combo->load([
            'category',
            'combo.items' => fn ($q) => $q->orderBy('sort_order'),
            'combo.items.product',
            'combo.items.category',
        ]);

        return view('menu.combos.show', compact('combo'));
    }

    public function edit(Product $combo): View
    {
        $this->authorizeCombo($combo);
        $tenantId = $this->getTenantId();

        $combo->load(['combo.items' => fn ($q) => $q->orderBy('sort_order')]);

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('product_type', '!=', Product::TYPE_COMBO)
            ->orderBy('name')
            ->get();

        return view('menu.combos.edit', compact('combo', 'categories', 'products'));
    }

    public function update(Request $request, Product $combo): RedirectResponse
    {
        $this->authorizeCombo($combo);
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'show_in_pos' => ['boolean'],
            'show_in_menu' => ['boolean'],
            'pricing_type' => ['required', 'in:fixed,sum,discount_percent,discount_amount'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'allow_substitutions' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.category_id' => ['nullable', 'exists:product_categories,id'],
            'items.*.group_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.is_required' => ['boolean'],
            'items.*.allow_variant_selection' => ['boolean'],
            'items.*.price_adjustment' => ['nullable', 'numeric'],
        ]);

        // Update product
        $productData = [
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'base_price' => $validated['base_price'],
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured', false),
            'show_in_pos' => $request->boolean('show_in_pos', true),
            'show_in_menu' => $request->boolean('show_in_menu', true),
        ];

        if ($validated['name'] !== $combo->name) {
            $productData['slug'] = Str::slug($validated['name']);
            $baseSlug = $productData['slug'];
            $counter = 1;
            while (Product::where('tenant_id', $tenantId)
                ->where('slug', $productData['slug'])
                ->where('id', '!=', $combo->id)
                ->exists()) {
                $productData['slug'] = $baseSlug.'-'.$counter++;
            }
        }

        $combo->update($productData);

        // Update combo settings
        $combo->combo->update([
            'pricing_type' => $validated['pricing_type'],
            'discount_value' => $validated['discount_value'] ?? 0,
            'allow_substitutions' => $request->boolean('allow_substitutions', false),
        ]);

        // Delete and recreate items
        $combo->combo->items()->delete();

        foreach ($validated['items'] as $index => $itemData) {
            ComboItem::create([
                'combo_id' => $combo->combo->id,
                'product_id' => $itemData['product_id'] ?? null,
                'category_id' => $itemData['category_id'] ?? null,
                'group_name' => $itemData['group_name'] ?? null,
                'quantity' => $itemData['quantity'],
                'is_required' => $itemData['is_required'] ?? true,
                'allow_variant_selection' => $itemData['allow_variant_selection'] ?? true,
                'price_adjustment' => $itemData['price_adjustment'] ?? 0,
                'sort_order' => $index,
            ]);
        }

        // Recalculate cost
        $this->updateComboCost($combo, $combo->combo);

        return redirect()->route('menu.combos.show', $combo)
            ->with('success', 'Combo updated successfully.');
    }

    public function destroy(Product $combo): RedirectResponse
    {
        $this->authorizeCombo($combo);

        $combo->combo->items()->delete();
        $combo->combo->delete();
        $combo->delete();

        return redirect()->route('menu.combos.index')
            ->with('success', 'Combo deleted successfully.');
    }

    private function updateComboCost(Product $product, Combo $combo): void
    {
        $combo->load('items.product');

        $totalCost = $combo->items->sum(function ($item) {
            if ($item->product) {
                return $item->product->cost_price * $item->quantity;
            }

            return 0;
        });

        $product->update(['cost_price' => $totalCost]);
    }

    private function authorizeCombo(Product $combo): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($combo->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }

        if ($combo->product_type !== Product::TYPE_COMBO) {
            abort(404, 'Combo not found.');
        }
    }
}
