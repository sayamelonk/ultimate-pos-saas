<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();

        $stats = [];

        // Stats berbeda berdasarkan role
        if ($user->isSuperAdmin()) {
            // Super Admin melihat semua statistik
            $stats = [
                'tenants' => Tenant::count(),
                'outlets' => Outlet::count(),
                'users' => User::count(),
                'active_tenants' => Tenant::where('is_active', true)->count(),
            ];
        } elseif ($user->tenant_id) {
            // Tenant Admin melihat statistik tenant mereka saja
            $stats = [
                'outlets' => Outlet::where('tenant_id', $user->tenant_id)->count(),
                'users' => User::where('tenant_id', $user->tenant_id)->count(),
                'today_orders' => 0,
                'today_revenue' => 0,
            ];
        }

        return view('admin.dashboard', compact('stats'));
    }
}
