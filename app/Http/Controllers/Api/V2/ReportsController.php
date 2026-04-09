<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\PosSession;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ReportsController extends Controller
{
    /**
     * Get sales summary
     */
    #[OA\Get(
        path: '/reports/sales/summary',
        summary: 'Get sales summary report',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'session_id', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sales summary data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function salesSummary(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->notFound('Outlet not found');
        }

        $dates = $this->parseDateRange($request);

        $query = Transaction::where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outlet->id)
            ->whereBetween('completed_at', [$dates['from'], $dates['to']]);

        if ($request->has('session_id')) {
            $query->where('pos_session_id', $request->input('session_id'));
        }

        // Sales (completed, type=sale)
        $salesQuery = (clone $query)
            ->where('type', Transaction::TYPE_SALE)
            ->where('status', Transaction::STATUS_COMPLETED);

        $totalSales = $salesQuery->sum('grand_total');
        $totalTransactions = $salesQuery->count();
        $totalDiscount = $salesQuery->sum('discount_amount');
        $totalTax = $salesQuery->sum('tax_amount');
        $totalServiceCharge = $salesQuery->sum('service_charge_amount');
        $subtotal = $salesQuery->sum('subtotal');

        // Refunds
        $refundsQuery = (clone $query)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('status', Transaction::STATUS_COMPLETED);

        $totalRefunds = $refundsQuery->sum('grand_total');

        // Voids
        $voidsQuery = Transaction::where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outlet->id)
            ->whereBetween('updated_at', [$dates['from'], $dates['to']])
            ->where('status', Transaction::STATUS_VOIDED);

        if ($request->has('session_id')) {
            $voidsQuery->where('pos_session_id', $request->input('session_id'));
        }

        $totalVoids = $voidsQuery->count();

        $netSales = $totalSales - $totalRefunds;
        $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        return $this->success([
            'total_sales' => (float) $totalSales,
            'total_transactions' => $totalTransactions,
            'average_transaction' => round($averageTransaction, 2),
            'total_discount' => (float) $totalDiscount,
            'total_tax' => (float) $totalTax,
            'total_service_charge' => (float) $totalServiceCharge,
            'net_sales' => (float) $netSales,
            'total_refunds' => (float) $totalRefunds,
            'total_voids' => $totalVoids,
        ]);
    }

    /**
     * Get sales by payment method
     */
    #[OA\Get(
        path: '/reports/sales/by-payment-method',
        summary: 'Get sales breakdown by payment method',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sales by payment method'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function byPaymentMethod(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->notFound('Outlet not found');
        }

        $dates = $this->parseDateRange($request);

        $payments = TransactionPayment::query()
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->join('payment_methods', 'transaction_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('transactions.tenant_id', $this->tenantId())
            ->where('transactions.outlet_id', $outlet->id)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->where('transactions.type', Transaction::TYPE_SALE)
            ->whereBetween('transactions.completed_at', [$dates['from'], $dates['to']])
            ->select(
                'payment_methods.id as payment_method_id',
                'payment_methods.name as payment_method_name',
                'payment_methods.type as payment_method_type',
                DB::raw('SUM(transaction_payments.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT transactions.id) as transaction_count')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name', 'payment_methods.type')
            ->orderByDesc('total_amount')
            ->get();

        $grandTotal = $payments->sum('total_amount');

        $data = $payments->map(fn ($payment) => [
            'payment_method_id' => $payment->payment_method_id,
            'payment_method_name' => $payment->payment_method_name,
            'payment_method_type' => $payment->payment_method_type,
            'total_amount' => (float) $payment->total_amount,
            'transaction_count' => $payment->transaction_count,
            'percentage' => $grandTotal > 0 ? round(($payment->total_amount / $grandTotal) * 100, 2) : 0,
        ]);

        return $this->success($data);
    }

    /**
     * Get sales by category
     */
    #[OA\Get(
        path: '/reports/sales/by-category',
        summary: 'Get sales breakdown by product category',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sales by category'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function byCategory(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->notFound('Outlet not found');
        }

        $dates = $this->parseDateRange($request);

        $categories = TransactionItem::query()
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('transactions.tenant_id', $this->tenantId())
            ->where('transactions.outlet_id', $outlet->id)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->where('transactions.type', Transaction::TYPE_SALE)
            ->whereBetween('transactions.completed_at', [$dates['from'], $dates['to']])
            ->select(
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                DB::raw('SUM(transaction_items.quantity) as total_quantity'),
                DB::raw('SUM(transaction_items.subtotal) as total_amount'),
                DB::raw('COUNT(DISTINCT transaction_items.id) as item_count')
            )
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        $grandTotal = $categories->sum('total_amount');

        $data = $categories->map(fn ($cat) => [
            'category_id' => $cat->category_id,
            'category_name' => $cat->category_name,
            'total_quantity' => (float) $cat->total_quantity,
            'total_amount' => (float) $cat->total_amount,
            'item_count' => $cat->item_count,
            'percentage' => $grandTotal > 0 ? round(($cat->total_amount / $grandTotal) * 100, 2) : 0,
        ]);

        return $this->success($data);
    }

    /**
     * Get sales by product
     */
    #[OA\Get(
        path: '/reports/sales/by-product',
        summary: 'Get sales breakdown by product',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['quantity', 'amount'])),
            new OA\Parameter(name: 'order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sales by product'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function byProduct(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->notFound('Outlet not found');
        }

        $dates = $this->parseDateRange($request);
        $sort = $request->input('sort', 'amount');
        $order = $request->input('order', 'desc');
        $limit = min($request->input('limit', 50), 100);

        $sortColumn = $sort === 'quantity' ? 'total_quantity' : 'total_amount';

        $products = TransactionItem::query()
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('transactions.tenant_id', $this->tenantId())
            ->where('transactions.outlet_id', $outlet->id)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->where('transactions.type', Transaction::TYPE_SALE)
            ->whereBetween('transactions.completed_at', [$dates['from'], $dates['to']])
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'product_categories.name as category_name',
                DB::raw('SUM(transaction_items.quantity) as total_quantity'),
                DB::raw('SUM(transaction_items.subtotal) as total_amount'),
                DB::raw('COUNT(DISTINCT transactions.id) as transaction_count')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'product_categories.name')
            ->orderBy($sortColumn, $order)
            ->limit($limit)
            ->get();

        $data = $products->map(fn ($product) => [
            'product_id' => $product->product_id,
            'product_name' => $product->product_name,
            'product_sku' => $product->product_sku,
            'category_name' => $product->category_name,
            'total_quantity' => (float) $product->total_quantity,
            'total_amount' => (float) $product->total_amount,
            'transaction_count' => $product->transaction_count,
        ]);

        return $this->success($data);
    }

    /**
     * Get hourly sales
     */
    #[OA\Get(
        path: '/reports/sales/hourly',
        summary: 'Get hourly sales breakdown',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hourly sales data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function hourlySales(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->notFound('Outlet not found');
        }

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : now();

        // Use driver-agnostic hour extraction
        $driver = DB::getDriverName();
        $hourExpression = $driver === 'sqlite'
            ? "strftime('%H', completed_at)"
            : 'HOUR(completed_at)';

        $hourlyData = Transaction::where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outlet->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_SALE)
            ->whereDate('completed_at', $date)
            ->select(
                DB::raw("CAST({$hourExpression} AS INTEGER) as hour"),
                DB::raw('SUM(grand_total) as total_sales'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw($hourExpression))
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        // Fill all 24 hours
        $data = [];
        for ($i = 0; $i < 24; $i++) {
            $hourData = $hourlyData->get($i);
            $data[] = [
                'hour' => $i,
                'total_sales' => $hourData ? (float) $hourData->total_sales : 0,
                'transaction_count' => $hourData ? $hourData->transaction_count : 0,
            ];
        }

        return $this->success($data);
    }

    /**
     * Get daily sales
     */
    #[OA\Get(
        path: '/reports/sales/daily',
        summary: 'Get daily sales breakdown',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daily sales data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function dailySales(Request $request): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return $this->notFound('Outlet not found');
        }

        $dates = $this->parseDateRange($request);

        // Use driver-agnostic date extraction
        $driver = DB::getDriverName();
        $dateExpression = $driver === 'sqlite'
            ? 'date(completed_at)'
            : 'DATE(completed_at)';

        $dailyData = Transaction::where('tenant_id', $this->tenantId())
            ->where('outlet_id', $outlet->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_SALE)
            ->whereBetween('completed_at', [$dates['from'], $dates['to']])
            ->select(
                DB::raw("{$dateExpression} as date"),
                DB::raw('SUM(grand_total) as total_sales'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw($dateExpression))
            ->orderBy('date')
            ->get();

        $data = $dailyData->map(fn ($day) => [
            'date' => $day->date,
            'total_sales' => (float) $day->total_sales,
            'transaction_count' => $day->transaction_count,
            'average_transaction' => $day->transaction_count > 0
                ? round($day->total_sales / $day->transaction_count, 2)
                : 0,
        ]);

        return $this->success($data);
    }

    /**
     * Get session report
     */
    #[OA\Get(
        path: '/reports/sessions/{session}',
        summary: 'Get session/shift report',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'session', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Session report'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Session not found'),
        ]
    )]
    public function sessionReport(Request $request, string $session): JsonResponse
    {
        $outlet = $this->currentOutlet($request);

        $posSession = PosSession::where('id', $session)
            ->where('outlet_id', $outlet?->id)
            ->with('user:id,name')
            ->first();

        if (! $posSession) {
            return $this->notFound('Session not found');
        }

        // Get sales
        $salesQuery = Transaction::where('pos_session_id', $posSession->id)
            ->where('type', Transaction::TYPE_SALE)
            ->where('status', Transaction::STATUS_COMPLETED);

        $totalSales = $salesQuery->sum('grand_total');
        $totalTransactions = $salesQuery->count();

        // Get refunds
        $refundsQuery = Transaction::where('pos_session_id', $posSession->id)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('status', Transaction::STATUS_COMPLETED);

        $totalRefunds = $refundsQuery->sum('grand_total');

        // Get voids
        $totalVoids = Transaction::where('pos_session_id', $posSession->id)
            ->where('status', Transaction::STATUS_VOIDED)
            ->count();

        // Payment breakdown
        $paymentBreakdown = TransactionPayment::query()
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->join('payment_methods', 'transaction_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('transactions.pos_session_id', $posSession->id)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->where('transactions.type', Transaction::TYPE_SALE)
            ->select(
                'payment_methods.name as payment_method',
                DB::raw('SUM(transaction_payments.amount) as amount'),
                DB::raw('COUNT(DISTINCT transactions.id) as count')
            )
            ->groupBy('payment_methods.name')
            ->get()
            ->map(fn ($p) => [
                'payment_method' => $p->payment_method,
                'amount' => (float) $p->amount,
                'count' => $p->count,
            ]);

        return $this->success([
            'session_id' => $posSession->id,
            'session_number' => $posSession->session_number,
            'cashier_name' => $posSession->user?->name,
            'opened_at' => $posSession->opened_at?->toIso8601String(),
            'closed_at' => $posSession->closed_at?->toIso8601String(),
            'status' => $posSession->status,
            'opening_cash' => (float) $posSession->opening_cash,
            'closing_cash' => $posSession->closing_cash ? (float) $posSession->closing_cash : null,
            'expected_cash' => $posSession->expected_cash ? (float) $posSession->expected_cash : null,
            'cash_difference' => $posSession->cash_difference ? (float) $posSession->cash_difference : null,
            'total_sales' => (float) $totalSales,
            'total_transactions' => $totalTransactions,
            'total_refunds' => (float) $totalRefunds,
            'total_voids' => $totalVoids,
            'payment_breakdown' => $paymentBreakdown,
        ]);
    }
}
