<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PosSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class SessionController extends Controller
{
    #[OA\Get(
        path: '/sessions/current',
        summary: 'Get current active POS session',
        security: [['sanctum' => []]],
        tags: ['Sessions'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Active session with stats or null'),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function current(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected. Please set X-Outlet-Id header.', 400);
        }

        $session = PosSession::query()
            ->where('outlet_id', $outletId)
            ->where('user_id', $this->user()->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->latest('opened_at')
            ->first();

        if (! $session) {
            return $this->success(null, 'No active session');
        }

        return $this->success($this->formatSession($session, withStats: true));
    }

    #[OA\Post(
        path: '/sessions/open',
        summary: 'Open new POS session',
        security: [['sanctum' => []]],
        tags: ['Sessions'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['opening_cash'],
                properties: [
                    new OA\Property(property: 'opening_cash', type: 'number', minimum: 0),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Session opened successfully'),
            new OA\Response(response: 400, description: 'Existing open session or no outlet'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function open(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        // Check if user already has an open session
        $existingSession = PosSession::query()
            ->where('outlet_id', $outletId)
            ->where('user_id', $this->user()->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();

        if ($existingSession) {
            return $this->error('You already have an open session. Please close it first.', 400);
        }

        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $session = DB::transaction(function () use ($outletId, $validated) {
            // Generate session number
            $sessionNumber = $this->generateSessionNumber($outletId);
            $tenantId = $this->tenantId();

            return PosSession::create([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'user_id' => $this->user()->id,
                'session_number' => $sessionNumber,
                'opening_cash' => $validated['opening_cash'],
                'opening_notes' => $validated['notes'] ?? null,
                'opened_at' => now(),
                'status' => PosSession::STATUS_OPEN,
            ]);
        });

        return $this->created($this->formatSession($session), 'Session opened successfully');
    }

    #[OA\Post(
        path: '/sessions/close',
        summary: 'Close current POS session',
        security: [['sanctum' => []]],
        tags: ['Sessions'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['closing_cash'],
                properties: [
                    new OA\Property(property: 'closing_cash', type: 'number', minimum: 0),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Session closed with cash summary'),
            new OA\Response(response: 400, description: 'No active session or no outlet'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function close(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $session = PosSession::query()
            ->where('outlet_id', $outletId)
            ->where('user_id', $this->user()->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();

        if (! $session) {
            return $this->error('No active session to close.', 400);
        }

        $validated = $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($session, $validated) {
            $session->close(
                closingCash: $validated['closing_cash'],
                closedBy: $this->user()->id,
                notes: $validated['notes'] ?? null
            );
        });

        $session->refresh();

        return $this->success($this->formatSession($session, withStats: true), 'Session closed successfully');
    }

    #[OA\Get(
        path: '/sessions/{session}/report',
        summary: 'Get comprehensive session report',
        security: [['sanctum' => []]],
        tags: ['Sessions'],
        parameters: [
            new OA\Parameter(name: 'session', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Session report with payment breakdown'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Session not found'),
        ]
    )]
    public function report(string $sessionId): JsonResponse
    {
        $session = PosSession::with(['user:id,name', 'closedByUser:id,name', 'outlet:id,name'])
            ->find($sessionId);

        if (! $session || ! $this->canAccessSession($session)) {
            return $this->notFound('Session not found');
        }

        $report = $this->generateReport($session);

        return $this->success($report);
    }

    /**
     * Check if user can access session
     */
    private function canAccessSession(PosSession $session): bool
    {
        // Managers can view all sessions, cashiers only their own
        if ($this->hasPermission('pos.sessions.view_all')) {
            return $session->outlet && $this->canAccessOutlet($session->outlet_id);
        }

        return $session->user_id === $this->user()->id;
    }

    /**
     * Generate unique session number
     */
    private function generateSessionNumber(string $outletId): string
    {
        $today = now()->format('Ymd');
        $prefix = 'SES';

        $lastSession = PosSession::where('outlet_id', $outletId)
            ->where('session_number', 'like', "{$prefix}{$today}%")
            ->orderByDesc('session_number')
            ->first();

        if ($lastSession) {
            $lastNumber = (int) substr($lastSession->session_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$today}{$newNumber}";
    }

    /**
     * Format session data
     */
    private function formatSession(PosSession $session, bool $withStats = false): array
    {
        $data = [
            'id' => $session->id,
            'outlet_id' => $session->outlet_id,
            'user_id' => $session->user_id,
            'user_name' => $session->user?->name,
            'session_number' => $session->session_number,
            'opening_cash' => (float) $session->opening_cash,
            'closing_cash' => $session->closing_cash ? (float) $session->closing_cash : null,
            'expected_cash' => $session->expected_cash ? (float) $session->expected_cash : null,
            'cash_difference' => $session->cash_difference ? (float) $session->cash_difference : null,
            'opening_notes' => $session->opening_notes,
            'closing_notes' => $session->closing_notes,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
            'closed_by' => $session->closed_by,
            'closed_by_name' => $session->closedByUser?->name,
            'status' => $session->status,
            'created_at' => $session->created_at?->toIso8601String(),
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];

        if ($withStats) {
            $data['stats'] = [
                'total_sales' => (float) $session->getTotalSales(),
                'cash_sales' => (float) $session->getCashSales(),
                'transaction_count' => $session->getTransactionCount(),
                'expected_cash_now' => (float) $session->getExpectedCash(),
            ];
        }

        return $data;
    }

    /**
     * Generate comprehensive session report
     */
    private function generateReport(PosSession $session): array
    {
        $transactions = $session->transactions()
            ->with(['items.product', 'payments.paymentMethod'])
            ->get();

        // Group by transaction type
        $salesTransactions = $transactions->where('type', 'sale');
        $refundTransactions = $transactions->where('type', 'refund');

        // Group by payment method
        $paymentSummary = [];
        foreach ($transactions->where('status', 'completed') as $transaction) {
            foreach ($transaction->payments as $payment) {
                $methodName = $payment->paymentMethod->name ?? 'Unknown';
                $methodType = $payment->paymentMethod->type ?? 'unknown';

                if (! isset($paymentSummary[$methodType])) {
                    $paymentSummary[$methodType] = [
                        'type' => $methodType,
                        'name' => $methodName,
                        'count' => 0,
                        'amount' => 0,
                    ];
                }

                $paymentSummary[$methodType]['count']++;
                $paymentSummary[$methodType]['amount'] += (float) $payment->amount;
            }
        }

        // Group by status
        $statusSummary = $transactions->groupBy('status')->map(fn ($group) => [
            'count' => $group->count(),
            'total' => (float) $group->sum('grand_total'),
        ]);

        return [
            'session' => $this->formatSession($session),
            'summary' => [
                'total_transactions' => $transactions->count(),
                'sales_count' => $salesTransactions->count(),
                'refund_count' => $refundTransactions->count(),
                'gross_sales' => (float) $salesTransactions->where('status', 'completed')->sum('grand_total'),
                'total_refunds' => (float) $refundTransactions->where('status', 'completed')->sum('grand_total'),
                'net_sales' => (float) ($salesTransactions->where('status', 'completed')->sum('grand_total') - $refundTransactions->where('status', 'completed')->sum('grand_total')),
                'total_discount' => (float) $transactions->where('status', 'completed')->sum('discount_amount'),
                'total_tax' => (float) $transactions->where('status', 'completed')->sum('tax_amount'),
                'total_service_charge' => (float) $transactions->where('status', 'completed')->sum('service_charge_amount'),
            ],
            'cash_summary' => [
                'opening_cash' => (float) $session->opening_cash,
                'cash_sales' => (float) $session->getCashSales(),
                'cash_refunds' => (float) $session->getCashRefunds(),
                'expected_cash' => (float) $session->getExpectedCash(),
                'closing_cash' => $session->closing_cash ? (float) $session->closing_cash : null,
                'difference' => $session->cash_difference ? (float) $session->cash_difference : null,
            ],
            'payment_methods' => array_values($paymentSummary),
            'status_breakdown' => $statusSummary,
        ];
    }
}
