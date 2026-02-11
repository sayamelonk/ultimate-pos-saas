<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\CashDrawerLog;
use App\Services\PosSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashDrawerController extends Controller
{
    public function __construct(
        private PosSessionService $sessionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $outletId = $request->header('X-Outlet-Id') ?? $user->defaultOutlet()?->id;

        if (! $outletId) {
            return response()->json(['logs' => [], 'balance' => 0]);
        }

        $session = $this->sessionService->getOpenSession($user->id, $outletId);

        if (! $session) {
            return response()->json(['logs' => [], 'balance' => 0]);
        }

        $logs = CashDrawerLog::where('pos_session_id', $session->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'type' => $log->type,
                'type_label' => CashDrawerLog::getTypeLabel($log->type),
                'amount' => $log->amount,
                'balance_before' => $log->balance_before,
                'balance_after' => $log->balance_after,
                'reference' => $log->reference,
                'reason' => $log->reason,
                'user_name' => $log->user?->name,
                'is_inflow' => $log->isInflow(),
                'created_at' => $log->created_at->format('H:i'),
                'created_at_full' => $log->created_at->format('Y-m-d H:i:s'),
            ]);

        $currentBalance = $this->calculateCurrentBalance($session->id);

        return response()->json([
            'logs' => $logs,
            'balance' => $currentBalance,
            'opening_cash' => $session->opening_cash,
        ]);
    }

    public function cashIn(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $user = auth()->user();
        $outletId = $request->header('X-Outlet-Id') ?? $user->defaultOutlet()?->id;

        if (! $outletId) {
            return response()->json([
                'success' => false,
                'message' => 'No outlet selected.',
            ], 400);
        }

        $session = $this->sessionService->getOpenSession($user->id, $outletId);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'No open session.',
            ], 400);
        }

        $currentBalance = $this->calculateCurrentBalance($session->id);

        $log = CashDrawerLog::create([
            'tenant_id' => $user->tenant_id,
            'outlet_id' => $outletId,
            'pos_session_id' => $session->id,
            'user_id' => $user->id,
            'type' => CashDrawerLog::TYPE_CASH_IN,
            'amount' => $request->amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance + $request->amount,
            'reference' => $request->reference,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cash added successfully.',
            'log' => [
                'id' => $log->id,
                'amount' => $log->amount,
                'balance_after' => $log->balance_after,
            ],
        ]);
    }

    public function cashOut(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $user = auth()->user();
        $outletId = $request->header('X-Outlet-Id') ?? $user->defaultOutlet()?->id;

        if (! $outletId) {
            return response()->json([
                'success' => false,
                'message' => 'No outlet selected.',
            ], 400);
        }

        $session = $this->sessionService->getOpenSession($user->id, $outletId);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'No open session.',
            ], 400);
        }

        $currentBalance = $this->calculateCurrentBalance($session->id);

        if ($request->amount > $currentBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient cash in drawer. Current balance: '.number_format($currentBalance, 2),
            ], 400);
        }

        $log = CashDrawerLog::create([
            'tenant_id' => $user->tenant_id,
            'outlet_id' => $outletId,
            'pos_session_id' => $session->id,
            'user_id' => $user->id,
            'type' => CashDrawerLog::TYPE_CASH_OUT,
            'amount' => $request->amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance - $request->amount,
            'reference' => $request->reference,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cash withdrawn successfully.',
            'log' => [
                'id' => $log->id,
                'amount' => $log->amount,
                'balance_after' => $log->balance_after,
            ],
        ]);
    }

    public function report(Request $request): View
    {
        $user = auth()->user();
        $sessionId = $request->session_id;

        $session = \App\Models\PosSession::where('id', $sessionId)
            ->where('outlet_id', function ($query) use ($user) {
                $query->select('id')
                    ->from('outlets')
                    ->where('tenant_id', $user->tenant_id);
            })
            ->firstOrFail();

        $logs = CashDrawerLog::where('pos_session_id', $session->id)
            ->with(['user:id,name', 'transaction:id,transaction_number'])
            ->orderBy('created_at')
            ->get();

        $summary = [
            'opening_cash' => $session->opening_cash,
            'total_cash_in' => $logs->where('type', CashDrawerLog::TYPE_CASH_IN)->sum('amount'),
            'total_cash_out' => $logs->where('type', CashDrawerLog::TYPE_CASH_OUT)->sum('amount'),
            'total_sales' => $logs->where('type', CashDrawerLog::TYPE_SALE)->sum('amount'),
            'total_refunds' => $logs->where('type', CashDrawerLog::TYPE_REFUND)->sum('amount'),
            'closing_cash' => $session->closing_cash,
            'expected_cash' => $session->expected_cash,
            'cash_difference' => $session->cash_difference,
        ];

        return view('pos.cash-drawer.report', [
            'session' => $session,
            'logs' => $logs,
            'summary' => $summary,
        ]);
    }

    private function calculateCurrentBalance(int $sessionId): float
    {
        $lastLog = CashDrawerLog::where('pos_session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastLog) {
            return (float) $lastLog->balance_after;
        }

        $session = \App\Models\PosSession::find($sessionId);

        return $session ? (float) $session->opening_cash : 0;
    }
}
