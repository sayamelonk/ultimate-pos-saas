<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Transaction;
use App\Models\User;

class PosSessionService
{
    public function openSession(
        string $outletId,
        string $userId,
        float $openingCash,
        ?string $notes = null
    ): PosSession {
        if ($this->hasOpenSession($userId, $outletId)) {
            throw new \RuntimeException('User already has an open session at this outlet');
        }

        $sessionNumber = $this->generateSessionNumber($outletId, $userId);

        return PosSession::create([
            'outlet_id' => $outletId,
            'user_id' => $userId,
            'session_number' => $sessionNumber,
            'opening_cash' => $openingCash,
            'opening_notes' => $notes,
            'opened_at' => now(),
            'status' => PosSession::STATUS_OPEN,
        ]);
    }

    public function closeSession(
        PosSession $session,
        float $closingCash,
        string $closedBy,
        ?string $notes = null
    ): PosSession {
        if (! $session->isOpen()) {
            throw new \RuntimeException('Session is already closed');
        }

        $session->close($closingCash, $closedBy, $notes);

        return $session->fresh();
    }

    public function hasOpenSession(string $userId, string $outletId): bool
    {
        return PosSession::where('user_id', $userId)
            ->where('outlet_id', $outletId)
            ->where('status', PosSession::STATUS_OPEN)
            ->exists();
    }

    public function getOpenSession(string $userId, string $outletId): ?PosSession
    {
        return PosSession::where('user_id', $userId)
            ->where('outlet_id', $outletId)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();
    }

    public function getOpenSessionForOutlet(string $outletId): ?PosSession
    {
        return PosSession::where('outlet_id', $outletId)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();
    }

    public function getSettlementReport(PosSession $session): array
    {
        $transactions = $session->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->with(['payments.paymentMethod', 'items'])
            ->get();

        $sales = $transactions->where('type', Transaction::TYPE_SALE);
        $refunds = $transactions->where('type', Transaction::TYPE_REFUND);

        $paymentSummary = [];
        foreach ($transactions as $transaction) {
            foreach ($transaction->payments as $payment) {
                $methodId = $payment->payment_method_id;
                $methodName = $payment->paymentMethod->name;
                $methodType = $payment->paymentMethod->type;

                if (! isset($paymentSummary[$methodId])) {
                    $paymentSummary[$methodId] = [
                        'name' => $methodName,
                        'type' => $methodType,
                        'count' => 0,
                        'amount' => 0,
                        'charges' => 0,
                    ];
                }

                $multiplier = $transaction->type === Transaction::TYPE_REFUND ? -1 : 1;
                $paymentSummary[$methodId]['count']++;
                $paymentSummary[$methodId]['amount'] += $payment->amount * $multiplier;
                $paymentSummary[$methodId]['charges'] += $payment->charge_amount;
            }
        }

        $cashPayment = collect($paymentSummary)->first(fn ($p) => $p['type'] === PaymentMethod::TYPE_CASH);
        $cashSales = $cashPayment ? $cashPayment['amount'] : 0;

        return [
            'session' => $session,
            'outlet' => $session->outlet,
            'user' => $session->user,
            'summary' => [
                'total_transactions' => $sales->count(),
                'total_refunds' => $refunds->count(),
                'gross_sales' => $sales->sum('subtotal'),
                'total_discounts' => $sales->sum('discount_amount'),
                'total_tax' => $sales->sum('tax_amount'),
                'total_service_charge' => $sales->sum('service_charge_amount'),
                'net_sales' => $sales->sum('grand_total'),
                'total_refund_amount' => $refunds->sum('grand_total'),
                'net_revenue' => $sales->sum('grand_total') - $refunds->sum('grand_total'),
                'total_items_sold' => $sales->sum(fn ($t) => $t->items->sum('quantity')),
                'average_transaction' => $sales->count() > 0 ? $sales->sum('grand_total') / $sales->count() : 0,
            ],
            'payments' => array_values($paymentSummary),
            'cash' => [
                'opening_cash' => $session->opening_cash,
                'cash_sales' => $cashSales,
                'expected_cash' => $session->opening_cash + $cashSales,
                'closing_cash' => $session->closing_cash,
                'difference' => $session->cash_difference,
            ],
            'transactions' => $transactions,
        ];
    }

    private function generateSessionNumber(string $outletId, string $userId): string
    {
        $outlet = Outlet::find($outletId);
        $user = User::find($userId);

        $outletCode = $outlet ? strtoupper(substr($outlet->code ?? $outlet->name, 0, 5)) : 'OUT';
        $userInitial = $user ? strtoupper(substr($user->name, 0, 2)) : 'XX';
        $date = now()->format('Ymd');

        $todayCount = PosSession::where('outlet_id', $outletId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $sequence = str_pad($todayCount + 1, 2, '0', STR_PAD_LEFT);

        return "{$outletCode}-{$date}-{$userInitial}-{$sequence}";
    }

    public function getRecentSessions(string $outletId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return PosSession::where('outlet_id', $outletId)
            ->with(['user', 'closedByUser'])
            ->orderByDesc('opened_at')
            ->limit($limit)
            ->get();
    }

    public function getSessionsByDateRange(
        string $outletId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): \Illuminate\Database\Eloquent\Collection {
        return PosSession::where('outlet_id', $outletId)
            ->whereBetween('opened_at', [$startDate, $endDate])
            ->with(['user', 'closedByUser'])
            ->orderByDesc('opened_at')
            ->get();
    }
}
