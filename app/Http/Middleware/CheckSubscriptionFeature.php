<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionFeature
{
    /**
     * Handle an incoming request.
     *
     * Check if tenant's subscription plan includes the required feature.
     * Features are defined in subscription_plans.features JSON column.
     *
     * Usage: middleware('feature:inventory_basic') or middleware('feature:api_access')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tenant) {
            return $this->denyAccess($request, $feature);
        }

        $tenant = $user->tenant;

        if (! $tenant->hasFeature($feature)) {
            return $this->denyAccess($request, $feature);
        }

        return $next($request);
    }

    protected function denyAccess(Request $request, string $feature): Response
    {
        $featureLabels = [
            'pos_core' => 'POS Core',
            'product_management' => 'Product Management',
            'basic_reports' => 'Basic Reports',
            'customer_management' => 'Customer Management',
            'multi_payment' => 'Multi Payment Method',
            'product_variant' => 'Product Variants',
            'product_combo' => 'Combo Products',
            'discount_promo' => 'Discounts & Promos',
            'inventory_basic' => 'Basic Inventory',
            'inventory_advanced' => 'Advanced Inventory',
            'table_management' => 'Table Management',
            'recipe_bom' => 'Recipe & BOM',
            'stock_transfer' => 'Stock Transfer',
            'waiter_app' => 'Waiter App',
            'qr_order' => 'QR Order',
            'kds' => 'Kitchen Display System',
            'manager_authorization' => 'Manager Authorization',
            'export_excel_pdf' => 'Export Excel/PDF',
            'loyalty_points' => 'Loyalty Points',
            'api_access' => 'API Access',
            'custom_branding' => 'Custom Branding',
            'dedicated_support' => 'Dedicated Support',
            'sla_uptime' => 'SLA Uptime Guarantee',
        ];

        $featureLabel = $featureLabels[$feature] ?? ucfirst(str_replace('_', ' ', $feature));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "This feature ({$featureLabel}) is not available in your current plan. Please upgrade to access it.",
                'feature' => $feature,
                'upgrade_url' => route('subscription.plans'),
            ], 403);
        }

        return redirect()->route('subscription.plans')
            ->with('warning', "This feature ({$featureLabel}) is not available in your current plan. Please upgrade to access it.");
    }
}
