<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\UserPin;
use App\Services\KitchenOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class WaiterController extends Controller
{
    public function __construct(
        private KitchenOrderService $kitchenOrderService
    ) {}

    // ==================== AUTH ====================

    /**
     * List outlets for waiter login selection.
     */
    public function outlets(): JsonResponse
    {
        $outlets = Outlet::where('is_active', true)
            ->select('id', 'name', 'address')
            ->get();

        return response()->json(['data' => $outlets]);
    }

    /**
     * Login waiter with PIN.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'outlet_id' => 'required|uuid|exists:outlets,id',
            'pin' => 'required|string|size:4',
        ]);

        $outlet = Outlet::findOrFail($request->outlet_id);

        // Find user with matching PIN for this outlet's tenant
        $userPin = UserPin::whereHas('user', function ($query) use ($outlet) {
            $query->where('tenant_id', $outlet->tenant_id)
                ->where('is_active', true);
        })
            ->where('is_active', true)
            ->get()
            ->first(function ($pin) use ($request) {
                return Hash::check($request->pin, $pin->pin_hash);
            });

        if (! $userPin) {
            return response()->json([
                'message' => 'Invalid PIN',
            ], 401);
        }

        $user = $userPin->user;

        // Create token
        $token = $user->createToken('waiter-token', ['waiter'])->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'outlet' => [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                ],
            ],
        ]);
    }

    /**
     * Logout waiter.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    // ==================== TABLES ====================

    /**
     * List tables for the outlet.
     */
    public function tables(Request $request): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $query = Table::where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->with(['floor', 'currentSession.openedByUser']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by floor
        if ($request->has('floor_id')) {
            $query->where('floor_id', $request->floor_id);
        }

        $tables = $query->orderBy('number')->get();

        return response()->json([
            'data' => $tables->map(fn ($table) => $this->formatTable($table)),
        ]);
    }

    /**
     * Get table detail.
     */
    public function showTable(Request $request, string $id): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $table = Table::where('outlet_id', $outlet->id)
            ->where('id', $id)
            ->with(['floor', 'currentSession.openedByUser', 'currentSession.transactions.items'])
            ->firstOrFail();

        $data = $this->formatTable($table);

        // Add current order if exists
        $currentOrder = null;
        if ($table->currentSession) {
            $transaction = $table->currentSession->transactions()
                ->where('status', '!=', 'completed')
                ->latest()
                ->first();

            if ($transaction) {
                $currentOrder = $this->formatOrder($transaction);
            }
        }
        $data['current_order'] = $currentOrder;

        return response()->json(['data' => $data]);
    }

    /**
     * Open a table (start session).
     */
    public function openTable(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'guest_count' => 'nullable|integer|min:1',
        ]);

        $outlet = $this->getOutlet($request);

        $table = Table::where('outlet_id', $outlet->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $table->isAvailable()) {
            return response()->json([
                'message' => 'Table is not available',
            ], 422);
        }

        $session = TableSession::openTable(
            $table,
            $request->input('guest_count', 1),
            $request->user()->id
        );

        $table->refresh()->load(['floor', 'currentSession']);

        return response()->json([
            'data' => $this->formatTable($table),
        ]);
    }

    /**
     * Close a table (end session).
     */
    public function closeTable(Request $request, string $id): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $table = Table::where('outlet_id', $outlet->id)
            ->where('id', $id)
            ->with('currentSession')
            ->firstOrFail();

        if (! $table->currentSession) {
            return response()->json([
                'message' => 'Table has no active session',
            ], 422);
        }

        $table->currentSession->close($request->user()->id);
        $table->refresh()->load(['floor', 'currentSession']);

        return response()->json([
            'data' => $this->formatTable($table),
        ]);
    }

    /**
     * Update table status.
     */
    public function updateTableStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(array_keys(Table::getStatuses()))],
        ]);

        $outlet = $this->getOutlet($request);

        $table = Table::where('outlet_id', $outlet->id)
            ->where('id', $id)
            ->firstOrFail();

        $table->update(['status' => $request->status]);
        $table->refresh()->load(['floor', 'currentSession']);

        return response()->json([
            'data' => $this->formatTable($table),
        ]);
    }

    // ==================== FLOORS ====================

    /**
     * List floors for the outlet.
     */
    public function floors(Request $request): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $floors = Floor::where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->withCount(['tables' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $floors->map(fn ($floor) => [
                'id' => $floor->id,
                'name' => $floor->name,
                'tables_count' => $floor->tables_count,
            ]),
        ]);
    }

    // ==================== MENU ====================

    /**
     * List menu items.
     */
    public function menu(Request $request): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $query = Product::where('tenant_id', $outlet->tenant_id)
            ->where('is_active', true)
            ->with(['category', 'variantGroups.options']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->orderBy('name')->get();

        return response()->json([
            'data' => $products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->base_price,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'variants' => $product->variantGroups->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'is_required' => $group->pivot->is_required ?? $group->is_required ?? false,
                    'is_multiple' => false,
                    'options' => $group->options->map(fn ($opt) => [
                        'id' => $opt->id,
                        'name' => $opt->name,
                        'price_modifier' => $opt->price_modifier ?? 0,
                    ]),
                ]),
                'image_url' => $product->image_url,
            ]),
        ]);
    }

    /**
     * List product categories.
     */
    public function categories(Request $request): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $categories = ProductCategory::where('tenant_id', $outlet->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]),
        ]);
    }

    // ==================== ORDERS ====================

    /**
     * List waiter's orders.
     *
     * Query params:
     * - status: filter by transaction status (pending, completed, voided)
     * - kitchen_status: filter by kitchen order status (pending, preparing, ready, served)
     * - scope: 'outlet' to show all orders in outlet (not just this waiter's)
     * - all: if present, don't filter by today's date
     */
    public function orders(Request $request): JsonResponse
    {
        $outlet = $this->getOutlet($request);
        $user = $request->user();

        $query = Transaction::where('outlet_id', $outlet->id)
            ->with(['items.product', 'table', 'kitchenOrder'])
            ->latest();

        // Scope: outlet-wide (for kitchen status) or waiter-only (for my orders)
        if ($request->get('scope') !== 'outlet') {
            $query->where('waiter_id', $user->id);
        }

        // Filter by transaction status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by kitchen order status (ready, preparing, etc.)
        if ($request->has('kitchen_status')) {
            $query->whereHas('kitchenOrder', function ($q) use ($request) {
                $q->where('status', $request->kitchen_status);
            });
        }

        // Filter by date (today by default)
        if (! $request->has('all')) {
            $query->whereDate('created_at', today());
        }

        $orders = $query->limit(50)->get();

        return response()->json([
            'data' => $orders->map(fn ($order) => $this->formatOrderSummary($order)),
        ]);
    }

    /**
     * Get order detail.
     */
    public function showOrder(Request $request, string $id): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $order = Transaction::where('outlet_id', $outlet->id)
            ->where('id', $id)
            ->with(['items.product', 'table', 'customer', 'kitchenOrder.items'])
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatOrder($order),
        ]);
    }

    /**
     * Create new order.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'order_type' => ['required', Rule::in(['dine_in', 'takeaway', 'take_away'])],
            'table_id' => 'required_if:order_type,dine_in|nullable|uuid|exists:tables,id',
            'customer_name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.variants' => 'nullable|array',
        ]);

        $outlet = $this->getOutlet($request);
        $user = $request->user();

        // Normalize order type
        $orderType = $request->order_type === 'take_away' ? 'takeaway' : $request->order_type;

        // Validate table for dine_in
        $table = null;
        $tableSession = null;
        if ($orderType === 'dine_in') {
            if (! $request->table_id) {
                return response()->json([
                    'message' => 'Table is required for dine-in orders',
                    'errors' => ['table_id' => ['Table is required for dine-in orders']],
                ], 422);
            }

            $table = Table::where('outlet_id', $outlet->id)
                ->where('id', $request->table_id)
                ->with('currentSession')
                ->firstOrFail();

            // Create table session if not exists → marks table as occupied
            if (! $table->currentSession) {
                $tableSession = TableSession::openTable($table, 1, $user->id);
            } else {
                $tableSession = $table->currentSession;
            }
        }

        return DB::transaction(function () use ($request, $outlet, $user, $orderType, $table, $tableSession) {
            // Generate order number
            $orderNumber = $this->generateOrderNumber($outlet->id);

            // Calculate totals
            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $unitPrice = $product->base_price;

                // TODO: Add variant price modifiers
                $quantity = $itemData['quantity'];
                $itemSubtotal = $unitPrice * $quantity;
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'notes' => $itemData['notes'] ?? null,
                    'variants' => $itemData['variants'] ?? [],
                ];
            }

            // Calculate tax
            $taxRate = $outlet->tax_percentage ?? 0;
            $tax = $subtotal * ($taxRate / 100);
            $grandTotal = $subtotal + $tax;

            // Create transaction
            $transaction = Transaction::create([
                'tenant_id' => $outlet->tenant_id,
                'outlet_id' => $outlet->id,
                'table_id' => $table?->id,
                'table_session_id' => $tableSession?->id,
                'user_id' => $user->id,
                'waiter_id' => $user->id,
                'customer_name' => $request->customer_name,
                'transaction_number' => $orderNumber,
                'order_type' => $orderType,
                'type' => 'sale',
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'tax_percentage' => $taxRate,
                'discount_amount' => 0,
                'service_charge_amount' => 0,
                'payment_amount' => 0,
                'grand_total' => $grandTotal,
                'status' => 'pending',
            ]);

            // Create transaction items
            foreach ($itemsData as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product']->id,
                    'item_name' => $item['product']->name,
                    'item_sku' => $item['product']->sku ?? $item['product']->id,
                    'unit_name' => $item['product']->unit_name ?? 'pcs',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'base_price' => $item['unit_price'],
                    'variant_price_adjustment' => 0,
                    'modifiers_total' => 0,
                    'cost_price' => $item['product']->cost_price ?? 0,
                    'subtotal' => $item['subtotal'],
                    'discount_amount' => 0,
                    'notes' => $item['notes'],
                    'item_notes' => $item['notes'],
                    'modifiers' => $item['variants'],
                ]);
            }

            $transaction->load(['items', 'table']);

            return response()->json([
                'data' => $this->formatOrder($transaction),
            ], 201);
        });
    }

    /**
     * Add items to existing order.
     */
    public function addItems(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $outlet = $this->getOutlet($request);

        $transaction = Transaction::where('outlet_id', $outlet->id)
            ->where('id', $id)
            ->firstOrFail();

        return DB::transaction(function () use ($request, $transaction, $outlet) {
            $additionalSubtotal = 0;

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $unitPrice = $product->base_price;
                $quantity = $itemData['quantity'];
                $itemSubtotal = $unitPrice * $quantity;
                $additionalSubtotal += $itemSubtotal;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'item_name' => $product->name,
                    'item_sku' => $product->sku ?? $product->id,
                    'unit_name' => $product->unit_name ?? 'pcs',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'base_price' => $unitPrice,
                    'variant_price_adjustment' => 0,
                    'modifiers_total' => 0,
                    'cost_price' => $product->cost_price ?? 0,
                    'subtotal' => $itemSubtotal,
                    'discount_amount' => 0,
                    'notes' => $itemData['notes'] ?? null,
                    'item_notes' => $itemData['notes'] ?? null,
                ]);
            }

            // Update transaction totals
            $newSubtotal = $transaction->subtotal + $additionalSubtotal;
            $taxRate = $outlet->tax_percentage ?? 0;
            $newTax = $newSubtotal * ($taxRate / 100);
            $newGrandTotal = $newSubtotal + $newTax;

            $transaction->update([
                'subtotal' => $newSubtotal,
                'tax_amount' => $newTax,
                'grand_total' => $newGrandTotal,
            ]);

            $transaction->refresh()->load(['items', 'table']);

            return response()->json([
                'data' => $this->formatOrder($transaction),
            ]);
        });
    }

    /**
     * Send order to kitchen (create KitchenOrder or add new items to existing).
     */
    public function sendToKitchen(Request $request, string $id): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $transaction = Transaction::where('outlet_id', $outlet->id)
            ->where('id', $id)
            ->with(['items', 'kitchenOrder.items'])
            ->firstOrFail();

        // If kitchen order already exists, add any new items
        if ($transaction->kitchenOrder) {
            $kitchenOrder = $transaction->kitchenOrder;

            // Find transaction items that don't have a kitchen_order_item yet
            $existingTransactionItemIds = $kitchenOrder->items
                ->pluck('transaction_item_id')
                ->toArray();

            $newItems = $transaction->items
                ->whereNotIn('id', $existingTransactionItemIds);

            if ($newItems->isEmpty()) {
                $transaction->refresh()->load(['items', 'table', 'kitchenOrder']);

                return response()->json([
                    'data' => $this->formatOrder($transaction),
                    'message' => 'No new items to send',
                ]);
            }

            // Get default station
            $defaultStation = KitchenStation::where('outlet_id', $outlet->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();

            // Create kitchen_order_items for new items
            foreach ($newItems as $item) {
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kitchenOrder->id,
                    'transaction_item_id' => $item->id,
                    'station_id' => $defaultStation?->id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'modifiers' => $item->modifiers,
                    'notes' => $item->item_notes,
                    'status' => KitchenOrderItem::STATUS_PENDING,
                ]);
            }

            // Reset kitchen order status to preparing so kitchen sees it
            $kitchenOrder->update([
                'status' => KitchenOrder::STATUS_PREPARING,
                'completed_at' => null,
                'served_at' => null,
            ]);

            $transaction->refresh()->load(['items', 'table', 'kitchenOrder']);

            return response()->json([
                'data' => $this->formatOrder($transaction),
                'message' => 'Item tambahan dikirim ke dapur',
            ]);
        }

        // Create kitchen order (first time)
        $kitchenOrder = $this->kitchenOrderService->createFromTransaction($transaction);

        if (! $kitchenOrder) {
            return response()->json([
                'message' => 'No kitchen station available',
            ], 422);
        }

        $transaction->refresh()->load(['items', 'table', 'kitchenOrder']);

        return response()->json([
            'data' => $this->formatOrder($transaction),
        ]);
    }

    /**
     * Mark order as picked up (served).
     */
    public function pickupOrder(Request $request, string $id): JsonResponse
    {
        $outlet = $this->getOutlet($request);

        $transaction = Transaction::where('outlet_id', $outlet->id)
            ->where('id', $id)
            ->with('kitchenOrder')
            ->firstOrFail();

        if (! $transaction->kitchenOrder) {
            return response()->json([
                'message' => 'Order has not been sent to kitchen',
            ], 422);
        }

        // Mark as served
        $transaction->kitchenOrder->update([
            'status' => KitchenOrder::STATUS_SERVED,
            'served_at' => now(),
        ]);

        $transaction->refresh()->load(['items', 'table', 'kitchenOrder']);

        return response()->json([
            'data' => $this->formatOrder($transaction),
        ]);
    }

    // ==================== HELPERS ====================

    private function getOutlet(Request $request): Outlet
    {
        $outletId = $request->header('X-Outlet-Id');

        return Outlet::findOrFail($outletId);
    }

    private function formatTable(Table $table): array
    {
        return [
            'id' => $table->id,
            'number' => $table->number,
            'name' => $table->name ?? 'Table '.$table->number,
            'capacity' => $table->capacity,
            'status' => $table->status,
            'floor' => $table->floor ? [
                'id' => $table->floor->id,
                'name' => $table->floor->name,
            ] : null,
            'current_session' => $table->currentSession ? [
                'id' => $table->currentSession->id,
                'guest_count' => $table->currentSession->guest_count,
                'opened_at' => $table->currentSession->opened_at->toIso8601String(),
                'opened_by' => $table->currentSession->openedByUser?->name,
                'duration_minutes' => $table->currentSession->duration_minutes,
            ] : null,
        ];
    }

    private function formatOrder(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'order_number' => $transaction->transaction_number,
            'order_type' => $transaction->order_type,
            'table' => $transaction->table ? [
                'id' => $transaction->table->id,
                'number' => $transaction->table->number,
                'name' => $transaction->table->name,
            ] : null,
            'customer_name' => $transaction->customer_name ?? $transaction->customer?->name,
            'items' => $transaction->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->item_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'notes' => $item->notes,
                'modifiers' => $item->modifiers,
            ]),
            'subtotal' => $transaction->subtotal,
            'tax' => $transaction->tax_amount,
            'total' => $transaction->grand_total,
            'status' => $transaction->status,
            'kitchen_status' => $transaction->kitchenOrder?->status,
            'created_at' => $transaction->created_at->toIso8601String(),
            'waiter' => [
                'id' => $transaction->waiter_id,
                'name' => $transaction->waiter?->name,
            ],
        ];
    }

    private function formatOrderSummary(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'order_number' => $transaction->transaction_number,
            'order_type' => $transaction->order_type,
            'table_number' => $transaction->table?->number,
            'table_name' => $transaction->table?->name,
            'customer_name' => $transaction->customer_name ?? $transaction->customer?->name,
            'items_count' => $transaction->items->sum('quantity'),
            'items' => $transaction->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product?->name ?? $item->product_name ?? '-',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
            ])->values()->toArray(),
            'total' => $transaction->grand_total,
            'status' => $transaction->status,
            'kitchen_status' => $transaction->kitchenOrder?->status,
            'created_at' => $transaction->created_at->toIso8601String(),
        ];
    }

    private function generateOrderNumber(string $outletId): string
    {
        $today = now()->format('Ymd');
        $count = Transaction::where('outlet_id', $outletId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('ORD-%s-%04d', $today, $count);
    }
}
