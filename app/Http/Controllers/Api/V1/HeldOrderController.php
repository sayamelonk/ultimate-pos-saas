<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\HeldOrder;
use App\Models\PosSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class HeldOrderController extends Controller
{
    #[OA\Get(
        path: '/held-orders',
        summary: 'List held orders for current session/outlet',
        security: [['sanctum' => []]],
        tags: ['Held Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'session_only', in: 'query', description: 'Only current session orders', schema: new OA\Schema(type: 'boolean', default: false)),
            new OA\Parameter(name: 'include_expired', in: 'query', description: 'Include expired orders', schema: new OA\Schema(type: 'boolean', default: false)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of held orders'),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $query = HeldOrder::query()
            ->where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->with(['user:id,name', 'customer:id,name,phone']);

        // Filter by current session only
        if ($request->boolean('session_only')) {
            $session = PosSession::where('outlet_id', $outletId)
                ->where('user_id', $this->user()->id)
                ->where('status', PosSession::STATUS_OPEN)
                ->first();

            if ($session) {
                $query->where('pos_session_id', $session->id);
            }
        }

        // Exclude expired by default
        if (! $request->boolean('include_expired')) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        $heldOrders = $query->orderByDesc('created_at')
            ->get()
            ->map(fn ($order) => $this->formatHeldOrder($order));

        return $this->success($heldOrders);
    }

    #[OA\Post(
        path: '/held-orders',
        summary: 'Create held order (hold current cart)',
        security: [['sanctum' => []]],
        tags: ['Held Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                        required: ['product_id', 'quantity', 'unit_price'],
                        properties: [
                            new OA\Property(property: 'product_id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'variant_id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'product_name', type: 'string'),
                            new OA\Property(property: 'quantity', type: 'number'),
                            new OA\Property(property: 'unit_price', type: 'number'),
                            new OA\Property(property: 'discount_amount', type: 'number'),
                            new OA\Property(property: 'subtotal', type: 'number'),
                            new OA\Property(property: 'modifiers', type: 'array', items: new OA\Items(type: 'object')),
                            new OA\Property(property: 'notes', type: 'string'),
                        ]
                    )),
                    new OA\Property(property: 'reference', type: 'string', description: 'Customer name or reference'),
                    new OA\Property(property: 'table_number', type: 'string'),
                    new OA\Property(property: 'customer_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'discounts', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'subtotal', type: 'number'),
                    new OA\Property(property: 'discount_amount', type: 'number'),
                    new OA\Property(property: 'tax_amount', type: 'number'),
                    new OA\Property(property: 'service_charge_amount', type: 'number'),
                    new OA\Property(property: 'grand_total', type: 'number'),
                    new OA\Property(property: 'notes', type: 'string'),
                    new OA\Property(property: 'expires_in_hours', type: 'integer', description: 'Hours until expiry (default 24)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Held order created'),
            new OA\Response(response: 400, description: 'No outlet or session'),
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

        // Check active session
        $session = PosSession::where('outlet_id', $outletId)
            ->where('user_id', $this->user()->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();

        if (! $session) {
            return $this->error('No active POS session. Please open a session first.', 400);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.variant_id' => 'nullable|uuid',
            'items.*.product_name' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric',
            'items.*.modifiers' => 'nullable|array',
            'items.*.notes' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
            'table_number' => 'nullable|string|max:50',
            'customer_id' => 'nullable|uuid',
            'discounts' => 'nullable|array',
            'subtotal' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'service_charge_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'expires_in_hours' => 'nullable|integer|min:1|max:168', // max 1 week
        ]);

        // Calculate totals if not provided
        $subtotal = $validated['subtotal'] ?? collect($validated['items'])->sum(function ($item) {
            return ($item['unit_price'] * $item['quantity']) - ($item['discount_amount'] ?? 0);
        });

        $grandTotal = $validated['grand_total'] ?? (
            $subtotal
            - ($validated['discount_amount'] ?? 0)
            + ($validated['tax_amount'] ?? 0)
            + ($validated['service_charge_amount'] ?? 0)
        );

        // Generate hold number
        $holdNumber = $this->generateHoldNumber($outletId);

        // Set expiry (default 24 hours)
        $expiresInHours = $validated['expires_in_hours'] ?? 24;
        $expiresAt = now()->addHours($expiresInHours);

        $heldOrder = HeldOrder::create([
            'tenant_id' => $this->tenantId(),
            'outlet_id' => $outletId,
            'pos_session_id' => $session->id,
            'user_id' => $this->user()->id,
            'customer_id' => $validated['customer_id'] ?? null,
            'hold_number' => $holdNumber,
            'reference' => $validated['reference'] ?? null,
            'table_number' => $validated['table_number'] ?? null,
            'items' => $validated['items'],
            'discounts' => $validated['discounts'] ?? null,
            'subtotal' => $subtotal,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'service_charge_amount' => $validated['service_charge_amount'] ?? 0,
            'grand_total' => $grandTotal,
            'notes' => $validated['notes'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        return $this->created($this->formatHeldOrder($heldOrder), 'Order held successfully');
    }

    #[OA\Get(
        path: '/held-orders/{heldOrder}',
        summary: 'Get held order detail',
        security: [['sanctum' => []]],
        tags: ['Held Orders'],
        parameters: [
            new OA\Parameter(name: 'heldOrder', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Held order detail'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Held order not found'),
        ]
    )]
    public function show(HeldOrder $heldOrder): JsonResponse
    {
        if ($heldOrder->tenant_id !== $this->tenantId()) {
            return $this->notFound('Held order not found');
        }

        $heldOrder->load(['user:id,name', 'customer:id,name,phone,email', 'outlet:id,name']);

        return $this->success($this->formatHeldOrder($heldOrder, detailed: true));
    }

    #[OA\Delete(
        path: '/held-orders/{heldOrder}',
        summary: 'Delete held order (cancel/discard)',
        security: [['sanctum' => []]],
        tags: ['Held Orders'],
        parameters: [
            new OA\Parameter(name: 'heldOrder', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Held order deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Held order not found'),
        ]
    )]
    public function destroy(HeldOrder $heldOrder): JsonResponse
    {
        if ($heldOrder->tenant_id !== $this->tenantId()) {
            return $this->notFound('Held order not found');
        }

        $heldOrder->delete();

        return $this->success(null, 'Held order deleted successfully');
    }

    #[OA\Post(
        path: '/held-orders/{heldOrder}/restore',
        summary: 'Restore held order to cart (returns cart data for checkout)',
        security: [['sanctum' => []]],
        tags: ['Held Orders'],
        parameters: [
            new OA\Parameter(name: 'heldOrder', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'delete_after_restore', in: 'query', description: 'Delete held order after restore', schema: new OA\Schema(type: 'boolean', default: true)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cart data to restore',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                            new OA\Property(property: 'customer_id', type: 'string'),
                            new OA\Property(property: 'discounts', type: 'array', items: new OA\Items(type: 'object')),
                            new OA\Property(property: 'notes', type: 'string'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Order expired'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Held order not found'),
        ]
    )]
    public function restore(Request $request, HeldOrder $heldOrder): JsonResponse
    {
        if ($heldOrder->tenant_id !== $this->tenantId()) {
            return $this->notFound('Held order not found');
        }

        if ($heldOrder->isExpired()) {
            return $this->error('This held order has expired.', 400);
        }

        // Return cart data for POS to restore
        $cartData = [
            'items' => $heldOrder->items,
            'customer_id' => $heldOrder->customer_id,
            'customer' => $heldOrder->customer ? [
                'id' => $heldOrder->customer->id,
                'name' => $heldOrder->customer->name,
                'phone' => $heldOrder->customer->phone,
            ] : null,
            'discounts' => $heldOrder->discounts,
            'table_number' => $heldOrder->table_number,
            'notes' => $heldOrder->notes,
            'subtotal' => (float) $heldOrder->subtotal,
            'discount_amount' => (float) $heldOrder->discount_amount,
            'tax_amount' => (float) $heldOrder->tax_amount,
            'service_charge_amount' => (float) $heldOrder->service_charge_amount,
            'grand_total' => (float) $heldOrder->grand_total,
            'held_order_id' => $heldOrder->id,
            'hold_number' => $heldOrder->hold_number,
        ];

        // Delete after restore by default
        if ($request->boolean('delete_after_restore', true)) {
            $heldOrder->delete();
        }

        return $this->success($cartData, 'Order restored to cart');
    }

    /**
     * Generate unique hold number
     */
    private function generateHoldNumber(string $outletId): string
    {
        $today = now()->format('Ymd');
        $prefix = 'HLD';

        $lastHold = HeldOrder::where('outlet_id', $outletId)
            ->where('hold_number', 'like', "{$prefix}{$today}%")
            ->orderByDesc('hold_number')
            ->first();

        if ($lastHold) {
            $lastNumber = (int) substr($lastHold->hold_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$today}{$newNumber}";
    }

    /**
     * Format held order for response
     */
    private function formatHeldOrder(HeldOrder $heldOrder, bool $detailed = false): array
    {
        $data = [
            'id' => $heldOrder->id,
            'hold_number' => $heldOrder->hold_number,
            'reference' => $heldOrder->reference,
            'display_name' => $heldOrder->getDisplayName(),
            'table_number' => $heldOrder->table_number,
            'customer_id' => $heldOrder->customer_id,
            'customer_name' => $heldOrder->customer?->name,
            'user_name' => $heldOrder->user?->name,
            'items_count' => $heldOrder->getItemCount(),
            'grand_total' => (float) $heldOrder->grand_total,
            'notes' => $heldOrder->notes,
            'is_expired' => $heldOrder->isExpired(),
            'expires_at' => $heldOrder->expires_at?->toIso8601String(),
            'created_at' => $heldOrder->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['outlet_id'] = $heldOrder->outlet_id;
            $data['outlet_name'] = $heldOrder->outlet?->name;
            $data['pos_session_id'] = $heldOrder->pos_session_id;
            $data['items'] = $heldOrder->items;
            $data['discounts'] = $heldOrder->discounts;
            $data['subtotal'] = (float) $heldOrder->subtotal;
            $data['discount_amount'] = (float) $heldOrder->discount_amount;
            $data['tax_amount'] = (float) $heldOrder->tax_amount;
            $data['service_charge_amount'] = (float) $heldOrder->service_charge_amount;
            $data['customer'] = $heldOrder->customer ? [
                'id' => $heldOrder->customer->id,
                'name' => $heldOrder->customer->name,
                'phone' => $heldOrder->customer->phone,
                'email' => $heldOrder->customer->email,
            ] : null;
        }

        return $data;
    }
}
