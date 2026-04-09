<?php

namespace App\Http\Controllers\QrMenu;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrMenu\PlaceQrOrderRequest;
use App\Models\QrOrder;
use App\Services\QrOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class QrMenuController extends Controller
{
    public function __construct(private QrOrderService $qrOrderService) {}

    /**
     * Show the customer-facing QR menu page.
     */
    public function show(string $qrToken): View
    {
        $menuData = $this->qrOrderService->getMenuData($qrToken);

        return view('qr-menu.show', $menuData);
    }

    /**
     * Place a new QR order.
     */
    public function placeOrder(PlaceQrOrderRequest $request, string $qrToken): JsonResponse
    {
        $menuData = $this->qrOrderService->getMenuData($qrToken);
        $table = $menuData['table'];

        try {
            $qrOrder = $this->qrOrderService->createOrder(
                $table,
                $request->items,
                $request->customer_name,
                $request->customer_phone,
                $request->notes
            );

            $paymentMethod = $request->payment_method;

            if ($paymentMethod === 'qris') {
                $qrOrder = $this->qrOrderService->initiateQrisPayment($qrOrder);

                return response()->json([
                    'success' => true,
                    'message' => 'Order created. Redirecting to payment.',
                    'order' => [
                        'id' => $qrOrder->id,
                        'order_number' => $qrOrder->order_number,
                        'grand_total' => $qrOrder->grand_total,
                        'payment_url' => $qrOrder->xendit_invoice_url,
                        'status_url' => url("/qr/order/{$qrOrder->id}/status"),
                    ],
                ]);
            }

            // Pay at counter
            $qrOrder = $this->qrOrderService->selectPayAtCounter($qrOrder);

            return response()->json([
                'success' => true,
                'message' => 'Order placed. Please pay at the counter.',
                'order' => [
                    'id' => $qrOrder->id,
                    'order_number' => $qrOrder->order_number,
                    'grand_total' => $qrOrder->grand_total,
                    'status_url' => url("/qr/order/{$qrOrder->id}/status"),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Show the order status page (confirmation page for customers).
     */
    public function orderStatus(QrOrder $qrOrder): View
    {
        $qrOrder->load(['items', 'table', 'outlet']);

        return view('qr-menu.order-status', [
            'order' => $qrOrder,
        ]);
    }

    /**
     * JSON endpoint for customer status polling.
     */
    public function orderStatusJson(QrOrder $qrOrder): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->qrOrderService->getOrderStatus($qrOrder),
        ]);
    }
}
