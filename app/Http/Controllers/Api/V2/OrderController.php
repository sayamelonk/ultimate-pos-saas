<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    #[OA\Get(
        path: '/orders',
        summary: 'List orders for current outlet',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'session_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'completed', 'voided'])),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['sale', 'refund', 'void'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of orders with pagination'),
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

        $query = Transaction::query()
            ->where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->with(['user:id,name', 'customer:id,name,phone', 'table:id,number,name'])
            ->withCount('items');

        // Filter by session
        if ($request->has('session_id')) {
            $query->where('pos_session_id', $request->session_id);
        }

        // Filter by date range
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        } elseif ($request->has('from') && $request->has('to')) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        } else {
            // Default: today's orders
            $query->whereDate('created_at', now()->toDateString());
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = min($request->get('per_page', 20), 50);

        $transactions = $query->orderByDesc('created_at')
            ->paginate($perPage);

        $data = $transactions->map(fn ($txn) => $this->formatOrderList($txn));

        return $this->successWithPagination($data, $this->paginationMeta($transactions));
    }

    #[OA\Post(
        path: '/orders/calculate',
        summary: 'Calculate cart totals with tax mode support',
        description: 'Calculates cart totals including tax based on outlet tax_mode setting. Supports both inclusive (tax included in price) and exclusive (tax added on top) modes.',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
                            type: 'object',
                            required: ['product_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'product_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'variant_id', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'quantity', type: 'number', minimum: 0.01),
                                new OA\Property(property: 'modifiers', type: 'array', nullable: true, items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'price', type: 'number'),
                                        new OA\Property(property: 'quantity', type: 'integer', default: 1),
                                    ]
                                )),
                                new OA\Property(property: 'discount_amount', type: 'number', minimum: 0, nullable: true),
                                new OA\Property(property: 'notes', type: 'string', maxLength: 255, nullable: true),
                            ]
                        )
                    ),
                    new OA\Property(property: 'discount_type', type: 'string', enum: ['percentage', 'fixed'], nullable: true),
                    new OA\Property(property: 'discount_value', type: 'number', minimum: 0, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cart calculation result with tax breakdown',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'product_id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'variant_id', type: 'string', format: 'uuid', nullable: true),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'sku', type: 'string'),
                                        new OA\Property(property: 'quantity', type: 'number'),
                                        new OA\Property(property: 'base_price', type: 'number'),
                                        new OA\Property(property: 'unit_price', type: 'number'),
                                        new OA\Property(property: 'variant_price_adjustment', type: 'number'),
                                        new OA\Property(property: 'modifiers_total', type: 'number'),
                                        new OA\Property(property: 'modifiers', type: 'array', nullable: true, items: new OA\Items(type: 'object')),
                                        new OA\Property(property: 'cost_price', type: 'number'),
                                        new OA\Property(property: 'discount_amount', type: 'number'),
                                        new OA\Property(property: 'subtotal', type: 'number'),
                                        new OA\Property(property: 'notes', type: 'string', nullable: true),
                                    ]
                                )),
                                new OA\Property(property: 'items_count', type: 'integer', example: 3),
                                new OA\Property(property: 'subtotal', type: 'number', description: 'Subtotal before tax (for inclusive mode, this is price minus tax)', example: 100000),
                                new OA\Property(property: 'discount_amount', type: 'number', example: 0),
                                new OA\Property(property: 'after_discount', type: 'number', example: 100000),
                                new OA\Property(property: 'tax_enabled', type: 'boolean', description: 'Whether tax calculation is enabled for this outlet', example: true),
                                new OA\Property(property: 'tax_mode', type: 'string', enum: ['inclusive', 'exclusive'], description: 'Tax calculation mode', example: 'exclusive'),
                                new OA\Property(property: 'tax_percentage', type: 'number', description: 'Tax percentage applied', example: 10.0),
                                new OA\Property(property: 'tax_amount', type: 'number', description: 'Calculated tax amount', example: 10000),
                                new OA\Property(property: 'service_charge_enabled', type: 'boolean', description: 'Whether service charge is enabled', example: true),
                                new OA\Property(property: 'service_charge_percentage', type: 'number', example: 5.0),
                                new OA\Property(property: 'service_charge_amount', type: 'number', example: 5000),
                                new OA\Property(property: 'rounding', type: 'number', description: 'Rounding adjustment to nearest 100', example: 0),
                                new OA\Property(property: 'grand_total', type: 'number', description: 'Final total after all calculations', example: 115000),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function calculate(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.variant_id' => 'nullable|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.modifiers' => 'nullable|array',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            'discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $outlet = $this->currentOutlet($request);
        $calculation = $this->calculateCart($validated, $outlet);

        return $this->success($calculation);
    }

    #[OA\Post(
        path: '/orders/checkout',
        summary: 'Create order with tax mode support',
        description: 'Creates a new order transaction. Tax is calculated based on outlet tax_mode setting (inclusive or exclusive). Requires an active POS session.',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items', 'order_type', 'payments'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['product_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'product_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'variant_id', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'quantity', type: 'number', minimum: 0.01),
                                new OA\Property(property: 'modifiers', type: 'array', nullable: true, items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'price', type: 'number'),
                                        new OA\Property(property: 'quantity', type: 'integer', default: 1),
                                    ]
                                )),
                                new OA\Property(property: 'discount_amount', type: 'number', minimum: 0, nullable: true),
                                new OA\Property(property: 'notes', type: 'string', maxLength: 255, nullable: true),
                            ]
                        )
                    ),
                    new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway', 'delivery']),
                    new OA\Property(property: 'table_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'customer_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'discount_type', type: 'string', enum: ['percentage', 'fixed'], nullable: true),
                    new OA\Property(property: 'discount_value', type: 'number', minimum: 0, nullable: true),
                    new OA\Property(
                        property: 'payments',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['payment_method_id', 'amount'],
                            properties: [
                                new OA\Property(property: 'payment_method_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'amount', type: 'number', minimum: 0.01),
                                new OA\Property(property: 'reference_number', type: 'string', maxLength: 100, nullable: true),
                            ]
                        )
                    ),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 500, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Order completed successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'transaction_number', type: 'string', example: 'TRX202602120001'),
                                new OA\Property(property: 'type', type: 'string', example: 'sale'),
                                new OA\Property(property: 'order_type', type: 'string', example: 'dine_in'),
                                new OA\Property(property: 'subtotal', type: 'number', description: 'Subtotal (for inclusive mode, this is price minus tax)'),
                                new OA\Property(property: 'discount_amount', type: 'number'),
                                new OA\Property(property: 'tax_percentage', type: 'number'),
                                new OA\Property(property: 'tax_amount', type: 'number'),
                                new OA\Property(property: 'service_charge_percentage', type: 'number'),
                                new OA\Property(property: 'service_charge_amount', type: 'number'),
                                new OA\Property(property: 'rounding', type: 'number'),
                                new OA\Property(property: 'grand_total', type: 'number'),
                                new OA\Property(property: 'payment_amount', type: 'number'),
                                new OA\Property(property: 'change_amount', type: 'number'),
                                new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'payments', type: 'array', items: new OA\Items(type: 'object')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'No outlet selected or no active POS session'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Payment amount is less than grand total'),
        ]
    )]
    public function checkout(Request $request): JsonResponse
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
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.modifiers' => 'nullable|array',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            'order_type' => ['required', Rule::in(array_keys(Transaction::getOrderTypes()))],
            'table_id' => 'nullable|uuid',
            'customer_id' => 'nullable|uuid',
            'discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'discount_value' => 'nullable|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|uuid',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $outlet = $this->currentOutlet($request);
        $calculation = $this->calculateCart($validated, $outlet);

        // Validate payment amount
        $totalPayment = collect($validated['payments'])->sum('amount');
        if ($totalPayment < $calculation['grand_total']) {
            return $this->error('Payment amount is less than grand total.', 422);
        }

        try {
            $transaction = DB::transaction(function () use ($validated, $calculation, $session, $outlet) {
                $transactionNumber = $this->generateTransactionNumber($outlet->id);

                $transaction = Transaction::create([
                    'tenant_id' => $this->tenantId(),
                    'outlet_id' => $outlet->id,
                    'pos_session_id' => $session->id,
                    'table_id' => $validated['table_id'] ?? null,
                    'order_type' => $validated['order_type'],
                    'customer_id' => $validated['customer_id'] ?? null,
                    'user_id' => $this->user()->id,
                    'transaction_number' => $transactionNumber,
                    'type' => Transaction::TYPE_SALE,
                    'subtotal' => $calculation['subtotal'],
                    'discount_amount' => $calculation['discount_amount'],
                    'tax_amount' => $calculation['tax_amount'],
                    'service_charge_amount' => $calculation['service_charge_amount'],
                    'rounding' => $calculation['rounding'],
                    'grand_total' => $calculation['grand_total'],
                    'payment_amount' => collect($validated['payments'])->sum('amount'),
                    'change_amount' => collect($validated['payments'])->sum('amount') - $calculation['grand_total'],
                    'tax_percentage' => $calculation['tax_percentage'],
                    'tax_mode' => $calculation['tax_mode'],
                    'service_charge_percentage' => $calculation['service_charge_percentage'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => Transaction::STATUS_PENDING,
                ]);

                // Create transaction items
                foreach ($calculation['items'] as $item) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $item['variant_id'] ?? null,
                        'item_name' => $item['name'],
                        'item_sku' => $item['sku'],
                        'unit_name' => 'pcs',
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'base_price' => $item['base_price'],
                        'variant_price_adjustment' => $item['variant_price_adjustment'] ?? 0,
                        'modifiers_total' => $item['modifiers_total'] ?? 0,
                        'cost_price' => $item['cost_price'] ?? 0,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'subtotal' => $item['subtotal'],
                        'modifiers' => $item['modifiers'] ?? null,
                        'item_notes' => $item['notes'] ?? null,
                    ]);
                }

                // Create payments
                foreach ($validated['payments'] as $payment) {
                    $paymentMethod = PaymentMethod::find($payment['payment_method_id']);
                    $chargeAmount = $paymentMethod ? $paymentMethod->calculateCharge($payment['amount']) : 0;

                    TransactionPayment::create([
                        'transaction_id' => $transaction->id,
                        'payment_method_id' => $payment['payment_method_id'],
                        'amount' => $payment['amount'],
                        'charge_amount' => $chargeAmount,
                        'reference_number' => $payment['reference_number'] ?? null,
                    ]);
                }

                // Complete transaction
                $transaction->complete();

                return $transaction;
            });

            $transaction->load([
                'items.product:id,name,image',
                'payments.paymentMethod:id,name,type',
                'customer:id,name,phone',
                'user:id,name',
            ]);

            return $this->created($this->formatOrderDetail($transaction), 'Order completed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create order: '.$e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/orders/{order}',
        summary: 'Get order detail',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order detail with items and payments'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function show(string $orderId): JsonResponse
    {
        $transaction = Transaction::where('id', $orderId)
            ->where('tenant_id', $this->tenantId())
            ->first();

        if (! $transaction) {
            return $this->notFound('Order not found');
        }

        $transaction->load([
            'items.product:id,name,image',
            'items.productVariant:id,name',
            'payments.paymentMethod:id,name,type,icon',
            'customer:id,name,phone,email',
            'user:id,name',
            'table:id,number,name',
            'outlet:id,name,code',
        ]);

        return $this->success($this->formatOrderDetail($transaction));
    }

    #[OA\Post(
        path: '/orders/{order}/void',
        summary: 'Void order',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Order voided successfully'),
            new OA\Response(response: 400, description: 'Cannot void this order'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function void(Request $request, string $orderId): JsonResponse
    {
        $transaction = Transaction::where('id', $orderId)
            ->where('tenant_id', $this->tenantId())
            ->first();

        if (! $transaction) {
            return $this->notFound('Order not found');
        }

        if (! $transaction->canVoid()) {
            return $this->error('This order cannot be voided.', 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($transaction, $validated) {
            $transaction->void();
            $transaction->update(['notes' => 'VOIDED: '.$validated['reason']]);
        });

        $transaction->refresh();

        return $this->success($this->formatOrderDetail($transaction), 'Order voided successfully');
    }

    #[OA\Post(
        path: '/orders/{order}/refund',
        summary: 'Refund order (partial or full)',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'reason', 'payment_method_id'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', minimum: 0.01),
                    new OA\Property(property: 'reason', type: 'string', maxLength: 500),
                    new OA\Property(property: 'payment_method_id', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Refund processed successfully'),
            new OA\Response(response: 400, description: 'Cannot refund or no active session'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function refund(Request $request, string $orderId): JsonResponse
    {
        $transaction = Transaction::where('id', $orderId)
            ->where('tenant_id', $this->tenantId())
            ->first();

        if (! $transaction) {
            return $this->notFound('Order not found');
        }

        if (! $transaction->canRefund()) {
            return $this->error('This order cannot be refunded.', 400);
        }

        $refundableAmount = $transaction->getRefundableAmount();
        if ($refundableAmount <= 0) {
            return $this->error('This order has been fully refunded.', 400);
        }

        $validated = $request->validate([
            'amount' => "required|numeric|min:0.01|max:{$refundableAmount}",
            'reason' => 'required|string|max:500',
            'payment_method_id' => 'required|uuid',
        ]);

        $session = PosSession::where('outlet_id', $transaction->outlet_id)
            ->where('user_id', $this->user()->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();

        if (! $session) {
            return $this->error('No active POS session.', 400);
        }

        try {
            $refundTransaction = DB::transaction(function () use ($transaction, $validated, $session) {
                $transactionNumber = $this->generateTransactionNumber($transaction->outlet_id, 'REF');

                $refund = Transaction::create([
                    'tenant_id' => $this->tenantId(),
                    'outlet_id' => $transaction->outlet_id,
                    'pos_session_id' => $session->id,
                    'customer_id' => $transaction->customer_id,
                    'user_id' => $this->user()->id,
                    'transaction_number' => $transactionNumber,
                    'type' => Transaction::TYPE_REFUND,
                    'original_transaction_id' => $transaction->id,
                    'order_type' => $transaction->order_type,
                    'subtotal' => $validated['amount'],
                    'grand_total' => $validated['amount'],
                    'payment_amount' => $validated['amount'],
                    'notes' => 'REFUND: '.$validated['reason'],
                    'status' => Transaction::STATUS_PENDING,
                ]);

                TransactionPayment::create([
                    'transaction_id' => $refund->id,
                    'payment_method_id' => $validated['payment_method_id'],
                    'amount' => $validated['amount'],
                ]);

                $refund->complete();

                return $refund;
            });

            return $this->success($this->formatOrderDetail($refundTransaction), 'Refund processed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to process refund: '.$e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/orders/{order}/receipt',
        summary: 'Get receipt data for printing',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Receipt data with outlet info'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function receipt(string $orderId): JsonResponse
    {
        $transaction = Transaction::where('id', $orderId)
            ->where('tenant_id', $this->tenantId())
            ->first();

        if (! $transaction) {
            return $this->notFound('Order not found');
        }

        $transaction->load([
            'items',
            'payments.paymentMethod:id,name,type',
            'customer:id,name,phone',
            'user:id,name',
            'outlet',
        ]);

        return $this->success([
            'transaction' => $this->formatOrderDetail($transaction),
            'outlet' => [
                'name' => $transaction->outlet->name,
                'address' => $transaction->outlet->address,
                'city' => $transaction->outlet->city,
                'phone' => $transaction->outlet->phone,
                'receipt_header' => $transaction->outlet->receipt_header,
                'receipt_footer' => $transaction->outlet->receipt_footer,
                'receipt_show_logo' => $transaction->outlet->receipt_show_logo,
            ],
            'print_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Calculate cart totals
     */
    private function calculateCart(array $data, $outlet): array
    {
        $tenant = $this->user()?->tenant;

        // Get tax settings with inheritance from tenant
        $taxEnabled = $outlet->tax_enabled ?? $tenant?->tax_enabled ?? false;
        $taxMode = $outlet->tax_mode ?? $tenant?->tax_mode ?? 'exclusive';
        $taxPercentage = (float) ($outlet->tax_percentage ?? $tenant?->tax_percentage ?? 0);
        $serviceChargeEnabled = $outlet->service_charge_enabled ?? $tenant?->service_charge_enabled ?? false;
        $serviceChargePercentage = (float) ($outlet->service_charge_percentage ?? $tenant?->service_charge_percentage ?? 0);

        $items = [];
        $itemsTotal = 0;

        foreach ($data['items'] as $item) {
            $product = Product::where('tenant_id', $this->tenantId())
                ->where('id', $item['product_id'])
                ->first();

            if (! $product) {
                continue;
            }

            $basePrice = $product->getPriceForOutlet($outlet->id);
            $variantPriceAdjustment = 0;
            $variantName = null;
            $variantSku = null;

            // Handle variant
            if (! empty($item['variant_id'])) {
                $variant = ProductVariant::find($item['variant_id']);
                if ($variant && $variant->product_id === $product->id) {
                    $basePrice = (float) $variant->price;
                    $variantPriceAdjustment = $basePrice - (float) $product->base_price;
                    $variantName = $variant->name;
                    $variantSku = $variant->sku;
                }
            }

            // Calculate modifiers total
            $modifiersTotal = 0;
            $modifierDetails = [];
            if (! empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $modifier) {
                    $modPrice = $modifier['price'] ?? 0;
                    $modQty = $modifier['quantity'] ?? 1;
                    $modifiersTotal += $modPrice * $modQty;
                    $modifierDetails[] = [
                        'id' => $modifier['id'] ?? null,
                        'name' => $modifier['name'] ?? '',
                        'price' => (float) $modPrice,
                        'quantity' => (int) $modQty,
                    ];
                }
            }

            $unitPrice = $basePrice + $modifiersTotal;
            $quantity = (float) $item['quantity'];
            $itemDiscount = (float) ($item['discount_amount'] ?? 0);
            $itemSubtotal = ($unitPrice * $quantity) - $itemDiscount;

            $items[] = [
                'product_id' => $product->id,
                'variant_id' => $item['variant_id'] ?? null,
                'name' => $variantName ? "{$product->name} - {$variantName}" : $product->name,
                'sku' => $variantSku ?? $product->sku,
                'quantity' => $quantity,
                'base_price' => (float) $product->base_price,
                'unit_price' => $unitPrice,
                'variant_price_adjustment' => $variantPriceAdjustment,
                'modifiers_total' => $modifiersTotal,
                'modifiers' => $modifierDetails ?: null,
                'cost_price' => (float) $product->cost_price,
                'discount_amount' => $itemDiscount,
                'subtotal' => $itemSubtotal,
                'notes' => $item['notes'] ?? null,
            ];

            $itemsTotal += $itemSubtotal;
        }

        // Calculate order discount
        $discountAmount = 0;
        if (! empty($data['discount_type']) && ! empty($data['discount_value'])) {
            if ($data['discount_type'] === 'percentage') {
                $discountAmount = ($itemsTotal * $data['discount_value']) / 100;
            } else {
                $discountAmount = min($data['discount_value'], $itemsTotal);
            }
        }

        $afterDiscount = $itemsTotal - $discountAmount;

        // Calculate tax based on mode
        $taxAmount = 0;
        $subtotal = $afterDiscount;

        if ($taxEnabled && $taxPercentage > 0) {
            if ($taxMode === 'inclusive') {
                // Tax is included in the price - extract it
                // Formula: basePrice = totalPrice / (1 + taxRate)
                // Tax = totalPrice - basePrice
                $taxRate = $taxPercentage / 100;
                $subtotal = round($afterDiscount / (1 + $taxRate), 2);
                $taxAmount = round($afterDiscount - $subtotal, 2);
            } else {
                // Tax is exclusive - add on top
                $subtotal = $afterDiscount;
                $taxAmount = round(($afterDiscount * $taxPercentage) / 100, 2);
            }
        }

        // Calculate service charge (only if enabled)
        $serviceChargeAmount = 0;
        if ($serviceChargeEnabled && $serviceChargePercentage > 0) {
            // Service charge is calculated on subtotal (base price before tax)
            $serviceChargeAmount = round(($subtotal * $serviceChargePercentage) / 100, 2);
        }

        // Calculate grand total with rounding
        if ($taxMode === 'inclusive') {
            // For inclusive: items already include tax, just add service charge
            $grandTotal = $afterDiscount + $serviceChargeAmount;
        } else {
            // For exclusive: add tax and service charge on top
            $grandTotal = $subtotal + $taxAmount + $serviceChargeAmount;
        }

        $roundedTotal = round($grandTotal / 100) * 100;
        $rounding = $roundedTotal - $grandTotal;

        return [
            'items' => $items,
            'items_count' => count($items),
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'after_discount' => round($afterDiscount, 2),
            'tax_enabled' => (bool) $taxEnabled,
            'tax_mode' => $taxMode,
            'tax_percentage' => $taxPercentage,
            'tax_amount' => round($taxAmount, 2),
            'service_charge_enabled' => (bool) $serviceChargeEnabled,
            'service_charge_percentage' => $serviceChargePercentage,
            'service_charge_amount' => round($serviceChargeAmount, 2),
            'rounding' => round($rounding, 2),
            'grand_total' => round($roundedTotal, 2),
        ];
    }

    /**
     * Generate unique transaction number
     */
    private function generateTransactionNumber(string $outletId, string $prefix = 'TRX'): string
    {
        $today = now()->format('Ymd');

        $lastTransaction = Transaction::where('outlet_id', $outletId)
            ->where('transaction_number', 'like', "{$prefix}{$today}%")
            ->orderByDesc('transaction_number')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$today}{$newNumber}";
    }

    /**
     * Format order for list view
     */
    private function formatOrderList(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'type' => $transaction->type,
            'order_type' => $transaction->order_type,
            'customer_name' => $transaction->customer?->name,
            'user_name' => $transaction->user?->name,
            'table_number' => $transaction->table?->number,
            'items_count' => $transaction->items_count ?? 0,
            'grand_total' => (float) $transaction->grand_total,
            'status' => $transaction->status,
            'created_at' => $transaction->created_at?->toIso8601String(),
            'completed_at' => $transaction->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Format order detail
     */
    private function formatOrderDetail(Transaction $transaction): array
    {
        $data = [
            'id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'type' => $transaction->type,
            'order_type' => $transaction->order_type,
            'outlet_id' => $transaction->outlet_id,
            'outlet_name' => $transaction->outlet?->name,
            'pos_session_id' => $transaction->pos_session_id,
            'table_id' => $transaction->table_id,
            'table_number' => $transaction->table?->number,
            'customer' => $transaction->customer ? [
                'id' => $transaction->customer->id,
                'name' => $transaction->customer->name,
                'phone' => $transaction->customer->phone,
                'email' => $transaction->customer->email ?? null,
            ] : null,
            'user' => [
                'id' => $transaction->user?->id,
                'name' => $transaction->user?->name,
            ],
            'subtotal' => (float) $transaction->subtotal,
            'discount_amount' => (float) $transaction->discount_amount,
            'tax_percentage' => (float) $transaction->tax_percentage,
            'tax_amount' => (float) $transaction->tax_amount,
            'service_charge_percentage' => (float) $transaction->service_charge_percentage,
            'service_charge_amount' => (float) $transaction->service_charge_amount,
            'rounding' => (float) $transaction->rounding,
            'grand_total' => (float) $transaction->grand_total,
            'payment_amount' => (float) $transaction->payment_amount,
            'change_amount' => (float) $transaction->change_amount,
            'notes' => $transaction->notes,
            'status' => $transaction->status,
            'created_at' => $transaction->created_at?->toIso8601String(),
            'completed_at' => $transaction->completed_at?->toIso8601String(),
        ];

        // Include items
        if ($transaction->relationLoaded('items')) {
            $data['items'] = $transaction->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? $item->item_name,
                'product_image' => $item->product?->image,
                'variant_id' => $item->product_variant_id,
                'variant_name' => $item->productVariant?->name,
                'item_name' => $item->item_name,
                'sku' => $item->item_sku,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
                'subtotal' => (float) $item->subtotal,
                'modifiers' => $item->modifiers,
                'notes' => $item->item_notes,
            ])->toArray();
        }

        // Include payments
        if ($transaction->relationLoaded('payments')) {
            $data['payments'] = $transaction->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_method_id' => $payment->payment_method_id,
                'payment_method_name' => $payment->paymentMethod?->name,
                'payment_method_type' => $payment->paymentMethod?->type,
                'amount' => (float) $payment->amount,
                'charge_amount' => (float) $payment->charge_amount,
                'reference_number' => $payment->reference_number,
            ])->toArray();
        }

        return $data;
    }
}
