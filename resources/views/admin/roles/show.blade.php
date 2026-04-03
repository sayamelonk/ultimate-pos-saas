<x-app-layout>
    <x-slot name="title">{{ $role->name }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.role_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.roles.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('app.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $role->name }}</h2>
                    <p class="text-muted mt-1 font-mono text-sm">{{ $role->slug }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button href="{{ route('admin.roles.permissions', $role) }}" variant="outline-secondary" icon="shield">
                    {{ __('admin.manage_permissions') }}
                </x-button>
                @if(!$role->is_system)
                    <x-button href="{{ route('admin.roles.edit', $role) }}" variant="outline-secondary" icon="pencil">
                        {{ __('app.edit') }}
                    </x-button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <x-card title="{{ __('admin.role_information') }}">
                <dl class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm text-muted">{{ __('admin.name') }}</dt>
                        <dd class="mt-1 font-medium text-text">{{ $role->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('admin.slug') }}</dt>
                        <dd class="mt-1 font-mono text-sm text-text">{{ $role->slug }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('app.type') }}</dt>
                        <dd class="mt-1">
                            @if($role->is_system)
                                <x-badge type="warning">{{ __('admin.system_role') }}</x-badge>
                            @else
                                <x-badge type="accent">{{ __('admin.custom_role') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('admin.created') }}</dt>
                        <dd class="mt-1 text-text">{{ $role->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-sm text-muted">{{ __('app.description') }}</dt>
                        <dd class="mt-1 text-text">{{ $role->description ?? __('admin.no_description') }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Permissions -->
            <x-card title="{{ __('admin.permissions') }}" subtitle="{{ __('admin.permissions_assigned', ['count' => $role->permissions->count()]) }}">
                @if($role->permissions->count() > 0)
                    @php
                        $groupedPermissions = $role->permissions->groupBy('module');
                    @endphp
                    <div class="space-y-4">
                        @foreach($groupedPermissions as $module => $modulePermissions)
                            <div class="border border-border rounded-lg p-4">
                                <h4 class="font-medium text-text mb-2 capitalize">{{ str_replace('_', ' ', $module) }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($modulePermissions as $permission)
                                        <x-badge type="primary" size="sm">{{ $permission->name }}</x-badge>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-sm">{{ __('admin.no_permissions_assigned') }}</p>
                    <x-button href="{{ route('admin.roles.permissions', $role) }}" variant="outline-secondary" size="sm" class="mt-4">
                        {{ __('admin.assign_permissions') }}
                    </x-button>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Stats -->
            <x-card title="{{ __('admin.statistics') }}">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.users_with_role') }}</span>
                        <span class="font-semibold text-text">{{ $role->users->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.permissions') }}</span>
                        <span class="font-semibold text-text">{{ $role->permissions->count() }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Recent Users -->
            <x-card title="{{ __('admin.users_with_role_title') }}">
                @if($role->users->count() > 0)
                    <div class="space-y-3">
                        @foreach($role->users as $user)
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                    <span class="text-xs font-medium text-white">{{ $user->initials }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-text truncate">{{ $user->name }}</p>
                                    <p class="text-xs text-muted truncate">{{ $user->email }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-sm">{{ __('admin.no_users_with_role') }}</p>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
