<x-app-layout>
    <x-slot name="title">Inventory Items - Ultimate POS</x-slot>

    @section('page-title', 'Inventory Items')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Inventory Items</h2>
                <p class="text-muted mt-1">Manage your inventory items and raw materials</p>
            </div>
            <x-button href="{{ route('inventory.items.create') }}" icon="plus">
                Add Item
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.items.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search items..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="category_id" class="w-48">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="type" class="w-40">
                    <option value="">All Types</option>
                    <option value="raw_material" @selected(request('type') === 'raw_material')>Raw Material</option>
                    <option value="finished_good" @selected(request('type') === 'finished_good')>Finished Good</option>
                    <option value="consumable" @selected(request('type') === 'consumable')>Consumable</option>
                    <option value="packaging" @selected(request('type') === 'packaging')>Packaging</option>
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'category_id', 'type', 'status']))
                    <x-button href="{{ route('inventory.items.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($items->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th>Category</x-th>
                    <x-th>Type</x-th>
                    <x-th align="right">Cost Price</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($items as $item)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="cube" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $item->name }}</p>
                                    <p class="text-xs text-muted">{{ $item->unit->abbreviation ?? '' }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->sku }}</code>
                        </x-td>
                        <x-td>
                            {{ $item->category->name ?? '-' }}
                        </x-td>
                        <x-td>
                            <span class="capitalize text-xs">{{ str_replace('_', ' ', $item->type) }}</span>
                        </x-td>
                        <x-td align="right">
                            Rp {{ number_format($item->cost_price, 0, ',', '.') }}
                        </x-td>
                        <x-td align="center">
                            @if($item->is_active)
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

                                    <x-dropdown-item href="{{ route('inventory.items.show', $item) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('inventory.items.edit', $item) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Item',
                                            message: 'Are you sure you want to delete {{ $item->name }}? This action cannot be undone.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.items.destroy', $item) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$items" />
            </div>
        @else
            <x-empty-state
                title="No inventory items found"
                description="Get started by creating your first inventory item."
                icon="cube"
            >
                <x-button href="{{ route('inventory.items.create') }}" icon="plus">
                    Add Item
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
