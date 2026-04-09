<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SettingsController extends Controller
{
    /**
     * Features available per subscription tier
     */
    private const FEATURES = [
        'pos_core' => ['starter', 'growth', 'professional', 'enterprise'],
        'product_variants' => ['growth', 'professional', 'enterprise'],
        'product_combos' => ['growth', 'professional', 'enterprise'],
        'modifiers' => ['growth', 'professional', 'enterprise'],
        'table_management' => ['growth', 'professional', 'enterprise'],
        'inventory_basic' => ['growth', 'professional', 'enterprise'],
        'inventory_advanced' => ['professional', 'enterprise'],
        'recipe_bom' => ['professional', 'enterprise'],
        'stock_transfer' => ['professional', 'enterprise'],
        'manager_authorization' => ['professional', 'enterprise'],
        'waiter_app' => ['professional', 'enterprise'],
        'qr_order' => ['professional', 'enterprise'],
        'kds' => ['enterprise'],
        'api_access' => ['enterprise'],
        'custom_branding' => ['enterprise'],
        'export_excel' => ['growth', 'professional', 'enterprise'],
        'loyalty_points' => ['growth', 'professional', 'enterprise'],
        'discounts' => ['growth', 'professional', 'enterprise'],
    ];

    /**
     * Get all settings bundled
     */
    #[OA\Get(
        path: '/settings',
        summary: 'Get all settings (bundled)',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'All settings'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'outlet' => $this->getOutletSettings($request),
            'pos' => $this->getPosSettings($request),
            'authorization' => $this->getAuthorizationSettings($request),
            'receipt' => $this->getReceiptSettings($request),
            'printer' => $this->getPrinterSettings($request),
            'subscription' => $this->getSubscriptionInfo(),
        ]);
    }

    /**
     * Get outlet settings
     */
    #[OA\Get(
        path: '/settings/outlet',
        summary: 'Get outlet settings including tax configuration',
        description: 'Returns outlet settings with tax and service charge configuration. Tax settings inherit from tenant when outlet values are null.',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Outlet settings',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'outlet_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'outlet_name', type: 'string', example: 'Main Store'),
                                new OA\Property(property: 'outlet_code', type: 'string', example: 'MAIN'),
                                new OA\Property(property: 'address', type: 'string', nullable: true),
                                new OA\Property(property: 'city', type: 'string', nullable: true),
                                new OA\Property(property: 'phone', type: 'string', nullable: true),
                                new OA\Property(property: 'email', type: 'string', nullable: true),
                                new OA\Property(property: 'tax_enabled', type: 'boolean', description: 'Whether tax calculation is enabled', example: true),
                                new OA\Property(property: 'tax_mode', type: 'string', enum: ['inclusive', 'exclusive'], description: 'Tax calculation mode. Inclusive means price already includes tax, exclusive means tax is added on top', example: 'exclusive'),
                                new OA\Property(property: 'tax_percentage', type: 'number', format: 'float', description: 'Tax percentage (e.g., 10 for 10%)', example: 10.0),
                                new OA\Property(property: 'service_charge_enabled', type: 'boolean', description: 'Whether service charge is enabled', example: true),
                                new OA\Property(property: 'service_charge_percentage', type: 'number', format: 'float', description: 'Service charge percentage', example: 5.0),
                                new OA\Property(property: 'opening_time', type: 'string', format: 'time', nullable: true, example: '08:00'),
                                new OA\Property(property: 'closing_time', type: 'string', format: 'time', nullable: true, example: '22:00'),
                                new OA\Property(property: 'receipt_header', type: 'string', nullable: true),
                                new OA\Property(property: 'receipt_footer', type: 'string', example: 'Terima kasih atas kunjungan Anda!'),
                                new OA\Property(property: 'receipt_show_logo', type: 'boolean', example: true),
                                new OA\Property(property: 'currency', type: 'string', example: 'IDR'),
                                new OA\Property(property: 'timezone', type: 'string', example: 'Asia/Jakarta'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Outlet not found'),
        ]
    )]
    public function outlet(Request $request): JsonResponse
    {
        $data = $this->getOutletSettings($request);

        if (! $data) {
            return $this->notFound('Outlet not found');
        }

        return $this->success($data);
    }

    /**
     * Get POS settings
     */
    #[OA\Get(
        path: '/settings/pos',
        summary: 'Get POS operational settings',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'POS settings'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function pos(Request $request): JsonResponse
    {
        return $this->success($this->getPosSettings($request));
    }

    /**
     * Get authorization settings
     */
    #[OA\Get(
        path: '/settings/authorization',
        summary: 'Get manager authorization settings',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Authorization settings'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function authorization(Request $request): JsonResponse
    {
        return $this->success($this->getAuthorizationSettings($request));
    }

    /**
     * Get receipt settings
     */
    #[OA\Get(
        path: '/settings/receipt',
        summary: 'Get receipt/printing settings',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Receipt settings'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function receipt(Request $request): JsonResponse
    {
        return $this->success($this->getReceiptSettings($request));
    }

    /**
     * Get printer settings
     */
    #[OA\Get(
        path: '/settings/printer',
        summary: 'Get printer configuration',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(name: 'X-Outlet-Id', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Printer settings'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function printer(Request $request): JsonResponse
    {
        return $this->success($this->getPrinterSettings($request));
    }

    /**
     * Get subscription info
     */
    #[OA\Get(
        path: '/settings/subscription',
        summary: 'Get subscription information',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        responses: [
            new OA\Response(response: 200, description: 'Subscription info'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function subscription(): JsonResponse
    {
        return $this->success($this->getSubscriptionInfo());
    }

    /**
     * Get feature flags
     */
    #[OA\Get(
        path: '/settings/features',
        summary: 'Get all feature flags',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        responses: [
            new OA\Response(response: 200, description: 'Feature flags'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function features(): JsonResponse
    {
        $tenant = $this->user()?->tenant;
        $currentPlan = $tenant?->subscription?->plan?->slug ?? 'starter';

        $features = [];
        foreach (self::FEATURES as $feature => $allowedPlans) {
            $features[$feature] = in_array($currentPlan, $allowedPlans);
        }

        return $this->success($features);
    }

    /**
     * Check specific feature
     */
    #[OA\Get(
        path: '/settings/features/{feature}',
        summary: 'Check if specific feature is enabled',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(name: 'feature', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Feature status'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Feature not found'),
        ]
    )]
    public function checkFeature(string $feature): JsonResponse
    {
        if (! array_key_exists($feature, self::FEATURES)) {
            return $this->notFound('Feature not found');
        }

        $tenant = $this->user()?->tenant;
        $currentPlan = $tenant?->subscription?->plan?->slug ?? 'starter';
        $allowedPlans = self::FEATURES[$feature];
        $enabled = in_array($currentPlan, $allowedPlans);

        // Find minimum required plan
        $planOrder = ['starter', 'growth', 'professional', 'enterprise'];
        $requiredPlan = null;
        foreach ($planOrder as $plan) {
            if (in_array($plan, $allowedPlans)) {
                $requiredPlan = $plan;
                break;
            }
        }

        return $this->success([
            'feature' => $feature,
            'enabled' => $enabled,
            'required_plan' => $requiredPlan,
        ]);
    }

    /**
     * Get outlet settings data
     */
    private function getOutletSettings(Request $request): ?array
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return null;
        }

        $tenant = $this->user()?->tenant;

        // Tax settings with inheritance from tenant
        $taxEnabled = $outlet->tax_enabled ?? $tenant?->tax_enabled ?? false;
        $taxMode = $outlet->tax_mode ?? $tenant?->tax_mode ?? 'exclusive';
        $serviceChargeEnabled = $outlet->service_charge_enabled ?? $tenant?->service_charge_enabled ?? false;

        return [
            'outlet_id' => $outlet->id,
            'outlet_name' => $outlet->name,
            'outlet_code' => $outlet->code,
            'address' => $outlet->address,
            'city' => $outlet->city,
            'phone' => $outlet->phone,
            'email' => $outlet->email,
            'tax_enabled' => (bool) $taxEnabled,
            'tax_mode' => $taxMode,
            'tax_percentage' => (float) ($outlet->tax_percentage ?? $tenant?->tax_percentage ?? 0),
            'service_charge_enabled' => (bool) $serviceChargeEnabled,
            'service_charge_percentage' => (float) ($outlet->service_charge_percentage ?? $tenant?->service_charge_percentage ?? 0),
            'opening_time' => $outlet->opening_time,
            'closing_time' => $outlet->closing_time,
            'receipt_header' => $outlet->receipt_header ?? $tenant?->name,
            'receipt_footer' => $outlet->receipt_footer ?? 'Terima kasih atas kunjungan Anda!',
            'receipt_show_logo' => $outlet->receipt_show_logo ?? true,
            'currency' => $tenant?->currency ?? 'IDR',
            'timezone' => $tenant?->timezone ?? 'Asia/Jakarta',
        ];
    }

    /**
     * Get POS settings data
     */
    private function getPosSettings(Request $request): array
    {
        $tenant = $this->user()?->tenant;
        $settings = $tenant?->settings ?? [];

        return [
            'require_customer' => $settings['require_customer'] ?? false,
            'allow_negative_stock' => $settings['allow_negative_stock'] ?? false,
            'auto_print_receipt' => $settings['auto_print_receipt'] ?? true,
            'default_order_type' => $settings['default_order_type'] ?? 'dine_in',
            'enable_table_management' => $settings['enable_table_management'] ?? false,
            'enable_kitchen_display' => $settings['enable_kitchen_display'] ?? false,
            'enable_customer_display' => $settings['enable_customer_display'] ?? false,
            'receipt_printer_type' => $settings['receipt_printer_type'] ?? 'thermal',
            'receipt_paper_size' => $settings['receipt_paper_size'] ?? '80mm',
            'enable_cash_drawer' => $settings['enable_cash_drawer'] ?? false,
            'enable_barcode_scanner' => $settings['enable_barcode_scanner'] ?? true,
            'session_required' => $settings['session_required'] ?? true,
            'allow_held_orders' => $settings['allow_held_orders'] ?? true,
            'held_order_expiry_hours' => $settings['held_order_expiry_hours'] ?? 24,
        ];
    }

    /**
     * Get authorization settings data
     */
    private function getAuthorizationSettings(Request $request): array
    {
        $tenant = $this->user()?->tenant;
        $settings = $tenant?->settings ?? [];

        return [
            'require_auth_for_void' => $settings['require_auth_for_void'] ?? true,
            'require_auth_for_refund' => $settings['require_auth_for_refund'] ?? true,
            'require_auth_for_discount' => $settings['require_auth_for_discount'] ?? false,
            'require_auth_for_price_change' => $settings['require_auth_for_price_change'] ?? true,
            'require_auth_for_cash_out' => $settings['require_auth_for_cash_out'] ?? true,
            'max_discount_without_auth' => $settings['max_discount_without_auth'] ?? 10,
            'manager_pin_required' => $settings['manager_pin_required'] ?? true,
        ];
    }

    /**
     * Get receipt settings data
     */
    private function getReceiptSettings(Request $request): array
    {
        $outlet = $this->currentOutlet($request);
        $tenant = $this->user()?->tenant;
        $settings = $tenant?->settings ?? [];

        return [
            'header' => $outlet?->receipt_header ?? $tenant?->name,
            'footer' => $outlet?->receipt_footer ?? 'Terima kasih atas kunjungan Anda!',
            'show_logo' => $outlet?->receipt_show_logo ?? true,
            'logo_url' => $tenant?->logo_url,
            'paper_size' => $settings['receipt_paper_size'] ?? '80mm',
            'show_cashier_name' => $settings['receipt_show_cashier'] ?? true,
            'show_outlet_address' => $settings['receipt_show_address'] ?? true,
            'show_outlet_phone' => $settings['receipt_show_phone'] ?? true,
            'show_tax_breakdown' => $settings['receipt_show_tax_breakdown'] ?? true,
            'show_payment_method' => $settings['receipt_show_payment_method'] ?? true,
            'show_transaction_number' => $settings['receipt_show_transaction_number'] ?? true,
            'show_qr_code' => $settings['receipt_show_qr_code'] ?? false,
        ];
    }

    /**
     * Get printer settings data
     */
    private function getPrinterSettings(Request $request): array
    {
        $tenant = $this->user()?->tenant;
        $settings = $tenant?->settings ?? [];

        return [
            'receipt_printer' => [
                'enabled' => $settings['receipt_printer_enabled'] ?? false,
                'type' => $settings['receipt_printer_type'] ?? 'thermal',
                'ip_address' => $settings['receipt_printer_ip'] ?? null,
                'port' => $settings['receipt_printer_port'] ?? 9100,
                'paper_size' => $settings['receipt_paper_size'] ?? '80mm',
            ],
            'kitchen_printer' => [
                'enabled' => $settings['kitchen_printer_enabled'] ?? false,
                'type' => $settings['kitchen_printer_type'] ?? 'thermal',
                'ip_address' => $settings['kitchen_printer_ip'] ?? null,
                'port' => $settings['kitchen_printer_port'] ?? 9100,
            ],
        ];
    }

    /**
     * Get subscription info data
     */
    private function getSubscriptionInfo(): array
    {
        $tenant = $this->user()?->tenant;
        $subscription = $tenant?->subscription;
        $plan = $subscription?->plan;

        // Count usage
        $outletsUsed = $tenant?->outlets()->count() ?? 0;
        $usersUsed = $tenant?->users()->count() ?? 0;
        $productsUsed = $tenant?->products()->count() ?? 0;

        $currentPlan = $plan?->slug ?? 'starter';
        $features = [];
        foreach (self::FEATURES as $feature => $allowedPlans) {
            $features[$feature] = in_array($currentPlan, $allowedPlans);
        }

        return [
            'plan_name' => $plan?->name ?? 'Free',
            'plan_slug' => $plan?->slug ?? 'free',
            'status' => $subscription?->status ?? 'inactive',
            'is_trial' => $subscription?->is_trial ?? false,
            'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
            'current_period_start' => $subscription?->current_period_start?->toIso8601String(),
            'current_period_end' => $subscription?->current_period_end?->toIso8601String(),
            'outlet_limit' => $plan?->outlet_limit ?? 1,
            'user_limit' => $plan?->user_limit ?? 3,
            'product_limit' => $plan?->product_limit ?? 100,
            'outlets_used' => $outletsUsed,
            'users_used' => $usersUsed,
            'products_used' => $productsUsed,
            'features' => $features,
        ];
    }
}
