<x-app-layout>
    <x-slot name="title">{{ $tenant->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Tenant Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.tenants.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
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
                    <x-badge type="success" dot>Active</x-badge>
                @else
                    <x-badge type="danger" dot>Inactive</x-badge>
                @endif
                <x-button href="{{ route('admin.tenants.edit', $tenant) }}" variant="secondary" icon="pencil">
                    Edit
                </x-button>
                <div x-data>
                    <x-button
                        variant="danger"
                        icon="trash"
                        @click="$dispatch('confirm', {
                            title: 'Delete Tenant',
                            message: 'Are you sure you want to delete {{ $tenant->name }}? All associated outlets, users, and data will be permanently deleted.',
                            confirmText: 'Delete',
                            variant: 'danger',
                            onConfirm: () => $refs.deleteForm.submit()
                        })"
                    >
                        Delete
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
            label="Outlets"
            :value="$tenant->outlets_count"
            icon="building"
            color="accent"
        />
        <x-stat-card
            label="Users"
            :value="$tenant->users_count"
            icon="users"
            color="primary"
        />
        <x-stat-card
            label="Subscription"
            :value="ucfirst($tenant->subscription_plan)"
            icon="credit-card"
            color="success"
        />
        <x-stat-card
            label="Max Outlets"
            :value="$tenant->max_outlets"
            icon="building"
            color="secondary"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <x-card title="Basic Information">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">Tenant Name</dt>
                        <dd class="text-text">{{ $tenant->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">Code</dt>
                        <dd class="font-mono text-sm text-text bg-secondary-100 px-2 py-1 rounded inline-block">{{ $tenant->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">Email</dt>
                        <dd class="text-text">{{ $tenant->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">Phone</dt>
                        <dd class="text-text">{{ $tenant->phone ?? '-' }}</dd>
                    </div>
                </div>
            </x-card>

            <!-- Settings -->
            <x-card title="Settings">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">Currency</dt>
                        <dd class="text-text font-medium">{{ $tenant->currency }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">Timezone</dt>
                        <dd class="text-text">{{ $tenant->timezone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">Tax Rate</dt>
                        <dd class="text-text">{{ number_format($tenant->tax_percentage, 2) }}%</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">Service Charge</dt>
                        <dd class="text-text">{{ number_format($tenant->service_charge_percentage, 2) }}%</dd>
                    </div>
                </div>
            </x-card>

            <!-- Outlets -->
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-text">Outlets</h3>
                        <p class="text-sm text-muted">{{ $tenant->outlets_count }} of {{ $tenant->max_outlets }} outlets</p>
                    </div>
                    @if($tenant->canAddOutlet())
                        <x-button href="{{ route('admin.outlets.create') }}" size="sm" icon="plus">
                            Add Outlet
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
                                        <x-badge type="success" size="sm">Active</x-badge>
                                    @else
                                        <x-badge type="danger" size="sm">Inactive</x-badge>
                                    @endif
                                    <x-button href="{{ route('admin.outlets.show', $outlet) }}" variant="ghost" size="sm" icon="eye" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="No outlets"
                        description="This tenant doesn't have any outlets yet."
                        icon="building"
                    />
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Subscription -->
            <x-card title="Subscription">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Plan</span>
                        <x-badge type="{{ $tenant->subscription_plan === 'free' ? 'secondary' : 'primary' }}">
                            {{ ucfirst($tenant->subscription_plan) }}
                        </x-badge>
                    </div>
                    @if($tenant->subscription_expires_at)
                        <div class="flex items-center justify-between">
                            <span class="text-muted">Expires</span>
                            <span class="text-sm text-text">{{ $tenant->subscription_expires_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Status</span>
                        @if($tenant->isSubscriptionActive())
                            <x-badge type="success" size="sm">Active</x-badge>
                        @else
                            <x-badge type="danger" size="sm">Expired</x-badge>
                        @endif
                    </div>
                </div>
            </x-card>

            <!-- Recent Users -->
            <x-card title="Recent Users">
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
                                View all {{ $tenant->users_count }} users
                            </x-button>
                        </div>
                    @endif
                @else
                    <p class="text-muted text-sm text-center py-4">No users yet.</p>
                @endif
            </x-card>

            <!-- Timestamps -->
            <x-card title="Activity">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Created</span>
                        <span class="text-text">{{ $tenant->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Last Updated</span>
                        <span class="text-text">{{ $tenant->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
