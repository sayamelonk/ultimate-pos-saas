<x-app-layout>
    <x-slot name="title">Variant Groups - Ultimate POS</x-slot>

    @section('page-title', 'Variant Groups')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Variant Groups</h2>
                <p class="text-muted mt-1">Manage product variations like size, ice level, etc.</p>
            </div>
            <x-button href="{{ route('menu.variant-groups.create') }}" icon="plus">
                Add Variant Group
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('menu.variant-groups.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search variant groups..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-32">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->hasAny(['search', 'status']))
                <x-button href="{{ route('menu.variant-groups.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($variantGroups->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Group Name</x-th>
                    <x-th>Display Type</x-th>
                    <x-th>Options</x-th>
                    <x-th align="right">Products Using</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($variantGroups as $group)
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
                            @if($group->display_type === 'button')
                                <x-badge type="secondary">Button</x-badge>
                            @elseif($group->display_type === 'dropdown')
                                <x-badge type="info">Dropdown</x-badge>
                            @elseif($group->display_type === 'color')
                                <x-badge type="warning">Color</x-badge>
                            @else
                                <x-badge type="success">Image</x-badge>
                            @endif
                        </x-td>
                        <x-td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($group->options->take(4) as $option)
                                    <span class="px-2 py-0.5 bg-secondary-100 rounded text-xs">{{ $option->name }}</span>
                                @endforeach
                                @if($group->options->count() > 4)
                                    <span class="px-2 py-0.5 bg-secondary-100 rounded text-xs text-muted">+{{ $group->options->count() - 4 }} more</span>
                                @endif
                            </div>
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

                                    <x-dropdown-item href="{{ route('menu.variant-groups.show', $group) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('menu.variant-groups.edit', $group) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Variant Group',
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
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('menu.variant-groups.destroy', $group) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$variantGroups" />
            </div>
        @else
            <x-empty-state
                title="No variant groups found"
                description="Create variant groups to add options like size, ice level, sugar level to your products."
                icon="squares-2x2"
            >
                <x-button href="{{ route('menu.variant-groups.create') }}" icon="plus">
                    Add Variant Group
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
