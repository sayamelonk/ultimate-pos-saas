<x-app-layout>
    <x-slot name="title">{{ __('inventory.edit_unit') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.edit_unit'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.units.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.edit_unit') }}</h2>
                <p class="text-muted mt-1">{{ $unit->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('inventory.units.update', $unit) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="name"
                        label="{{ __('inventory.unit_name') }}"
                        placeholder="{{ __('inventory.unit_name_placeholder') }}"
                        :value="$unit->name"
                        required
                    />

                    <x-input
                        name="abbreviation"
                        label="{{ __('inventory.abbreviation') }}"
                        placeholder="{{ __('inventory.abbreviation_placeholder') }}"
                        :value="$unit->abbreviation"
                        required
                    />
                </div>

                <x-select
                    name="base_unit_id"
                    label="{{ __('inventory.base_unit') }}"
                >
                    <option value="">{{ __('inventory.is_base_unit') }}</option>
                    @foreach($baseUnits as $baseUnit)
                        <option value="{{ $baseUnit->id }}" @selected(old('base_unit_id', $unit->base_unit_id) == $baseUnit->id)>
                            {{ $baseUnit->name }} ({{ $baseUnit->abbreviation }})
                        </option>
                    @endforeach
                </x-select>

                <x-input
                    type="number"
                    step="any"
                    name="conversion_factor"
                    label="{{ __('inventory.conversion_factor') }}"
                    placeholder="e.g., 1000"
                    :value="old('conversion_factor', rtrim(rtrim(number_format($unit->conversion_factor, 6, '.', ''), '0'), '.'))"
                    hint="{{ __('inventory.conversion_factor_hint') }}"
                />

                <x-checkbox
                    name="is_active"
                    label="{{ __('inventory.active') }}"
                    :checked="$unit->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.units.index') }}" variant="outline-secondary">
                        {{ __('inventory.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('inventory.update_unit') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
