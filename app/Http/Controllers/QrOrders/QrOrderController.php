<?php

namespace App\Http\Controllers\QrOrders;

use App\Http\Controllers\Controller;
use App\Models\QrOrder;
use App\Models\Table;
use App\Services\QrOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class QrOrderController extends Controller
{
    public function __construct(private QrOrderService $qrOrderService) {}

    /**
     * List QR orders for the current outlet.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $outletId = $user->defaultOutlet()?->id;

        $activeOrders = QrOrder::where('outlet_id', $outletId)
            ->active()
            ->with(['items', 'table'])
            ->orderByDesc('created_at')
            ->get();

        $completedOrders = QrOrder::where('outlet_id', $outletId)
            ->whereIn('status', [QrOrder::STATUS_COMPLETED, QrOrder::STATUS_CANCELLED, QrOrder::STATUS_EXPIRED])
            ->with(['items', 'table'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $tables = Table::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('number')
            ->get();

        return view('qr-orders.index', [
            'activeOrders' => $activeOrders,
            'completedOrders' => $completedOrders,
            'tables' => $tables,
        ]);
    }

    /**
     * Show a specific QR order detail.
     */
    public function show(QrOrder $qrOrder): View
    {
        $this->authorizeOrder($qrOrder);
        $qrOrder->load(['items', 'table', 'outlet', 'transaction']);

        return view('qr-orders.show', [
            'order' => $qrOrder,
        ]);
    }

    /**
     * Cancel a QR order.
     */
    public function cancel(QrOrder $qrOrder): JsonResponse
    {
        $this->authorizeOrder($qrOrder);

        if (! $qrOrder->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled.',
            ], 400);
        }

        $this->qrOrderService->cancelOrder($qrOrder);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
        ]);
    }

    /**
     * Approve a QR order and send to kitchen.
     */
    public function approve(QrOrder $qrOrder): JsonResponse
    {
        $this->authorizeOrder($qrOrder);

        if (! in_array($qrOrder->status, [QrOrder::STATUS_PAY_AT_COUNTER, QrOrder::STATUS_PENDING])) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be approved.',
            ], 400);
        }

        $qrOrder = $this->qrOrderService->approveOrder($qrOrder);

        return response()->json([
            'success' => true,
            'message' => 'Order approved and sent to kitchen.',
            'order' => [
                'id' => $qrOrder->id,
                'status' => $qrOrder->status,
            ],
        ]);
    }

    /**
     * Complete a pay-at-counter order (cashier creates Transaction).
     */
    public function complete(QrOrder $qrOrder): JsonResponse
    {
        $this->authorizeOrder($qrOrder);

        if (! in_array($qrOrder->status, [QrOrder::STATUS_PROCESSING, QrOrder::STATUS_PAY_AT_COUNTER])) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved/processing orders can be completed.',
            ], 400);
        }

        $qrOrder = $this->qrOrderService->completePayAtCounterOrder($qrOrder);

        return response()->json([
            'success' => true,
            'message' => 'Order completed successfully.',
            'order' => [
                'id' => $qrOrder->id,
                'status' => $qrOrder->status,
                'transaction_id' => $qrOrder->transaction_id,
            ],
        ]);
    }

    /**
     * Poll pending orders (for dashboard auto-refresh).
     */
    public function pollPending(Request $request): JsonResponse
    {
        $user = auth()->user();
        $outletId = $user->defaultOutlet()?->id;

        $orders = $this->qrOrderService->getPendingOrdersForOutlet($outletId);

        return response()->json([
            'success' => true,
            'data' => $orders,
            'count' => $orders->count(),
        ]);
    }

    /**
     * Generate QR code for a table.
     */
    public function generateQr(Table $table): JsonResponse
    {
        $this->authorizeTable($table);

        $token = $this->qrOrderService->generateQrForTable($table);

        return response()->json([
            'success' => true,
            'message' => 'QR code generated successfully.',
            'qr_token' => $token,
            'qr_url' => $table->getQrMenuUrl(),
        ]);
    }

    /**
     * Revoke QR code for a table.
     */
    public function revokeQr(Table $table): JsonResponse
    {
        $this->authorizeTable($table);

        $this->qrOrderService->revokeQrForTable($table);

        return response()->json([
            'success' => true,
            'message' => 'QR code revoked successfully.',
        ]);
    }

    /**
     * Download QR code as SVG.
     */
    public function downloadQr(Table $table): Response
    {
        $this->authorizeTable($table);

        if (! $table->hasQrCode()) {
            abort(404, 'No QR code generated for this table.');
        }

        $qrCode = QrCode::format('svg')
            ->size(400)
            ->errorCorrection('H')
            ->generate($table->getQrMenuUrl());

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', "attachment; filename=\"qr-table-{$table->number}.svg\"");
    }

    /**
     * Print view with QR code and outlet/table info.
     */
    public function printQr(Table $table): View
    {
        $this->authorizeTable($table);

        if (! $table->hasQrCode()) {
            abort(404, 'No QR code generated for this table.');
        }

        $table->load('outlet');

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($table->getQrMenuUrl());

        return view('qr-orders.print-qr', [
            'table' => $table,
            'qrCode' => $qrCode,
        ]);
    }

    protected function authorizeOrder(QrOrder $qrOrder): void
    {
        if ($qrOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }

    protected function authorizeTable(Table $table): void
    {
        if ($table->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
