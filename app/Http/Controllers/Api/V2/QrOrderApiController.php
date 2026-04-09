<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\QrOrder;
use App\Services\QrOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class QrOrderApiController extends Controller
{
    public function __construct(
        private QrOrderService $qrOrderService
    ) {}

    #[OA\Get(
        path: '/api/v2/qr-orders',
        summary: 'List QR orders for current outlet',
        security: [['sanctum' => []]],
        tags: ['QR Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'waiting_payment', 'paid', 'pay_at_counter', 'processing', 'completed', 'cancelled'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of QR orders'),
            new OA\Response(response: 400, description: 'No outlet selected'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $query = QrOrder::query()
            ->where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->with(['items', 'table'])
            ->withCount('items');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->active();
        }

        $perPage = min($request->get('per_page', 20), 50);
        $orders = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->successWithPagination(
            $orders->map(fn (QrOrder $order) => $this->formatOrder($order)),
            $this->paginationMeta($orders),
        );
    }

    #[OA\Get(
        path: '/api/v2/qr-orders/count',
        summary: 'Count active QR orders for badge display',
        security: [['sanctum' => []]],
        tags: ['QR Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Count of active QR orders'),
        ]
    )]
    public function count(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $counts = [
            'total' => QrOrder::where('tenant_id', $this->tenantId())
                ->where('outlet_id', $outletId)
                ->active()
                ->count(),
            'pending' => QrOrder::where('tenant_id', $this->tenantId())
                ->where('outlet_id', $outletId)
                ->where('status', QrOrder::STATUS_PENDING)
                ->count(),
            'pay_at_counter' => QrOrder::where('tenant_id', $this->tenantId())
                ->where('outlet_id', $outletId)
                ->where('status', QrOrder::STATUS_PAY_AT_COUNTER)
                ->count(),
            'processing' => QrOrder::where('tenant_id', $this->tenantId())
                ->where('outlet_id', $outletId)
                ->where('status', QrOrder::STATUS_PROCESSING)
                ->count(),
        ];

        return $this->success($counts);
    }

    #[OA\Get(
        path: '/api/v2/qr-orders/{qrOrder}',
        summary: 'Get QR order detail',
        security: [['sanctum' => []]],
        tags: ['QR Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'qrOrder', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'QR order detail'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function show(Request $request, QrOrder $qrOrder): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        if ($qrOrder->tenant_id !== $this->tenantId() || $qrOrder->outlet_id !== $outletId) {
            return $this->notFound('QR order not found.');
        }

        $qrOrder->load(['items', 'table']);

        return $this->success($this->formatOrder($qrOrder));
    }

    #[OA\Post(
        path: '/api/v2/qr-orders/{qrOrder}/approve',
        summary: 'Approve QR order (create transaction + send to kitchen)',
        security: [['sanctum' => []]],
        tags: ['QR Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'qrOrder', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order approved'),
            new OA\Response(response: 400, description: 'Cannot approve this order'),
        ]
    )]
    public function approve(Request $request, QrOrder $qrOrder): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        if ($qrOrder->tenant_id !== $this->tenantId() || $qrOrder->outlet_id !== $outletId) {
            return $this->notFound('QR order not found.');
        }

        if (! in_array($qrOrder->status, [QrOrder::STATUS_PAY_AT_COUNTER, QrOrder::STATUS_PAID])) {
            return $this->error('Order cannot be approved in current status: '.$qrOrder->status, 400);
        }

        $request->validate([
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        try {
            $qrOrder = $this->qrOrderService->approveOrder($qrOrder, $request->payment_method_id);
            $qrOrder->load(['items', 'table']);

            return $this->success($this->formatOrder($qrOrder), 'Order approved and sent to kitchen.');
        } catch (\Exception $e) {
            return $this->error('Failed to approve order: '.$e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/api/v2/qr-orders/{qrOrder}/complete',
        summary: 'Complete pay-at-counter QR order',
        security: [['sanctum' => []]],
        tags: ['QR Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'qrOrder', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order completed'),
            new OA\Response(response: 400, description: 'Cannot complete this order'),
        ]
    )]
    public function complete(Request $request, QrOrder $qrOrder): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        if ($qrOrder->tenant_id !== $this->tenantId() || $qrOrder->outlet_id !== $outletId) {
            return $this->notFound('QR order not found.');
        }

        if ($qrOrder->status !== QrOrder::STATUS_PROCESSING) {
            return $this->error('Order cannot be completed in current status: '.$qrOrder->status, 400);
        }

        try {
            $qrOrder = $this->qrOrderService->completePayAtCounterOrder($qrOrder);
            $qrOrder->load(['items', 'table']);

            return $this->success($this->formatOrder($qrOrder), 'Order completed.');
        } catch (\Exception $e) {
            return $this->error('Failed to complete order: '.$e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/api/v2/qr-orders/{qrOrder}/cancel',
        summary: 'Cancel a QR order',
        security: [['sanctum' => []]],
        tags: ['QR Orders'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'qrOrder', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order cancelled'),
            new OA\Response(response: 400, description: 'Cannot cancel this order'),
        ]
    )]
    public function cancel(Request $request, QrOrder $qrOrder): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        if ($qrOrder->tenant_id !== $this->tenantId() || $qrOrder->outlet_id !== $outletId) {
            return $this->notFound('QR order not found.');
        }

        if (! $qrOrder->canCancel()) {
            return $this->error('Order cannot be cancelled in current status: '.$qrOrder->status, 400);
        }

        try {
            $this->qrOrderService->cancelOrder($qrOrder);
            $qrOrder->refresh();
            $qrOrder->load(['items', 'table']);

            return $this->success($this->formatOrder($qrOrder), 'Order cancelled.');
        } catch (\Exception $e) {
            return $this->error('Failed to cancel order: '.$e->getMessage(), 500);
        }
    }

    /**
     * Format a QR order for API response.
     */
    private function formatOrder(QrOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'table_name' => $order->table?->name ?? $order->table?->number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'items_count' => $order->items_count ?? $order->items->count(),
            'subtotal' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_amount,
            'service_charge_amount' => (float) $order->service_charge_amount,
            'grand_total' => (float) $order->grand_total,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toISOString(),
            'items' => $order->relationLoaded('items')
                ? $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'item_sku' => $item->item_sku,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'modifiers_total' => (float) ($item->modifiers_total ?? 0),
                    'subtotal' => (float) $item->subtotal,
                    'modifiers' => $item->modifiers,
                    'notes' => $item->item_notes,
                ])->toArray()
                : [],
        ];
    }
}
