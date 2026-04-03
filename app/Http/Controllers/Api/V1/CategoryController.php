<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: '/categories',
        summary: 'List categories for POS',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'parent_id', in: 'query', description: 'Filter by parent (null = root categories)', schema: new OA\Schema(type: 'string', format: 'uuid', nullable: true)),
            new OA\Parameter(name: 'with_children', in: 'query', description: 'Include child categories', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category list with products_count'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    /**
     * List categories for POS
     *
     * GET /api/v1/categories
     *
     * Schema product_categories:
     * - id (uuid)
     * - tenant_id (uuid)
     * - parent_id (uuid, nullable)
     * - code (string 50, nullable)
     * - name (string 100)
     * - slug (string 120)
     * - description (text, nullable)
     * - image (string, nullable)
     * - color (string 20, nullable)
     * - icon (string 50, nullable)
     * - sort_order (int)
     * - is_active (boolean)
     * - show_in_pos (boolean)
     * - show_in_menu (boolean)
     * - created_at, updated_at
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductCategory::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where('show_in_pos', true)
            ->withCount(['products' => function ($query) {
                $query->where('is_active', true)->where('show_in_pos', true);
            }]);

        // Filter by parent (null = root categories)
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            // Default to root categories
            $query->whereNull('parent_id');
        }

        // Include children if requested
        if ($request->boolean('with_children')) {
            $query->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->where('show_in_pos', true)
                    ->withCount(['products' => function ($query) {
                        $query->where('is_active', true)->where('show_in_pos', true);
                    }])
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }]);
        }

        $categories = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $data = $categories->map(fn ($category) => $this->formatCategory($category, $request->boolean('with_children')));

        return $this->success($data);
    }

    #[OA\Get(
        path: '/categories/{category}',
        summary: 'Get category detail with children',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category detail with children'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function show(ProductCategory $category): JsonResponse
    {
        if ($category->tenant_id !== $this->tenantId()) {
            return $this->notFound('Category not found');
        }

        $category->loadCount(['products' => function ($query) {
            $query->where('is_active', true)->where('show_in_pos', true);
        }]);

        $category->load(['children' => function ($q) {
            $q->where('is_active', true)
                ->where('show_in_pos', true)
                ->withCount(['products' => function ($query) {
                    $query->where('is_active', true)->where('show_in_pos', true);
                }])
                ->orderBy('sort_order')
                ->orderBy('name');
        }]);

        return $this->success($this->formatCategory($category, withChildren: true));
    }

    #[OA\Get(
        path: '/categories/{category}/products',
        summary: 'Get products in category',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Products in category with pagination'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function products(Request $request, ProductCategory $category): JsonResponse
    {
        if ($category->tenant_id !== $this->tenantId()) {
            return $this->notFound('Category not found');
        }

        $outletId = $this->currentOutletId($request);

        $query = $category->products()
            ->where('is_active', true)
            ->where('show_in_pos', true)
            ->with([
                'category:id,name,slug,color',
                'variants' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                },
                'variantGroups.variants' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                },
                'modifierGroups.modifiers' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                },
            ]);

        // Filter available products at outlet if outlet selected
        if ($outletId) {
            $query->with(['productOutlets' => function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            }]);
        }

        $perPage = min($request->get('per_page', 50), 100);

        $products = $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);

        $data = $products->map(fn ($product) => $this->formatProduct($product, $outletId));

        return $this->successWithPagination($data, $this->paginationMeta($products));
    }

    /**
     * Format category data
     */
    private function formatCategory(ProductCategory $category, bool $withChildren = false): array
    {
        $data = [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'code' => $category->code,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image,
            'color' => $category->color,
            'icon' => $category->icon,
            'sort_order' => (int) $category->sort_order,
            'products_count' => (int) ($category->products_count ?? 0),
            'is_active' => (bool) $category->is_active,
            'show_in_pos' => (bool) $category->show_in_pos,
            'created_at' => $category->created_at?->toIso8601String(),
            'updated_at' => $category->updated_at?->toIso8601String(),
        ];

        if ($withChildren && $category->relationLoaded('children')) {
            $data['children'] = $category->children->map(fn ($child) => $this->formatCategory($child, false))->toArray();
        }

        return $data;
    }

    /**
     * Format product data for list
     */
    private function formatProduct($product, ?string $outletId = null): array
    {
        $price = $product->base_price;
        $isAvailable = true;

        // Check outlet specific price and availability
        if ($outletId && $product->relationLoaded('productOutlets') && $product->productOutlets->isNotEmpty()) {
            $productOutlet = $product->productOutlets->first();
            if ($productOutlet->custom_price !== null) {
                $price = $productOutlet->custom_price;
            }
            $isAvailable = (bool) $productOutlet->is_available;
        }

        $data = [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'image' => $product->image,
            'base_price' => (float) $product->base_price,
            'price' => (float) $price,
            'cost_price' => (float) $product->cost_price,
            'product_type' => $product->product_type,
            'track_stock' => (bool) $product->track_stock,
            'is_available' => $isAvailable,
            'is_featured' => (bool) $product->is_featured,
            'allow_notes' => (bool) $product->allow_notes,
            'prep_time_minutes' => $product->prep_time_minutes,
            'sort_order' => (int) $product->sort_order,
            'tags' => $product->tags ?? [],
            'allergens' => $product->allergens ?? [],
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];

        // Include variants for variant type
        if ($product->product_type === 'variant' && $product->relationLoaded('variants')) {
            $data['variants'] = $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'name' => $v->name,
                'price' => (float) $v->price,
                'cost_price' => (float) $v->cost_price,
                'is_active' => (bool) $v->is_active,
            ])->toArray();
        }

        // Include variant groups (for POS selection)
        if ($product->relationLoaded('variantGroups') && $product->variantGroups->isNotEmpty()) {
            $data['variant_groups'] = $product->variantGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'is_required' => (bool) $group->pivot->is_required,
                'variants' => $group->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'price_adjustment' => (float) $v->price_adjustment,
                ])->toArray(),
            ])->toArray();
        }

        // Include modifier groups
        if ($product->relationLoaded('modifierGroups') && $product->modifierGroups->isNotEmpty()) {
            $data['modifier_groups'] = $product->modifierGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'is_required' => (bool) $group->pivot->is_required,
                'min_selections' => (int) $group->pivot->min_selections,
                'max_selections' => (int) $group->pivot->max_selections,
                'modifiers' => $group->modifiers->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'price' => (float) $m->price,
                ])->toArray(),
            ])->toArray();
        }

        return $data;
    }
}
