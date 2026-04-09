<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DiscountController extends Controller
{
    #[OA\Get(
        path: '/discounts',
        summary: 'List available discounts for current outlet',
        security: [['sanctum' => []]],
        tags: ['Discounts'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', description: 'Current outlet UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'auto_only', in: 'query', description: 'Filter auto-apply discounts only', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'scope', in: 'query', description: 'Filter by scope', schema: new OA\Schema(type: 'string', enum: ['order', 'item'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of available discounts'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    /**
     * List available discounts for current outlet
     *
     * GET /api/v1/discounts
     *
     * Schema discounts:
     * - id (uuid)
     * - tenant_id (uuid)
     * - code (string 50)
     * - name (string 200)
     * - description (text, nullable)
     * - type (string: percentage, fixed_amount, buy_x_get_y)
     * - scope (string: order, item)
     * - value (decimal)
     * - max_discount (decimal, nullable)
     * - min_purchase (decimal, nullable)
     * - min_qty (int, nullable)
     * - member_only (boolean)
     * - membership_levels (json, nullable)
     * - applicable_outlets (json, nullable)
     * - applicable_items (json, nullable)
     * - valid_from (date, nullable)
     * - valid_until (date, nullable)
     * - usage_limit (int, nullable)
     * - usage_count (int)
     * - is_auto_apply (boolean)
     * - is_active (boolean)
     * - created_at, updated_at
     */
    public function index(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        $query = Discount::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            });

        // Filter by outlet applicability
        if ($outletId) {
            $query->where(function ($q) use ($outletId) {
                $q->whereNull('applicable_outlets')
                    ->orWhereJsonContains('applicable_outlets', $outletId);
            });
        }

        // Filter auto-apply discounts
        if ($request->boolean('auto_only')) {
            $query->where('is_auto_apply', true);
        }

        // Filter by scope
        if ($request->has('scope')) {
            $query->where('scope', $request->scope);
        }

        $discounts = $query->orderBy('name')
            ->get()
            ->map(fn ($discount) => $this->formatDiscount($discount));

        return $this->success($discounts);
    }

    #[OA\Post(
        path: '/discounts/validate',
        summary: 'Validate discount code and calculate discount amount',
        security: [['sanctum' => []]],
        tags: ['Discounts'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, description: 'Current outlet UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'subtotal'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', maxLength: 50, description: 'Discount code'),
                    new OA\Property(property: 'subtotal', type: 'number', format: 'float', description: 'Order subtotal'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, description: 'Item quantity'),
                    new OA\Property(property: 'customer_id', type: 'string', format: 'uuid', description: 'Customer UUID for member discounts'),
                    new OA\Property(property: 'item_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid'), description: 'Product UUIDs for item-specific discounts'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Discount is valid',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'discount', type: 'object'),
                            new OA\Property(property: 'calculated', type: 'object', properties: [
                                new OA\Property(property: 'subtotal', type: 'number'),
                                new OA\Property(property: 'discount_amount', type: 'number'),
                                new OA\Property(property: 'after_discount', type: 'number'),
                            ]),
                            new OA\Property(property: 'valid', type: 'boolean', example: true),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Discount not applicable (inactive, expired, min purchase, etc)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Discount code not found'),
        ]
    )]
    public function validateDiscount(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'customer_id' => 'nullable|uuid',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'uuid',
        ]);

        $discount = Discount::where('tenant_id', $this->tenantId())
            ->where('code', strtoupper($validated['code']))
            ->first();

        if (! $discount) {
            return $this->error('Discount code not found.', 404);
        }

        // Check if discount is valid
        if (! $discount->isValid()) {
            if (! $discount->is_active) {
                return $this->error('This discount is no longer active.', 400);
            }
            if ($discount->valid_from && $discount->valid_from->isFuture()) {
                return $this->error('This discount is not yet valid.', 400);
            }
            if ($discount->valid_until && $discount->valid_until->isPast()) {
                return $this->error('This discount has expired.', 400);
            }
            if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
                return $this->error('This discount has reached its usage limit.', 400);
            }
        }

        // Check outlet applicability
        if (! $discount->isApplicableToOutlet($outletId)) {
            return $this->error('This discount is not available at this outlet.', 400);
        }

        // Check member requirements
        $customer = null;
        if (! empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
        }

        if (! $discount->isApplicableToMember($customer)) {
            if ($discount->member_only && ! $customer) {
                return $this->error('This discount is for members only. Please select a customer.', 400);
            }
            if ($discount->membership_levels && $customer) {
                $requiredLevels = implode(', ', $discount->membership_levels);

                return $this->error("This discount requires {$requiredLevels} membership level.", 400);
            }

            return $this->error('This discount is not applicable to this customer.', 400);
        }

        // Check item applicability
        if ($discount->applicable_items && ! empty($validated['item_ids'])) {
            $applicableItems = array_intersect($discount->applicable_items, $validated['item_ids']);
            if (empty($applicableItems)) {
                return $this->error('This discount is not applicable to the selected items.', 400);
            }
        }

        // Calculate discount
        $subtotal = (float) $validated['subtotal'];
        $quantity = (int) ($validated['quantity'] ?? 1);
        $discountAmount = $discount->calculateDiscount($subtotal, $quantity);

        if ($discountAmount <= 0) {
            if ($discount->min_purchase && $subtotal < $discount->min_purchase) {
                $minPurchase = number_format($discount->min_purchase, 0);

                return $this->error("Minimum purchase of Rp {$minPurchase} is required.", 400);
            }
            if ($discount->min_qty && $quantity < $discount->min_qty) {
                return $this->error("Minimum quantity of {$discount->min_qty} items is required.", 400);
            }

            return $this->error('Discount cannot be applied to this order.', 400);
        }

        return $this->success([
            'discount' => $this->formatDiscount($discount),
            'calculated' => [
                'subtotal' => $subtotal,
                'discount_amount' => round($discountAmount, 2),
                'after_discount' => round($subtotal - $discountAmount, 2),
            ],
            'valid' => true,
        ], 'Discount is valid');
    }

    /**
     * Format discount for response
     */
    private function formatDiscount(Discount $discount): array
    {
        return [
            'id' => $discount->id,
            'code' => $discount->code,
            'name' => $discount->name,
            'description' => $discount->description,
            'type' => $discount->type,
            'scope' => $discount->scope,
            'value' => (float) $discount->value,
            'max_discount' => $discount->max_discount ? (float) $discount->max_discount : null,
            'min_purchase' => $discount->min_purchase ? (float) $discount->min_purchase : null,
            'min_qty' => $discount->min_qty,
            'member_only' => (bool) $discount->member_only,
            'membership_levels' => $discount->membership_levels,
            'valid_from' => $discount->valid_from?->toDateString(),
            'valid_until' => $discount->valid_until?->toDateString(),
            'is_auto_apply' => (bool) $discount->is_auto_apply,
            'is_valid' => $discount->isValid(),
        ];
    }
}
