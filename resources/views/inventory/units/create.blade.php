<x-app-layout>
    <x-slot name="title">Create Unit - Ultimate POS</x-slot>

    @section('page-title', 'Create Unit')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.units.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Create Unit</h2>
                <p class="text-muted mt-1">Add a new unit of measure</p>
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
                        label="Unit Name"
                        placeholder="e.g., Kilogram"
                        required
                    />

                    <x-input
                        name="abbreviation"
                        label="Abbreviation"
                        placeholder="e.g., kg"
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
                        <option value="{{ $baseUnit->id }}" @selected(old('base_unit_id') == $baseUnit->id)>
                            {{ $baseUnit->name }} ({{ $baseUnit->abbreviation }})
                        </option>
                    @endforeach
                </x-select>

                <x-input
                    type="number"
                    step="0.000001"
                    name="conversion_factor"
                    label="Conversion Factor"
                    placeholder="e.g., 1000"
                    :value="old('conversion_factor', 1)"
                    hint="How many base units equal 1 of this unit"
                />

                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive units won't appear in selections"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.units.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Create Unit
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
