<!-- Mobile Menu Overlay -->
<div x-show="mobileMenuOpen"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"
     @click="mobileMenuOpen = false">
</div>

<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 z-50 flex flex-col bg-primary text-white transition-all duration-300 ease-in-out"
       :class="{
           'w-64': sidebarOpen,
           'w-20': !sidebarOpen,
           '-translate-x-full lg:translate-x-0': !mobileMenuOpen,
           'translate-x-0': mobileMenuOpen
       }">

    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-primary-400/30">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="font-bold text-lg whitespace-nowrap" x-show="sidebarOpen" x-transition>
                Ultimate POS
            </span>
        </a>

        <!-- Toggle Button (Desktop) -->
        <button @click="sidebarOpen = !sidebarOpen"
                class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg hover:bg-white/10 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 :class="{ 'rotate-180': !sidebarOpen }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        <!-- Close Button (Mobile) -->
        <button @click="mobileMenuOpen = false"
                class="lg:hidden flex items-center justify-center w-8 h-8 rounded-lg hover:bg-white/10 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3"
         x-ref="sidebarNav"
         x-init="
            // Restore scroll position on page load
            $nextTick(() => {
                const savedPosition = localStorage.getItem('sidebarScrollPosition');
                if (savedPosition) {
                    $refs.sidebarNav.scrollTop = parseInt(savedPosition);
                }
            });
         "
         @scroll.debounce.100ms="localStorage.setItem('sidebarScrollPosition', $refs.sidebarNav.scrollTop)"
         @click="
            // Save scroll position before navigation
            localStorage.setItem('sidebarScrollPosition', $refs.sidebarNav.scrollTop);
         ">
        <!-- Main Menu -->
        <div class="space-y-1">
            <p class="px-3 text-xs font-semibold text-primary-300 uppercase tracking-wider mb-2"
               x-show="sidebarOpen" x-transition>
                {{ __('menu.pos') }}
            </p>

            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('admin.dashboard') || request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.dashboard') }}</span>
            </a>

            <!-- POS -->
            <a href="{{ route('pos.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('pos.index') || request()->routeIs('pos.checkout') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.open_pos') }}</span>
            </a>

            <!-- Sessions -->
            <a href="{{ route('pos.sessions.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('pos.sessions.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('pos.session') }}</span>
            </a>

            <!-- Transactions -->
            <a href="{{ route('transactions.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('transactions.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.transactions') }}</span>
            </a>

            <!-- Customers -->
            <a href="{{ route('customers.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('customers.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.customers') }}</span>
            </a>
        </div>

        <!-- Pricing -->
        <div class="mt-6 space-y-1">
            <p class="px-3 text-xs font-semibold text-primary-300 uppercase tracking-wider mb-2"
               x-show="sidebarOpen" x-transition>
                {{ __('menu.pricing') }}
            </p>

            <!-- Prices -->
            <a href="{{ route('pricing.prices.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('pricing.prices.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('pricing.price_management') }}</span>
            </a>

            <!-- Discounts -->
            <a href="{{ route('pricing.discounts.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('pricing.discounts.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.discounts') }}</span>
            </a>

            <!-- Payment Methods -->
            <a href="{{ route('pricing.payment-methods.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('pricing.payment-methods.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.payment_methods') }}</span>
            </a>
        </div>

        <!-- Menu Management -->
        <div class="mt-6 space-y-1">
            <p class="px-3 text-xs font-semibold text-primary-300 uppercase tracking-wider mb-2"
               x-show="sidebarOpen" x-transition>
                {{ __('menu.menu') }}
            </p>

            <!-- Products -->
            <a href="{{ route('menu.products.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('menu.products.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.products') }}</span>
            </a>

            <!-- Product Categories -->
            <a href="{{ route('menu.categories.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('menu.categories.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.menu_categories') }}</span>
            </a>

            <!-- Variant Groups -->
            <a href="{{ route('menu.variant-groups.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('menu.variant-groups.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.variant_groups') }}</span>
            </a>

            <!-- Modifier Groups -->
            <a href="{{ route('menu.modifier-groups.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('menu.modifier-groups.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.modifier_groups') }}</span>
            </a>

            <!-- Combos -->
            <a href="{{ route('menu.combos.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('menu.combos.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.combos') }}</span>
            </a>
        </div>

        <!-- Inventory -->
        <div class="mt-6 space-y-1">
            <p class="px-3 text-xs font-semibold text-primary-300 uppercase tracking-wider mb-2"
               x-show="sidebarOpen" x-transition>
                {{ __('menu.inventory') }}
            </p>

            <!-- Items -->
            <a href="{{ route('inventory.items.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.items.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('inventory.items') }}</span>
            </a>

            <!-- Units -->
            <a href="{{ route('inventory.units.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.units.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('inventory.units') }}</span>
            </a>

            <!-- Categories -->
            <a href="{{ route('inventory.categories.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.categories.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('inventory.categories') }}</span>
            </a>

            <!-- Stock -->
            <a href="{{ route('inventory.stocks.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.stocks.index') || request()->routeIs('inventory.stocks.show') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('inventory.stock') }}</span>
            </a>

            <!-- Stock Movements -->
            <a href="{{ route('inventory.stocks.movements') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.stocks.movements') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('inventory.stock_movements') }}</span>
            </a>

            <!-- Suppliers -->
            <a href="{{ route('inventory.suppliers.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.suppliers.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('inventory.suppliers') }}</span>
            </a>

            <!-- Purchase Orders -->
            <a href="{{ route('inventory.purchase-orders.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.purchase-orders.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.purchase_orders') }}</span>
            </a>

            <!-- Goods Receive -->
            <a href="{{ route('inventory.goods-receives.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.goods-receives.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.goods_receives') }}</span>
            </a>

            <!-- Stock Adjustments -->
            <a href="{{ route('inventory.stock-adjustments.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.stock-adjustments.index') || request()->routeIs('inventory.stock-adjustments.show') || request()->routeIs('inventory.stock-adjustments.create') || request()->routeIs('inventory.stock-adjustments.edit') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.stock_adjustments') }}</span>
            </a>

            <!-- Stock Take / Stock Opname -->
            <a href="{{ route('inventory.stock-adjustments.stock-take') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.stock-adjustments.stock-take') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('inventory.stock_opname') }}</span>
            </a>

            <!-- Stock Transfers -->
            <a href="{{ route('inventory.stock-transfers.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.stock-transfers.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.stock_transfers') }}</span>
            </a>

            <!-- Recipes -->
            <a href="{{ route('inventory.recipes.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.recipes.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.recipes') }}</span>
            </a>

            <!-- Waste Logs -->
            <a href="{{ route('inventory.waste-logs.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.waste-logs.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.waste_logs') }}</span>
            </a>

            <!-- Stock Batches -->
            <a href="{{ route('inventory.batches.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.batches.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('inventory.batches') }}</span>
            </a>
        </div>

        <!-- Reports -->
        <div class="mt-6 space-y-1">
            <p class="px-3 text-xs font-semibold text-primary-300 uppercase tracking-wider mb-2"
               x-show="sidebarOpen" x-transition>
                {{ __('menu.reports') }}
            </p>

            <!-- Stock Valuation -->
            <a href="{{ route('inventory.reports.stock-valuation') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.reports.stock-valuation') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('reports.stock_valuation') }}</span>
            </a>

            <!-- Stock Movement Report -->
            <a href="{{ route('inventory.reports.stock-movement') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.reports.stock-movement') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('reports.stock_movement') }}</span>
            </a>

            <!-- COGS Report -->
            <a href="{{ route('inventory.reports.cogs') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.reports.cogs') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('reports.cogs') }}</span>
            </a>

            <!-- Food Cost Report -->
            <a href="{{ route('inventory.reports.food-cost') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.reports.food-cost') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('reports.food_cost') }}</span>
            </a>

            <!-- Waste Report -->
            <a href="{{ route('inventory.waste-logs.report') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('inventory.waste-logs.report') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('reports.waste') }}</span>
            </a>
        </div>

        <!-- Settings -->
        <div class="mt-6 space-y-1">
            <p class="px-3 text-xs font-semibold text-primary-300 uppercase tracking-wider mb-2"
               x-show="sidebarOpen" x-transition>
                {{ __('menu.admin') }}
            </p>

            @if(auth()->user()->isSuperAdmin())
            <!-- Tenants (Super Admin Only) -->
            <a href="{{ route('admin.tenants.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('admin.tenants.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.tenants') }}</span>
            </a>
            @endif

            <!-- Outlets -->
            <a href="{{ route('admin.outlets.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('admin.outlets.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.outlets') }}</span>
            </a>

            <!-- Users -->
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.users') }}</span>
            </a>

            <!-- Roles -->
            <a href="{{ route('admin.roles.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('admin.roles.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('menu.roles') }}</span>
            </a>

            <!-- Authorization Settings -->
            <a href="{{ route('admin.authorization.settings') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('admin.authorization.*') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('pos.spv_authorization') }}</span>
            </a>

            <!-- My PIN -->
            @if(auth()->user()->canAuthorize())
            <a href="{{ route('admin.my-pin') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('admin.my-pin') ? 'bg-white/10 text-white' : 'text-primary-100 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('admin.pin') }}</span>
            </a>
            @endif

            <!-- Settings -->
            <a href="#"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-primary-100 hover:bg-white/5 hover:text-white">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ __('app.settings') }}</span>
            </a>
        </div>
    </nav>

    <!-- User Info -->
    <div class="border-t border-primary-400/30 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0">
                <span class="text-sm font-medium">
                    {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 2)) : 'US' }}
                </span>
            </div>
            <div x-show="sidebarOpen" x-transition class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">
                    {{ auth()->check() ? auth()->user()->name : 'Guest' }}
                </p>
                <p class="text-xs text-primary-300 truncate">
                    {{ auth()->check() ? auth()->user()->email : '' }}
                </p>
            </div>
        </div>
    </div>
</aside>
