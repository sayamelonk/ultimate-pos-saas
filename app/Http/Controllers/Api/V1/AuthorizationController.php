<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AuthorizationLog;
use App\Models\AuthorizationSetting;
use App\Models\PinAttempt;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AuthorizationController extends Controller
{
    #[OA\Post(
        path: '/authorize',
        summary: 'Verify manager PIN for authorization',
        description: 'Verifies a manager/supervisor PIN to authorize an action (void, refund, discount, etc.)',
        security: [['sanctum' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['manager_id', 'pin', 'action_type'],
                properties: [
                    new OA\Property(property: 'manager_id', type: 'string', format: 'uuid', description: 'Manager/Supervisor user ID'),
                    new OA\Property(property: 'pin', type: 'string', minLength: 4, maxLength: 6, example: '1234'),
                    new OA\Property(property: 'action_type', type: 'string', enum: ['void', 'refund', 'discount', 'price_override', 'no_sale', 'reprint', 'cancel_order', 'other']),
                    new OA\Property(property: 'reference_type', type: 'string', nullable: true, description: 'Model class (Transaction, Order, etc.)'),
                    new OA\Property(property: 'reference_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'reference_number', type: 'string', nullable: true, example: 'TRX-20260204-0001'),
                    new OA\Property(property: 'amount', type: 'number', nullable: true, description: 'Amount involved'),
                    new OA\Property(property: 'reason', type: 'string', nullable: true, description: 'Reason for authorization request'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authorization successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'authorization_id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'authorized_by', type: 'string'),
                            new OA\Property(property: 'authorized_at', type: 'string', format: 'date-time'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Invalid PIN or unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too many attempts, locked out'),
        ]
    )]
    public function authorize(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $validated = $request->validate([
            'manager_id' => 'required|uuid',
            'pin' => 'required|string|min:4|max:6',
            'action_type' => 'required|string|in:void,refund,discount,price_override,no_sale,reprint,cancel_order,other',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|uuid',
            'reference_number' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        $tenantId = $this->tenantId();
        $settings = AuthorizationSetting::getForTenant($tenantId);

        // Check lockout
        $recentAttempts = PinAttempt::where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('attempted_for', $validated['manager_id'])
            ->where('success', false)
            ->where('attempted_at', '>=', now()->subMinutes($settings->lockout_minutes))
            ->count();

        if ($recentAttempts >= $settings->max_pin_attempts) {
            return $this->error(
                "Too many failed attempts. Please wait {$settings->lockout_minutes} minutes before trying again.",
                429
            );
        }

        // Find manager
        $manager = User::where('id', $validated['manager_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $manager) {
            $this->logPinAttempt($tenantId, $outletId, $validated['manager_id'], false, $request);

            return $this->error('Manager not found.', 403);
        }

        // Check if manager has authorization role
        if (! $manager->hasAnyRole(['tenant-owner', 'outlet-manager', 'supervisor', 'spv', 'manager', 'admin', 'super-admin'])) {
            $this->logPinAttempt($tenantId, $outletId, $validated['manager_id'], false, $request);

            return $this->error('User does not have authorization privileges.', 403);
        }

        // Verify PIN
        $userPin = UserPin::where('user_id', $manager->id)
            ->where('is_active', true)
            ->first();

        if (! $userPin || ! $userPin->verifyPin($validated['pin'])) {
            $this->logPinAttempt($tenantId, $outletId, $validated['manager_id'], false, $request);

            $remainingAttempts = $settings->max_pin_attempts - $recentAttempts - 1;

            return $this->error(
                "Invalid PIN. {$remainingAttempts} attempts remaining.",
                403
            );
        }

        // Log successful attempt
        $this->logPinAttempt($tenantId, $outletId, $validated['manager_id'], true, $request);

        // Create authorization log
        $authLog = DB::transaction(function () use ($tenantId, $outletId, $manager, $validated) {
            return AuthorizationLog::create([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'requested_by' => $this->user()->id,
                'authorized_by' => $manager->id,
                'action_type' => $validated['action_type'],
                'status' => AuthorizationLog::STATUS_APPROVED,
                'reference_type' => $validated['reference_type'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'requested_at' => now(),
                'responded_at' => now(),
            ]);
        });

        return $this->success([
            'authorization_id' => $authLog->id,
            'authorized_by' => $manager->name,
            'authorized_by_id' => $manager->id,
            'action_type' => $validated['action_type'],
            'action_label' => AuthorizationLog::getActionLabel($validated['action_type']),
            'authorized_at' => now()->toIso8601String(),
        ], 'Authorization successful');
    }

    #[OA\Get(
        path: '/authorize/check',
        summary: 'Check if action requires authorization',
        description: 'Check if a specific action requires manager authorization based on tenant settings',
        security: [['sanctum' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'action', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['void', 'refund', 'discount', 'price_override', 'no_sale', 'reprint', 'cancel_order'])),
            new OA\Parameter(name: 'discount_percent', in: 'query', description: 'For discount action, the discount percentage', schema: new OA\Schema(type: 'number')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authorization requirement check',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'requires_auth', type: 'boolean'),
                            new OA\Property(property: 'action', type: 'string'),
                            new OA\Property(property: 'action_label', type: 'string'),
                            new OA\Property(property: 'threshold_percent', type: 'number', nullable: true),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:void,refund,discount,price_override,no_sale,reprint,cancel_order',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $settings = AuthorizationSetting::getForTenant($this->tenantId());
        $discountPercent = $validated['discount_percent'] ?? null;

        $requiresAuth = $settings->requiresAuth($validated['action'], $discountPercent);

        return $this->success([
            'requires_auth' => $requiresAuth,
            'action' => $validated['action'],
            'action_label' => AuthorizationLog::getActionLabel($validated['action']),
            'threshold_percent' => $validated['action'] === 'discount' ? (float) $settings->discount_threshold_percent : null,
        ]);
    }

    #[OA\Get(
        path: '/authorize/managers',
        summary: 'List users who can authorize',
        description: 'Get list of managers/supervisors who can authorize actions',
        security: [['sanctum' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of managers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'role', type: 'string'),
                                new OA\Property(property: 'has_pin', type: 'boolean'),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function managers(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $managers = User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['tenant-owner', 'outlet-manager', 'supervisor', 'spv', 'manager', 'admin', 'super-admin']);
            })
            ->with(['userPin', 'roles'])
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->roles->first()?->name ?? 'unknown',
                'has_pin' => $user->userPin && $user->userPin->is_active,
            ]);

        return $this->success($managers);
    }

    #[OA\Get(
        path: '/authorize/settings',
        summary: 'Get authorization settings',
        description: 'Get current tenant authorization settings',
        security: [['sanctum' => []]],
        tags: ['Authorization'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authorization settings',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'require_auth_void', type: 'boolean'),
                            new OA\Property(property: 'require_auth_refund', type: 'boolean'),
                            new OA\Property(property: 'require_auth_discount', type: 'boolean'),
                            new OA\Property(property: 'discount_threshold_percent', type: 'number'),
                            new OA\Property(property: 'require_auth_price_override', type: 'boolean'),
                            new OA\Property(property: 'require_auth_no_sale', type: 'boolean'),
                            new OA\Property(property: 'require_auth_reprint', type: 'boolean'),
                            new OA\Property(property: 'require_auth_cancel_order', type: 'boolean'),
                            new OA\Property(property: 'pin_length', type: 'integer'),
                            new OA\Property(property: 'max_pin_attempts', type: 'integer'),
                            new OA\Property(property: 'lockout_minutes', type: 'integer'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function settings(): JsonResponse
    {
        $settings = AuthorizationSetting::getForTenant($this->tenantId());

        return $this->success([
            'require_auth_void' => (bool) $settings->require_auth_void,
            'require_auth_refund' => (bool) $settings->require_auth_refund,
            'require_auth_discount' => (bool) $settings->require_auth_discount,
            'discount_threshold_percent' => (float) $settings->discount_threshold_percent,
            'require_auth_price_override' => (bool) $settings->require_auth_price_override,
            'require_auth_no_sale' => (bool) $settings->require_auth_no_sale,
            'require_auth_reprint' => (bool) $settings->require_auth_reprint,
            'require_auth_cancel_order' => (bool) $settings->require_auth_cancel_order,
            'pin_length' => (int) $settings->pin_length,
            'max_pin_attempts' => (int) $settings->max_pin_attempts,
            'lockout_minutes' => (int) $settings->lockout_minutes,
        ]);
    }

    #[OA\Get(
        path: '/authorize/logs',
        summary: 'Get authorization logs',
        description: 'Get authorization history for the current outlet',
        security: [['sanctum' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'action_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['void', 'refund', 'discount', 'price_override', 'no_sale', 'reprint', 'cancel_order', 'other'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'denied', 'expired'])),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Authorization logs with pagination'),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logs(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $perPage = min($request->get('per_page', 15), 50);

        $query = AuthorizationLog::where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->with(['requestedBy:id,name', 'authorizedBy:id,name'])
            ->orderByDesc('requested_at');

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('requested_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('requested_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($perPage);

        $data = $logs->map(fn ($log) => $this->formatLog($log));

        return $this->successWithPagination($data, $this->paginationMeta($logs));
    }

    /**
     * Log PIN attempt
     */
    private function logPinAttempt(string $tenantId, string $outletId, string $attemptedFor, bool $success, Request $request): void
    {
        PinAttempt::create([
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'user_id' => $this->user()->id,
            'attempted_for' => $attemptedFor,
            'success' => $success,
            'ip_address' => $request->ip(),
            'attempted_at' => now(),
        ]);
    }

    /**
     * Format authorization log
     */
    private function formatLog(AuthorizationLog $log): array
    {
        return [
            'id' => $log->id,
            'action_type' => $log->action_type,
            'action_label' => AuthorizationLog::getActionLabel($log->action_type),
            'status' => $log->status,
            'status_badge' => AuthorizationLog::getStatusBadgeType($log->status),
            'requested_by' => $log->requestedBy?->name,
            'requested_by_id' => $log->requested_by,
            'authorized_by' => $log->authorizedBy?->name,
            'authorized_by_id' => $log->authorized_by,
            'reference_type' => $log->reference_type,
            'reference_id' => $log->reference_id,
            'reference_number' => $log->reference_number,
            'amount' => $log->amount ? (float) $log->amount : null,
            'reason' => $log->reason,
            'notes' => $log->notes,
            'requested_at' => $log->requested_at?->toIso8601String(),
            'responded_at' => $log->responded_at?->toIso8601String(),
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }
}
