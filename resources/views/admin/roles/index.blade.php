<x-app-layout>
    <x-slot name="title">Roles - Ultimate POS</x-slot>

    @section('page-title', 'Roles')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Role Management</h2>
                <p class="text-muted mt-1">Manage roles and permissions</p>
            </div>
            <x-button href="{{ route('admin.roles.create') }}" icon="plus">
                Add Role
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.roles.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search roles..."
                :value="request('search')"
                class="w-64"
            />
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->has('search'))
                <x-button href="{{ route('admin.roles.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($roles->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Role</x-th>
                    <x-th>Description</x-th>
                    <x-th align="center">Users</x-th>
                    <x-th align="center">Permissions</x-th>
                    <x-th align="center">Type</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($roles as $role)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="shield" class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $role->name }}</p>
                                    <p class="text-xs text-muted font-mono">{{ $role->slug }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <p class="text-sm text-muted max-w-xs truncate">
                                {{ $role->description ?? '-' }}
                            </p>
                        </x-td>
                        <x-td align="center">
                            <x-badge type="info">{{ $role->users_count }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            <x-badge type="secondary">{{ $role->permissions_count }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            @if($role->is_system)
                                <x-badge type="warning">System</x-badge>
                            @else
                                <x-badge type="accent">Custom</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.roles.show', $role) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="View Details">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.roles.permissions', $role) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="Manage Permissions">
                                    <x-icon name="shield" class="w-4 h-4" />
                                </a>
                                @if(!$role->is_system)
                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                       title="Edit">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                    </a>
                                    <form x-ref="deleteRole{{ $loop->index }}" action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors"
                                                title="Delete"
                                                x-on:click="$dispatch('confirm', {
                                                    title: 'Delete Role',
                                                    message: 'Are you sure you want to delete {{ $role->name }}? Users with this role will lose these permissions.',
                                                    confirmText: 'Yes, Delete',
                                                    cancelText: 'Cancel',
                                                    variant: 'danger',
                                                    onConfirm: () => $refs.deleteRole{{ $loop->index }}.submit()
                                                })">
                                            <x-icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$roles" />
            </div>
        @else
            <x-empty-state
                title="No roles found"
                description="Get started by creating your first custom role."
                icon="shield"
            >
                <x-button href="{{ route('admin.roles.create') }}" icon="plus">
                    Add Role
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
