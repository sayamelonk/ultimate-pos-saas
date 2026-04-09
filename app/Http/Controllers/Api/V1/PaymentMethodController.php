<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PaymentMethodController extends Controller
{
    #[OA\Get(
        path: '/payment-methods',
        summary: 'List active payment methods',
        description: 'Returns all active payment methods for the tenant, sorted by sort_order',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', description: 'Filter by type', schema: new OA\Schema(type: 'string', enum: ['cash', 'card', 'digital_wallet', 'transfer', 'other'])),
            new OA\Parameter(name: 'include_inactive', in: 'query', description: 'Include inactive methods', schema: new OA\Schema(type: 'boolean', default: false)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of payment methods',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'code', type: 'string', example: 'CASH'),
                                new OA\Property(property: 'name', type: 'string', example: 'Cash'),
                                new OA\Property(property: 'type', type: 'string', enum: ['cash', 'card', 'digital_wallet', 'transfer', 'other']),
                                new OA\Property(property: 'provider', type: 'string', nullable: true, example: 'BCA'),
                                new OA\Property(property: 'icon', type: 'string', nullable: true),
                                new OA\Property(property: 'charge_percentage', type: 'number', example: 0),
                                new OA\Property(property: 'charge_fixed', type: 'number', example: 0),
                                new OA\Property(property: 'requires_reference', type: 'boolean', example: false),
                                new OA\Property(property: 'opens_cash_drawer', type: 'boolean', example: true),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = PaymentMethod::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('sort_order');

        // Filter by active status
        if (! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $paymentMethods = $query->get();

        $data = $paymentMethods->map(fn ($pm) => $this->formatPaymentMethod($pm));

        return $this->success($data);
    }

    #[OA\Get(
        path: '/payment-methods/{paymentMethod}',
        summary: 'Get payment method detail',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        parameters: [
            new OA\Parameter(name: 'paymentMethod', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payment method detail'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Payment method not found'),
        ]
    )]
    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        if ($paymentMethod->tenant_id !== $this->tenantId()) {
            return $this->notFound('Payment method not found');
        }

        return $this->success($this->formatPaymentMethod($paymentMethod));
    }

    #[OA\Post(
        path: '/payment-methods/calculate-charge',
        summary: 'Calculate payment charge/fee',
        description: 'Calculate the service charge for a payment method based on amount',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['payment_method_id', 'amount'],
                properties: [
                    new OA\Property(property: 'payment_method_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'amount', type: 'number', example: 100000),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Calculated charge',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'amount', type: 'number', example: 100000),
                            new OA\Property(property: 'charge_percentage', type: 'number', example: 2.5),
                            new OA\Property(property: 'charge_fixed', type: 'number', example: 500),
                            new OA\Property(property: 'charge_amount', type: 'number', example: 3000),
                            new OA\Property(property: 'total_amount', type: 'number', example: 103000),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Payment method not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function calculateCharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|uuid',
            'amount' => 'required|numeric|min:0',
        ]);

        $paymentMethod = PaymentMethod::where('tenant_id', $this->tenantId())
            ->where('id', $validated['payment_method_id'])
            ->first();

        if (! $paymentMethod) {
            return $this->notFound('Payment method not found');
        }

        $amount = (float) $validated['amount'];
        $chargeAmount = $paymentMethod->calculateCharge($amount);

        return $this->success([
            'amount' => $amount,
            'charge_percentage' => (float) $paymentMethod->charge_percentage,
            'charge_fixed' => (float) $paymentMethod->charge_fixed,
            'charge_amount' => round($chargeAmount, 2),
            'total_amount' => round($amount + $chargeAmount, 2),
        ]);
    }

    #[OA\Get(
        path: '/payment-methods/types',
        summary: 'Get available payment method types',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of payment types',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'value', type: 'string'),
                                new OA\Property(property: 'label', type: 'string'),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function types(): JsonResponse
    {
        $types = collect(PaymentMethod::getTypes())
            ->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values();

        return $this->success($types);
    }

    /**
     * Format payment method for response
     */
    private function formatPaymentMethod(PaymentMethod $pm): array
    {
        return [
            'id' => $pm->id,
            'code' => $pm->code,
            'name' => $pm->name,
            'type' => $pm->type,
            'provider' => $pm->provider,
            'icon' => $pm->icon,
            'charge_percentage' => (float) $pm->charge_percentage,
            'charge_fixed' => (float) $pm->charge_fixed,
            'requires_reference' => (bool) $pm->requires_reference,
            'opens_cash_drawer' => (bool) $pm->opens_cash_drawer,
            'is_active' => (bool) $pm->is_active,
        ];
    }
}
