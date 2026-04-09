<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Table;
use App\Models\TableSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class TableController extends Controller
{
    #[OA\Get(
        path: '/tables',
        summary: 'List tables for current outlet',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'floor_id', in: 'query', description: 'Filter by floor', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['available', 'occupied', 'reserved', 'dirty'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of tables',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
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

        $query = Table::query()
            ->where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->with(['floor', 'currentSession.transactions' => function ($query) {
                $query->where('status', '!=', 'voided');
            }]);

        // Optional filters
        if ($request->has('floor_id')) {
            $query->where('floor_id', $request->floor_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tables = $query->orderBy('floor_id')
            ->orderBy('number')
            ->get();

        $data = $tables->map(fn ($table) => $this->formatTable($table));

        return $this->success($data);
    }

    #[OA\Post(
        path: '/tables',
        summary: 'Create a new table',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['floor_id', 'number'],
                properties: [
                    new OA\Property(property: 'floor_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'number', type: 'string', maxLength: 20, example: 'A1'),
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, nullable: true),
                    new OA\Property(property: 'capacity', type: 'integer', minimum: 1, maximum: 100, default: 4),
                    new OA\Property(property: 'position_x', type: 'integer', minimum: 0, default: 0),
                    new OA\Property(property: 'position_y', type: 'integer', minimum: 0, default: 0),
                    new OA\Property(property: 'width', type: 'integer', minimum: 50, maximum: 500, default: 100),
                    new OA\Property(property: 'height', type: 'integer', minimum: 50, maximum: 500, default: 100),
                    new OA\Property(property: 'shape', type: 'string', enum: ['rectangle', 'circle', 'square'], default: 'rectangle'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Table created'),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error or duplicate table number'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $validated = $request->validate([
            'floor_id' => [
                'required',
                'uuid',
                Rule::exists('floors', 'id')->where(function ($query) use ($outletId) {
                    $query->where('outlet_id', $outletId)
                        ->where('tenant_id', $this->tenantId());
                }),
            ],
            'number' => 'required|string|max:20',
            'name' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:100',
            'position_x' => 'nullable|integer|min:0',
            'position_y' => 'nullable|integer|min:0',
            'width' => 'nullable|integer|min:50|max:500',
            'height' => 'nullable|integer|min:50|max:500',
            'shape' => ['nullable', Rule::in(array_keys(Table::getShapes()))],
        ]);

        // Check unique table number in floor
        $exists = Table::where('tenant_id', $this->tenantId())
            ->where('floor_id', $validated['floor_id'])
            ->where('number', $validated['number'])
            ->exists();

        if ($exists) {
            return $this->error('Table number already exists in this floor.', 422);
        }

        $table = Table::create([
            'tenant_id' => $this->tenantId(),
            'outlet_id' => $outletId,
            'floor_id' => $validated['floor_id'],
            'number' => $validated['number'],
            'name' => $validated['name'] ?? null,
            'capacity' => $validated['capacity'] ?? 4,
            'position_x' => $validated['position_x'] ?? 0,
            'position_y' => $validated['position_y'] ?? 0,
            'width' => $validated['width'] ?? 100,
            'height' => $validated['height'] ?? 100,
            'shape' => $validated['shape'] ?? Table::SHAPE_RECTANGLE,
            'status' => Table::STATUS_AVAILABLE,
            'is_active' => true,
        ]);

        $table->load('floor');

        return $this->created($this->formatTable($table), 'Table created successfully');
    }

    #[OA\Get(
        path: '/tables/{table}',
        summary: 'Get table detail',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Table detail with current session'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
        ]
    )]
    public function show(Table $table): JsonResponse
    {
        if ($table->tenant_id !== $this->tenantId()) {
            return $this->notFound('Table not found');
        }

        $table->load(['floor', 'currentSession.transactions' => function ($query) {
            $query->where('status', '!=', 'voided');
        }]);

        return $this->success($this->formatTable($table));
    }

    #[OA\Put(
        path: '/tables/{table}',
        summary: 'Update table',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'floor_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'number', type: 'string', maxLength: 20),
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, nullable: true),
                    new OA\Property(property: 'capacity', type: 'integer', minimum: 1, maximum: 100),
                    new OA\Property(property: 'position_x', type: 'integer', minimum: 0),
                    new OA\Property(property: 'position_y', type: 'integer', minimum: 0),
                    new OA\Property(property: 'width', type: 'integer', minimum: 50, maximum: 500),
                    new OA\Property(property: 'height', type: 'integer', minimum: 50, maximum: 500),
                    new OA\Property(property: 'shape', type: 'string', enum: ['rectangle', 'circle', 'square']),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Table updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Table $table): JsonResponse
    {
        if ($table->tenant_id !== $this->tenantId()) {
            return $this->notFound('Table not found');
        }

        $validated = $request->validate([
            'floor_id' => [
                'sometimes',
                'uuid',
                Rule::exists('floors', 'id')->where(function ($query) use ($table) {
                    $query->where('outlet_id', $table->outlet_id)
                        ->where('tenant_id', $this->tenantId());
                }),
            ],
            'number' => 'sometimes|string|max:20',
            'name' => 'nullable|string|max:100',
            'capacity' => 'sometimes|integer|min:1|max:100',
            'position_x' => 'sometimes|integer|min:0',
            'position_y' => 'sometimes|integer|min:0',
            'width' => 'sometimes|integer|min:50|max:500',
            'height' => 'sometimes|integer|min:50|max:500',
            'shape' => ['sometimes', Rule::in(array_keys(Table::getShapes()))],
            'is_active' => 'sometimes|boolean',
        ]);

        // Check unique table number if changing
        if (isset($validated['number']) || isset($validated['floor_id'])) {
            $floorId = $validated['floor_id'] ?? $table->floor_id;
            $number = $validated['number'] ?? $table->number;

            $exists = Table::where('tenant_id', $this->tenantId())
                ->where('floor_id', $floorId)
                ->where('number', $number)
                ->where('id', '!=', $table->id)
                ->exists();

            if ($exists) {
                return $this->error('Table number already exists in this floor.', 422);
            }
        }

        $table->update($validated);
        $table->load('floor');

        return $this->success($this->formatTable($table), 'Table updated successfully');
    }

    #[OA\Delete(
        path: '/tables/{table}',
        summary: 'Delete table',
        description: 'Cannot delete table with active session or transaction history',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Table deleted'),
            new OA\Response(response: 400, description: 'Cannot delete table with active session'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
        ]
    )]
    public function destroy(Table $table): JsonResponse
    {
        if ($table->tenant_id !== $this->tenantId()) {
            return $this->notFound('Table not found');
        }

        // Check if table has active session
        if ($table->currentSession) {
            return $this->error('Cannot delete table with active session. Please close the session first.', 400);
        }

        // Check if table has any transactions
        if ($table->transactions()->exists()) {
            return $this->error('Cannot delete table with transaction history. Consider deactivating instead.', 400);
        }

        $table->delete();

        return $this->success(null, 'Table deleted successfully');
    }

    #[OA\Post(
        path: '/tables/{table}/open',
        summary: 'Open table (start session)',
        description: 'Opens a table for guests, creating a new table session',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['guest_count'],
                properties: [
                    new OA\Property(property: 'guest_count', type: 'integer', minimum: 1, maximum: 100, example: 4),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 500, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Table opened',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'table', type: 'object'),
                            new OA\Property(property: 'session', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Table not available'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
        ]
    )]
    public function open(Request $request, Table $table): JsonResponse
    {
        if ($table->tenant_id !== $this->tenantId()) {
            return $this->notFound('Table not found');
        }

        if (! $table->isAvailable()) {
            return $this->error('Table is not available. Current status: '.$table->status, 400);
        }

        $validated = $request->validate([
            'guest_count' => 'required|integer|min:1|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $session = DB::transaction(function () use ($table, $validated) {
            $session = TableSession::openTable(
                table: $table,
                guestCount: $validated['guest_count'],
                openedBy: $this->user()->id
            );

            if (isset($validated['notes'])) {
                $session->update(['notes' => $validated['notes']]);
            }

            return $session;
        });

        $table->refresh();
        $table->load(['floor', 'currentSession']);

        return $this->success([
            'table' => $this->formatTable($table),
            'session' => $this->formatSession($session),
        ], 'Table opened successfully');
    }

    #[OA\Post(
        path: '/tables/{table}/close',
        summary: 'Close table (end session)',
        description: 'Closes the table session. Cannot close if there are unpaid orders.',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Table closed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'table', type: 'object'),
                            new OA\Property(property: 'session', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Table not occupied or has unpaid orders'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
        ]
    )]
    public function close(Request $request, Table $table): JsonResponse
    {
        if ($table->tenant_id !== $this->tenantId()) {
            return $this->notFound('Table not found');
        }

        if (! $table->isOccupied()) {
            return $this->error('Table is not occupied.', 400);
        }

        $session = $table->currentSession;

        if (! $session) {
            return $this->error('No active session found for this table.', 400);
        }

        // Check for unpaid transactions
        $unpaidTransactions = $session->transactions()
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($unpaidTransactions) {
            return $this->error('Cannot close table with unpaid orders. Please complete or void all orders first.', 400);
        }

        DB::transaction(function () use ($session) {
            $session->close($this->user()->id);
        });

        $table->refresh();
        $table->load('floor');

        return $this->success([
            'table' => $this->formatTable($table),
            'session' => $this->formatSession($session->fresh()),
        ], 'Table closed successfully');
    }

    #[OA\Patch(
        path: '/tables/{table}/status',
        summary: 'Update table status',
        description: 'Update table status (available, reserved, dirty). Cannot set to occupied without opening session.',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['available', 'occupied', 'reserved', 'dirty']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status updated'),
            new OA\Response(response: 400, description: 'Invalid status transition'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
        ]
    )]
    public function updateStatus(Request $request, Table $table): JsonResponse
    {
        if ($table->tenant_id !== $this->tenantId()) {
            return $this->notFound('Table not found');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Table::getStatuses()))],
        ]);

        $newStatus = $validated['status'];

        // Validate status transitions
        if ($newStatus === Table::STATUS_OCCUPIED && ! $table->currentSession) {
            return $this->error('Cannot set status to occupied without opening a session. Use the open endpoint instead.', 400);
        }

        if ($table->isOccupied() && $newStatus === Table::STATUS_AVAILABLE) {
            return $this->error('Cannot set occupied table to available. Use the close endpoint first.', 400);
        }

        $table->update(['status' => $newStatus]);
        $table->load('floor');

        return $this->success($this->formatTable($table), 'Table status updated successfully');
    }

    #[OA\Get(
        path: '/tables/{table}/sessions',
        summary: 'Get table session history',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Session history with pagination'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
        ]
    )]
    public function sessions(Request $request, Table $table): JsonResponse
    {
        if ($table->tenant_id !== $this->tenantId()) {
            return $this->notFound('Table not found');
        }

        $perPage = min($request->get('per_page', 15), 50);

        $sessions = $table->sessions()
            ->with(['openedByUser:id,name', 'closedByUser:id,name', 'transactions' => function ($query) {
                $query->where('status', '!=', 'voided');
            }])
            ->orderByDesc('opened_at')
            ->paginate($perPage);

        $data = $sessions->map(fn ($session) => $this->formatSession($session));

        return $this->successWithPagination($data, $this->paginationMeta($sessions));
    }

    #[OA\Post(
        path: '/tables/{table}/move',
        summary: 'Move table to another floor',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['floor_id'],
                properties: [
                    new OA\Property(property: 'floor_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'number', type: 'string', maxLength: 20, description: 'New table number (optional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Table moved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
            new OA\Response(response: 422, description: 'Table number exists in target floor'),
        ]
    )]
    public function move(Request $request, Table $table): JsonResponse
    {
        if ($table->tenant_id !== $this->tenantId()) {
            return $this->notFound('Table not found');
        }

        $validated = $request->validate([
            'floor_id' => [
                'required',
                'uuid',
                Rule::exists('floors', 'id')->where(function ($query) use ($table) {
                    $query->where('outlet_id', $table->outlet_id)
                        ->where('tenant_id', $this->tenantId());
                }),
            ],
            'number' => 'nullable|string|max:20',
        ]);

        $newNumber = $validated['number'] ?? $table->number;

        // Check unique table number in target floor
        $exists = Table::where('tenant_id', $this->tenantId())
            ->where('floor_id', $validated['floor_id'])
            ->where('number', $newNumber)
            ->where('id', '!=', $table->id)
            ->exists();

        if ($exists) {
            return $this->error('Table number already exists in the target floor.', 422);
        }

        $table->update([
            'floor_id' => $validated['floor_id'],
            'number' => $newNumber,
        ]);

        $table->load('floor');

        return $this->success($this->formatTable($table), 'Table moved successfully');
    }

    #[OA\Post(
        path: '/tables/positions',
        summary: 'Bulk update table positions',
        description: 'Update multiple table positions at once (for drag-drop floor plan editor)',
        security: [['sanctum' => []]],
        tags: ['Floors & Tables'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tables'],
                properties: [
                    new OA\Property(property: 'tables', type: 'array', items: new OA\Items(
                        required: ['id', 'position_x', 'position_y'],
                        properties: [
                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'position_x', type: 'integer', minimum: 0),
                            new OA\Property(property: 'position_y', type: 'integer', minimum: 0),
                            new OA\Property(property: 'width', type: 'integer', minimum: 50, maximum: 500),
                            new OA\Property(property: 'height', type: 'integer', minimum: 50, maximum: 500),
                        ]
                    )),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Positions updated'),
            new OA\Response(response: 400, description: 'No outlet or invalid tables'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function updatePositions(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $validated = $request->validate([
            'tables' => 'required|array|min:1',
            'tables.*.id' => 'required|uuid',
            'tables.*.position_x' => 'required|integer|min:0',
            'tables.*.position_y' => 'required|integer|min:0',
            'tables.*.width' => 'sometimes|integer|min:50|max:500',
            'tables.*.height' => 'sometimes|integer|min:50|max:500',
        ]);

        $tableIds = collect($validated['tables'])->pluck('id');

        // Verify all tables belong to tenant and outlet
        $tables = Table::where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->whereIn('id', $tableIds)
            ->get()
            ->keyBy('id');

        if ($tables->count() !== $tableIds->count()) {
            return $this->error('Some tables not found or do not belong to this outlet.', 400);
        }

        DB::transaction(function () use ($validated, $tables) {
            foreach ($validated['tables'] as $tableData) {
                $table = $tables->get($tableData['id']);
                $updateData = [
                    'position_x' => $tableData['position_x'],
                    'position_y' => $tableData['position_y'],
                ];

                if (isset($tableData['width'])) {
                    $updateData['width'] = $tableData['width'];
                }
                if (isset($tableData['height'])) {
                    $updateData['height'] = $tableData['height'];
                }

                $table->update($updateData);
            }
        });

        return $this->success(null, 'Table positions updated successfully');
    }

    /**
     * Format table data
     */
    private function formatTable(Table $table): array
    {
        $data = [
            'id' => $table->id,
            'floor_id' => $table->floor_id,
            'floor_name' => $table->floor?->name,
            'number' => $table->number,
            'name' => $table->name,
            'display_name' => $table->display_name,
            'capacity' => (int) $table->capacity,
            'position_x' => (int) $table->position_x,
            'position_y' => (int) $table->position_y,
            'width' => (int) $table->width,
            'height' => (int) $table->height,
            'shape' => $table->shape,
            'status' => $table->status,
            'is_active' => (bool) $table->is_active,
            'created_at' => $table->created_at?->toIso8601String(),
            'updated_at' => $table->updated_at?->toIso8601String(),
        ];

        // Include current session if occupied
        if ($table->relationLoaded('currentSession') && $table->currentSession) {
            $session = $table->currentSession;
            $data['current_session'] = [
                'id' => $session->id,
                'opened_at' => $session->opened_at?->toIso8601String(),
                'guest_count' => (int) $session->guest_count,
                'duration_minutes' => $session->duration_minutes,
                'notes' => $session->notes,
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

    /**
     * Format session data
     */
    private function formatSession(TableSession $session): array
    {
        $data = [
            'id' => $session->id,
            'table_id' => $session->table_id,
            'opened_by' => $session->opened_by,
            'opened_by_name' => $session->openedByUser?->name,
            'closed_by' => $session->closed_by,
            'closed_by_name' => $session->closedByUser?->name,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
            'guest_count' => (int) $session->guest_count,
            'notes' => $session->notes,
            'status' => $session->status,
            'duration_minutes' => $session->duration_minutes,
            'duration_formatted' => $session->duration_formatted,
            'created_at' => $session->created_at?->toIso8601String(),
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];

        // Include transactions if loaded
        if ($session->relationLoaded('transactions')) {
            $data['orders'] = $session->transactions->map(fn ($txn) => [
                'id' => $txn->id,
                'order_number' => $txn->transaction_number,
                'total' => (float) $txn->grand_total,
                'status' => $txn->status,
            ])->toArray();
            $data['total_amount'] = (float) $session->transactions->sum('grand_total');
        }

        return $data;
    }
}
