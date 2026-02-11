<x-app-layout>
    <x-slot name="title">Combo Meals - Ultimate POS</x-slot>

    @section('page-title', 'Combo Meals')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Combo Meals</h2>
                <p class="text-muted mt-1">Manage bundled product offerings</p>
            </div>
            <x-button href="{{ route('menu.combos.create') }}" icon="plus">
                Add Combo
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('menu.combos.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search combos..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="pricing_type" class="w-40">
                <option value="">All Pricing</option>
                <option value="fixed" @selected(request('pricing_type') === 'fixed')>Fixed Price</option>
                <option value="sum" @selected(request('pricing_type') === 'sum')>Sum of Items</option>
                <option value="discount_percent" @selected(request('pricing_type') === 'discount_percent')>% Discount</option>
                <option value="discount_amount" @selected(request('pricing_type') === 'discount_amount')>Fixed Discount</option>
            </x-select>
            <x-select name="status" class="w-32">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->hasAny(['search', 'pricing_type', 'status']))
                <x-button href="{{ route('menu.combos.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($combos->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Combo</x-th>
                    <x-th>Items</x-th>
                    <x-th>Pricing Type</x-th>
                    <x-th align="right">Price</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($combos as $combo)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-accent/20 to-warning/20 rounded-lg flex items-center justify-center">
                                    <x-icon name="rectangle-stack" class="w-6 h-6 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $combo->name }}</p>
                                    @if($combo->description)
                                        <p class="text-xs text-muted truncate max-w-[200px]">{{ $combo->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <div class="flex flex-wrap gap-1">
                                @if($combo->combo && $combo->combo->items)
                                    @foreach($combo->combo->items->take(3) as $item)
                                        <span class="px-2 py-0.5 bg-secondary-100 rounded text-xs">
                                            {{ $item->quantity }}x {{ $item->product->name ?? $item->category->name ?? 'Any' }}
                                        </span>
                                    @endforeach
                                    @if($combo->combo->items->count() > 3)
                                        <span class="px-2 py-0.5 bg-secondary-100 rounded text-xs text-muted">+{{ $combo->combo->items->count() - 3 }} more</span>
                                    @endif
                                @else
                                    <span class="text-xs text-muted">No items</span>
                                @endif
                            </div>
                        </x-td>
                        <x-td>
                            @if($combo->combo?->pricing_type === 'fixed')
                                <x-badge type="success">Fixed Price</x-badge>
                            @elseif($combo->combo?->pricing_type === 'sum')
                                <x-badge type="secondary">Sum of Items</x-badge>
                            @elseif($combo->combo?->pricing_type === 'discount_percent')
                                <x-badge type="warning">{{ $combo->combo->discount_value }}% Off</x-badge>
                            @elseif($combo->combo?->pricing_type === 'discount_amount')
                                <x-badge type="info">-Rp {{ number_format($combo->combo->discount_value, 0, ',', '.') }}</x-badge>
                            @else
                                <x-badge type="secondary">-</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <span class="font-medium">Rp {{ number_format($combo->base_price, 0, ',', '.') }}</span>
                        </x-td>
                        <x-td align="center">
                            @if($combo->is_active)
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

                                    <x-dropdown-item href="{{ route('menu.combos.show', $combo) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('menu.combos.edit', $combo) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Combo',
                                            message: 'Are you sure you want to delete {{ $combo->name }}? This action cannot be undone.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('menu.combos.destroy', $combo) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$combos" />
            </div>
        @else
            <x-empty-state
                title="No combos found"
                description="Create combo meals to offer bundled products at special prices."
                icon="rectangle-stack"
            >
                <x-button href="{{ route('menu.combos.create') }}" icon="plus">
                    Add Combo
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
