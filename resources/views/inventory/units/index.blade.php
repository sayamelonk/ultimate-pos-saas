<x-app-layout>
    <x-slot name="title">{{ __('inventory.units') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.units'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.units') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_new_unit') }}</p>
            </div>
            <x-button href="{{ route('inventory.units.create') }}" icon="plus">
                {{ __('inventory.create_unit') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.units.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="{{ __('inventory.search') }}..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="type" :value="request('type')" class="w-40">
                    <option value="">{{ __('inventory.all_types') }}</option>
                    <option value="base" @selected(request('type') === 'base')>{{ __('inventory.base_unit') }}</option>
                    <option value="derived" @selected(request('type') === 'derived')>{{ __('inventory.unit') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('inventory.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'type']))
                    <x-button href="{{ route('inventory.units.index') }}" variant="ghost">
                        {{ __('app.clear') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($units->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.unit_name') }}</x-th>
                    <x-th>{{ __('inventory.abbreviation') }}</x-th>
                    <x-th>{{ __('inventory.base_unit') }}</x-th>
                    <x-th align="center">{{ __('inventory.conversion_factor') }}</x-th>
                    <x-th align="center">{{ __('inventory.status') }}</x-th>
                    <x-th align="right">{{ __('inventory.actions') }}</x-th>
                </x-slot>

                @foreach($units as $unit)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="scale" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $unit->name }}</p>
                                    @if($unit->baseUnit)
                                        <p class="text-xs text-muted">{{ __('inventory.unit') }}</p>
                                    @else
                                        <p class="text-xs text-muted">{{ __('inventory.base_unit') }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $unit->abbreviation }}</code>
                        </x-td>
                        <x-td>
                            {{ $unit->baseUnit->name ?? '-' }}
                        </x-td>
                        <x-td align="center">
                            @if($unit->baseUnit)
                                1 {{ $unit->abbreviation }} = {{ number_format($unit->conversion_factor, 4) }} {{ $unit->baseUnit->abbreviation }}
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($unit->is_active)
                                <x-badge type="success" dot>{{ __('inventory.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('inventory.inactive') }}</x-badge>
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

                                    <x-dropdown-item href="{{ route('inventory.units.show', $unit) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('app.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('inventory.units.edit', $unit) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('inventory.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('inventory.delete') }} {{ __('inventory.unit') }}',
                                            message: '{{ __('app.confirm_delete', ['name' => $unit->name]) }}',
                                            confirmText: '{{ __('inventory.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('inventory.delete') }}
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.units.destroy', $unit) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$units" />
            </div>
        @else
            <x-empty-state
                title="{{ __('inventory.no_items_found') }}"
                description="{{ __('inventory.no_items_using_unit') }}"
                icon="scale"
            >
                <x-button href="{{ route('inventory.units.create') }}" icon="plus">
                    {{ __('inventory.create_unit') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
