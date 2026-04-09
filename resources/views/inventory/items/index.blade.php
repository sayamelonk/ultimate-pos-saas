<x-app-layout>
    <x-slot name="title">{{ __('inventory.items') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.items'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.items') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.manage_items') }}</p>
            </div>
            <x-button href="{{ route('inventory.items.create') }}" icon="plus">
                {{ __('inventory.add_item') }}
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
                        placeholder="{{ __('inventory.search_items') }}"
                        :value="request('search')"
                    />
                </div>
                <x-select name="category_id" class="w-48">
                    <option value="">{{ __('inventory.all_categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="type" class="w-40">
                    <option value="">{{ __('inventory.all_types') }}</option>
                    <option value="raw_material" @selected(request('type') === 'raw_material')>{{ __('inventory.raw_material') }}</option>
                    <option value="finished_good" @selected(request('type') === 'finished_good')>{{ __('inventory.finished_good') }}</option>
                    <option value="consumable" @selected(request('type') === 'consumable')>{{ __('inventory.consumable') }}</option>
                    <option value="packaging" @selected(request('type') === 'packaging')>{{ __('inventory.packaging') }}</option>
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">{{ __('inventory.all_status') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('app.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('app.inactive') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('app.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'category_id', 'type', 'status']))
                    <x-button href="{{ route('inventory.items.index') }}" variant="ghost">
                        {{ __('app.clear') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($items->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.sku') }}</x-th>
                    <x-th>{{ __('inventory.category') }}</x-th>
                    <x-th>{{ __('inventory.item_type') }}</x-th>
                    <x-th align="right">{{ __('inventory.cost_price') }}</x-th>
                    <x-th align="center">{{ __('app.status') }}</x-th>
                    <x-th align="right">{{ __('app.actions') }}</x-th>
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
                            <span class="capitalize text-xs">{{ __('inventory.' . $item->type) }}</span>
                        </x-td>
                        <x-td align="right">
                            Rp {{ number_format($item->cost_price, 0, ',', '.') }}
                        </x-td>
                        <x-td align="center">
                            @if($item->is_active)
                                <x-badge type="success" dot>{{ __('app.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('app.inactive') }}</x-badge>
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
                                        {{ __('app.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('inventory.items.edit', $item) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('app.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('inventory.delete_item') }}',
                                            message: '{{ __('app.confirm_delete', ['name' => $item->name]) }}',
                                            confirmText: '{{ __('app.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('app.delete') }}
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
                title="{{ __('inventory.no_items_found') }}"
                description="{{ __('inventory.no_items_description') }}"
                icon="cube"
            >
                <x-button href="{{ route('inventory.items.create') }}" icon="plus">
                    {{ __('inventory.add_item') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
