<x-app-layout>
    <x-slot name="title">{{ __('pricing.discounts') }} - Ultimate POS</x-slot>

    @section('page-title', __('pricing.discounts'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('pricing.discounts') }}</h2>
                <p class="text-muted mt-1">{{ __('pricing.manage_discounts_description') }}</p>
            </div>
            <x-button href="{{ route('pricing.discounts.create') }}" icon="plus">
                {{ __('pricing.add_discount') }}
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
                        placeholder="{{ __('pricing.search_discounts') }}"
                        :value="request('search')"
                    />
                </div>
                <x-select name="type" class="w-40">
                    <option value="">{{ __('pricing.all_types') }}</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">{{ __('pricing.all_status') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('pricing.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('pricing.inactive') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">{{ __('pricing.filter') }}</x-button>
            </form>
        </div>

        @if($discounts->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('pricing.discount') }}</x-th>
                    <x-th>{{ __('pricing.code') }}</x-th>
                    <x-th>{{ __('pricing.type') }}</x-th>
                    <x-th align="right">{{ __('pricing.value') }}</x-th>
                    <x-th>{{ __('pricing.validity') }}</x-th>
                    <x-th align="center">{{ __('pricing.usage') }}</x-th>
                    <x-th align="center">{{ __('pricing.status') }}</x-th>
                    <x-th align="right">{{ __('pricing.actions') }}</x-th>
                </x-slot>

                @foreach($discounts as $discount)
                    <tr>
                        <x-td>
                            <div>
                                <p class="font-medium text-text">{{ $discount->name }}</p>
                                <p class="text-xs text-muted">{{ $discount->scope === 'order' ? __('pricing.order_level') : __('pricing.item_level') }}</p>
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
                                    <span class="text-xs text-muted">({{ __('pricing.max') }} Rp {{ number_format($discount->max_discount, 0, ',', '.') }})</span>
                                @endif
                            @else
                                Rp {{ number_format($discount->value, 0, ',', '.') }}
                            @endif
                        </x-td>
                        <x-td>
                            <div class="text-sm">
                                <p>{{ $discount->valid_from->format('d M Y') }}</p>
                                @if($discount->valid_until)
                                    <p class="text-xs text-muted">{{ __('pricing.to') }} {{ $discount->valid_until->format('d M Y') }}</p>
                                @else
                                    <p class="text-xs text-muted">{{ __('pricing.no_expiry') }}</p>
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
                                <x-badge type="success" dot>{{ __('pricing.active') }}</x-badge>
                            @elseif(!$discount->is_active)
                                <x-badge type="danger" dot>{{ __('pricing.disabled') }}</x-badge>
                            @else
                                <x-badge type="warning" dot>{{ __('pricing.expired') }}</x-badge>
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

                                    <x-dropdown-item href="{{ route('pricing.discounts.show', $discount) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('pricing.view') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('pricing.discounts.edit', $discount) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('pricing.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('pricing.delete_discount_title') }}',
                                            message: '{{ __('pricing.delete_discount_message', ['name' => $discount->name]) }}',
                                            confirmText: '{{ __('pricing.delete_confirm') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('pricing.delete') }}
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('pricing.discounts.destroy', $discount) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$discounts" />
            </div>
        @else
            <x-empty-state
                title="{{ __('pricing.no_discounts_found') }}"
                description="{{ __('pricing.no_discounts_description') }}"
                icon="tag"
            >
                <x-button href="{{ route('pricing.discounts.create') }}" icon="plus">
                    {{ __('pricing.add_discount') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
