<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchSetting;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockBatch;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $stats = [];
        $expiringBatches = collect();
        $lowStockItems = collect();

        if ($user->isSuperAdmin()) {
            $stats = [
                'tenants' => Tenant::count(),
                'outlets' => Outlet::count(),
                'users' => User::count(),
                'active_tenants' => Tenant::where('is_active', true)->count(),
            ];
        } elseif ($user->tenant_id) {
            // Today's transactions
            $todayOrdersCount = Transaction::where('tenant_id', $user->tenant_id)
                ->whereDate('created_at', today())
                ->where('status', 'completed')
                ->count();

            $todayRevenue = Transaction::where('tenant_id', $user->tenant_id)
                ->whereDate('created_at', today())
                ->where('status', 'completed')
                ->sum('grand_total');

            $stats = [
                'outlets' => Outlet::where('tenant_id', $user->tenant_id)->count(),
                'users' => User::where('tenant_id', $user->tenant_id)->count(),
                'today_orders' => $todayOrdersCount,
                'today_revenue' => $todayRevenue,
            ];

            // Get batch settings
            $batchSettings = BatchSetting::getForTenant($user->tenant_id);

            // Get expiring batches (within warning days)
            $expiringBatches = StockBatch::where('tenant_id', $user->tenant_id)
                ->where('status', StockBatch::STATUS_ACTIVE)
                ->where('current_quantity', '>', 0)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays($batchSettings->expiry_warning_days))
                ->with(['inventoryItem.unit', 'outlet'])
                ->orderBy('expiry_date', 'asc')
                ->limit(10)
                ->get();

            // Get low stock items
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

        return view('admin.dashboard', compact('stats', 'expiringBatches', 'lowStockItems'));
    }
}
