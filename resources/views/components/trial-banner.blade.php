@props([
    'subscription' => null,
])

@php
    if (!$subscription) {
        return;
    }

    $isTrial = $subscription->isTrial();
    $isFrozen = $subscription->isFrozen();
    $isGracePeriod = $subscription->isInGracePeriod();
    $daysRemaining = $subscription->daysRemaining();

    // Don't show banner for active paid subscriptions
    if (!$isTrial && !$isFrozen && !$isGracePeriod) {
        return;
    }

    // Determine urgency level
    if ($isFrozen) {
        $type = 'frozen';
        $bgClass = 'bg-gradient-to-r from-danger-600 to-danger-700';
        $iconBgClass = 'bg-white/20';
    } elseif ($daysRemaining <= 1) {
        $type = 'urgent';
        $bgClass = 'bg-gradient-to-r from-danger-500 to-warning-500';
        $iconBgClass = 'bg-white/20';
    } elseif ($daysRemaining <= 3) {
        $type = 'warning';
        $bgClass = 'bg-gradient-to-r from-warning-500 to-warning-600';
        $iconBgClass = 'bg-white/20';
    } else {
        $type = 'info';
        $bgClass = 'bg-gradient-to-r from-slate-700 to-slate-800';
        $iconBgClass = 'bg-primary-500';
    }
@endphp

@if($subscription && ($isTrial || $isFrozen || $isGracePeriod))
<div {{ $attributes->merge(['class' => 'rounded-xl shadow-lg overflow-hidden mb-6 ' . $bgClass]) }}>
    <div class="px-6 py-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Left: Status Info --}}
            <div class="flex items-center gap-4">
                {{-- Icon --}}
                <div class="flex-shrink-0 w-12 h-12 rounded-xl {{ $iconBgClass }} flex items-center justify-center">
                    @if($isFrozen)
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    @elseif($type === 'urgent')
                        <svg class="w-6 h-6 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    @else
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @endif
                </div>

                {{-- Text Content --}}
                <div>
                    @if($isFrozen)
                        <h3 class="text-lg font-bold text-white">{{ __('dashboard.account_frozen') }}</h3>
                        <p class="text-white/80 text-sm">
                            {{ __('dashboard.trial_ended_choose_plan') }}
                        </p>
                    @elseif($isGracePeriod)
                        <h3 class="text-lg font-bold text-white">{{ __('dashboard.grace_period_active') }}</h3>
                        <p class="text-white/80 text-sm">
                            {{ __('dashboard.account_frozen_in_days', ['days' => $daysRemaining]) }}
                        </p>
                    @else
                        <h3 class="text-lg font-bold text-white">
                            @if($daysRemaining <= 1)
                                {{ __('dashboard.trial_ends_tomorrow') }}
                            @elseif($daysRemaining <= 3)
                                {{ __('dashboard.trial_remaining_days', ['days' => $daysRemaining]) }}
                            @else
                                {{ __('dashboard.trial_period_days', ['days' => $daysRemaining]) }}
                            @endif
                        </h3>
                        <p class="text-white/80 text-sm">
                            @if($daysRemaining <= 3)
                                {{ __('dashboard.dont_lose_access') }}
                            @else
                                {{ __('dashboard.enjoy_professional_trial') }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            {{-- Right: Countdown & CTA --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                @if(!$isFrozen && $daysRemaining > 0)
                    {{-- Countdown Display --}}
                    <div class="flex items-center gap-2 bg-white/20 rounded-lg px-4 py-2" x-data="countdown()" x-init="start()">
                        <div class="text-center">
                            <span class="block text-2xl font-bold text-white">{{ $daysRemaining }}</span>
                            <span class="block text-xs text-white uppercase tracking-wide">{{ __('dashboard.days_label') }}</span>
                        </div>
                        <div class="text-white text-xl font-light">:</div>
                        <div class="text-center">
                            <span class="block text-2xl font-bold text-white" x-text="hours.toString().padStart(2, '0')">00</span>
                            <span class="block text-xs text-white uppercase tracking-wide">{{ __('dashboard.hours_label') }}</span>
                        </div>
                        <div class="text-white text-xl font-light">:</div>
                        <div class="text-center">
                            <span class="block text-2xl font-bold text-white" x-text="minutes.toString().padStart(2, '0')">00</span>
                            <span class="block text-xs text-white uppercase tracking-wide">{{ __('dashboard.minutes_label') }}</span>
                        </div>
                    </div>
                @endif

                {{-- CTA Button --}}
                <a href="{{ route('subscription.choose-plan') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-900 font-semibold rounded-lg shadow-md hover:bg-gray-100 transition-all duration-200 hover:scale-105">
                    @if($isFrozen)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        {{ __('dashboard.reactivate_now') }}
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        {{ __('dashboard.choose_plan') }}
                    @endif
                </a>
            </div>
        </div>

        {{-- Frozen Mode Restrictions --}}
        @if($isFrozen)
            <div class="mt-4 pt-4 border-t border-white/20">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center gap-2 text-white/80">
                        <svg class="w-4 h-4 text-success-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('dashboard.login_view_data') }}
                    </div>
                    <div class="flex items-center gap-2 text-white/80">
                        <svg class="w-4 h-4 text-success-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('dashboard.export_reports') }}
                    </div>
                    <div class="flex items-center gap-2 text-white/60">
                        <svg class="w-4 h-4 text-danger-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ __('dashboard.new_transactions') }}
                    </div>
                    <div class="flex items-center gap-2 text-white/60">
                        <svg class="w-4 h-4 text-danger-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ __('dashboard.add_product_user_outlet') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function countdown() {
        return {
            hours: 0,
            minutes: 0,
            seconds: 0,
            start() {
                this.updateTime();
                setInterval(() => this.updateTime(), 1000);
            },
            updateTime() {
                const now = new Date();
                const endOfDay = new Date(now);
                endOfDay.setHours(23, 59, 59, 999);
                const diff = endOfDay - now;

                this.hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                this.minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                this.seconds = Math.floor((diff % (1000 * 60)) / 1000);
            }
        };
    }
</script>
@endpush
@endif
