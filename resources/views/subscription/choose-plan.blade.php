<x-app-layout>
    <x-slot name="title">{{ __('subscription.choose_plan') }} - Ultimate POS</x-slot>

    <div class="min-h-[80vh] flex flex-col items-center justify-center py-12 px-4" x-data="{ billing: 'monthly' }">
        <!-- Status Banner -->
        @if($subscription?->isFrozen())
            <div class="w-full max-w-4xl mb-8">
                <div class="bg-gradient-to-r from-danger-500 to-danger-600 rounded-xl p-6 text-white">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-1">{{ __('subscription.account_frozen') }}</h3>
                            <p class="text-white/80">
                                {{ __('subscription.frozen_message') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($subscription?->isTrial())
            <div class="w-full max-w-4xl mb-8">
                <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-6 text-white">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-bold mb-1">{{ __('subscription.trial_remaining', ['days' => $subscription->daysRemaining()]) }}</h3>
                                    <p class="text-white/80">
                                        {{ __('subscription.trial_choose_plan') }}
                                    </p>
                                </div>
                                <div class="bg-white/20 rounded-lg px-4 py-2 text-center">
                                    <span class="text-3xl font-bold">{{ $subscription->daysRemaining() }}</span>
                                    <span class="text-sm block text-white/70">{{ __('subscription.days_left') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-text mb-2">{{ __('subscription.choose_right_plan') }}</h1>
            <p class="text-text-light max-w-xl mx-auto">
                {{ __('subscription.upgrade_anytime') }}
            </p>
        </div>

        <!-- Billing Toggle -->
        <div class="flex justify-center mb-10">
            <div class="inline-flex items-center gap-3 p-1.5 bg-secondary-100 rounded-xl">
                <button type="button"
                        @click="billing = 'monthly'"
                        :class="billing === 'monthly' ? 'bg-white shadow-md text-text' : 'text-secondary-600 hover:text-text'"
                        class="px-6 py-2.5 rounded-lg text-sm font-semibold transition-all">
                    {{ __('subscription.monthly') }}
                </button>
                <button type="button"
                        @click="billing = 'yearly'"
                        :class="billing === 'yearly' ? 'bg-white shadow-md text-text' : 'text-secondary-600 hover:text-text'"
                        class="px-6 py-2.5 rounded-lg text-sm font-semibold transition-all flex items-center gap-2">
                    {{ __('subscription.yearly') }}
                    <span class="bg-success text-white text-xs px-2 py-0.5 rounded-full">-20%</span>
                </button>
            </div>
        </div>

        <!-- Plans Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 w-full max-w-6xl">
            @foreach($plans as $plan)
                @php
                    $isPopular = $plan->slug === 'growth';
                    $isEnterprise = $plan->slug === 'enterprise';
                    $isCurrentPlan = $currentPlan && $currentPlan->id === $plan->id;
                @endphp
                <div class="relative {{ $isPopular ? 'ring-2 ring-accent shadow-xl lg:scale-105 z-10' : '' }} {{ $isEnterprise ? 'bg-primary' : 'bg-white' }} rounded-2xl p-6 border {{ $isPopular ? 'border-accent' : ($isEnterprise ? 'border-primary' : 'border-border') }} shadow-lg transition-all hover:shadow-xl flex flex-col">
                    @if($isPopular)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-accent text-white text-sm font-semibold rounded-full shadow-lg">
                            {{ __('subscription.recommended') }}
                        </div>
                    @endif

                    @if($isCurrentPlan)
                        <div class="absolute -top-4 right-4 px-3 py-1 bg-success text-white text-xs font-semibold rounded-full">
                            {{ __('subscription.current_plan') }}
                        </div>
                    @endif

                    <div class="text-center mb-6 {{ $isPopular ? 'pt-2' : '' }}">
                        <h3 class="text-xl font-bold {{ $isEnterprise ? 'text-white' : 'text-text' }} mb-1">{{ $plan->name }}</h3>
                        <p class="{{ $isEnterprise ? 'text-secondary-200' : 'text-text-light' }} text-sm mb-4">{{ $plan->description }}</p>

                        <!-- Monthly Price -->
                        <div x-show="billing === 'monthly'" class="flex items-baseline justify-center gap-1">
                            <span class="text-4xl font-bold {{ $isEnterprise ? 'text-white' : ($isPopular ? 'text-accent' : 'text-text') }}">
                                Rp {{ number_format($plan->price_monthly / 1000, 0, ',', '.') }}K
                            </span>
                            <span class="{{ $isEnterprise ? 'text-secondary-200' : 'text-text-light' }} text-sm">{{ __('subscription.per_month') }}</span>
                        </div>

                        <!-- Yearly Price -->
                        <div x-show="billing === 'yearly'" x-cloak class="flex flex-col items-center">
                            <div class="flex items-baseline justify-center gap-1">
                                <span class="text-4xl font-bold {{ $isEnterprise ? 'text-white' : ($isPopular ? 'text-accent' : 'text-text') }}">
                                    Rp {{ number_format($plan->price_yearly / 1000000, 1, ',', '.') }} Jt
                                </span>
                                <span class="{{ $isEnterprise ? 'text-secondary-200' : 'text-text-light' }} text-sm">{{ __('subscription.per_year') }}</span>
                            </div>
                            <p class="text-success text-sm mt-1 font-medium">
                                {{ __('subscription.save_amount', ['amount' => number_format((($plan->price_monthly * 12) - $plan->price_yearly) / 1000, 0, ',', '.') . 'K']) }}
                            </p>
                        </div>
                    </div>

                    <!-- Key Features -->
                    <div class="space-y-3 mb-6 flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">
                                {{ $plan->max_outlets === -1 ? 'Unlimited' : $plan->max_outlets }} Outlet
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">
                                {{ $plan->max_users === -1 ? 'Unlimited' : $plan->max_users }} User
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">
                                {{ $plan->max_products === -1 ? 'Unlimited' : $plan->max_products }} Produk
                            </span>
                        </div>

                        @if($plan->features['inventory_basic'] ?? false)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">
                                    {{ $plan->features['inventory_advanced'] ?? false ? 'Inventory Advanced' : 'Inventory Basic' }}
                                </span>
                            </div>
                        @endif

                        @if($plan->features['table_management'] ?? false)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">Table Management</span>
                            </div>
                        @endif

                        @if($plan->features['qr_order'] ?? false)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">QR Order & Waiter App</span>
                            </div>
                        @endif

                        @if($plan->features['api_access'] ?? false)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">API Access & KDS</span>
                            </div>
                        @endif
                    </div>

                    <!-- Subscribe Button -->
                    @if($isCurrentPlan && !$subscription?->isFrozen() && !$subscription?->isTrial())
                        <button type="button" disabled
                                class="w-full py-3 px-4 bg-secondary-200 text-secondary-500 rounded-xl font-semibold cursor-not-allowed text-sm">
                            {{ __('subscription.current_plan') }}
                        </button>
                    @elseif($subscription && $subscription->isActive() && !$isCurrentPlan)
                        {{-- Active subscription: show upgrade preview --}}
                        <a :href="'{{ route('subscription.upgrade.preview', $plan) }}?billing_cycle=' + billing"
                           class="block w-full py-3 px-4 rounded-xl font-semibold transition-colors text-sm text-center {{ $isPopular ? 'bg-accent text-white hover:bg-accent-600 shadow-lg' : ($isEnterprise ? 'bg-accent text-white hover:bg-accent-600' : 'bg-secondary-100 text-text hover:bg-secondary-200') }}">
                            @if($plan->sort_order > ($currentPlan->sort_order ?? 0))
                                {{ __('subscription.upgrade_to', ['plan' => $plan->name]) }}
                            @else
                                {{ __('subscription.downgrade_to', ['plan' => $plan->name]) }}
                            @endif
                        </a>
                    @else
                        <form action="{{ route('subscription.subscribe', $plan) }}" method="POST">
                            @csrf
                            <input type="hidden" name="billing_cycle" x-bind:value="billing">
                            <button type="submit"
                                    class="w-full py-3 px-4 rounded-xl font-semibold transition-colors text-sm {{ $isPopular ? 'bg-accent text-white hover:bg-accent-600 shadow-lg' : ($isEnterprise ? 'bg-accent text-white hover:bg-accent-600' : 'bg-secondary-100 text-text hover:bg-secondary-200') }}">
                                @if($subscription?->isFrozen())
                                    {{ __('subscription.reactivate_with', ['plan' => $plan->name]) }}
                                @else
                                    {{ __('subscription.select_plan', ['plan' => $plan->name]) }}
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Compare Features Link -->
        <div class="mt-8 text-center">
            <a href="{{ route('pricing') }}" class="text-primary hover:text-primary-600 font-medium inline-flex items-center gap-2">
                <span>{{ __('subscription.compare_features') }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Payment Methods -->
        <div class="mt-12 text-center">
            <p class="text-sm text-text-light mb-4">{{ __('subscription.accepted_payments') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-6">
                <div class="flex items-center gap-2 text-secondary-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                    </svg>
                    <span class="text-sm">{{ __('subscription.virtual_account') }}</span>
                </div>
                <div class="flex items-center gap-2 text-secondary-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 3h18v18H3V3zm16 16V5H5v14h14zm-6-2h-2v-4H7v-2h4V7h2v4h4v2h-4v4z"/>
                    </svg>
                    <span class="text-sm">QRIS</span>
                </div>
                <div class="flex items-center gap-2 text-secondary-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4V6h16v12zm-9-2h2v-2h-2v2zm0-4h2V8h-2v4z"/>
                    </svg>
                    <span class="text-sm">{{ __('subscription.credit_card') }}</span>
                </div>
                <div class="flex items-center gap-2 text-secondary-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/>
                    </svg>
                    <span class="text-sm">{{ __('subscription.ewallet') }}</span>
                </div>
            </div>
            <p class="text-xs text-secondary-400 mt-4">{{ __('subscription.powered_by') }}</p>
        </div>

        <!-- Back to Dashboard -->
        @if(!$subscription?->isFrozen())
            <div class="mt-8">
                <a href="{{ route('dashboard') }}" class="text-text-light hover:text-text text-sm">
                    &larr; {{ __('subscription.back_to_dashboard') }}
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
