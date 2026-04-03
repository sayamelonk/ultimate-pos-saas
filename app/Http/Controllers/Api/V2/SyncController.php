<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Customer;
use App\Models\Floor;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Table;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class SyncController extends Controller
{
    #[OA\Get(
        path: '/sync/master',
        summary: 'Full master data sync for POS (on Open Shift)',
        description: 'Returns all data needed for offline POS: categories, products, payment methods, floors & tables, outlet settings',
        security: [['sanctum' => []]],
        tags: ['Sync'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Master sync data with counts'),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function master(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $outlet = $this->currentOutlet($request);

        // Get categories
        $categories = ProductCategory::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where('show_in_pos', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true)->where('show_in_pos', true);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'parent_id' => $cat->parent_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'image' => $cat->image,
                'color' => $cat->color,
                'icon' => $cat->icon,
                'sort_order' => (int) $cat->sort_order,
                'products_count' => (int) $cat->products_count,
                'updated_at' => $cat->updated_at?->toIso8601String(),
            ]);

        // Get products with variants and modifiers (only products assigned to this outlet)
        $products = Product::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where('show_in_pos', true)
            // Only show products assigned to this outlet
            ->whereHas('productOutlets', function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)
                    ->where('is_available', true);
            })
            ->with([
                'category',
                'variants' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                },
                'variantGroups.options' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                },
                'modifierGroups' => function ($q) {
                    $q->where('is_active', true);
                },
                'modifierGroups.modifiers' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                },
                'productOutlets' => function ($q) use ($outletId) {
                    $q->where('outlet_id', $outletId);
                },
                'combo.items.product',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => $this->formatProductForSync($product, $outletId));

        // Get payment methods
        $paymentMethods = PaymentMethod::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($pm) => [
                'id' => $pm->id,
                'code' => $pm->code,
                'name' => $pm->name,
                'type' => $pm->type,
                'icon' => $pm->icon,
                'charge_percentage' => (float) $pm->charge_percentage,
                'charge_fixed' => (float) $pm->charge_fixed,
                'requires_reference' => (bool) $pm->requires_reference,
                'opens_cash_drawer' => (bool) $pm->opens_cash_drawer,
                'sort_order' => (int) $pm->sort_order,
                'updated_at' => $pm->updated_at?->toIso8601String(),
            ]);

        // Get floors with tables
        $floors = Floor::query()
            ->where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->with(['tables' => function ($q) {
                $q->where('is_active', true)->orderBy('number');
            }])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($floor) => [
                'id' => $floor->id,
                'name' => $floor->name,
                'sort_order' => (int) $floor->sort_order,
                'tables' => $floor->tables->map(fn ($table) => [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'capacity' => (int) $table->capacity,
                    'position_x' => (int) $table->position_x,
                    'position_y' => (int) $table->position_y,
                    'width' => (int) $table->width,
                    'height' => (int) $table->height,
                    'shape' => $table->shape,
                    'status' => $table->status,
                    'updated_at' => $table->updated_at?->toIso8601String(),
                ])->toArray(),
                'updated_at' => $floor->updated_at?->toIso8601String(),
            ]);

        // Outlet settings (use effective methods for tax/service charge)
        $outletSettings = [
            'id' => $outlet->id,
            'name' => $outlet->name,
            'code' => $outlet->code,
            'tax_enabled' => $outlet->isTaxEnabled(),
            'tax_mode' => $outlet->getTaxMode(),
            'tax_percentage' => $outlet->getEffectiveTaxPercentage(),
            'service_charge_enabled' => $outlet->isServiceChargeEnabled(),
            'service_charge_percentage' => $outlet->getEffectiveServiceChargePercentage(),
            'receipt_header' => $outlet->receipt_header,
            'receipt_footer' => $outlet->receipt_footer,
            'receipt_show_logo' => (bool) $outlet->receipt_show_logo,
        ];

        return $this->success([
            'categories' => $categories,
            'products' => $products,
            'payment_methods' => $paymentMethods,
            'floors' => $floors,
            'outlet' => $outletSettings,
            'sync_timestamp' => now()->toIso8601String(),
            'counts' => [
                'categories' => $categories->count(),
                'products' => $products->count(),
                'payment_methods' => $paymentMethods->count(),
                'floors' => $floors->count(),
                'tables' => $floors->sum(fn ($f) => count($f['tables'])),
            ],
        ]);
    }

    #[OA\Get(
        path: '/sync/delta',
        summary: 'Delta sync (incremental updates since last sync)',
        security: [['sanctum' => []]],
        tags: ['Sync'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'since', in: 'query', required: true, description: 'ISO8601 timestamp', schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated records since timestamp'),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function delta(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $request->validate([
            'since' => 'required|date',
        ]);

        $since = $request->date('since');

        // Get updated categories
        $categories = ProductCategory::query()
            ->where('tenant_id', $this->tenantId())
            ->where('updated_at', '>', $since)
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'parent_id' => $cat->parent_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'image' => $cat->image,
                'color' => $cat->color,
                'icon' => $cat->icon,
                'sort_order' => (int) $cat->sort_order,
                'is_active' => (bool) $cat->is_active,
                'show_in_pos' => (bool) $cat->show_in_pos,
                'updated_at' => $cat->updated_at?->toIso8601String(),
                '_deleted' => ! $cat->is_active || ! $cat->show_in_pos,
            ]);

        // Get updated products
        $products = Product::query()
            ->where('tenant_id', $this->tenantId())
            ->where('updated_at', '>', $since)
            ->with([
                'variants' => function ($q) {
                    $q->orderBy('sort_order');
                },
                'productOutlets' => function ($q) use ($outletId) {
                    $q->where('outlet_id', $outletId);
                },
            ])
            ->get()
            ->map(fn ($product) => $this->formatProductForSync($product, $outletId, true));

        // Get updated tables
        $tables = Table::query()
            ->where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outletId)
            ->where('updated_at', '>', $since)
            ->get()
            ->map(fn ($table) => [
                'id' => $table->id,
                'floor_id' => $table->floor_id,
                'number' => $table->number,
                'name' => $table->name,
                'capacity' => (int) $table->capacity,
                'position_x' => (int) $table->position_x,
                'position_y' => (int) $table->position_y,
                'width' => (int) $table->width,
                'height' => (int) $table->height,
                'shape' => $table->shape,
                'status' => $table->status,
                'is_active' => (bool) $table->is_active,
                'updated_at' => $table->updated_at?->toIso8601String(),
                '_deleted' => ! $table->is_active,
            ]);

        // Get updated payment methods
        $paymentMethods = PaymentMethod::query()
            ->where('tenant_id', $this->tenantId())
            ->where('updated_at', '>', $since)
            ->get()
            ->map(fn ($pm) => [
                'id' => $pm->id,
                'code' => $pm->code,
                'name' => $pm->name,
                'type' => $pm->type,
                'icon' => $pm->icon,
                'charge_percentage' => (float) $pm->charge_percentage,
                'charge_fixed' => (float) $pm->charge_fixed,
                'requires_reference' => (bool) $pm->requires_reference,
                'opens_cash_drawer' => (bool) $pm->opens_cash_drawer,
                'sort_order' => (int) $pm->sort_order,
                'is_active' => (bool) $pm->is_active,
                'updated_at' => $pm->updated_at?->toIso8601String(),
                '_deleted' => ! $pm->is_active,
            ]);

        return $this->success([
            'categories' => $categories,
            'products' => $products,
            'tables' => $tables,
            'payment_methods' => $paymentMethods,
            'sync_timestamp' => now()->toIso8601String(),
            'counts' => [
                'categories' => $categories->count(),
                'products' => $products->count(),
                'tables' => $tables->count(),
                'payment_methods' => $paymentMethods->count(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/sync/transactions',
        summary: 'Bulk upload offline transactions',
        description: 'Upload transactions created offline. Duplicates detected by local_id',
        security: [['sanctum' => []]],
        tags: ['Sync'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['transactions', 'session_id'],
                properties: [
                    new OA\Property(property: 'session_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'transactions', type: 'array', items: new OA\Items(type: 'object')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Sync results with success/duplicate/error counts'),
            new OA\Response(response: 400, description: 'No outlet or invalid session'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function uploadTransactions(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $validated = $request->validate([
            'transactions' => 'required|array|min:1',
            'transactions.*.local_id' => 'required|string|max:50',
            'transactions.*.items' => 'required|array|min:1',
            'transactions.*.items.*.product_id' => 'required|uuid',
            'transactions.*.items.*.variant_id' => 'nullable|uuid',
            'transactions.*.items.*.quantity' => 'required|numeric|min:0.01',
            'transactions.*.items.*.unit_price' => 'required|numeric|min:0',
            'transactions.*.items.*.modifiers' => 'nullable|array',
            'transactions.*.items.*.discount_amount' => 'nullable|numeric|min:0',
            'transactions.*.items.*.notes' => 'nullable|string',
            'transactions.*.order_type' => 'required|string',
            'transactions.*.table_id' => 'nullable|uuid',
            'transactions.*.customer_id' => 'nullable|uuid',
            'transactions.*.subtotal' => 'required|numeric',
            'transactions.*.discount_amount' => 'nullable|numeric',
            'transactions.*.tax_amount' => 'nullable|numeric',
            'transactions.*.service_charge_amount' => 'nullable|numeric',
            'transactions.*.rounding' => 'nullable|numeric',
            'transactions.*.grand_total' => 'required|numeric',
            'transactions.*.payments' => 'required|array|min:1',
            'transactions.*.payments.*.payment_method_id' => 'required|uuid',
            'transactions.*.payments.*.amount' => 'required|numeric',
            'transactions.*.payments.*.reference_number' => 'nullable|string',
            'transactions.*.notes' => 'nullable|string',
            'transactions.*.created_at' => 'required|date',
            'session_id' => 'required|uuid',
        ]);

        $session = PosSession::find($validated['session_id']);
        if (! $session || $session->outlet_id !== $outletId) {
            return $this->error('Invalid session.', 400);
        }

        $outlet = $this->currentOutlet($request);
        $results = [];

        foreach ($validated['transactions'] as $txnData) {
            // Check for duplicate
            $existing = Transaction::where('outlet_id', $outletId)
                ->where('notes', 'like', "%local_id:{$txnData['local_id']}%")
                ->first();

            if ($existing) {
                $results[] = [
                    'local_id' => $txnData['local_id'],
                    'status' => 'duplicate',
                    'server_id' => $existing->id,
                    'transaction_number' => $existing->transaction_number,
                ];

                continue;
            }

            try {
                $transaction = DB::transaction(function () use ($txnData, $session, $outlet) {
                    $transactionNumber = $this->generateTransactionNumber($outlet->id);

                    $transaction = Transaction::create([
                        'tenant_id' => $this->tenantId(),
                        'outlet_id' => $outlet->id,
                        'pos_session_id' => $session->id,
                        'table_id' => $txnData['table_id'] ?? null,
                        'order_type' => $txnData['order_type'],
                        'customer_id' => $txnData['customer_id'] ?? null,
                        'user_id' => $this->user()->id,
                        'transaction_number' => $transactionNumber,
                        'type' => Transaction::TYPE_SALE,
                        'subtotal' => $txnData['subtotal'],
                        'discount_amount' => $txnData['discount_amount'] ?? 0,
                        'tax_amount' => $txnData['tax_amount'] ?? 0,
                        'service_charge_amount' => $txnData['service_charge_amount'] ?? 0,
                        'rounding' => $txnData['rounding'] ?? 0,
                        'grand_total' => $txnData['grand_total'],
                        'payment_amount' => collect($txnData['payments'])->sum('amount'),
                        'change_amount' => collect($txnData['payments'])->sum('amount') - $txnData['grand_total'],
                        'tax_percentage' => $outlet->tax_percentage ?? 0,
                        'service_charge_percentage' => $outlet->service_charge_percentage ?? 0,
                        'notes' => ($txnData['notes'] ?? '')."\nlocal_id:{$txnData['local_id']}",
                        'status' => Transaction::STATUS_COMPLETED,
                        'completed_at' => $txnData['created_at'],
                        'created_at' => $txnData['created_at'],
                    ]);

                    // Create items
                    foreach ($txnData['items'] as $item) {
                        $product = Product::find($item['product_id']);
                        $itemName = $product?->name ?? 'Unknown Product';

                        TransactionItem::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $item['product_id'],
                            'product_variant_id' => $item['variant_id'] ?? null,
                            'item_name' => $itemName,
                            'item_sku' => $product?->sku ?? '',
                            'unit_name' => 'pcs',
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'base_price' => $item['unit_price'],
                            'modifiers_total' => 0,
                            'cost_price' => $product?->cost_price ?? 0,
                            'discount_amount' => $item['discount_amount'] ?? 0,
                            'subtotal' => ($item['unit_price'] * $item['quantity']) - ($item['discount_amount'] ?? 0),
                            'modifiers' => $item['modifiers'] ?? null,
                            'item_notes' => $item['notes'] ?? null,
                        ]);
                    }

                    // Create payments
                    foreach ($txnData['payments'] as $payment) {
                        TransactionPayment::create([
                            'transaction_id' => $transaction->id,
                            'payment_method_id' => $payment['payment_method_id'],
                            'amount' => $payment['amount'],
                            'charge_amount' => 0,
                            'reference_number' => $payment['reference_number'] ?? null,
                        ]);
                    }

                    return $transaction;
                });

                $results[] = [
                    'local_id' => $txnData['local_id'],
                    'status' => 'success',
                    'server_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'local_id' => $txnData['local_id'],
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('status', 'success')->count();
        $duplicateCount = collect($results)->where('status', 'duplicate')->count();
        $errorCount = collect($results)->where('status', 'error')->count();

        return $this->success([
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'success' => $successCount,
                'duplicate' => $duplicateCount,
                'error' => $errorCount,
            ],
        ], "Synced {$successCount} transactions, {$duplicateCount} duplicates, {$errorCount} errors");
    }

    #[OA\Post(
        path: '/sync/session',
        summary: 'Sync POS session (open/close from offline)',
        security: [['sanctum' => []]],
        tags: ['Sync'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['action', 'local_id'],
                properties: [
                    new OA\Property(property: 'action', type: 'string', enum: ['open', 'close']),
                    new OA\Property(property: 'local_id', type: 'string'),
                    new OA\Property(property: 'opening_cash', type: 'number'),
                    new OA\Property(property: 'closing_cash', type: 'number'),
                    new OA\Property(property: 'session_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'opened_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'closed_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Session opened/closed or existing session returned'),
            new OA\Response(response: 400, description: 'No outlet selected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Session not found'),
        ]
    )]
    public function syncSession(Request $request): JsonResponse
    {
        $outletId = $this->currentOutletId($request);

        if (! $outletId) {
            return $this->error('No outlet selected.', 400);
        }

        $validated = $request->validate([
            'action' => 'required|in:open,close',
            'local_id' => 'required|string|max:50',
            'opening_cash' => 'required_if:action,open|numeric|min:0',
            'closing_cash' => 'required_if:action,close|numeric|min:0',
            'session_id' => 'required_if:action,close|uuid',
            'opened_at' => 'required_if:action,open|date',
            'closed_at' => 'required_if:action,close|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['action'] === 'open') {
            // Check for existing session
            $existing = PosSession::where('outlet_id', $outletId)
                ->where('user_id', $this->user()->id)
                ->where('status', PosSession::STATUS_OPEN)
                ->first();

            if ($existing) {
                return $this->success([
                    'session_id' => $existing->id,
                    'session_number' => $existing->session_number,
                    'status' => 'existing',
                ], 'Using existing open session');
            }

            $sessionNumber = $this->generateSessionNumber($outletId);

            $session = PosSession::create([
                'outlet_id' => $outletId,
                'user_id' => $this->user()->id,
                'session_number' => $sessionNumber,
                'opening_cash' => $validated['opening_cash'],
                'opening_notes' => ($validated['notes'] ?? '')."\nlocal_id:{$validated['local_id']}",
                'opened_at' => $validated['opened_at'],
                'status' => PosSession::STATUS_OPEN,
            ]);

            return $this->success([
                'session_id' => $session->id,
                'session_number' => $session->session_number,
                'status' => 'opened',
            ], 'Session opened successfully');
        } else {
            $session = PosSession::find($validated['session_id']);

            if (! $session || $session->outlet_id !== $outletId) {
                return $this->error('Session not found.', 404);
            }

            if (! $session->isOpen()) {
                return $this->success([
                    'session_id' => $session->id,
                    'status' => 'already_closed',
                ], 'Session already closed');
            }

            $session->close(
                closingCash: $validated['closing_cash'],
                closedBy: $this->user()->id,
                notes: $validated['notes'] ?? null
            );

            return $this->success([
                'session_id' => $session->id,
                'status' => 'closed',
                'expected_cash' => (float) $session->expected_cash,
                'cash_difference' => (float) $session->cash_difference,
            ], 'Session closed successfully');
        }
    }

    #[OA\Get(
        path: '/sync/customers/search',
        summary: 'Search customers (lazy load for POS)',
        security: [['sanctum' => []]],
        tags: ['Sync'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Search by name/phone/code/email (min 2 chars)', schema: new OA\Schema(type: 'string', minLength: 2)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of matching customers (max 20)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function searchCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $keyword = $request->q;

        $customers = Customer::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($customer) => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'membership_level' => $customer->membership_level,
                'total_points' => (float) $customer->total_points,
                'total_spent' => (float) $customer->total_spent,
                'total_visits' => (int) $customer->total_visits,
            ]);

        return $this->success($customers);
    }

    /**
     * Format product for sync
     */
    private function formatProductForSync(Product $product, string $outletId, bool $includeMeta = false): array
    {
        $price = $product->base_price;
        $isAvailable = true;

        if ($product->relationLoaded('productOutlets') && $product->productOutlets->isNotEmpty()) {
            $productOutlet = $product->productOutlets->first();
            if ($productOutlet->custom_price !== null) {
                $price = $productOutlet->custom_price;
            }
            $isAvailable = (bool) $productOutlet->is_available;
        }

        // Determine helper flags
        $hasVariants = $product->isVariant() && $product->relationLoaded('variants') && $product->variants->isNotEmpty();
        $hasModifiers = $product->relationLoaded('modifierGroups') && $product->modifierGroups->isNotEmpty();
        $isCombo = $product->isCombo();

        $data = [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'category_name' => $product->relationLoaded('category') ? $product->category?->name : null,
            'category_color' => $product->relationLoaded('category') ? $product->category?->color : null,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'image' => $product->image,
            'base_price' => (float) $product->base_price,
            'price' => (float) $price,
            'cost_price' => (float) $product->cost_price,
            'product_type' => $product->product_type,
            'track_stock' => (bool) $product->track_stock,
            'is_available' => $isAvailable,
            'allow_notes' => (bool) $product->allow_notes,
            'sort_order' => (int) $product->sort_order,
            'has_variants' => $hasVariants,
            'has_modifiers' => $hasModifiers,
            'is_combo' => $isCombo,
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];

        if ($includeMeta) {
            $data['is_active'] = (bool) $product->is_active;
            $data['show_in_pos'] = (bool) $product->show_in_pos;
            $data['_deleted'] = ! $product->is_active || ! $product->show_in_pos;
        }

        // Include variants with price_adjustment
        if ($product->relationLoaded('variants') && $product->variants->isNotEmpty()) {
            $basePrice = (float) $product->base_price;
            $data['variants'] = $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'name' => $v->name,
                'price' => (float) $v->price,
                'price_adjustment' => (float) $v->price - $basePrice,
                'is_active' => (bool) $v->is_active,
            ])->toArray();
        }

        // Include variant groups
        if ($product->relationLoaded('variantGroups') && $product->variantGroups->isNotEmpty()) {
            $data['variant_groups'] = $product->variantGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'is_required' => (bool) ($group->pivot->is_required ?? false),
                'options' => $group->options->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'price_adjustment' => (float) ($v->price_adjustment ?? 0),
                ])->toArray(),
            ])->toArray();
        }

        // Include modifier groups with display_name and selection_type
        if ($product->relationLoaded('modifierGroups') && $product->modifierGroups->isNotEmpty()) {
            $data['modifier_groups'] = $product->modifierGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'display_name' => $group->display_name ?? $group->name,
                'selection_type' => $group->selection_type,
                'is_required' => (bool) ($group->pivot->is_required ?? $group->is_required ?? false),
                'min_selections' => (int) ($group->pivot->min_selections ?? $group->min_selections ?? 0),
                'max_selections' => (int) ($group->pivot->max_selections ?? $group->max_selections ?? 0),
                'modifiers' => $group->modifiers->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'display_name' => $m->display_name ?? $m->name,
                    'price' => (float) $m->price,
                    'is_default' => (bool) $m->is_default,
                ])->toArray(),
            ])->toArray();
        }

        // Include combo items
        if ($isCombo && $product->relationLoaded('combo') && $product->combo) {
            $data['combo_items'] = $product->combo->items->map(fn ($ci) => [
                'id' => $ci->id,
                'product_id' => $ci->product_id,
                'product_name' => $ci->product?->name,
                'category_id' => $ci->category_id,
                'quantity' => (int) $ci->quantity,
                'is_required' => (bool) $ci->is_required,
            ])->toArray();
        }

        return $data;
    }

    /**
     * Generate transaction number
     */
    private function generateTransactionNumber(string $outletId): string
    {
        $today = now()->format('Ymd');
        $prefix = 'TRX';

        $lastTransaction = Transaction::where('outlet_id', $outletId)
            ->where('transaction_number', 'like', "{$prefix}{$today}%")
            ->orderByDesc('transaction_number')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$today}{$newNumber}";
    }

    /**
     * Generate session number
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
}
