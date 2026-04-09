<?php

namespace App\View\Composers;

use App\Models\Tenant;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        $features = [
            'inventory_basic' => false,
            'inventory_advanced' => false,
            'recipe_bom' => false,
            'stock_transfer' => false,
            'discount_promo' => false,
            'product_variant' => false,
            'product_combo' => false,
            'table_management' => false,
            'waiter_app' => false,
            'qr_order' => false,
            'kds' => false,
            'manager_authorization' => false,
            'export_excel_pdf' => false,
            'loyalty_points' => false,
            'api_access' => false,
            'custom_branding' => false,
        ];

        if ($user && $user->tenant_id) {
            // Fresh load tenant with subscription to avoid caching issues
            $tenant = Tenant::with('activeSubscription.plan')->find($user->tenant_id);
            if ($tenant) {
                foreach ($features as $key => $value) {
                    $features[$key] = $tenant->hasFeature($key);
                }
            }
        }

        $view->with('sidebarFeatures', $features);
    }
}
