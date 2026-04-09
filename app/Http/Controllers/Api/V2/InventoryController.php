<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class InventoryController extends Controller
{
    /**
     * List inventory items
     */
    #[OA\Get(
        path: '/inventory/items',
        summary: 'List inventory items',
        security: [['sanctum' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'low_stock', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of inventory items'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function items(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->success([]);
        }

        $query = InventoryItem::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->with(['unit:id,name,symbol', 'category:id,name']);

        if ($request->has('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Get stock for outlet
        $query->with(['stocks' => function ($q) use ($outlet) {
            $q->where('outlet_id', $outlet->id);
        }]);

        // Filter low stock
        if ($request->boolean('low_stock')) {
            $query->whereHas('stocks', function ($q) use ($outlet) {
                $q->where('outlet_id', $outlet->id)
                    ->whereRaw('quantity <= inventory_items.reorder_point');
            });
        }

        $perPage = min($request->input('per_page', 20), 100);
        $items = $query->orderBy('name')->paginate($perPage);

        $data = $items->map(function ($item) {
            $stock = $item->stocks->first();

            return [
                'id' => $item->id,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'name' => $item->name,
                'unit_name' => $item->unit?->name,
                'cost_price' => (float) $item->cost_price,
                'stock_quantity' => $stock ? (float) $stock->quantity : 0,
                'min_stock' => (float) $item->min_stock,
                'max_stock' => $item->max_stock ? (float) $item->max_stock : null,
                'reorder_point' => (float) $item->reorder_point,
                'is_low_stock' => $stock ? $stock->quantity <= $item->reorder_point : true,
                'is_active' => $item->is_active,
            ];
        });

        return $this->successWithPagination($data, $this->paginationMeta($items));
    }

    /**
     * Get inventory item detail
     */
    #[OA\Get(
        path: '/inventory/items/{item}',
        summary: 'Get inventory item detail',
        security: [['sanctum' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'item', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inventory item detail'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    public function show(Request $request, string $item): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        $inventoryItem = InventoryItem::where('id', $item)
            ->where('tenant_id', $this->tenantId())
            ->with(['unit:id,name,symbol', 'category:id,name'])
            ->first();

        if (! $inventoryItem) {
            return $this->notFound('Item not found');
        }

        $stock = $outlet
            ? InventoryStock::where('inventory_item_id', $inventoryItem->id)
                ->where('outlet_id', $outlet->id)
                ->first()
            : null;

        return $this->success([
            'id' => $inventoryItem->id,
            'sku' => $inventoryItem->sku,
            'barcode' => $inventoryItem->barcode,
            'name' => $inventoryItem->name,
            'description' => $inventoryItem->description,
            'unit_id' => $inventoryItem->unit_id,
            'unit_name' => $inventoryItem->unit?->name,
            'cost_price' => (float) $inventoryItem->cost_price,
            'min_stock' => (float) $inventoryItem->min_stock,
            'max_stock' => $inventoryItem->max_stock ? (float) $inventoryItem->max_stock : null,
            'reorder_point' => (float) $inventoryItem->reorder_point,
            'reorder_qty' => (float) $inventoryItem->reorder_qty,
            'track_batches' => $inventoryItem->track_batches,
            'is_active' => $inventoryItem->is_active,
            'stock' => $stock ? [
                'quantity' => (float) $stock->quantity,
                'reserved_qty' => (float) $stock->reserved_qty,
                'available_qty' => (float) $stock->getAvailableQuantity(),
                'avg_cost' => (float) $stock->avg_cost,
                'stock_value' => (float) $stock->getStockValue(),
                'is_low_stock' => $stock->quantity <= $inventoryItem->reorder_point,
            ] : [
                'quantity' => 0,
                'reserved_qty' => 0,
                'available_qty' => 0,
                'avg_cost' => (float) $inventoryItem->cost_price,
                'stock_value' => 0,
                'is_low_stock' => true,
            ],
        ]);
    }

    /**
     * Get stock levels for outlet
     */
    #[OA\Get(
        path: '/inventory/stock',
        summary: 'Get stock levels for outlet',
        security: [['sanctum' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stock levels'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function stock(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->success([]);
        }

        $perPage = min($request->input('per_page', 50), 100);

        $stocks = InventoryStock::where('outlet_id', $outlet->id)
            ->with(['inventoryItem' => function ($q) {
                $q->where('tenant_id', $this->tenantId())
                    ->with('unit:id,name,symbol');
            }])
            ->whereHas('inventoryItem', function ($q) {
                $q->where('tenant_id', $this->tenantId())
                    ->where('is_active', true);
            })
            ->paginate($perPage);

        $data = $stocks->map(function ($stock) {
            $item = $stock->inventoryItem;

            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_sku' => $item->sku,
                'unit_name' => $item->unit?->name,
                'quantity' => (float) $stock->quantity,
                'reserved_qty' => (float) $stock->reserved_qty,
                'available_qty' => (float) $stock->getAvailableQuantity(),
                'avg_cost' => (float) $stock->avg_cost,
                'stock_value' => (float) $stock->getStockValue(),
                'is_low_stock' => $stock->quantity <= $item->reorder_point,
                'reorder_point' => (float) $item->reorder_point,
            ];
        });

        return $this->successWithPagination($data, $this->paginationMeta($stocks));
    }

    /**
     * Check product stock availability
     */
    #[OA\Get(
        path: '/inventory/products/{product}/stock',
        summary: 'Check product stock availability',
        security: [['sanctum' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'quantity', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product stock info'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function productStock(Request $request, string $product): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        $productModel = Product::where('id', $product)
            ->where('tenant_id', $this->tenantId())
            ->with('recipe.items.inventoryItem')
            ->first();

        if (! $productModel) {
            return $this->notFound('Product not found');
        }

        $quantity = $request->input('quantity', 1);
        $hasRecipe = $productModel->recipe !== null;
        $stockAvailable = true;
        $canSellQuantity = PHP_INT_MAX;
        $ingredients = [];

        if ($hasRecipe && $outlet) {
            foreach ($productModel->recipe->items as $recipeItem) {
                $inventoryItem = $recipeItem->inventoryItem;
                $requiredQty = $recipeItem->quantity * $quantity;

                $stock = InventoryStock::where('inventory_item_id', $inventoryItem->id)
                    ->where('outlet_id', $outlet->id)
                    ->first();

                $availableQty = $stock ? $stock->getAvailableQuantity() : 0;
                $isSufficient = $availableQty >= $requiredQty;

                if (! $isSufficient) {
                    $stockAvailable = false;
                }

                // Calculate max sellable quantity
                if ($recipeItem->quantity > 0) {
                    $maxFromThis = (int) floor($availableQty / $recipeItem->quantity);
                    $canSellQuantity = min($canSellQuantity, $maxFromThis);
                }

                $ingredients[] = [
                    'item_id' => $inventoryItem->id,
                    'item_name' => $inventoryItem->name,
                    'required_qty' => $requiredQty,
                    'available_qty' => $availableQty,
                    'is_sufficient' => $isSufficient,
                ];
            }
        }

        if ($canSellQuantity === PHP_INT_MAX) {
            $canSellQuantity = null; // No limit
        }

        return $this->success([
            'product_id' => $productModel->id,
            'product_name' => $productModel->name,
            'track_stock' => $productModel->track_stock,
            'has_recipe' => $hasRecipe,
            'stock_available' => $stockAvailable,
            'can_sell_quantity' => $canSellQuantity,
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * Create stock adjustment
     */
    #[OA\Post(
        path: '/inventory/adjustments',
        summary: 'Create stock adjustment',
        security: [['sanctum' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['inventory_item_id', 'adjustment_type', 'quantity'],
                            properties: [
                                new OA\Property(property: 'inventory_item_id', type: 'string'),
                                new OA\Property(property: 'adjustment_type', type: 'string', enum: ['add', 'subtract', 'set']),
                                new OA\Property(property: 'quantity', type: 'number'),
                                new OA\Property(property: 'reason', type: 'string'),
                            ]
                        )
                    ),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Adjustment created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function createAdjustment(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|uuid|exists:inventory_items,id',
            'items.*.adjustment_type' => 'required|in:add,subtract,set',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->error('No outlet selected', 422);
        }

        $adjustmentId = (string) Str::uuid();
        $results = [];

        DB::beginTransaction();
        try {
            foreach ($request->input('items') as $itemData) {
                $inventoryItem = InventoryItem::where('id', $itemData['inventory_item_id'])
                    ->where('tenant_id', $this->tenantId())
                    ->first();

                if (! $inventoryItem) {
                    continue;
                }

                $stock = InventoryStock::firstOrCreate(
                    [
                        'outlet_id' => $outlet->id,
                        'inventory_item_id' => $inventoryItem->id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'avg_cost' => $inventoryItem->cost_price,
                        'last_cost' => $inventoryItem->cost_price,
                    ]
                );

                $previousQty = $stock->quantity;
                $adjustmentQty = $itemData['quantity'];
                $type = $itemData['adjustment_type'];

                switch ($type) {
                    case 'add':
                        $newQty = $previousQty + $adjustmentQty;
                        break;
                    case 'subtract':
                        if ($adjustmentQty > $previousQty) {
                            DB::rollBack();

                            return $this->validationError([
                                'items' => ["Insufficient stock for {$inventoryItem->name}. Available: {$previousQty}"],
                            ]);
                        }
                        $newQty = $previousQty - $adjustmentQty;
                        break;
                    case 'set':
                        $newQty = $adjustmentQty;
                        break;
                }

                $stock->update(['quantity' => $newQty]);

                // Create stock movement record if table exists
                if (class_exists(StockMovement::class)) {
                    StockMovement::create([
                        'outlet_id' => $outlet->id,
                        'inventory_item_id' => $inventoryItem->id,
                        'type' => StockMovement::TYPE_ADJUSTMENT,
                        'quantity' => $type === 'subtract' ? -$adjustmentQty : $adjustmentQty,
                        'stock_before' => $previousQty,
                        'stock_after' => $newQty,
                        'reference_type' => 'adjustment',
                        'reference_id' => $adjustmentId,
                        'notes' => $itemData['reason'] ?? 'Stock adjustment',
                        'created_by' => $this->user()->id,
                    ]);
                }

                $results[] = [
                    'item_id' => $inventoryItem->id,
                    'item_name' => $inventoryItem->name,
                    'previous_qty' => (float) $previousQty,
                    'adjustment_qty' => (float) ($type === 'subtract' ? -$adjustmentQty : $adjustmentQty),
                    'new_qty' => (float) $newQty,
                ];
            }

            DB::commit();

            return $this->success([
                'adjustment_id' => $adjustmentId,
                'items' => $results,
            ], 'Stock adjustment created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to create adjustment: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get low stock alerts
     */
    #[OA\Get(
        path: '/inventory/alerts/low-stock',
        summary: 'Get low stock alerts',
        security: [['sanctum' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Low stock items'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function lowStockAlerts(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->success([]);
        }

        $lowStockItems = InventoryStock::where('outlet_id', $outlet->id)
            ->with(['inventoryItem' => function ($q) {
                $q->where('tenant_id', $this->tenantId())
                    ->where('is_active', true)
                    ->with('unit:id,name');
            }])
            ->whereHas('inventoryItem', function ($q) {
                $q->where('tenant_id', $this->tenantId())
                    ->where('is_active', true);
            })
            ->get()
            ->filter(function ($stock) {
                return $stock->quantity <= $stock->inventoryItem->reorder_point;
            })
            ->map(function ($stock) {
                $item = $stock->inventoryItem;

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_sku' => $item->sku,
                    'current_qty' => (float) $stock->quantity,
                    'reorder_point' => (float) $item->reorder_point,
                    'reorder_qty' => (float) $item->reorder_qty,
                    'unit_name' => $item->unit?->name,
                ];
            })
            ->values();

        return $this->success($lowStockItems);
    }

    /**
     * Get stock history for an item
     */
    #[OA\Get(
        path: '/inventory/items/{item}/history',
        summary: 'Get stock movement history',
        security: [['sanctum' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'item', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stock history'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    public function history(Request $request, string $item): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        $inventoryItem = InventoryItem::where('id', $item)
            ->where('tenant_id', $this->tenantId())
            ->first();

        if (! $inventoryItem) {
            return $this->notFound('Item not found');
        }

        // Check if StockMovement exists
        if (! class_exists(StockMovement::class)) {
            return $this->successWithPagination([], [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 20,
                'total' => 0,
                'from' => null,
                'to' => null,
            ]);
        }

        $perPage = min($request->input('per_page', 20), 100);

        $movements = StockMovement::where('inventory_item_id', $inventoryItem->id)
            ->where('outlet_id', $outlet?->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $data = $movements->map(fn ($movement) => [
            'id' => $movement->id,
            'type' => $movement->type,
            'quantity' => (float) $movement->quantity,
            'balance_before' => (float) $movement->stock_before,
            'balance_after' => (float) $movement->stock_after,
            'reference' => $movement->reference_id,
            'reason' => $movement->notes,
            'user_name' => $movement->createdBy?->name,
            'created_at' => $movement->created_at?->toIso8601String(),
        ]);

        return $this->successWithPagination($data, $this->paginationMeta($movements));
    }
}
