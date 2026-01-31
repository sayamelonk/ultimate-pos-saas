<x-app-layout>
    <x-slot name="title">Discounts - Ultimate POS</x-slot>

    @section('page-title', 'Discounts')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Discounts</h2>
                <p class="text-muted mt-1">Manage discounts and promotions</p>
            </div>
            <x-button href="{{ route('pricing.discounts.create') }}" icon="plus">
                Add Discount
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <div class="mb-6">
            <form method="GET" action="{{ route('pricing.discounts.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search discounts..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="type" class="w-40">
                    <option value="">All Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </x-select>
                <x-button type="submit" variant="secondary">Filter</x-button>
            </form>
        </div>

        @if($discounts->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Discount</x-th>
                    <x-th>Code</x-th>
                    <x-th>Type</x-th>
                    <x-th align="right">Value</x-th>
                    <x-th>Validity</x-th>
                    <x-th align="center">Usage</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($discounts as $discount)
                    <tr>
                        <x-td>
                            <div>
                                <p class="font-medium text-text">{{ $discount->name }}</p>
                                <p class="text-xs text-muted">{{ $discount->scope === 'order' ? 'Order Level' : 'Item Level' }}</p>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $discount->code }}</code>
                        </x-td>
                        <x-td>
                            <x-badge type="secondary">{{ $types[$discount->type] ?? $discount->type }}</x-badge>
                        </x-td>
                        <x-td align="right">
                            @if($discount->type === 'percentage')
                                {{ number_format($discount->value, 0) }}%
                                @if($discount->max_discount)
                                    <span class="text-xs text-muted">(max Rp {{ number_format($discount->max_discount, 0, ',', '.') }})</span>
                                @endif
                            @else
                                Rp {{ number_format($discount->value, 0, ',', '.') }}
                            @endif
                        </x-td>
                        <x-td>
                            <div class="text-sm">
                                <p>{{ $discount->valid_from->format('d M Y') }}</p>
                                @if($discount->valid_until)
                                    <p class="text-xs text-muted">to {{ $discount->valid_until->format('d M Y') }}</p>
                                @else
                                    <p class="text-xs text-muted">No expiry</p>
                                @endif
                            </div>
                        </x-td>
                        <x-td align="center">
                            {{ $discount->usage_count }}
                            @if($discount->usage_limit)
                                / {{ $discount->usage_limit }}
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($discount->isValid())
                                <x-badge type="success" dot>Active</x-badge>
                            @elseif(!$discount->is_active)
                                <x-badge type="danger" dot>Disabled</x-badge>
                            @else
                                <x-badge type="warning" dot>Expired</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <x-dropdown align="right">
                                <x-slot name="trigger">
                                    <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                        <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                    </button>
                                </x-slot>

                                <x-dropdown-item href="{{ route('pricing.discounts.show', $discount) }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                    View
                                </x-dropdown-item>
                                <x-dropdown-item href="{{ route('pricing.discounts.edit', $discount) }}">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                    Edit
                                </x-dropdown-item>
                                <x-dropdown-item
                                    type="button"
                                    danger
                                    @click="$dispatch('open-delete-modal', {
                                        title: 'Delete Discount',
                                        message: 'Are you sure you want to delete {{ $discount->name }}? This action cannot be undone.',
                                        action: '{{ route('pricing.discounts.destroy', $discount) }}'
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
                <x-pagination :paginator="$discounts" />
            </div>
        @else
            <x-empty-state
                title="No discounts found"
                description="Create discounts to offer promotions to customers."
                icon="tag"
            >
                <x-button href="{{ route('pricing.discounts.create') }}" icon="plus">
                    Add Discount
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
