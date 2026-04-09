<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\CashDrawerLog;
use App\Models\PosSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class CashDrawerController extends Controller
{
    /**
     * Get cash drawer status for current session
     */
    #[OA\Get(
        path: '/cash-drawer/status',
        summary: 'Get cash drawer status',
        security: [['sanctum' => []]],
        tags: ['Cash Drawer'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cash drawer status'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function status(Request $request): JsonResponse
    {
        $session = $this->getActiveSession($request);

        if (! $session) {
            return $this->success(null);
        }

        $logs = CashDrawerLog::where('pos_session_id', $session->id)->get();

        $cashInTotal = $logs->where('type', CashDrawerLog::TYPE_CASH_IN)->sum('amount');
        $cashOutTotal = $logs->where('type', CashDrawerLog::TYPE_CASH_OUT)->sum('amount');
        $cashSales = $logs->where('type', CashDrawerLog::TYPE_SALE)->sum('amount');
        $cashRefunds = $logs->where('type', CashDrawerLog::TYPE_REFUND)->sum('amount');

        $currentBalance = $session->opening_cash + $cashInTotal + $cashSales - $cashOutTotal - $cashRefunds;
        $expectedCash = $session->opening_cash + $cashSales - $cashRefunds + $cashInTotal - $cashOutTotal;

        return $this->success([
            'session_id' => $session->id,
            'session_number' => $session->session_number,
            'opening_cash' => (float) $session->opening_cash,
            'current_balance' => $currentBalance,
            'cash_sales' => $cashSales,
            'cash_refunds' => $cashRefunds,
            'cash_in_total' => $cashInTotal,
            'cash_out_total' => $cashOutTotal,
            'expected_cash' => $expectedCash,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'status' => $session->status,
        ]);
    }

    /**
     * Get cash drawer logs
     */
    #[OA\Get(
        path: '/cash-drawer/logs',
        summary: 'Get cash drawer logs',
        security: [['sanctum' => []]],
        tags: ['Cash Drawer'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'session_id', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of cash drawer logs'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logs(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->success([], 'No outlet selected');
        }

        $query = CashDrawerLog::where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outlet->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc');

        if ($request->has('session_id')) {
            $query->where('pos_session_id', $request->input('session_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('date')) {
            $date = Carbon::parse($request->input('date'));
            $query->whereDate('created_at', $date);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $logs = $query->paginate($perPage);

        $data = $logs->map(fn ($log) => [
            'id' => $log->id,
            'type' => $log->type,
            'type_label' => CashDrawerLog::getTypeLabel($log->type),
            'amount' => (float) $log->amount,
            'balance_before' => (float) $log->balance_before,
            'balance_after' => (float) $log->balance_after,
            'reference' => $log->reference,
            'reason' => $log->reason,
            'user_name' => $log->user?->name,
            'created_at' => $log->created_at?->toIso8601String(),
        ]);

        return $this->successWithPagination($data, $this->paginationMeta($logs));
    }

    /**
     * Cash in (add cash to drawer)
     */
    #[OA\Post(
        path: '/cash-drawer/cash-in',
        summary: 'Add cash to drawer',
        security: [['sanctum' => []]],
        tags: ['Cash Drawer'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 50000),
                    new OA\Property(property: 'reason', type: 'string', example: 'Change refill'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cash in successful'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error or no active session'),
        ]
    )]
    public function cashIn(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $session = $this->getActiveSession($request);

        if (! $session) {
            return $this->error('No active session found', 422);
        }

        $currentBalance = $this->getCurrentBalance($session);

        $log = DB::transaction(function () use ($request, $session, $currentBalance) {
            return CashDrawerLog::create([
                'tenant_id' => $this->tenantId(),
                'outlet_id' => $session->outlet_id,
                'pos_session_id' => $session->id,
                'user_id' => $this->user()->id,
                'type' => CashDrawerLog::TYPE_CASH_IN,
                'amount' => $request->input('amount'),
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance + $request->input('amount'),
                'reason' => $request->input('reason'),
            ]);
        });

        return $this->success([
            'id' => $log->id,
            'type' => $log->type,
            'amount' => (float) $log->amount,
            'balance_before' => (float) $log->balance_before,
            'balance_after' => (float) $log->balance_after,
            'reason' => $log->reason,
        ], 'Cash in successful');
    }

    /**
     * Cash out (remove cash from drawer)
     */
    #[OA\Post(
        path: '/cash-drawer/cash-out',
        summary: 'Remove cash from drawer',
        security: [['sanctum' => []]],
        tags: ['Cash Drawer'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'reason'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 30000),
                    new OA\Property(property: 'reason', type: 'string', example: 'Bank deposit'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cash out successful'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error or no active session'),
        ]
    )]
    public function cashOut(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $session = $this->getActiveSession($request);

        if (! $session) {
            return $this->error('No active session found', 422);
        }

        $currentBalance = $this->getCurrentBalance($session);

        if ($request->input('amount') > $currentBalance) {
            return $this->validationError([
                'amount' => ['Insufficient cash in drawer. Current balance: '.number_format($currentBalance, 2)],
            ]);
        }

        $log = DB::transaction(function () use ($request, $session, $currentBalance) {
            return CashDrawerLog::create([
                'tenant_id' => $this->tenantId(),
                'outlet_id' => $session->outlet_id,
                'pos_session_id' => $session->id,
                'user_id' => $this->user()->id,
                'type' => CashDrawerLog::TYPE_CASH_OUT,
                'amount' => $request->input('amount'),
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance - $request->input('amount'),
                'reason' => $request->input('reason'),
            ]);
        });

        return $this->success([
            'id' => $log->id,
            'type' => $log->type,
            'amount' => (float) $log->amount,
            'balance_before' => (float) $log->balance_before,
            'balance_after' => (float) $log->balance_after,
            'reason' => $log->reason,
        ], 'Cash out successful');
    }

    /**
     * Get current balance
     */
    #[OA\Get(
        path: '/cash-drawer/balance',
        summary: 'Get current cash drawer balance',
        security: [['sanctum' => []]],
        tags: ['Cash Drawer'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Current balance'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function balance(Request $request): JsonResponse
    {
        $session = $this->getActiveSession($request);

        if (! $session) {
            return $this->success([
                'current_balance' => 0,
                'opening_cash' => 0,
                'cash_in_total' => 0,
                'cash_out_total' => 0,
                'cash_sales' => 0,
                'cash_refunds' => 0,
            ]);
        }

        $logs = CashDrawerLog::where('pos_session_id', $session->id)->get();

        $cashInTotal = $logs->where('type', CashDrawerLog::TYPE_CASH_IN)->sum('amount');
        $cashOutTotal = $logs->where('type', CashDrawerLog::TYPE_CASH_OUT)->sum('amount');
        $cashSales = $logs->where('type', CashDrawerLog::TYPE_SALE)->sum('amount');
        $cashRefunds = $logs->where('type', CashDrawerLog::TYPE_REFUND)->sum('amount');

        $currentBalance = $session->opening_cash + $cashInTotal + $cashSales - $cashOutTotal - $cashRefunds;

        return $this->success([
            'current_balance' => $currentBalance,
            'opening_cash' => (float) $session->opening_cash,
            'cash_in_total' => $cashInTotal,
            'cash_out_total' => $cashOutTotal,
            'cash_sales' => $cashSales,
            'cash_refunds' => $cashRefunds,
        ]);
    }

    /**
     * Open cash drawer (trigger drawer open signal)
     */
    #[OA\Post(
        path: '/cash-drawer/open',
        summary: 'Open cash drawer',
        security: [['sanctum' => []]],
        tags: ['Cash Drawer'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cash drawer opened'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function open(Request $request): JsonResponse
    {
        // This endpoint is used by Flutter app to trigger physical cash drawer
        // The actual drawer opening is handled by the app's hardware integration
        // Here we just return success to confirm the request was received

        return $this->success(null, 'Cash drawer opened');
    }

    /**
     * Calculate current balance for a session
     */
    private function getCurrentBalance(PosSession $session): float
    {
        $logs = CashDrawerLog::where('pos_session_id', $session->id)->get();

        $cashInTotal = $logs->where('type', CashDrawerLog::TYPE_CASH_IN)->sum('amount');
        $cashOutTotal = $logs->where('type', CashDrawerLog::TYPE_CASH_OUT)->sum('amount');
        $cashSales = $logs->where('type', CashDrawerLog::TYPE_SALE)->sum('amount');
        $cashRefunds = $logs->where('type', CashDrawerLog::TYPE_REFUND)->sum('amount');

        return $session->opening_cash + $cashInTotal + $cashSales - $cashOutTotal - $cashRefunds;
    }
}
