<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\AuthorizationLog;
use App\Models\AuthorizationSetting;
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

        $selectedOutletId = $request->outlet_id;

        $query = Transaction::where('tenant_id', $user->tenant_id)
            ->with(['outlet', 'customer', 'user', 'payments.paymentMethod']);

        if ($request->filled('outlet_id')) {
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

        $user = auth()->user();
        $settings = AuthorizationSetting::getForTenant($user->tenant_id);

        // Check if void requires authorization
        // Users who can authorize themselves don't need PIN if they have one set
        $requiresVoidAuth = $settings->require_auth_void;
        if ($requiresVoidAuth && $user->canAuthorize() && $user->hasPin()) {
            $requiresVoidAuth = false;
        }

        // Check if refund requires authorization
        $requiresRefundAuth = $settings->require_auth_refund;
        if ($requiresRefundAuth && $user->canAuthorize() && $user->hasPin()) {
            $requiresRefundAuth = false;
        }

        return view('transactions.show', [
            'transaction' => $transaction,
            'requiresVoidAuth' => $requiresVoidAuth,
            'requiresRefundAuth' => $requiresRefundAuth,
        ]);
    }

    public function void(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransaction($transaction);

        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'authorization_log_id' => ['nullable', 'uuid'],
        ]);

        if (! $transaction->canVoid()) {
            return back()->with('error', 'This transaction cannot be voided.');
        }

        $user = auth()->user();
        $settings = AuthorizationSetting::getForTenant($user->tenant_id);

        // Check if authorization is required
        $requiresAuth = $settings->require_auth_void;

        // Users who can authorize themselves don't need additional authorization
        if ($requiresAuth && $user->canAuthorize() && $user->hasPin()) {
            $requiresAuth = false;
        }

        // Verify authorization if required
        if ($requiresAuth) {
            if (! $request->authorization_log_id) {
                return back()->with('error', 'Authorization is required to void this transaction.');
            }

            $authLog = AuthorizationLog::where('id', $request->authorization_log_id)
                ->where('tenant_id', $user->tenant_id)
                ->where('action_type', 'void')
                ->where('status', 'approved')
                ->where('reference_id', $transaction->id)
                ->first();

            if (! $authLog) {
                return back()->with('error', 'Invalid or expired authorization.');
            }
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

        $user = auth()->user();
        $paymentMethods = PaymentMethod::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $settings = AuthorizationSetting::getForTenant($user->tenant_id);

        // Check if refund requires authorization
        $requiresRefundAuth = $settings->require_auth_refund;
        if ($requiresRefundAuth && $user->canAuthorize() && $user->hasPin()) {
            $requiresRefundAuth = false;
        }

        return view('transactions.refund', [
            'transaction' => $transaction,
            'paymentMethods' => $paymentMethods,
            'requiresRefundAuth' => $requiresRefundAuth,
        ]);
    }

    public function refund(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransaction($transaction);

        $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.selected' => ['nullable'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'reason' => ['required', 'string', 'max:255'],
            'refund_type' => ['required', 'in:full,partial'],
            'authorization_log_id' => ['nullable', 'uuid'],
        ]);

        if (! $transaction->canRefund()) {
            return back()->with('error', 'This transaction cannot be refunded.');
        }

        $user = auth()->user();
        $settings = AuthorizationSetting::getForTenant($user->tenant_id);

        // Check if authorization is required
        $requiresAuth = $settings->require_auth_refund;

        // Users who can authorize themselves don't need additional authorization
        if ($requiresAuth && $user->canAuthorize() && $user->hasPin()) {
            $requiresAuth = false;
        }

        // Verify authorization if required
        if ($requiresAuth) {
            if (! $request->authorization_log_id) {
                return back()->with('error', 'Authorization is required to process this refund.');
            }

            $authLog = AuthorizationLog::where('id', $request->authorization_log_id)
                ->where('tenant_id', $user->tenant_id)
                ->where('action_type', 'refund')
                ->where('status', 'approved')
                ->where('reference_id', $transaction->id)
                ->first();

            if (! $authLog) {
                return back()->with('error', 'Invalid or expired authorization.');
            }
        }

        // Build items array based on refund type
        $items = [];
        if ($request->refund_type === 'full') {
            foreach ($transaction->items as $item) {
                $items[] = [
                    'transaction_item_id' => $item->id,
                    'quantity' => $item->quantity,
                ];
            }
        } else {
            // Partial refund - get selected items
            foreach ($request->items ?? [] as $itemId => $itemData) {
                if (isset($itemData['selected']) && $itemData['selected']) {
                    $items[] = [
                        'transaction_item_id' => $itemId,
                        'quantity' => $itemData['quantity'] ?? 1,
                    ];
                }
            }

            if (empty($items)) {
                return back()->with('error', 'Please select at least one item to refund.');
            }
        }

        try {
            $refund = $this->transactionService->refundTransaction(
                $transaction,
                $items,
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
