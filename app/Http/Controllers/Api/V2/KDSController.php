<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class KDSController extends Controller
{
    // ==================== KDS Auth ====================

    #[OA\Get(
        path: '/kds/auth/outlets',
        summary: 'List outlets for KDS login',
        description: 'Get list of outlets available for KDS login (no auth required)',
        tags: ['KDS Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Outlets list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'name', type: 'string'),
                            ]
                        )),
                    ]
                )
            ),
        ]
    )]
    public function outlets(): JsonResponse
    {
        $outlets = Outlet::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return $this->success($outlets->map(fn ($outlet) => [
            'id' => $outlet->id,
            'name' => $outlet->name,
        ]));
    }

    #[OA\Post(
        path: '/kds/auth/login',
        summary: 'KDS PIN login',
        description: 'Login to KDS using staff PIN',
        tags: ['KDS Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['outlet_id', 'pin'],
                properties: [
                    new OA\Property(property: 'outlet_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'pin', type: 'string', minLength: 4, maxLength: 6, example: '1234'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'token', type: 'string'),
                            new OA\Property(property: 'user', type: 'object'),
                            new OA\Property(property: 'outlet', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid PIN'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'outlet_id' => 'required|uuid|exists:outlets,id',
            'pin' => 'required|string|min:4|max:6',
        ]);

        $outlet = Outlet::find($validated['outlet_id']);

        // Find user with this PIN in the outlet's tenant
        $userPin = UserPin::where('is_active', true)
            ->whereHas('user', function ($query) use ($outlet) {
                $query->where('tenant_id', $outlet->tenant_id)
                    ->where('is_active', true);
            })
            ->get()
            ->first(function ($pin) use ($validated) {
                return Hash::check($validated['pin'], $pin->pin_hash);
            });

        if (! $userPin) {
            return $this->error('Invalid PIN', 401);
        }

        $user = $userPin->user;

        // Create token for KDS access
        $token = $user->createToken('kds-token', ['kds'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'outlet' => [
                'id' => $outlet->id,
                'name' => $outlet->name,
            ],
        ]);
    }

    // ==================== Kitchen Orders ====================

    #[OA\Get(
        path: '/kds/orders',
        summary: 'List kitchen orders',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'station_id', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kitchen orders list'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        $query = KitchenOrder::forOutlet($outlet->id)
            ->with(['items', 'station', 'table'])
            ->orderByRaw("CASE priority WHEN 'vip' THEN 1 WHEN 'rush' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'asc');

        if ($request->has('status')) {
            $query->withStatus($request->status);
        } else {
            // Default: show active orders
            $query->active();
        }

        if ($request->has('station_id')) {
            $query->forStation($request->station_id);
        }

        $orders = $query->get();

        return $this->success($orders->map(fn ($order) => $this->formatOrder($order)));
    }

    #[OA\Get(
        path: '/kds/orders/{id}',
        summary: 'Get kitchen order detail',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kitchen order detail'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function show(Request $request, KitchenOrder $order): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id) {
            return $this->notFound('Order not found');
        }

        $order->load(['items', 'station', 'table', 'transaction.customer']);

        return $this->success($this->formatOrderDetail($order));
    }

    // ==================== Order Status Updates ====================

    #[OA\Post(
        path: '/kds/orders/{id}/start',
        summary: 'Start preparing order',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order started'),
            new OA\Response(response: 422, description: 'Cannot start order'),
        ]
    )]
    public function start(Request $request, KitchenOrder $order): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id) {
            return $this->notFound('Order not found');
        }

        if (! $order->canStart()) {
            return $this->error('Cannot start order with status: '.$order->status, 422);
        }

        $order->start();
        $order->refresh();

        return $this->success($this->formatOrder($order));
    }

    #[OA\Post(
        path: '/kds/orders/{id}/ready',
        summary: 'Mark order as ready',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order marked ready'),
            new OA\Response(response: 422, description: 'Cannot mark order ready'),
        ]
    )]
    public function ready(Request $request, KitchenOrder $order): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id) {
            return $this->notFound('Order not found');
        }

        if (! $order->canMarkReady()) {
            return $this->error('Cannot mark order ready with status: '.$order->status, 422);
        }

        $order->markReady();
        $order->refresh();

        return $this->success($this->formatOrder($order));
    }

    #[OA\Post(
        path: '/kds/orders/{id}/served',
        summary: 'Mark order as served',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order marked served'),
            new OA\Response(response: 422, description: 'Cannot mark order served'),
        ]
    )]
    public function served(Request $request, KitchenOrder $order): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id) {
            return $this->notFound('Order not found');
        }

        if (! $order->canMarkServed()) {
            return $this->error('Cannot mark order served with status: '.$order->status, 422);
        }

        $order->markServed();
        $order->refresh();

        return $this->success($this->formatOrder($order));
    }

    #[OA\Post(
        path: '/kds/orders/{id}/cancel',
        summary: 'Cancel kitchen order',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Order cancelled'),
            new OA\Response(response: 422, description: 'Cannot cancel order'),
        ]
    )]
    public function cancel(Request $request, KitchenOrder $order): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id) {
            return $this->notFound('Order not found');
        }

        if (! $order->canCancel()) {
            return $this->error('Cannot cancel order with status: '.$order->status, 422);
        }

        $order->cancel($request->input('reason'));
        $order->refresh();

        return $this->success($this->formatOrder($order));
    }

    #[OA\Post(
        path: '/kds/orders/{id}/recall',
        summary: 'Recall served order',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order recalled'),
            new OA\Response(response: 422, description: 'Cannot recall order'),
        ]
    )]
    public function recall(Request $request, KitchenOrder $order): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id) {
            return $this->notFound('Order not found');
        }

        if (! $order->canRecall()) {
            return $this->error('Cannot recall order with status: '.$order->status, 422);
        }

        $order->recall();
        $order->refresh();

        return $this->success($this->formatOrder($order));
    }

    #[OA\Post(
        path: '/kds/orders/{id}/bump',
        summary: 'Bump order to next status',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order bumped'),
        ]
    )]
    public function bump(Request $request, KitchenOrder $order): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id) {
            return $this->notFound('Order not found');
        }

        $order->bump();
        $order->refresh();

        return $this->success($this->formatOrder($order));
    }

    #[OA\Post(
        path: '/kds/orders/{id}/priority',
        summary: 'Set order priority',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['priority'],
                properties: [
                    new OA\Property(property: 'priority', type: 'string', enum: ['normal', 'rush', 'vip']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Priority updated'),
        ]
    )]
    public function priority(Request $request, KitchenOrder $order): JsonResponse
    {
        $request->validate([
            'priority' => 'required|in:normal,rush,vip',
        ]);

        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id) {
            return $this->notFound('Order not found');
        }

        $order->setPriority($request->input('priority'));
        $order->refresh();

        return $this->success($this->formatOrder($order));
    }

    // ==================== Item Status Updates ====================

    #[OA\Post(
        path: '/kds/orders/{orderId}/items/{itemId}/start',
        summary: 'Start preparing item',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item started'),
        ]
    )]
    public function startItem(Request $request, KitchenOrder $order, KitchenOrderItem $item): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id || $item->kitchen_order_id !== $order->id) {
            return $this->notFound('Item not found');
        }

        if (! $item->canStart()) {
            return $this->error('Cannot start item with status: '.$item->status, 422);
        }

        $item->start();
        $item->refresh();

        return $this->success($this->formatItem($item));
    }

    #[OA\Post(
        path: '/kds/orders/{orderId}/items/{itemId}/ready',
        summary: 'Mark item as ready',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item marked ready'),
        ]
    )]
    public function readyItem(Request $request, KitchenOrder $order, KitchenOrderItem $item): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($order->outlet_id !== $outlet->id || $item->kitchen_order_id !== $order->id) {
            return $this->notFound('Item not found');
        }

        if (! $item->canMarkReady()) {
            return $this->error('Cannot mark item ready with status: '.$item->status, 422);
        }

        $item->markReady();
        $item->refresh();

        return $this->success($this->formatItem($item));
    }

    // ==================== Kitchen Stations ====================

    #[OA\Get(
        path: '/kds/stations',
        summary: 'List kitchen stations',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stations list'),
        ]
    )]
    public function stations(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        $stations = KitchenStation::forOutlet($outlet->id)
            ->active()
            ->orderBy('sort_order')
            ->get();

        return $this->success($stations->map(fn ($station) => [
            'id' => $station->id,
            'name' => $station->name,
            'code' => $station->code,
            'color' => $station->color,
            'description' => $station->description,
            'is_active' => $station->is_active,
            'pending_orders_count' => $station->pending_orders_count,
        ]));
    }

    #[OA\Post(
        path: '/kds/stations',
        summary: 'Create kitchen station',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'color', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Station created'),
        ]
    )]
    public function storeStation(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ]);

        $outlet = $this->currentOutlet($request);
        $tenant = $this->user()->tenant;

        $station = KitchenStation::create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'color' => $request->input('color', '#3B82F6'),
            'description' => $request->input('description'),
        ]);

        return $this->created([
            'id' => $station->id,
            'name' => $station->name,
            'code' => $station->code,
            'color' => $station->color,
            'description' => $station->description,
            'is_active' => $station->is_active,
        ]);
    }

    #[OA\Put(
        path: '/kds/stations/{id}',
        summary: 'Update kitchen station',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Station updated'),
        ]
    )]
    public function updateStation(Request $request, KitchenStation $station): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($station->outlet_id !== $outlet->id) {
            return $this->notFound('Station not found');
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $station->update($request->only(['name', 'code', 'color', 'description', 'is_active']));

        return $this->success([
            'id' => $station->id,
            'name' => $station->name,
            'code' => $station->code,
            'color' => $station->color,
            'description' => $station->description,
            'is_active' => $station->is_active,
        ]);
    }

    #[OA\Delete(
        path: '/kds/stations/{id}',
        summary: 'Delete kitchen station',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Station deleted'),
        ]
    )]
    public function destroyStation(Request $request, KitchenStation $station): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if ($station->outlet_id !== $outlet->id) {
            return $this->notFound('Station not found');
        }

        $station->delete();

        return $this->success(['message' => 'Station deleted']);
    }

    // ==================== KDS Stats ====================

    #[OA\Get(
        path: '/kds/stats',
        summary: 'Get KDS statistics',
        security: [['sanctum' => []]],
        tags: ['KDS'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'KDS stats'),
        ]
    )]
    public function stats(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        $pendingCount = KitchenOrder::forOutlet($outlet->id)
            ->withStatus(KitchenOrder::STATUS_PENDING)
            ->count();

        $preparingCount = KitchenOrder::forOutlet($outlet->id)
            ->withStatus(KitchenOrder::STATUS_PREPARING)
            ->count();

        $readyCount = KitchenOrder::forOutlet($outlet->id)
            ->withStatus(KitchenOrder::STATUS_READY)
            ->count();

        $servedToday = KitchenOrder::forOutlet($outlet->id)
            ->withStatus(KitchenOrder::STATUS_SERVED)
            ->today()
            ->count();

        // Average preparation time (in minutes) - use database-agnostic approach
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            $avgPrepTime = KitchenOrder::forOutlet($outlet->id)
                ->withStatus(KitchenOrder::STATUS_SERVED)
                ->today()
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->selectRaw('AVG((julianday(completed_at) - julianday(started_at)) * 1440) as avg_time')
                ->value('avg_time') ?? 0;
        } else {
            $avgPrepTime = KitchenOrder::forOutlet($outlet->id)
                ->withStatus(KitchenOrder::STATUS_SERVED)
                ->today()
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_time')
                ->value('avg_time') ?? 0;
        }

        // Orders by hour - use database-agnostic approach
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            $ordersByHour = KitchenOrder::forOutlet($outlet->id)
                ->today()
                ->selectRaw("strftime('%H', created_at) as hour, COUNT(*) as count")
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('count', 'hour')
                ->toArray();
        } else {
            $ordersByHour = KitchenOrder::forOutlet($outlet->id)
                ->today()
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('count', 'hour')
                ->toArray();
        }

        return $this->success([
            'pending_count' => $pendingCount,
            'preparing_count' => $preparingCount,
            'ready_count' => $readyCount,
            'served_today' => $servedToday,
            'avg_preparation_time' => round($avgPrepTime, 1),
            'orders_by_hour' => $ordersByHour,
        ]);
    }

    // ==================== Helpers ====================

    private function formatOrder(KitchenOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => $order->order_type,
            'table_name' => $order->table_name ?? $order->table?->name,
            'customer_name' => $order->customer_name,
            'status' => $order->status,
            'priority' => $order->priority,
            'notes' => $order->notes,
            'items' => $order->items->map(fn ($item) => $this->formatItem($item)),
            'created_at' => $order->created_at->toIso8601String(),
            'started_at' => $order->started_at?->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
            'elapsed_time' => $order->elapsed_time,
        ];
    }

    private function formatOrderDetail(KitchenOrder $order): array
    {
        $formatted = $this->formatOrder($order);
        $formatted['cancel_reason'] = $order->cancel_reason;
        $formatted['station'] = $order->station ? [
            'id' => $order->station->id,
            'name' => $order->station->name,
            'color' => $order->station->color,
        ] : null;

        return $formatted;
    }

    private function formatItem(KitchenOrderItem $item): array
    {
        return [
            'id' => $item->id,
            'item_name' => $item->item_name,
            'quantity' => (float) $item->quantity,
            'modifiers' => $item->modifiers,
            'modifiers_display' => $item->modifiers_display,
            'notes' => $item->notes,
            'status' => $item->status,
            'started_at' => $item->started_at?->toIso8601String(),
            'completed_at' => $item->completed_at?->toIso8601String(),
        ];
    }
}
