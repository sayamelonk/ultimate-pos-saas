<x-app-layout>
    <x-slot name="title">Categories - Ultimate POS</x-slot>

    @section('page-title', 'Categories')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Inventory Categories</h2>
                <p class="text-muted mt-1">Organize your inventory items</p>
            </div>
            <x-button href="{{ route('inventory.categories.create') }}" icon="plus">
                Add Category
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('inventory.categories.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search categories..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="parent_id" class="w-48">
                <option value="">All Categories</option>
                <option value="root" @selected(request('parent_id') === 'root')>Root Categories</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->hasAny(['search', 'parent_id']))
                <x-button href="{{ route('inventory.categories.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($categories->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Category</x-th>
                    <x-th>Code</x-th>
                    <x-th>Parent Category</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($categories as $category)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="folder" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $category->name }}</p>
                                    @if($category->description)
                                        <p class="text-xs text-muted truncate max-w-[200px]">{{ $category->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $category->code }}</code>
                        </x-td>
                        <x-td>
                            {{ $category->parent->name ?? '-' }}
                        </x-td>
                        <x-td align="center">
                            @if($category->is_active)
                                <x-badge type="success" dot>Active</x-badge>
                            @else
                                <x-badge type="danger" dot>Inactive</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <x-dropdown align="right">
                                <x-slot name="trigger">
                                    <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                        <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                    </button>
                                </x-slot>

                                <x-dropdown-item href="{{ route('inventory.categories.show', $category) }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                    View Details
                                </x-dropdown-item>
                                <x-dropdown-item href="{{ route('inventory.categories.edit', $category) }}">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                    Edit
                                </x-dropdown-item>
                                <x-dropdown-item
                                    type="button"
                                    danger
                                    @click="$dispatch('open-delete-modal', {
                                        title: 'Delete Category',
                                        message: 'Are you sure you want to delete {{ $category->name }}? This action cannot be undone.',
                                        action: '{{ route('inventory.categories.destroy', $category) }}'
                                    })"
                                >
                                    <x-icon name="trash" class="w-4 h-4" />
                                    Delete
                                </x-dropdown-item>
                            </x-dropdown>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$categories" />
            </div>
        @else
            <x-empty-state
                title="No categories found"
                description="Get started by creating your first category."
                icon="folder"
            >
                <x-button href="{{ route('inventory.categories.create') }}" icon="plus">
                    Add Category
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
