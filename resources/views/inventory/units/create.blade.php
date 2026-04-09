<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_unit') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_unit'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.units.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.add_unit_title') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_new_unit') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('inventory.units.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="name"
                        label="{{ __('inventory.unit_name') }}"
                        placeholder="{{ __('inventory.unit_name_placeholder') }}"
                        required
                    />

                    <x-input
                        name="abbreviation"
                        label="{{ __('inventory.abbreviation') }}"
                        placeholder="{{ __('inventory.abbreviation_placeholder') }}"
                        required
                    />
                </div>

                <x-select
                    name="base_unit_id"
                    label="{{ __('inventory.base_unit') }}"
                >
                    <option value="">{{ __('inventory.is_base_unit') }}</option>
                    @foreach($baseUnits as $baseUnit)
                        <option value="{{ $baseUnit->id }}" @selected(old('base_unit_id') == $baseUnit->id)>
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
                    :value="old('conversion_factor', 1)"
                    hint="{{ __('inventory.conversion_factor_hint') }}"
                />

                <x-checkbox
                    name="is_active"
                    label="{{ __('inventory.active') }}"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.units.index') }}" variant="outline-secondary">
                        {{ __('inventory.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('inventory.create_unit') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
