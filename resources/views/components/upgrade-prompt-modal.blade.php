@props([
    'feature' => null,
    'featureName' => 'Fitur ini',
    'requiredPlan' => 'Growth',
    'id' => 'upgrade-prompt-modal',
])

@php
    $featureDescriptions = [
        'inventory_basic' => [
            'name' => 'Inventory Management',
            'description' => 'Kelola stok bahan baku dengan tracking real-time, low stock alert, dan stock adjustment.',
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'plan' => 'Growth',
        ],
        'inventory_advanced' => [
            'name' => 'Advanced Inventory',
            'description' => 'Batch tracking, expiry date, Purchase Order, Goods Receive, dan supplier management.',
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'plan' => 'Professional',
        ],
        'table_management' => [
            'name' => 'Table Management',
            'description' => 'Kelola meja dan denah resto dengan status real-time dan assign order ke meja.',
            'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
            'plan' => 'Growth',
        ],
        'product_variant' => [
            'name' => 'Variant & Modifier',
            'description' => 'Buat produk dengan variasi (size, topping) dan modifier groups untuk customization.',
            'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
            'plan' => 'Growth',
        ],
        'product_combo' => [
            'name' => 'Combo / Paket',
            'description' => 'Buat paket bundling produk dengan harga special dan pilihan item.',
            'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
            'plan' => 'Growth',
        ],
        'discount_promo' => [
            'name' => 'Diskon & Promo',
            'description' => 'Buat diskon persentase, nominal, atau promo seperti buy 1 get 1.',
            'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
            'plan' => 'Growth',
        ],
        'loyalty_points' => [
            'name' => 'Loyalty Points',
            'description' => 'Program loyalitas dengan sistem poin untuk meningkatkan retensi pelanggan.',
            'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
            'plan' => 'Growth',
        ],
        'recipe_bom' => [
            'name' => 'Recipe / BOM',
            'description' => 'Auto deduct bahan baku berdasarkan resep saat produk terjual.',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'plan' => 'Professional',
        ],
        'stock_transfer' => [
            'name' => 'Stock Transfer',
            'description' => 'Transfer stok antar outlet dengan tracking dan approval workflow.',
            'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
            'plan' => 'Professional',
        ],
        'waiter_app' => [
            'name' => 'Waiter App',
            'description' => 'Aplikasi mobile untuk pelayan dengan mode offline dan sync otomatis.',
            'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
            'plan' => 'Professional',
        ],
        'qr_order' => [
            'name' => 'QR Self-Order',
            'description' => 'Pelanggan scan QR di meja untuk order mandiri. Kurangi antrian dan staff.',
            'icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z',
            'plan' => 'Professional',
        ],
        'kds' => [
            'name' => 'Kitchen Display System',
            'description' => 'Tampilan pesanan digital untuk dapur dengan multi-station support.',
            'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            'plan' => 'Enterprise',
        ],
        'manager_authorization' => [
            'name' => 'Manager Authorization',
            'description' => 'Approval manager untuk void, refund, dan diskon di atas batas tertentu.',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'plan' => 'Professional',
        ],
        'export_excel_pdf' => [
            'name' => 'Export Excel/PDF',
            'description' => 'Export laporan ke format Excel dan PDF untuk analisis dan arsip.',
            'icon' => 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'plan' => 'Growth',
        ],
        'api_access' => [
            'name' => 'API Access',
            'description' => 'REST API untuk integrasi dengan sistem eksternal dan custom development.',
            'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
            'plan' => 'Enterprise',
        ],
        'custom_branding' => [
            'name' => 'Custom Branding',
            'description' => 'Customisasi logo, warna, dan tampilan receipt sesuai brand Anda.',
            'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
            'plan' => 'Enterprise',
        ],
    ];

    $info = $featureDescriptions[$feature] ?? [
        'name' => $featureName,
        'description' => 'Upgrade paket Anda untuk mengakses fitur ini.',
        'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'plan' => $requiredPlan,
    ];
@endphp

<div x-data="{ open: false }"
     x-on:open-upgrade-modal.window="if ($event.detail.feature === '{{ $feature }}') open = true"
     x-on:keydown.escape.window="open = false"
     {{ $attributes }}>

    {{-- Trigger Slot --}}
    <div @click="open = true">
        {{ $slot }}
    </div>

    {{-- Modal --}}
    <template x-teleport="body">
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             x-cloak>
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>

            {{-- Modal Content --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="open"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden"
                     @click.stop>

                    {{-- Close Button --}}
                    <button @click="open = false"
                            class="absolute top-4 right-4 p-2 text-secondary-400 hover:text-secondary-600 rounded-lg hover:bg-secondary-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    {{-- Header with Icon --}}
                    <div class="pt-8 pb-6 px-6 text-center">
                        <div class="w-16 h-16 bg-gradient-to-br from-primary-100 to-accent/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info['icon'] }}"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-text mb-2">{{ $info['name'] }}</h3>
                        <p class="text-text-light text-sm">{{ $info['description'] }}</p>
                    </div>

                    {{-- Upgrade Info --}}
                    <div class="px-6 pb-6">
                        <div class="bg-gradient-to-r from-primary-50 to-accent/10 rounded-xl p-4 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-text">Tersedia di paket <span class="text-primary font-bold">{{ $info['plan'] }}</span></p>
                                    <p class="text-xs text-text-light">Upgrade sekarang untuk mengakses fitur ini</p>
                                </div>
                            </div>
                        </div>

                        {{-- Benefits --}}
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-text-light">Upgrade instan, langsung aktif</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-text-light">Bayar pro-rata (selisih saja)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-text-light">Downgrade kapan saja</span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex flex-col gap-3">
                            <a href="{{ route('subscription.choose-plan') }}"
                               class="w-full py-3 px-4 bg-primary hover:bg-primary-600 text-white font-semibold rounded-xl text-center transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                Lihat Paket & Upgrade
                            </a>
                            <button @click="open = false"
                                    class="w-full py-3 px-4 text-text-light hover:text-text font-medium rounded-xl text-center transition-colors">
                                Nanti Saja
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
