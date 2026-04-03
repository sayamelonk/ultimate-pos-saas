<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\CheckoutRequest;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
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

        // Check for open session - first try default outlet, then any outlet
        $session = $this->sessionService->getOpenSession($user->id, $defaultOutlet->id);

        // If no session at default outlet, check if user has session at another outlet
        if (! $session) {
            foreach ($outlets as $outlet) {
                $session = $this->sessionService->getOpenSession($user->id, $outlet->id);
                if ($session) {
                    $defaultOutlet = $outlet; // Switch to the outlet with active session
                    break;
                }
            }
        }

        // Use ProductCategory instead of InventoryCategory
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('show_in_pos', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->where('show_in_pos', true);
            }])
            ->orderBy('sort_order')
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

    public function getProducts(Request $request): JsonResponse
    {
        $user = auth()->user();
        $outletId = $request->outlet_id;

        $query = Product::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->where('show_in_pos', true)
            ->with([
                'category',
                'variants' => fn ($q) => $q->where('is_active', true),
                'modifierGroups' => fn ($q) => $q->where('is_active', true),
                'modifierGroups.modifiers' => fn ($q) => $q->where('is_active', true),
                'productOutlets' => fn ($q) => $q->where('outlet_id', $outletId),
                'combo.items.product',
            ]);

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

        $products = $query->orderBy('sort_order')->orderBy('name')->limit(50)->get();

        $productsData = $products->map(function ($product) {
            // Check availability at outlet
            $productOutlet = $product->productOutlets->first();
            if ($productOutlet && ! $productOutlet->is_available) {
                return null; // Skip unavailable products
            }

            // Get price (custom price for outlet or base price)
            $sellingPrice = $productOutlet?->custom_price ?? $product->base_price;

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'image' => $product->image,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'category_color' => $product->category?->color,
                'product_type' => $product->product_type,
                'base_price' => $product->base_price,
                'selling_price' => $sellingPrice,
                'cost_price' => $product->cost_price,
                'allow_notes' => $product->allow_notes,
                'has_variants' => $product->isVariant() && $product->variants->isNotEmpty(),
                'has_modifiers' => $product->modifierGroups->isNotEmpty(),
                'is_combo' => $product->isCombo(),
                'variants' => $product->isVariant() ? $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'price' => $v->price,
                    'price_adjustment' => $v->price - $product->base_price,
                ]) : [],
                'modifier_groups' => $product->modifierGroups->map(fn ($mg) => [
                    'id' => $mg->id,
                    'name' => $mg->name,
                    'display_name' => $mg->display_name ?? $mg->name,
                    'selection_type' => $mg->selection_type,
                    'min_selections' => $mg->pivot->min_selections ?? $mg->min_selections,
                    'max_selections' => $mg->pivot->max_selections ?? $mg->max_selections,
                    'is_required' => $mg->pivot->is_required ?? $mg->is_required,
                    'modifiers' => $mg->modifiers->map(fn ($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                        'display_name' => $m->display_name ?? $m->name,
                        'price' => $m->price,
                        'is_default' => $m->is_default,
                    ]),
                ]),
                'combo_items' => $product->isCombo() && $product->combo ? $product->combo->items->map(fn ($ci) => [
                    'id' => $ci->id,
                    'product_id' => $ci->product_id,
                    'product_name' => $ci->product?->name,
                    'category_id' => $ci->category_id,
                    'quantity' => $ci->quantity,
                    'is_required' => $ci->is_required,
                ]) : [],
            ];
        })->filter()->values();

        return response()->json([
            'products' => $productsData,
        ]);
    }

    // Keep old method for backward compatibility (will be removed later)
    public function getItems(Request $request): JsonResponse
    {
        return $this->getProducts($request);
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
