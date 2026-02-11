<x-app-layout>
    <x-slot name="title">Menu Categories - Ultimate POS</x-slot>

    @section('page-title', 'Menu Categories')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Menu Categories</h2>
                <p class="text-muted mt-1">Organize your menu items</p>
            </div>
            <x-button href="{{ route('menu.categories.create') }}" icon="plus">
                Add Category
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('menu.categories.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search categories..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="parent_id" class="w-48">
                <option value="">All Categories</option>
                <option value="root" @selected(request('parent_id') === 'root')>Root Only</option>
                @foreach($parentCategories as $parent)
                    <option value="{{ $parent->id }}" @selected(request('parent_id') == $parent->id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </x-select>
            <x-select name="status" class="w-32">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->hasAny(['search', 'parent_id', 'status']))
                <x-button href="{{ route('menu.categories.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($categories->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Category</x-th>
                    <x-th>Code</x-th>
                    <x-th>Parent</x-th>
                    <x-th align="right">Products</x-th>
                    <x-th align="center">POS</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($categories as $category)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $category->color ?? '#e5e7eb' }}20;">
                                    @if($category->icon)
                                        <x-icon :name="$category->icon" class="w-5 h-5" style="color: {{ $category->color ?? '#6b7280' }};" />
                                    @else
                                        <x-icon name="squares-2x2" class="w-5 h-5" style="color: {{ $category->color ?? '#6b7280' }};" />
                                    @endif
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
                            @if($category->code)
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $category->code }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td>{{ $category->parent->name ?? '-' }}</x-td>
                        <x-td align="right">{{ $category->products_count }}</x-td>
                        <x-td align="center">
                            @if($category->show_in_pos)
                                <x-badge type="success" size="sm">Yes</x-badge>
                            @else
                                <x-badge type="secondary" size="sm">No</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($category->is_active)
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

                                    <x-dropdown-item href="{{ route('menu.categories.show', $category) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('menu.categories.edit', $category) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Category',
                                            message: 'Are you sure you want to delete {{ $category->name }}? This action cannot be undone.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('menu.categories.destroy', $category) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
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
                description="Get started by creating your first menu category."
                icon="squares-2x2"
            >
                <x-button href="{{ route('menu.categories.create') }}" icon="plus">
                    Add Category
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
