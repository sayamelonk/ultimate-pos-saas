<x-app-layout>
    <x-slot name="title">Units - Ultimate POS</x-slot>

    @section('page-title', 'Units')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Unit Management</h2>
                <p class="text-muted mt-1">Manage units of measure for inventory</p>
            </div>
            <x-button href="{{ route('inventory.units.create') }}" icon="plus">
                Add Unit
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.units.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search units..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="type" :value="request('type')" class="w-40">
                    <option value="">All Types</option>
                    <option value="base" @selected(request('type') === 'base')>Base Units</option>
                    <option value="derived" @selected(request('type') === 'derived')>Derived Units</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'type']))
                    <x-button href="{{ route('inventory.units.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($units->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Unit Name</x-th>
                    <x-th>Abbreviation</x-th>
                    <x-th>Base Unit</x-th>
                    <x-th align="center">Conversion</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($units as $unit)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="scale" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $unit->name }}</p>
                                    @if($unit->baseUnit)
                                        <p class="text-xs text-muted">Derived unit</p>
                                    @else
                                        <p class="text-xs text-muted">Base unit</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $unit->abbreviation }}</code>
                        </x-td>
                        <x-td>
                            {{ $unit->baseUnit->name ?? '-' }}
                        </x-td>
                        <x-td align="center">
                            @if($unit->baseUnit)
                                1 {{ $unit->abbreviation }} = {{ number_format($unit->conversion_factor, 4) }} {{ $unit->baseUnit->abbreviation }}
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($unit->is_active)
                                <x-badge type="success" dot>Active</x-badge>
                            @else
                                <x-badge type="danger" dot>Inactive</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div x-data>
                                <x-dropdown align="right">
                                    <x-slot name="trigger">
                                        <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                            <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                        </button>
                                    </x-slot>

                                    <x-dropdown-item href="{{ route('inventory.units.show', $unit) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('inventory.units.edit', $unit) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Unit',
                                            message: 'Are you sure you want to delete {{ $unit->name }}? This action cannot be undone.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.units.destroy', $unit) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$units" />
            </div>
        @else
            <x-empty-state
                title="No units found"
                description="Get started by creating your first unit of measure."
                icon="scale"
            >
                <x-button href="{{ route('inventory.units.create') }}" icon="plus">
                    Add Unit
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
