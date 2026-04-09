<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\QrOrder;
use App\Services\XenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function __construct(protected XenditService $xenditService) {}

    /**
     * Handle invoice callback from Xendit.
     */
    public function handleInvoice(Request $request): JsonResponse
    {
        $callbackToken = $request->header('x-callback-token', '');

        if (! $this->xenditService->verifyWebhookSignature($callbackToken)) {
            Log::warning('Xendit webhook: invalid callback token');

            return response()->json(['error' => 'Invalid callback token'], 401);
        }

        $payload = $request->all();

        Log::info('Xendit invoice webhook received', [
            'id' => $payload['id'] ?? null,
            'status' => $payload['status'] ?? null,
            'external_id' => $payload['external_id'] ?? null,
        ]);

        try {
            $result = $this->xenditService->handlePaymentCallback($payload);

            if ($result) {
                $responseData = [
                    'success' => true,
                    'message' => 'Webhook processed',
                ];

                if ($result instanceof QrOrder) {
                    $responseData['qr_order_id'] = $result->id;
                } else {
                    $responseData['invoice_id'] = $result->id;
                }

                return response()->json($responseData);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Xendit webhook processing error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Processing error',
            ], 500);
        }
    }
}
