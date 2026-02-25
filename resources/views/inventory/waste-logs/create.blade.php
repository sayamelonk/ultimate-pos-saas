<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_waste') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_waste'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.waste-logs.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.create_waste') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.record_waste_description') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.waste-logs.store') }}" method="POST" x-data="wasteForm()" class="space-y-6">
        @csrf

        <div class="max-w-3xl space-y-6">
            <x-card :title="__('inventory.waste_details')">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="outlet_id" :label="__('app.outlet')" required>
                            <option value="">{{ __('inventory.select_outlet') }}</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </x-select>

                        <x-input
                            type="date"
                            name="waste_date"
                            :label="__('inventory.waste_date')"
                            :value="old('waste_date', date('Y-m-d'))"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="inventory_item_id" :label="__('inventory.item')" x-model="selectedItem" @change="updateCost()" required>
                            <option value="">{{ __('inventory.select_item') }}</option>
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}" data-cost="{{ $item->cost_price }}" data-unit="{{ $item->unit->abbreviation ?? '' }}">
                                    {{ $item->name }} ({{ $item->sku }})
                                </option>
                            @endforeach
                        </x-select>

                        <x-select name="reason" :label="__('inventory.waste_reason')" required>
                            <option value="">{{ __('app.select') }}</option>
                            <option value="expired" @selected(old('reason') === 'expired')>{{ __('inventory.waste_expired') }}</option>
                            <option value="damaged" @selected(old('reason') === 'damaged')>{{ __('inventory.waste_damaged') }}</option>
                            <option value="spoiled" @selected(old('reason') === 'spoiled')>{{ __('inventory.waste_spoiled') }}</option>
                            <option value="overproduction" @selected(old('reason') === 'overproduction')>{{ __('inventory.waste_overproduction') }}</option>
                            <option value="quality_issue" @selected(old('reason') === 'quality_issue')>{{ __('inventory.waste_quality_issue') }}</option>
                            <option value="other" @selected(old('reason') === 'other')>{{ __('inventory.waste_other') }}</option>
                        </x-select>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-text">{{ __('app.quantity') }}</label>
                            <div class="flex mt-1">
                                <input type="number" step="0.001" name="quantity" x-model="quantity" @input="updateCost()" class="flex-1 px-3 py-2 border border-border rounded-l-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0.001" required>
                                <span class="px-3 py-2 bg-secondary-100 border border-l-0 border-border rounded-r-lg text-sm text-muted" x-text="unit || '{{ __('inventory.unit') }}'"></span>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-text">{{ __('inventory.unit_cost') }}</label>
                            <div class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg" x-text="'Rp ' + formatNumber(unitCost)"></div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-text">{{ __('inventory.waste_value') }}</label>
                            <div class="mt-1 px-3 py-2 bg-danger-100 rounded-lg font-bold text-danger-700" x-text="'Rp ' + formatNumber(totalValue)"></div>
                        </div>
                    </div>

                    <x-input
                        name="batch_number"
                        :label="__('inventory.batch_number') . ' (' . __('app.optional') . ')'"
                        placeholder="e.g., BATCH-2024-001"
                        :value="old('batch_number')"
                    />

                    <x-textarea
                        name="notes"
                        :label="__('app.notes')"
                        :placeholder="__('inventory.waste_notes_placeholder')"
                        :value="old('notes')"
                        rows="3"
                    />
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('inventory.waste-logs.index') }}" variant="outline-secondary">
                    {{ __('app.cancel') }}
                </x-button>
                <x-button type="submit">
                    {{ __('inventory.create_waste') }}
                </x-button>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function wasteForm() {
            @php
                $itemUnitsData = $inventoryItems->mapWithKeys(function($item) {
                    return [$item->id => $item->unit?->abbreviation ?? ''];
                });
            @endphp
            return {
                selectedItem: '{{ old('inventory_item_id', '') }}',
                quantity: {{ old('quantity', 0) }},
                unitCost: 0,
                unit: '',
                itemCosts: @json($inventoryItems->pluck('cost_price', 'id')),
                itemUnits: @json($itemUnitsData),

                get totalValue() {
                    return (parseFloat(this.quantity) || 0) * this.unitCost;
                },

                updateCost() {
                    this.unitCost = this.itemCosts[this.selectedItem] || 0;
                    this.unit = this.itemUnits[this.selectedItem] || '';
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                },

                init() {
                    if (this.selectedItem) {
                        this.updateCost();
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
