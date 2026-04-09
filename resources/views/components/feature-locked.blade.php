@props([
    'feature' => null,
    'title' => 'Fitur Terkunci',
    'description' => 'Upgrade paket Anda untuk mengakses fitur ini.',
    'requiredPlan' => 'Growth',
    'showCard' => true,
])

@php
    $featureInfo = [
        'inventory_basic' => ['name' => 'Inventory Management', 'plan' => 'Growth'],
        'inventory_advanced' => ['name' => 'Advanced Inventory', 'plan' => 'Professional'],
        'table_management' => ['name' => 'Table Management', 'plan' => 'Growth'],
        'product_variant' => ['name' => 'Variant & Modifier', 'plan' => 'Growth'],
        'product_combo' => ['name' => 'Combo / Paket', 'plan' => 'Growth'],
        'discount_promo' => ['name' => 'Diskon & Promo', 'plan' => 'Growth'],
        'loyalty_points' => ['name' => 'Loyalty Points', 'plan' => 'Growth'],
        'recipe_bom' => ['name' => 'Recipe / BOM', 'plan' => 'Professional'],
        'stock_transfer' => ['name' => 'Stock Transfer', 'plan' => 'Professional'],
        'waiter_app' => ['name' => 'Waiter App', 'plan' => 'Professional'],
        'qr_order' => ['name' => 'QR Self-Order', 'plan' => 'Professional'],
        'kds' => ['name' => 'Kitchen Display System', 'plan' => 'Enterprise'],
        'manager_authorization' => ['name' => 'Manager Authorization', 'plan' => 'Professional'],
        'export_excel_pdf' => ['name' => 'Export Excel/PDF', 'plan' => 'Growth'],
        'api_access' => ['name' => 'API Access', 'plan' => 'Enterprise'],
        'custom_branding' => ['name' => 'Custom Branding', 'plan' => 'Enterprise'],
    ];

    $info = $featureInfo[$feature] ?? ['name' => $title, 'plan' => $requiredPlan];
@endphp

@if($showCard)
    <div {{ $attributes->merge(['class' => 'bg-secondary-50 border border-secondary-200 rounded-xl p-6 text-center']) }}>
        <div class="w-14 h-14 bg-secondary-200 rounded-xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-text mb-2">{{ $info['name'] }}</h3>
        <p class="text-text-light text-sm mb-4">{{ $description }}</p>
        <p class="text-sm text-text-light mb-4">
            Tersedia di paket <span class="font-semibold text-primary">{{ $info['plan'] }}</span> ke atas
        </p>
        <a href="{{ route('subscription.choose-plan') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-600 text-white font-semibold rounded-lg transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            Upgrade Sekarang
        </a>
    </div>
@else
    {{-- Inline banner version --}}
    <div {{ $attributes->merge(['class' => 'bg-warning-50 border border-warning-200 rounded-lg p-4 flex items-center gap-4']) }}>
        <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-text">{{ $info['name'] }} tersedia di paket {{ $info['plan'] }}</p>
            <p class="text-xs text-text-light">{{ $description }}</p>
        </div>
        <a href="{{ route('subscription.choose-plan') }}"
           class="shrink-0 px-4 py-2 bg-primary hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors">
            Upgrade
        </a>
    </div>
@endif
