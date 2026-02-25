<x-app-layout>
    <x-slot name="title">Edit Unit - Ultimate POS</x-slot>

    @section('page-title', 'Edit Unit')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.units.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Unit</h2>
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
                        label="Unit Name"
                        placeholder="e.g., Kilogram"
                        :value="$unit->name"
                        required
                    />

                    <x-input
                        name="abbreviation"
                        label="Abbreviation"
                        placeholder="e.g., kg"
                        :value="$unit->abbreviation"
                        hint="Short code for this unit"
                        required
                    />
                </div>

                <x-select
                    name="base_unit_id"
                    label="Base Unit (Optional)"
                    hint="Select if this is a derived unit"
                >
                    <option value="">None (This is a base unit)</option>
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
                    label="Conversion Factor"
                    placeholder="e.g., 1000"
                    :value="old('conversion_factor', rtrim(rtrim(number_format($unit->conversion_factor, 6, '.', ''), '0'), '.'))"
                    hint="How many base units equal 1 of this unit (e.g., 1 kg = 1000 g, so conversion for g is 0.001)"
                />

                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive units won't appear in selections"
                    :checked="$unit->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.units.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Update Unit
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
