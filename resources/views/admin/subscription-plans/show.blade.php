<x-app-layout>
    <x-slot name="title">{{ $subscriptionPlan->name }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.plan_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.subscription-plans.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('admin.back') }}
                </x-button>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
                        <x-icon name="credit-card" class="w-6 h-6 text-primary" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-text">{{ $subscriptionPlan->name }}</h2>
                        <p class="text-muted text-sm font-mono">{{ $subscriptionPlan->slug }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($subscriptionPlan->is_active)
                    <x-badge type="success" dot>{{ __('admin.active') }}</x-badge>
                @else
                    <x-badge type="danger" dot>{{ __('admin.inactive') }}</x-badge>
                @endif
                <x-button href="{{ route('admin.subscription-plans.edit', $subscriptionPlan) }}" variant="secondary" icon="pencil">
                    {{ __('admin.edit') }}
                </x-button>
                <div x-data>
                    <x-button
                        variant="danger"
                        icon="trash"
                        @click="$dispatch('confirm', {
                            title: '{{ __('admin.delete_plan') }}',
                            message: '{{ __('admin.confirm_delete_plan', ['name' => $subscriptionPlan->name]) }}',
                            confirmText: '{{ __('admin.delete') }}',
                            variant: 'danger',
                            onConfirm: () => $refs.deleteForm.submit()
                        })"
                    >
                        {{ __('admin.delete') }}
                    </x-button>
                    <form x-ref="deleteForm" action="{{ route('admin.subscription-plans.destroy', $subscriptionPlan) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            label="{{ __('admin.monthly_revenue') }}"
            :value="'Rp ' . number_format($monthlyRevenue ?? 0, 0, ',', '.')"
            icon="currency-dollar"
            color="success"
        />
        <x-stat-card
            label="{{ __('admin.yearly_revenue') }}"
            :value="'Rp ' . number_format($yearlyRevenue ?? 0, 0, ',', '.')"
            icon="currency-dollar"
            color="accent"
        />
        <x-stat-card
            label="{{ __('admin.active_subscriptions') }}"
            :value="$activeSubscriptionsCount ?? 0"
            icon="users"
            color="primary"
        />
        <x-stat-card
            label="{{ __('admin.total_revenue') }}"
            :value="'Rp ' . number_format($totalRevenue ?? 0, 0, ',', '.')"
            icon="chart-bar"
            color="secondary"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <x-card title="{{ __('admin.basic_information') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.name') }}</dt>
                        <dd class="text-text font-medium">{{ $subscriptionPlan->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.slug') }}</dt>
                        <dd class="font-mono text-sm text-text bg-secondary-100 px-2 py-1 rounded inline-block">{{ $subscriptionPlan->slug }}</dd>
                    </div>
                    @if($subscriptionPlan->description)
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.description') }}</dt>
                            <dd class="text-text">{{ $subscriptionPlan->description }}</dd>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Pricing -->
            <x-card title="{{ __('admin.pricing') }}">
                <div class="grid grid-cols-2 gap-6">
                    <div class="bg-primary-50 p-4 rounded-lg">
                        <dt class="text-sm font-medium text-primary mb-2">{{ __('admin.price_monthly') }}</dt>
                        <dd class="text-2xl font-bold text-primary">Rp {{ number_format($subscriptionPlan->price_monthly, 0, ',', '.') }}</dd>
                        <p class="text-xs text-primary-700 mt-1">{{ __('admin.per_month') }}</p>
                    </div>
                    <div class="bg-accent-50 p-4 rounded-lg">
                        <dt class="text-sm font-medium text-accent mb-2">{{ __('admin.price_yearly') }}</dt>
                        <dd class="text-2xl font-bold text-accent">Rp {{ number_format($subscriptionPlan->price_yearly, 0, ',', '.') }}</dd>
                        <p class="text-xs text-accent-700 mt-1">{{ __('admin.per_year') }}</p>
                        @if($subscriptionPlan->price_monthly > 0)
                            @php
                                $yearlySavings = ($subscriptionPlan->price_monthly * 12) - $subscriptionPlan->price_yearly;
                                $savingsPercentage = ($yearlySavings / ($subscriptionPlan->price_monthly * 12)) * 100;
                            @endphp
                            @if($yearlySavings > 0)
                                <p class="text-xs text-success mt-1 font-medium">
                                    {{ __('admin.save') }} {{ number_format($savingsPercentage, 0) }}%
                                </p>
                            @endif
                        @endif
                    </div>
                </div>
            </x-card>

            <!-- Limits -->
            <x-card title="{{ __('admin.limits') }}">
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <dt class="text-sm font-medium text-muted mb-2">{{ __('admin.max_outlets') }}</dt>
                        <dd class="text-2xl font-bold text-text">
                            @if($subscriptionPlan->max_outlets === -1)
                                <x-badge type="info" size="lg">{{ __('admin.unlimited') }}</x-badge>
                            @else
                                {{ $subscriptionPlan->max_outlets }}
                            @endif
                        </dd>
                    </div>
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <dt class="text-sm font-medium text-muted mb-2">{{ __('admin.max_users') }}</dt>
                        <dd class="text-2xl font-bold text-text">
                            @if($subscriptionPlan->max_users === -1)
                                <x-badge type="info" size="lg">{{ __('admin.unlimited') }}</x-badge>
                            @else
                                {{ $subscriptionPlan->max_users }}
                            @endif
                        </dd>
                    </div>
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <dt class="text-sm font-medium text-muted mb-2">{{ __('admin.max_products') }}</dt>
                        <dd class="text-2xl font-bold text-text">
                            @if($subscriptionPlan->max_products === -1)
                                <x-badge type="info" size="lg">{{ __('admin.unlimited') }}</x-badge>
                            @else
                                {{ $subscriptionPlan->max_products }}
                            @endif
                        </dd>
                    </div>
                </div>
            </x-card>

            <!-- Features -->
            @if($subscriptionPlan->features && count($subscriptionPlan->features) > 0)
                <x-card title="{{ __('admin.features') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($subscriptionPlan->features as $key => $value)
                            <div class="flex items-start gap-3 p-3 bg-secondary-50 rounded-lg">
                                <div class="w-5 h-5 bg-success rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                    <x-icon name="check" class="w-3 h-3 text-white" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-text">{{ $key }}</p>
                                    <p class="text-xs text-muted">{{ $value }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Recent Subscriptions -->
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-text">{{ __('admin.recent_subscriptions') }}</h3>
                        <p class="text-sm text-muted">{{ __('admin.subscriptions_using_plan') }}</p>
                    </div>
                </div>
                @if(isset($recentSubscriptions) && $recentSubscriptions->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentSubscriptions as $subscription)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                        <span class="text-sm font-semibold text-primary">
                                            {{ strtoupper(substr($subscription->tenant->name ?? 'T', 0, 2)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-text">{{ $subscription->tenant->name ?? __('admin.unknown_tenant') }}</p>
                                        <p class="text-xs text-muted">
                                            {{ __('admin.subscribed_on') }} {{ $subscription->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-badge type="{{ $subscription->billing_cycle === 'monthly' ? 'primary' : 'accent' }}" size="sm">
                                        {{ ucfirst($subscription->billing_cycle) }}
                                    </x-badge>
                                    @if($subscription->is_active)
                                        <x-badge type="success" size="sm">{{ __('admin.active') }}</x-badge>
                                    @else
                                        <x-badge type="danger" size="sm">{{ __('admin.inactive') }}</x-badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="{{ __('admin.no_subscriptions') }}"
                        description="{{ __('admin.no_subscriptions_plan_desc') }}"
                        icon="users"
                    />
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Settings -->
            <x-card title="{{ __('admin.settings') }}">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.status') }}</span>
                        @if($subscriptionPlan->is_active)
                            <x-badge type="success">{{ __('admin.active') }}</x-badge>
                        @else
                            <x-badge type="danger">{{ __('admin.inactive') }}</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.sort_order') }}</span>
                        <span class="text-sm font-medium text-text">{{ $subscriptionPlan->sort_order }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Statistics -->
            <x-card title="{{ __('admin.statistics') }}">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.total_subscriptions') }}</span>
                        <span class="text-lg font-bold text-text">{{ $totalSubscriptionsCount ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.active_subscriptions') }}</span>
                        <span class="text-lg font-bold text-success">{{ $activeSubscriptionsCount ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.total_revenue') }}</span>
                        <span class="text-lg font-bold text-primary">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Timestamps -->
            <x-card title="{{ __('admin.activity') }}">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.created') }}</span>
                        <span class="text-text">{{ $subscriptionPlan->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.last_updated') }}</span>
                        <span class="text-text">{{ $subscriptionPlan->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
