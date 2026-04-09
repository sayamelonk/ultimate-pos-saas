<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchSetting;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockBatch;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Date range filter
        $dateRange = $request->get('date_range', 'today');
        $customStart = $request->get('start_date');
        $customEnd = $request->get('end_date');

        [$startDate, $endDate, $dateRangeLabel] = $this->getDateRange($dateRange, $customStart, $customEnd);

        $stats = [];
        $expiringBatches = collect();
        $lowStockItems = collect();
        $subscription = null;
        $hasInventoryBasic = false;
        $hasInventoryAdvanced = false;

        if ($user->isSuperAdmin()) {
            $stats = [
                'tenants' => Tenant::count(),
                'outlets' => Outlet::count(),
                'users' => User::count(),
                'active_tenants' => Tenant::where('is_active', true)->count(),
            ];
        } elseif ($user->tenant_id) {
            $tenant = $user->tenant;

            // Get current subscription for trial banner
            $subscription = Subscription::where('tenant_id', $user->tenant_id)
                ->with('plan')
                ->latest()
                ->first();

            // Check feature access based on subscription plan
            $hasInventoryBasic = $tenant->hasFeature('inventory_basic');
            $hasInventoryAdvanced = $tenant->hasFeature('inventory_advanced');

            // Transactions based on date range
            $ordersQuery = Transaction::where('tenant_id', $user->tenant_id)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate]);

            $ordersCount = $ordersQuery->count();
            $revenue = $ordersQuery->sum('grand_total');

            // Previous period for comparison
            $periodDays = $startDate->diffInDays($endDate) + 1;
            $prevStartDate = $startDate->copy()->subDays($periodDays);
            $prevEndDate = $startDate->copy()->subDay();

            $prevOrdersCount = Transaction::where('tenant_id', $user->tenant_id)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
                ->count();

            $prevRevenue = Transaction::where('tenant_id', $user->tenant_id)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
                ->sum('grand_total');

            // Calculate percentage changes
            $ordersChange = $prevOrdersCount > 0
                ? round((($ordersCount - $prevOrdersCount) / $prevOrdersCount) * 100, 1)
                : ($ordersCount > 0 ? 100 : 0);

            $revenueChange = $prevRevenue > 0
                ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1)
                : ($revenue > 0 ? 100 : 0);

            $stats = [
                'outlets' => Outlet::where('tenant_id', $user->tenant_id)->count(),
                'users' => User::where('tenant_id', $user->tenant_id)->count(),
                'orders' => $ordersCount,
                'revenue' => $revenue,
                'orders_change' => $ordersChange,
                'revenue_change' => $revenueChange,
            ];

            // Only query inventory data if tenant has the features
            if ($hasInventoryAdvanced) {
                // Get batch settings
                $batchSettings = BatchSetting::getForTenant($user->tenant_id);

                // Get expiring batches (within warning days) - requires inventory_advanced
                $expiringBatches = StockBatch::where('tenant_id', $user->tenant_id)
                    ->where('status', StockBatch::STATUS_ACTIVE)
                    ->where('current_quantity', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addDays($batchSettings->expiry_warning_days))
                    ->with(['inventoryItem.unit', 'outlet'])
                    ->orderBy('expiry_date', 'asc')
                    ->limit(10)
                    ->get();
            }

            if ($hasInventoryBasic) {
                // Get low stock items - requires inventory_basic
                $lowStockItems = InventoryStock::whereHas('outlet', function ($q) use ($user) {
                    $q->where('tenant_id', $user->tenant_id);
                })
                    ->whereHas('inventoryItem', function ($q) {
                        $q->whereColumn('inventory_stocks.quantity', '<=', 'inventory_items.reorder_point');
                    })
                    ->with(['inventoryItem.unit', 'outlet'])
                    ->limit(10)
                    ->get();
            }
        }

        return view('admin.dashboard', compact(
            'stats',
            'expiringBatches',
            'lowStockItems',
            'subscription',
            'hasInventoryBasic',
            'hasInventoryAdvanced',
            'dateRange',
            'dateRangeLabel',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get start and end dates based on date range selection
     */
    private function getDateRange(string $range, ?string $customStart, ?string $customEnd): array
    {
        $now = Carbon::now();

        return match ($range) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                'Hari Ini',
            ],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
                'Kemarin',
            ],
            'this_week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                'Minggu Ini',
            ],
            'last_week' => [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
                'Minggu Lalu',
            ],
            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                'Bulan Ini',
            ],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
                'Bulan Lalu',
            ],
            'this_year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                'Tahun Ini',
            ],
            'custom' => [
                $customStart ? Carbon::parse($customStart)->startOfDay() : $now->copy()->startOfDay(),
                $customEnd ? Carbon::parse($customEnd)->endOfDay() : $now->copy()->endOfDay(),
                'Custom',
            ],
            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                'Hari Ini',
            ],
        };
    }
}
