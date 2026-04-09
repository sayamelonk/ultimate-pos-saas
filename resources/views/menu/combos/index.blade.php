<x-app-layout>
    <x-slot name="title">{{ __('products.combo_meals') }} - Ultimate POS</x-slot>

    @section('page-title', __('products.combo_meals'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('products.combo_meals') }}</h2>
                <p class="text-muted mt-1">{{ __('products.manage_combos') }}</p>
            </div>
            <x-button href="{{ route('menu.combos.create') }}" icon="plus">
                {{ __('products.add_combo') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('menu.combos.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('products.search_combos') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="pricing_type" class="w-40">
                <option value="">{{ __('products.all_pricing') }}</option>
                <option value="fixed" @selected(request('pricing_type') === 'fixed')>{{ __('products.fixed_price') }}</option>
                <option value="sum" @selected(request('pricing_type') === 'sum')>{{ __('products.sum_of_items') }}</option>
                <option value="discount_percent" @selected(request('pricing_type') === 'discount_percent')>{{ __('products.percent_discount') }}</option>
                <option value="discount_amount" @selected(request('pricing_type') === 'discount_amount')>{{ __('products.fixed_discount') }}</option>
            </x-select>
            <x-select name="status" class="w-32">
                <option value="">{{ __('products.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('products.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('products.inactive') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('products.filter') }}</x-button>
            @if(request()->hasAny(['search', 'pricing_type', 'status']))
                <x-button href="{{ route('menu.combos.index') }}" variant="ghost">{{ __('products.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($combos->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('products.combo') }}</x-th>
                    <x-th>{{ __('products.items') }}</x-th>
                    <x-th>{{ __('products.pricing_type') }}</x-th>
                    <x-th align="right">{{ __('products.price') }}</x-th>
                    <x-th align="center">{{ __('products.status') }}</x-th>
                    <x-th align="right">{{ __('products.actions') }}</x-th>
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
                                        <span class="px-2 py-0.5 bg-secondary-100 rounded text-xs text-muted">{{ __('products.more_items', ['count' => $combo->combo->items->count() - 3]) }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-muted">{{ __('products.no_items') }}</span>
                                @endif
                            </div>
                        </x-td>
                        <x-td>
                            @if($combo->combo?->pricing_type === 'fixed')
                                <x-badge type="success">{{ __('products.fixed_price') }}</x-badge>
                            @elseif($combo->combo?->pricing_type === 'sum')
                                <x-badge type="secondary">{{ __('products.sum_of_items') }}</x-badge>
                            @elseif($combo->combo?->pricing_type === 'discount_percent')
                                <x-badge type="warning">{{ __('products.percent_off', ['percent' => $combo->combo->discount_value]) }}</x-badge>
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
                                <x-badge type="success" dot>{{ __('products.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('products.inactive') }}</x-badge>
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
                                        {{ __('products.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('menu.combos.edit', $combo) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('products.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('products.delete_combo') }}',
                                            message: '{{ __('products.confirm_delete', ['name' => $combo->name]) }}',
                                            confirmText: '{{ __('products.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('products.delete') }}
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
                title="{{ __('products.no_combos') }}"
                description="{{ __('products.no_combos_desc') }}"
                icon="rectangle-stack"
            >
                <x-button href="{{ route('menu.combos.create') }}" icon="plus">
                    {{ __('products.add_combo') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
