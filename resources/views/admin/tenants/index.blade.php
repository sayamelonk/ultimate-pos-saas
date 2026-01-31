<x-app-layout>
    <x-slot name="title">Tenants - Ultimate POS</x-slot>

    @section('page-title', 'Tenants')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Tenant Management</h2>
                <p class="text-muted mt-1">Manage all tenants in the system</p>
            </div>
            <x-button href="{{ route('admin.tenants.create') }}" icon="plus">
                Add Tenant
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.tenants.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search tenants..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-40" placeholder="All Status">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->hasAny(['search', 'status']))
                <x-button href="{{ route('admin.tenants.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($tenants->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Tenant</x-th>
                    <x-th>Contact</x-th>
                    <x-th align="center">Outlets</x-th>
                    <x-th align="center">Users</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="center">Created</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($tenants as $tenant)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <span class="text-sm font-semibold text-primary">
                                        {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $tenant->name }}</p>
                                    <p class="text-xs text-muted font-mono">{{ $tenant->code }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <div class="text-sm">
                                @if($tenant->email)
                                    <p class="text-text">{{ $tenant->email }}</p>
                                @endif
                                @if($tenant->phone)
                                    <p class="text-muted text-xs">{{ $tenant->phone }}</p>
                                @endif
                                @if(!$tenant->email && !$tenant->phone)
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </x-td>
                        <x-td align="center">
                            <x-badge type="info">{{ $tenant->outlets_count }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            <x-badge type="secondary">{{ $tenant->users_count }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            @if($tenant->is_active)
                                <x-badge type="success" dot>Active</x-badge>
                            @else
                                <x-badge type="danger" dot>Inactive</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            <span class="text-muted text-xs">{{ $tenant->created_at->format('M d, Y') }}</span>
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.tenants.show', $tenant) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="View Details">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.tenants.edit', $tenant) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="Edit">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                </a>
                                <form x-ref="deleteTenant{{ $loop->index }}" action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors"
                                            title="Delete"
                                            x-on:click="$dispatch('confirm', {
                                                title: 'Delete Tenant',
                                                message: 'Are you sure you want to delete {{ $tenant->name }}? All associated outlets, users, and data will be permanently deleted.',
                                                confirmText: 'Yes, Delete',
                                                cancelText: 'Cancel',
                                                variant: 'danger',
                                                onConfirm: () => $refs.deleteTenant{{ $loop->index }}.submit()
                                            })">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$tenants" />
            </div>
        @else
            <x-empty-state
                title="No tenants found"
                description="Get started by creating your first tenant."
                icon="building"
            >
                <x-button href="{{ route('admin.tenants.create') }}" icon="plus">
                    Add Tenant
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
