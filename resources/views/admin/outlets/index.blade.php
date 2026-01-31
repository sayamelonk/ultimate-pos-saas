<x-app-layout>
    <x-slot name="title">Outlets - Ultimate POS</x-slot>

    @section('page-title', 'Outlets')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Outlet Management</h2>
                <p class="text-muted mt-1">Manage your business outlets</p>
            </div>
            <x-button href="{{ route('admin.outlets.create') }}" icon="plus">
                Add Outlet
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.outlets.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search outlets..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-40">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->hasAny(['search', 'status']))
                <x-button href="{{ route('admin.outlets.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($outlets->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Outlet</x-th>
                    <x-th>Code</x-th>
                    <x-th>Contact</x-th>
                    @if(auth()->user()->isSuperAdmin())
                        <x-th>Tenant</x-th>
                    @endif
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($outlets as $outlet)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="building" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $outlet->name }}</p>
                                    @if($outlet->address)
                                        <p class="text-xs text-muted truncate max-w-[200px]">{{ $outlet->address }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $outlet->code }}</code>
                        </x-td>
                        <x-td>
                            <div class="text-sm">
                                @if($outlet->phone)
                                    <p class="text-text">{{ $outlet->phone }}</p>
                                @endif
                                @if($outlet->email)
                                    <p class="text-muted text-xs">{{ $outlet->email }}</p>
                                @endif
                                @if(!$outlet->phone && !$outlet->email)
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </x-td>
                        @if(auth()->user()->isSuperAdmin())
                            <x-td>
                                <span class="text-sm text-muted">{{ $outlet->tenant?->name ?? '-' }}</span>
                            </x-td>
                        @endif
                        <x-td align="center">
                            @if($outlet->is_active)
                                <x-badge type="success" dot>Active</x-badge>
                            @else
                                <x-badge type="danger" dot>Inactive</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.outlets.show', $outlet) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="View Details">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.outlets.edit', $outlet) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="Edit">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                </a>
                                <div x-data class="inline">
                                    <form x-ref="deleteForm" action="{{ route('admin.outlets.destroy', $outlet) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors"
                                                title="Delete"
                                                onclick="console.log('onclick works'); window.dispatchEvent(new CustomEvent('confirm', { detail: { title: 'Delete Outlet', message: 'Are you sure you want to delete {{ $outlet->name }}?', confirmText: 'Yes, Delete', cancelText: 'Cancel', variant: 'danger', onConfirm: () => this.closest('form').submit() } }))">
                                            <x-icon name="trash" class="w-4 h-4 pointer-events-none" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$outlets" />
            </div>
        @else
            <x-empty-state
                title="No outlets found"
                description="Get started by creating your first outlet."
                icon="building"
            >
                <x-button href="{{ route('admin.outlets.create') }}" icon="plus">
                    Add Outlet
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
