<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $outlets = Outlet::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        $selectedOutletId = $request->outlet_id ?? $outlets->first()?->id;

        $query = Transaction::where('tenant_id', $user->tenant_id)
            ->with(['outlet', 'customer', 'user', 'payments.paymentMethod']);

        if ($selectedOutletId) {
            $query->where('outlet_id', $selectedOutletId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'outlets' => $outlets,
            'selectedOutletId' => $selectedOutletId,
            'statuses' => Transaction::getStatuses(),
            'types' => Transaction::getTypes(),
        ]);
    }

    public function show(Transaction $transaction): View
    {
        $this->authorizeTransaction($transaction);

        $transaction->load([
            'outlet',
            'customer',
            'user',
            'posSession',
            'items.inventoryItem',
            'payments.paymentMethod',
            'discounts',
            'originalTransaction',
            'refundTransactions',
        ]);

        return view('transactions.show', [
            'transaction' => $transaction,
        ]);
    }

    public function void(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransaction($transaction);

        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if (! $transaction->canVoid()) {
            return back()->with('error', 'This transaction cannot be voided.');
        }

        try {
            $this->transactionService->voidTransaction(
                $transaction,
                auth()->id(),
                $request->reason
            );

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction voided successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function refundForm(Transaction $transaction): View
    {
        $this->authorizeTransaction($transaction);

        if (! $transaction->canRefund()) {
            abort(403, 'This transaction cannot be refunded.');
        }

        $transaction->load('items.inventoryItem');

        $paymentMethods = PaymentMethod::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('transactions.refund', [
            'transaction' => $transaction,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function refund(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransaction($transaction);

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.transaction_item_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if (! $transaction->canRefund()) {
            return back()->with('error', 'This transaction cannot be refunded.');
        }

        try {
            $refund = $this->transactionService->refundTransaction(
                $transaction,
                $request->items,
                auth()->id(),
                $request->payment_method_id,
                $request->reason
            );

            return redirect()->route('transactions.show', $refund)
                ->with('success', 'Refund processed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function authorizeTransaction(Transaction $transaction): void
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
