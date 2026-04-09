<x-app-layout>
    <x-slot name="title">{{ $unit->name }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.unit_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.units.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('inventory.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $unit->name }}</h2>
                    <p class="text-muted mt-1">{{ $unit->abbreviation }}</p>
                </div>
            </div>
            <x-button href="{{ route('inventory.units.edit', $unit) }}" variant="outline-secondary" icon="pencil">
                {{ __('inventory.edit') }}
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <x-card title="{{ __('inventory.information') }}">
            <dl class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.name') }}</dt>
                    <dd class="mt-1 font-medium text-text">{{ $unit->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.abbreviation') }}</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $unit->abbreviation }}</code>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.item_type') }}</dt>
                    <dd class="mt-1 text-text">
                        @if($unit->baseUnit)
                            {{ __('inventory.unit') }}
                        @else
                            {{ __('inventory.base_unit') }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.status') }}</dt>
                    <dd class="mt-1">
                        @if($unit->is_active)
                            <x-badge type="success" dot>{{ __('inventory.active') }}</x-badge>
                        @else
                            <x-badge type="danger" dot>{{ __('inventory.inactive') }}</x-badge>
                        @endif
                    </dd>
                </div>
                @if($unit->baseUnit)
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.base_unit') }}</dt>
                        <dd class="mt-1 text-text">{{ $unit->baseUnit->name }} ({{ $unit->baseUnit->abbreviation }})</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.conversion_factor') }}</dt>
                        <dd class="mt-1 text-text">
                            1 {{ $unit->abbreviation }} = {{ number_format($unit->conversion_factor, 4) }} {{ $unit->baseUnit->abbreviation }}
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.created') }}</dt>
                    <dd class="mt-1 text-text">{{ $unit->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.updated') }}</dt>
                    <dd class="mt-1 text-text">{{ $unit->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        @if($unit->derivedUnits && $unit->derivedUnits->count() > 0)
            <x-card title="{{ __('inventory.units') }}">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.unit_name') }}</x-th>
                        <x-th>{{ __('inventory.abbreviation') }}</x-th>
                        <x-th align="center">{{ __('inventory.conversion_factor') }}</x-th>
                        <x-th align="center">{{ __('inventory.status') }}</x-th>
                    </x-slot>

                    @foreach($unit->derivedUnits as $derivedUnit)
                        <tr>
                            <x-td>{{ $derivedUnit->name }}</x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $derivedUnit->abbreviation }}</code>
                            </x-td>
                            <x-td align="center">
                                1 {{ $derivedUnit->abbreviation }} = {{ number_format($derivedUnit->conversion_factor, 4) }} {{ $unit->abbreviation }}
                            </x-td>
                            <x-td align="center">
                                @if($derivedUnit->is_active)
                                    <x-badge type="success" size="sm">{{ __('inventory.active') }}</x-badge>
                                @else
                                    <x-badge type="danger" size="sm">{{ __('inventory.inactive') }}</x-badge>
                                @endif
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    </div>
</x-app-layout>
