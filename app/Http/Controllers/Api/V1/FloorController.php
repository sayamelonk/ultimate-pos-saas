<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Floor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FloorController extends Controller
{
    #[OA\Get(
        path: '/floors',
        summary: 'List floors for current outlet',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of floors with table counts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'outlet_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'description', type: 'string', nullable: true),
                                new OA\Property(property: 'sort_order', type: 'integer'),
                                new OA\Property(property: 'tables_count', type: 'integer'),
                                new OA\Property(property: 'available_count', type: 'integer'),
                                new OA\Property(property: 'occupied_count', type: 'integer'),
                                new OA\Property(property: 'is_active', type: 'boolean'),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected. Please set X-Outlet-Id header.', 400);
        }

        $floors = Floor::query()
            ->where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount(['tables', 'tables as available_tables_count' => function ($query) {
                $query->where('status', 'available')->where('is_active', true);
            }, 'tables as occupied_tables_count' => function ($query) {
                $query->where('status', 'occupied')->where('is_active', true);
            }])
            ->get();

        $data = $floors->map(fn ($floor) => $this->formatFloor($floor));

        return $this->success($data);
    }

    #[OA\Post(
        path: '/floors',
        summary: 'Create a new floor',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Ground Floor'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', minimum: 0, default: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Floor created'),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $floor = Floor::create([
            'tenant_id' => $this->tenantId(),
            'outlet_id' => $outletId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return $this->created($this->formatFloor($floor), 'Floor created successfully');
    }

    #[OA\Get(
        path: '/floors/{floor}',
        summary: 'Get floor detail',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'floor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Floor detail'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Floor not found'),
        ]
    )]
    public function show(Floor $floor): JsonResponse
    {
        if ($floor->tenant_id !== $this->tenantId()) {
            return $this->notFound('Floor not found');
        }

        $floor->loadCount(['tables', 'tables as available_tables_count' => function ($query) {
            $query->where('status', 'available')->where('is_active', true);
        }, 'tables as occupied_tables_count' => function ($query) {
            $query->where('status', 'occupied')->where('is_active', true);
        }]);

        return $this->success($this->formatFloor($floor));
    }

    #[OA\Put(
        path: '/floors/{floor}',
        summary: 'Update floor',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'floor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', minimum: 0),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Floor updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Floor not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Floor $floor): JsonResponse
    {
        if ($floor->tenant_id !== $this->tenantId()) {
            return $this->notFound('Floor not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $floor->update($validated);

        return $this->success($this->formatFloor($floor), 'Floor updated successfully');
    }

    #[OA\Delete(
        path: '/floors/{floor}',
        summary: 'Delete floor',
        description: 'Cannot delete floor with tables. Move or delete tables first.',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'floor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Floor deleted'),
            new OA\Response(response: 400, description: 'Cannot delete floor with tables'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Floor not found'),
        ]
    )]
    public function destroy(Floor $floor): JsonResponse
    {
        if ($floor->tenant_id !== $this->tenantId()) {
            return $this->notFound('Floor not found');
        }

        // Check if floor has tables
        if ($floor->tables()->exists()) {
            return $this->error('Cannot delete floor with tables. Please delete or move tables first.', 400);
        }

        $floor->delete();

        return $this->success(null, 'Floor deleted successfully');
    }

    #[OA\Get(
        path: '/floors/{floor}/tables',
        summary: 'Get tables in floor',
        description: 'Returns all active tables in the floor with their current session info',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'floor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of tables in floor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'floor_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'number', type: 'string'),
                                new OA\Property(property: 'name', type: 'string', nullable: true),
                                new OA\Property(property: 'capacity', type: 'integer'),
                                new OA\Property(property: 'position_x', type: 'integer'),
                                new OA\Property(property: 'position_y', type: 'integer'),
                                new OA\Property(property: 'width', type: 'integer'),
                                new OA\Property(property: 'height', type: 'integer'),
                                new OA\Property(property: 'shape', type: 'string', enum: ['rectangle', 'circle', 'square']),
                                new OA\Property(property: 'status', type: 'string', enum: ['available', 'occupied', 'reserved', 'dirty']),
                                new OA\Property(property: 'is_active', type: 'boolean'),
                                new OA\Property(property: 'current_session', type: 'object', nullable: true),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Floor not found'),
        ]
    )]
    public function tables(Floor $floor): JsonResponse
    {
        if ($floor->tenant_id !== $this->tenantId()) {
            return $this->notFound('Floor not found');
        }

        $tables = $floor->tables()
            ->where('is_active', true)
            ->with(['currentSession.transactions' => function ($query) {
                $query->where('status', '!=', 'voided');
            }])
            ->orderBy('number')
            ->get();

        $data = $tables->map(fn ($table) => $this->formatTable($table));

        return $this->success($data);
    }

    /**
     * Format floor data
     */
    private function formatFloor(Floor $floor): array
    {
        return [
            'id' => $floor->id,
            'outlet_id' => $floor->outlet_id,
            'name' => $floor->name,
            'description' => $floor->description,
            'sort_order' => (int) $floor->sort_order,
            'tables_count' => (int) ($floor->tables_count ?? 0),
            'available_count' => (int) ($floor->available_tables_count ?? 0),
            'occupied_count' => (int) ($floor->occupied_tables_count ?? 0),
            'is_active' => (bool) $floor->is_active,
            'created_at' => $floor->created_at?->toIso8601String(),
            'updated_at' => $floor->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Format table data (used in tables endpoint)
     */
    private function formatTable($table): array
    {
        $data = [
            'id' => $table->id,
            'floor_id' => $table->floor_id,
            'number' => $table->number,
            'name' => $table->name,
            'capacity' => (int) $table->capacity,
            'position_x' => (int) $table->position_x,
            'position_y' => (int) $table->position_y,
            'width' => (int) $table->width,
            'height' => (int) $table->height,
            'shape' => $table->shape,
            'status' => $table->status,
            'is_active' => (bool) $table->is_active,
        ];

        // Include current session if occupied
        if ($table->currentSession) {
            $session = $table->currentSession;
            $data['current_session'] = [
                'id' => $session->id,
                'opened_at' => $session->opened_at?->toIso8601String(),
                'guest_count' => (int) $session->guest_count,
                'duration_minutes' => $session->duration_minutes,
                'orders' => $session->transactions->map(fn ($txn) => [
                    'id' => $txn->id,
                    'order_number' => $txn->transaction_number,
                    'total' => (float) $txn->grand_total,
                    'status' => $txn->status,
                ])->toArray(),
                'total_amount' => (float) $session->transactions->sum('grand_total'),
            ];
        }

        return $data;
    }
}
