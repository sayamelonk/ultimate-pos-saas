<x-app-layout>
    <x-slot name="title">{{ $tenant->name }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.tenant_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.tenants.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('admin.back') }}
                </x-button>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
                        <span class="text-lg font-bold text-primary">
                            {{ strtoupper(substr($tenant->name, 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-text">{{ $tenant->name }}</h2>
                        <p class="text-muted text-sm font-mono">{{ $tenant->code }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($tenant->is_active)
                    <x-badge type="success" dot>{{ __('admin.active') }}</x-badge>
                @else
                    <x-badge type="danger" dot>{{ __('admin.inactive') }}</x-badge>
                @endif
                <x-button href="{{ route('admin.tenants.edit', $tenant) }}" variant="secondary" icon="pencil">
                    {{ __('admin.edit') }}
                </x-button>
                <div x-data>
                    <x-button
                        variant="danger"
                        icon="trash"
                        @click="$dispatch('confirm', {
                            title: '{{ __('admin.delete_tenant') }}',
                            message: '{{ __('admin.confirm_delete_tenant', ['name' => $tenant->name]) }}',
                            confirmText: '{{ __('admin.delete') }}',
                            variant: 'danger',
                            onConfirm: () => $refs.deleteForm.submit()
                        })"
                    >
                        {{ __('admin.delete') }}
                    </x-button>
                    <form x-ref="deleteForm" action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" class="hidden">
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
            label="{{ __('admin.outlets') }}"
            :value="$tenant->outlets_count"
            icon="building"
            color="accent"
        />
        <x-stat-card
            label="{{ __('admin.users') }}"
            :value="$tenant->users_count"
            icon="users"
            color="primary"
        />
        <x-stat-card
            label="{{ __('admin.subscription') }}"
            :value="ucfirst($tenant->subscription_plan)"
            icon="credit-card"
            color="success"
        />
        <x-stat-card
            label="{{ __('admin.max_outlets') }}"
            :value="$tenant->max_outlets"
            icon="building"
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
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.tenant_name') }}</dt>
                        <dd class="text-text">{{ $tenant->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.code') }}</dt>
                        <dd class="font-mono text-sm text-text bg-secondary-100 px-2 py-1 rounded inline-block">{{ $tenant->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.email') }}</dt>
                        <dd class="text-text">{{ $tenant->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.phone') }}</dt>
                        <dd class="text-text">{{ $tenant->phone ?? '-' }}</dd>
                    </div>
                </div>
            </x-card>

            <!-- Settings -->
            <x-card title="{{ __('admin.settings') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.currency') }}</dt>
                        <dd class="text-text font-medium">{{ $tenant->currency }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.timezone') }}</dt>
                        <dd class="text-text">{{ $tenant->timezone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.tax_rate') }}</dt>
                        <dd class="text-text">{{ number_format($tenant->tax_percentage, 2) }}%</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.service_charge_rate') }}</dt>
                        <dd class="text-text">{{ number_format($tenant->service_charge_percentage, 2) }}%</dd>
                    </div>
                </div>
            </x-card>

            <!-- Outlets -->
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-text">{{ __('admin.outlets') }}</h3>
                        <p class="text-sm text-muted">{{ __('admin.outlets_count', ['count' => $tenant->outlets_count, 'max' => $tenant->max_outlets]) }}</p>
                    </div>
                    @if($tenant->canAddOutlet())
                        <x-button href="{{ route('admin.outlets.create') }}" size="sm" icon="plus">
                            {{ __('admin.can_add_outlet') }}
                        </x-button>
                    @endif
                </div>
                @if($tenant->outlets->count() > 0)
                    <div class="space-y-3">
                        @foreach($tenant->outlets as $outlet)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                        <x-icon name="building" class="w-5 h-5 text-accent" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-text">{{ $outlet->name }}</p>
                                        <p class="text-xs text-muted font-mono">{{ $outlet->code }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($outlet->is_active)
                                        <x-badge type="success" size="sm">{{ __('admin.active') }}</x-badge>
                                    @else
                                        <x-badge type="danger" size="sm">{{ __('admin.inactive') }}</x-badge>
                                    @endif
                                    <x-button href="{{ route('admin.outlets.show', $outlet) }}" variant="ghost" size="sm" icon="eye" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="{{ __('admin.no_outlets') }}"
                        description="{{ __('admin.no_outlets_tenant_desc') }}"
                        icon="building"
                    />
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Subscription -->
            <x-card title="{{ __('admin.subscription') }}">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.plan') }}</span>
                        <x-badge type="{{ $tenant->subscription_plan === 'free' ? 'secondary' : 'primary' }}">
                            {{ ucfirst($tenant->subscription_plan) }}
                        </x-badge>
                    </div>
                    @if($tenant->subscription_expires_at)
                        <div class="flex items-center justify-between">
                            <span class="text-muted">{{ __('admin.expires') }}</span>
                            <span class="text-sm text-text">{{ $tenant->subscription_expires_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.status') }}</span>
                        @if($tenant->isSubscriptionActive())
                            <x-badge type="success" size="sm">{{ __('admin.active') }}</x-badge>
                        @else
                            <x-badge type="danger" size="sm">{{ __('admin.expired') }}</x-badge>
                        @endif
                    </div>
                </div>
            </x-card>

            <!-- Recent Users -->
            <x-card title="{{ __('admin.recent_users') }}">
                @if($tenant->users->count() > 0)
                    <div class="space-y-3">
                        @foreach($tenant->users->take(5) as $user)
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-primary rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-xs font-medium text-white">{{ $user->initials }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-text truncate">{{ $user->name }}</p>
                                    <p class="text-xs text-muted truncate">{{ $user->email }}</p>
                                </div>
                                @if($user->is_active)
                                    <div class="w-2 h-2 bg-success rounded-full shrink-0"></div>
                                @else
                                    <div class="w-2 h-2 bg-danger rounded-full shrink-0"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($tenant->users_count > 5)
                        <div class="mt-4 pt-4 border-t border-border">
                            <x-button href="{{ route('admin.users.index', ['tenant' => $tenant->id]) }}" variant="ghost" size="sm" class="w-full">
                                {{ __('admin.view_all_users', ['count' => $tenant->users_count]) }}
                            </x-button>
                        </div>
                    @endif
                @else
                    <p class="text-muted text-sm text-center py-4">{{ __('admin.no_users_yet') }}</p>
                @endif
            </x-card>

            <!-- Timestamps -->
            <x-card title="{{ __('admin.activity') }}">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.created') }}</span>
                        <span class="text-text">{{ $tenant->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.last_updated') }}</span>
                        <span class="text-text">{{ $tenant->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
