<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/products',
        summary: 'List products for POS',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['single', 'variant', 'combo'])),
            new OA\Parameter(name: 'featured', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product list with pagination'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    /**
     * List products for POS
     *
     * GET /api/v1/products
     *
     * Schema products:
     * - id (uuid)
     * - tenant_id (uuid)
     * - category_id (uuid)
     * - recipe_id (uuid, nullable)
     * - sku (string 50)
     * - barcode (string 50, nullable)
     * - name (string 200)
     * - slug (string 220)
     * - description (text, nullable)
     * - image (string, nullable)
     * - base_price (decimal)
     * - cost_price (decimal, nullable)
     * - product_type (string: single, variant, combo)
     * - track_stock (boolean)
     * - inventory_item_id (uuid, nullable)
     * - is_active (boolean)
     * - is_featured (boolean)
     * - show_in_pos (boolean)
     * - show_in_menu (boolean)
     * - allow_notes (boolean)
     * - prep_time_minutes (int, nullable)
     * - sort_order (int)
     * - tags (json, nullable)
     * - allergens (json, nullable)
     * - nutritional_info (json, nullable)
     * - created_at, updated_at, deleted_at
     */
    public function index(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        $query = Product::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where('show_in_pos', true)
            ->with([
                'category:id,name,slug,color',
                'variants' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                },
            ]);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by product type
        if ($request->has('type')) {
            $query->where('product_type', $request->type);
        }

        // Filter featured only
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Filter products available at outlet
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

    #[OA\Get(
        path: '/products/search',
        summary: 'Search products by name, SKU, or barcode',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string', minLength: 1)),
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Search results (max 20)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $outletId = $this->currentOutletId($request);
        $keyword = $request->q;

        $query = Product::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where('show_in_pos', true)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%")
                    ->orWhere('barcode', $keyword);
            })
            ->with([
                'category:id,name,slug,color',
                'variants' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                },
            ]);

        if ($outletId) {
            $query->with(['productOutlets' => function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            }]);
        }

        $products = $query->orderBy('name')
            ->limit(20)
            ->get();

        $data = $products->map(fn ($product) => $this->formatProduct($product, $outletId));

        return $this->success($data);
    }

    #[OA\Get(
        path: '/products/barcode/{barcode}',
        summary: 'Get product by barcode (also checks variant barcodes)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'barcode', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product detail (includes matched_variant_id if barcode is variant)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function byBarcode(Request $request, string $barcode): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        $product = Product::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where('show_in_pos', true)
            ->where('barcode', $barcode)
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
            ])
            ->first();

        if (! $product) {
            // Also check product variants barcode
            $variant = ProductVariant::query()
                ->where('barcode', $barcode)
                ->where('is_active', true)
                ->with(['product' => function ($q) {
                    $q->where('is_active', true)->where('show_in_pos', true);
                }])
                ->first();

            if ($variant && $variant->product && $variant->product->tenant_id === $this->tenantId()) {
                $product = $variant->product;
                $product->load([
                    'category:id,name,slug,color',
                    'variants' => function ($q) {
                        $q->where('is_active', true)->orderBy('sort_order');
                    },
                ]);

                if ($outletId) {
                    $product->load(['productOutlets' => function ($q) use ($outletId) {
                        $q->where('outlet_id', $outletId);
                    }]);
                }

                // Mark the matched variant
                $data = $this->formatProductDetail($product, $outletId);
                $data['matched_variant_id'] = $variant->id;

                return $this->success($data);
            }

            return $this->notFound('Product not found');
        }

        if ($outletId) {
            $product->load(['productOutlets' => function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            }]);
        }

        return $this->success($this->formatProductDetail($product, $outletId));
    }

    #[OA\Get(
        path: '/products/{product}',
        summary: 'Get product detail with variants, modifiers, combo info',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product detail with variants/modifiers'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(Request $request, Product $product): JsonResponse
    {
        if ($product->tenant_id !== $this->tenantId()) {
            return $this->notFound('Product not found');
        }

        $outletId = $this->currentOutletId($request);

        $product->load([
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
            'combo.groups.items.product:id,name,base_price,image',
        ]);

        if ($outletId) {
            $product->load(['productOutlets' => function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            }]);
        }

        return $this->success($this->formatProductDetail($product, $outletId));
    }

    /**
     * Format product data for list
     */
    private function formatProduct(Product $product, ?string $outletId = null): array
    {
        $price = $product->base_price;
        $isAvailable = true;

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
            'category_color' => $product->category?->color,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'image' => $product->image,
            'base_price' => (float) $product->base_price,
            'price' => (float) $price,
            'product_type' => $product->product_type,
            'track_stock' => (bool) $product->track_stock,
            'is_available' => $isAvailable,
            'is_featured' => (bool) $product->is_featured,
            'allow_notes' => (bool) $product->allow_notes,
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];

        // Include basic variant info for variant type
        if ($product->product_type === 'variant' && $product->relationLoaded('variants')) {
            $data['variants'] = $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'price' => (float) $v->price,
            ])->toArray();
        }

        return $data;
    }

    /**
     * Format product detail (full info)
     */
    private function formatProductDetail(Product $product, ?string $outletId = null): array
    {
        $price = $product->base_price;
        $isAvailable = true;

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
            'category_color' => $product->category?->color,
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
            'tags' => $product->tags ?? [],
            'allergens' => $product->allergens ?? [],
            'nutritional_info' => $product->nutritional_info,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];

        // Include variants for variant type
        if ($product->product_type === 'variant' && $product->relationLoaded('variants')) {
            $data['variants'] = $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'name' => $v->name,
                'price' => (float) $v->price,
                'cost_price' => (float) $v->cost_price,
                'image' => $v->image,
                'is_active' => (bool) $v->is_active,
                'sort_order' => (int) $v->sort_order,
            ])->toArray();
        }

        // Include variant groups
        if ($product->relationLoaded('variantGroups') && $product->variantGroups->isNotEmpty()) {
            $data['variant_groups'] = $product->variantGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'is_required' => (bool) $group->pivot->is_required,
                'sort_order' => (int) $group->pivot->sort_order,
                'variants' => $group->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'price_adjustment' => (float) $v->price_adjustment,
                    'is_default' => (bool) $v->is_default,
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
                'sort_order' => (int) $group->pivot->sort_order,
                'modifiers' => $group->modifiers->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'price' => (float) $m->price,
                    'is_default' => (bool) $m->is_default,
                ])->toArray(),
            ])->toArray();
        }

        // Include combo configuration
        if ($product->product_type === 'combo' && $product->relationLoaded('combo') && $product->combo) {
            $data['combo'] = [
                'id' => $product->combo->id,
                'groups' => $product->combo->groups->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'min_selections' => (int) $group->min_selections,
                    'max_selections' => (int) $group->max_selections,
                    'items' => $group->items->map(fn ($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'product_image' => $item->product?->image,
                        'price_adjustment' => (float) $item->price_adjustment,
                        'is_default' => (bool) $item->is_default,
                    ])->toArray(),
                ])->toArray(),
            ];
        }

        return $data;
    }
}
