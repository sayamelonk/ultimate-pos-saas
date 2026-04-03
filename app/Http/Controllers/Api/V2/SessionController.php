<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\CashDrawerLog;
use App\Models\PosSession;
use App\Models\Transaction;
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
            $sessionNumber = $this->generateSessionNumber($outletId);

            $session = PosSession::create([
                'outlet_id' => $outletId,
                'user_id' => $this->user()->id,
                'session_number' => $sessionNumber,
                'opening_cash' => $validated['opening_cash'],
                'opening_notes' => $validated['notes'] ?? null,
                'opened_at' => now(),
                'status' => PosSession::STATUS_OPEN,
            ]);

            // Create opening cash log
            CashDrawerLog::create([
                'tenant_id' => $this->tenantId(),
                'outlet_id' => $outletId,
                'pos_session_id' => $session->id,
                'user_id' => $this->user()->id,
                'type' => CashDrawerLog::TYPE_OPENING,
                'amount' => $validated['opening_cash'],
                'balance_before' => 0,
                'balance_after' => $validated['opening_cash'],
                'reason' => 'Opening cash',
            ]);

            return $session;
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
        path: '/sessions/history',
        summary: 'Get session history for outlet',
        security: [['sanctum' => []]],
        tags: ['Sessions'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of sessions'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function history(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->success([]);
        }

        $query = PosSession::query()
            ->where('outlet_id', $outletId)
            ->with('user:id,name')
            ->orderByDesc('opened_at');

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('opened_at', $request->input('date'));
        } elseif ($request->has('from') && $request->has('to')) {
            $query->whereBetween('opened_at', [$request->input('from'), $request->input('to')]);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $sessions = $query->paginate($perPage);

        $data = $sessions->map(fn ($session) => $this->formatSession($session));

        return $this->successWithPagination($data, $this->paginationMeta($sessions));
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

    #[OA\Get(
        path: '/sessions/active-any',
        summary: 'Check if any session is active at outlet',
        security: [['sanctum' => []]],
        tags: ['Sessions'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Active session status'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function activeAny(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->success([
                'has_active_session' => false,
                'session' => null,
            ]);
        }

        $session = PosSession::query()
            ->where('outlet_id', $outletId)
            ->where('status', PosSession::STATUS_OPEN)
            ->with('user:id,name')
            ->latest('opened_at')
            ->first();

        return $this->success([
            'has_active_session' => $session !== null,
            'session' => $session ? [
                'id' => $session->id,
                'session_number' => $session->session_number,
                'user_name' => $session->user?->name,
                'opened_at' => $session->opened_at?->toIso8601String(),
            ] : null,
        ]);
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
                'expected_cash' => (float) $session->getExpectedCash(),
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
        $salesTransactions = $transactions->where('type', Transaction::TYPE_SALE);
        $refundTransactions = $transactions->where('type', Transaction::TYPE_REFUND);

        // Group by payment method
        $paymentSummary = [];
        foreach ($transactions->where('status', Transaction::STATUS_COMPLETED) as $transaction) {
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
                'gross_sales' => (float) $salesTransactions->where('status', Transaction::STATUS_COMPLETED)->sum('grand_total'),
                'total_refunds' => (float) $refundTransactions->where('status', Transaction::STATUS_COMPLETED)->sum('grand_total'),
                'net_sales' => (float) ($salesTransactions->where('status', Transaction::STATUS_COMPLETED)->sum('grand_total') - $refundTransactions->where('status', Transaction::STATUS_COMPLETED)->sum('grand_total')),
                'total_discount' => (float) $transactions->where('status', Transaction::STATUS_COMPLETED)->sum('discount_amount'),
                'total_tax' => (float) $transactions->where('status', Transaction::STATUS_COMPLETED)->sum('tax_amount'),
                'total_service_charge' => (float) $transactions->where('status', Transaction::STATUS_COMPLETED)->sum('service_charge_amount'),
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
