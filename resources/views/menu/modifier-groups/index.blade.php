<x-app-layout>
    <x-slot name="title">Modifier Groups - Ultimate POS</x-slot>

    @section('page-title', 'Modifier Groups')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Modifier Groups</h2>
                <p class="text-muted mt-1">Manage add-ons and extras for your products</p>
            </div>
            <x-button href="{{ route('menu.modifier-groups.create') }}" icon="plus">
                Add Modifier Group
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('menu.modifier-groups.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search modifier groups..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="selection_type" class="w-40">
                <option value="">All Types</option>
                <option value="single" @selected(request('selection_type') === 'single')>Single Select</option>
                <option value="multiple" @selected(request('selection_type') === 'multiple')>Multi Select</option>
            </x-select>
            <x-select name="status" class="w-32">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->hasAny(['search', 'selection_type', 'status']))
                <x-button href="{{ route('menu.modifier-groups.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($modifierGroups->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Group Name</x-th>
                    <x-th>Type</x-th>
                    <x-th>Modifiers</x-th>
                    <x-th align="center">Selection Rules</x-th>
                    <x-th align="right">Products Using</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($modifierGroups as $group)
                    <tr>
                        <x-td>
                            <div>
                                <p class="font-medium text-text">{{ $group->name }}</p>
                                @if($group->description)
                                    <p class="text-xs text-muted truncate max-w-[200px]">{{ $group->description }}</p>
                                @endif
                            </div>
                        </x-td>
                        <x-td>
                            @if($group->selection_type === 'single')
                                <x-badge type="secondary">Single Select</x-badge>
                            @else
                                <x-badge type="info">Multi Select</x-badge>
                            @endif
                        </x-td>
                        <x-td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($group->modifiers->take(3) as $modifier)
                                    <span class="px-2 py-0.5 bg-secondary-100 rounded text-xs">
                                        {{ $modifier->name }}
                                        @if($modifier->price > 0)
                                            <span class="text-muted">(+{{ number_format($modifier->price / 1000, 0) }}k)</span>
                                        @endif
                                    </span>
                                @endforeach
                                @if($group->modifiers->count() > 3)
                                    <span class="px-2 py-0.5 bg-secondary-100 rounded text-xs text-muted">+{{ $group->modifiers->count() - 3 }} more</span>
                                @endif
                            </div>
                        </x-td>
                        <x-td align="center">
                            @if($group->selection_type === 'multiple')
                                <span class="text-sm">
                                    {{ $group->min_selections ?? 0 }} - {{ $group->max_selections ?? '∞' }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td align="right">{{ $group->products_count ?? 0 }}</x-td>
                        <x-td align="center">
                            @if($group->is_active)
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

                                    <x-dropdown-item href="{{ route('menu.modifier-groups.show', $group) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('menu.modifier-groups.edit', $group) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Modifier Group',
                                            message: 'Are you sure you want to delete {{ $group->name }}? This will affect all products using this group.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('menu.modifier-groups.destroy', $group) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$modifierGroups" />
            </div>
        @else
            <x-empty-state
                title="No modifier groups found"
                description="Create modifier groups to add extras like toppings, sauces, and add-ons to your products."
                icon="plus-circle"
            >
                <x-button href="{{ route('menu.modifier-groups.create') }}" icon="plus">
                    Add Modifier Group
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
