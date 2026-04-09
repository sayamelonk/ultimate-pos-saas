<x-app-layout>
    <x-slot name="title">{{ __('subscription.upgrade_to_plan', ['plan' => $plan->name]) }} - Ultimate POS</x-slot>

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('subscription.choose-plan') }}" class="p-2 rounded-lg hover:bg-secondary-100 transition-colors">
                <svg class="w-5 h-5 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('subscription.upgrade_to_plan', ['plan' => $plan->name]) }}</h2>
                <p class="text-muted mt-1">{{ __('subscription.confirm_plan_change') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        @if(session('error'))
            <div class="mb-6 p-4 bg-danger/10 border border-danger/20 rounded-lg text-danger">
                {{ session('error') }}
            </div>
        @endif

        <!-- Plan Comparison -->
        <div class="bg-surface rounded-xl border border-border shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 bg-secondary-50 border-b border-border">
                <h3 class="font-semibold text-text">{{ __('subscription.plan_change') }}</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between gap-8">
                    <!-- Current Plan -->
                    <div class="flex-1 text-center">
                        <div class="w-12 h-12 bg-secondary-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                            </svg>
                        </div>
                        <p class="text-sm text-text-light mb-1">{{ __('subscription.current_plan_label') }}</p>
                        <p class="text-lg font-bold text-text">{{ $currentPlan->name }}</p>
                        <p class="text-sm text-text-light">
                            Rp {{ number_format($currentPlan->getPrice($currentSubscription->billing_cycle ?? 'monthly'), 0, ',', '.') }}
                            {{ $currentSubscription->billing_cycle === 'yearly' ? __('subscription.per_year') : __('subscription.per_month') }}
                        </p>
                    </div>

                    <!-- Arrow -->
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </div>
                    </div>

                    <!-- New Plan -->
                    <div class="flex-1 text-center">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-text-light mb-1">{{ __('subscription.new_plan_label') }}</p>
                        <p class="text-lg font-bold text-primary">{{ $plan->name }}</p>
                        <p class="text-sm text-text-light">
                            Rp {{ number_format($plan->getPrice($billingCycle), 0, ',', '.') }}
                            {{ $billingCycle === 'yearly' ? __('subscription.per_year') : __('subscription.per_month') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Proration Details -->
        <div class="bg-surface rounded-xl border border-border shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 bg-secondary-50 border-b border-border">
                <h3 class="font-semibold text-text">{{ __('subscription.payment_details') }}</h3>
            </div>
            <div class="p-6">
                @if($proration['is_trial'] ?? false)
                    <div class="bg-info/10 border border-info/20 rounded-lg p-4 mb-4">
                        <p class="text-info text-sm">
                            <strong>{{ __('subscription.trial_active_note') }}</strong>
                        </p>
                    </div>
                @endif

                <div class="space-y-4">
                    @if(!($proration['is_trial'] ?? true) && ($proration['days_remaining'] ?? 0) > 0)
                        <!-- Credit from current plan -->
                        <div class="flex items-center justify-between py-3 border-b border-border">
                            <div>
                                <p class="text-text font-medium">{{ __('subscription.credit_remaining', ['plan' => $currentPlan->name]) }}</p>
                                <p class="text-sm text-text-light">{{ __('subscription.days_remaining_rate', ['days' => $proration['days_remaining'] ?? 0, 'rate' => number_format($proration['current_daily_rate'] ?? 0, 0, ',', '.')]) }}</p>
                            </div>
                            <p class="text-success font-semibold">- {{ $formattedProration['credit'] }}</p>
                        </div>

                        <!-- Cost for remaining days -->
                        <div class="flex items-center justify-between py-3 border-b border-border">
                            <div>
                                <p class="text-text font-medium">{{ __('subscription.cost_for_days', ['plan' => $plan->name, 'days' => $proration['days_remaining'] ?? 0]) }}</p>
                                <p class="text-sm text-text-light">{{ __('subscription.days_remaining_rate', ['days' => $proration['days_remaining'] ?? 0, 'rate' => number_format($proration['new_daily_rate'] ?? 0, 0, ',', '.')]) }}</p>
                            </div>
                            <p class="text-text font-semibold">Rp {{ number_format($proration['new_plan_cost_for_remaining'] ?? 0, 0, ',', '.') }}</p>
                        </div>
                    @endif

                    <!-- Total to pay now -->
                    <div class="flex items-center justify-between py-4 bg-primary/5 -mx-6 px-6 rounded-lg">
                        <div>
                            <p class="text-text font-bold text-lg">{{ __('subscription.total_pay_now') }}</p>
                            @if(!($proration['is_trial'] ?? true))
                                <p class="text-sm text-text-light">{{ __('subscription.proration_difference') }}</p>
                            @endif
                        </div>
                        <p class="text-primary font-bold text-2xl">{{ $formattedProration['total'] }}</p>
                    </div>
                </div>

                <!-- Next billing info -->
                @if(!($proration['is_trial'] ?? true) && isset($proration['next_billing_date']))
                    <div class="mt-6 p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-text-light">
                            {{ __('subscription.next_billing_info', ['amount' => $formattedProration['next_billing'], 'date' => \Carbon\Carbon::parse($proration['next_billing_date'])->format('d M Y')]) }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- New Plan Features -->
        <div class="bg-surface rounded-xl border border-border shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 bg-secondary-50 border-b border-border">
                <h3 class="font-semibold text-text">{{ __('subscription.features_you_get') }}</h3>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-text">{{ $plan->max_outlets === -1 ? 'Unlimited' : $plan->max_outlets }} Outlet</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-text">{{ $plan->max_users === -1 ? 'Unlimited' : $plan->max_users }} User</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-text">{{ $plan->max_products === -1 ? 'Unlimited' : $plan->max_products }} Produk</span>
                    </div>
                    @if($plan->features['inventory_advanced'] ?? false)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text">Inventory Advanced</span>
                        </div>
                    @elseif($plan->features['inventory_basic'] ?? false)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text">Inventory Basic</span>
                        </div>
                    @endif
                    @if($plan->features['table_management'] ?? false)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text">Table Management</span>
                        </div>
                    @endif
                    @if($plan->features['qr_order'] ?? false)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text">QR Order & Waiter App</span>
                        </div>
                    @endif
                    @if($plan->features['api_access'] ?? false)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text">API Access & KDS</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4">
            <form action="{{ route('subscription.upgrade', $plan) }}" method="POST" class="flex-1">
                @csrf
                <input type="hidden" name="billing_cycle" value="{{ $billingCycle }}">
                <button type="submit" class="w-full py-4 px-6 bg-primary hover:bg-primary-600 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2 text-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    {{ __('subscription.upgrade_now') }} - {{ $formattedProration['total'] }}
                </button>
            </form>
            <a href="{{ route('subscription.choose-plan') }}" class="flex-shrink-0 py-4 px-6 bg-secondary-100 hover:bg-secondary-200 text-text font-semibold rounded-xl transition-colors text-center">
                {{ __('subscription.cancel') }}
            </a>
        </div>

        <!-- Downgrade Warning -->
        @if($isDowngrade)
            <div class="mt-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-warning font-medium">{{ __('subscription.this_is_downgrade') }}</p>
                        <p class="text-sm text-text-light mt-1">
                            {{ __('subscription.downgrade_warning') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
