<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\CheckoutRequest;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Price;
use App\Models\Transaction;
use App\Services\CustomerService;
use App\Services\PosSessionService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private PosSessionService $sessionService,
        private CustomerService $customerService
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $outlets = Outlet::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $defaultOutlet = $user->defaultOutlet() ?? $outlets->first();

        if (! $defaultOutlet) {
            return view('pos.no-outlet');
        }

        $session = $this->sessionService->getOpenSession($user->id, $defaultOutlet->id);

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pos.index', [
            'outlets' => $outlets,
            'currentOutlet' => $defaultOutlet,
            'session' => $session,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function getItems(Request $request): JsonResponse
    {
        $user = auth()->user();
        $outletId = $request->outlet_id;

        $query = InventoryItem::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->with(['category', 'unit']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('name')->limit(50)->get();

        $prices = Price::where('outlet_id', $outletId)
            ->whereIn('inventory_item_id', $items->pluck('id'))
            ->where('is_active', true)
            ->get()
            ->keyBy('inventory_item_id');

        $itemsWithPrices = $items->map(function ($item) use ($prices) {
            $price = $prices->get($item->id);

            return [
                'id' => $item->id,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'name' => $item->name,
                'image' => $item->image,
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name,
                'unit_name' => $item->unit?->name ?? 'pcs',
                'cost_price' => $item->cost_price,
                'selling_price' => $price?->selling_price ?? ($item->cost_price * 1.5),
                'member_price' => $price?->member_price,
                'has_price' => $price !== null,
            ];
        });

        return response()->json([
            'items' => $itemsWithPrices,
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->search;

        if (strlen($search) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = $this->customerService->searchCustomers(
            auth()->user()->tenant_id,
            $search,
            10
        );

        return response()->json([
            'customers' => $customers->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'membership_level' => $c->membership_level,
                'total_points' => $c->total_points,
                'points_value' => $c->getPointsValue(),
            ]),
        ]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'outlet_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['nullable', 'numeric'],
            'customer_id' => ['nullable', 'uuid'],
            'discounts' => ['nullable', 'array'],
            'points_to_redeem' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $calculation = $this->transactionService->calculateTransaction(
                $request->outlet_id,
                $request->items,
                $request->customer_id,
                $request->discounts ?? [],
                $request->points_to_redeem
            );

            return response()->json([
                'success' => true,
                'calculation' => $calculation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
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
                'message' => 'No open session. Please open a session first.',
            ], 400);
        }

        try {
            $transaction = $this->transactionService->createTransaction(
                $user->tenant_id,
                $outletId,
                $session->id,
                $user->id,
                $request->items,
                $request->payment_method_id,
                $request->payment_amount,
                $request->customer_id,
                $request->discounts ?? [],
                $request->points_to_redeem,
                $request->reference_number,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction completed successfully.',
                'transaction' => [
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'grand_total' => $transaction->grand_total,
                    'payment_amount' => $transaction->payment_amount,
                    'change_amount' => $transaction->change_amount,
                    'points_earned' => $transaction->points_earned,
                ],
                'receipt_url' => route('pos.receipt', $transaction->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function receipt(Transaction $transaction): View
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $transaction->load([
            'outlet',
            'customer',
            'user',
            'items.inventoryItem',
            'payments.paymentMethod',
            'discounts',
        ]);

        return view('pos.receipt', [
            'transaction' => $transaction,
        ]);
    }
}
