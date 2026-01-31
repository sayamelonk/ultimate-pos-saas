<x-app-layout>
    <x-slot name="title">Log Waste - Ultimate POS</x-slot>

    @section('page-title', 'Log Waste')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.waste-logs.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Log Waste</h2>
                <p class="text-muted mt-1">Record inventory waste or spoilage</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.waste-logs.store') }}" method="POST" x-data="wasteForm()" class="space-y-6">
        @csrf

        <div class="max-w-3xl space-y-6">
            <x-card title="Waste Details">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="outlet_id" label="Outlet" required>
                            <option value="">Select Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </x-select>

                        <x-input
                            type="date"
                            name="waste_date"
                            label="Waste Date"
                            :value="old('waste_date', date('Y-m-d'))"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="inventory_item_id" label="Inventory Item" x-model="selectedItem" @change="updateCost()" required>
                            <option value="">Select Item</option>
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}" data-cost="{{ $item->cost_price }}" data-unit="{{ $item->unit->abbreviation ?? '' }}">
                                    {{ $item->name }} ({{ $item->sku }})
                                </option>
                            @endforeach
                        </x-select>

                        <x-select name="reason" label="Reason" required>
                            <option value="">Select Reason</option>
                            <option value="expired" @selected(old('reason') === 'expired')>Expired</option>
                            <option value="damaged" @selected(old('reason') === 'damaged')>Damaged</option>
                            <option value="spoiled" @selected(old('reason') === 'spoiled')>Spoiled</option>
                            <option value="overproduction" @selected(old('reason') === 'overproduction')>Overproduction</option>
                            <option value="quality_issue" @selected(old('reason') === 'quality_issue')>Quality Issue</option>
                            <option value="other" @selected(old('reason') === 'other')>Other</option>
                        </x-select>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-text">Quantity</label>
                            <div class="flex mt-1">
                                <input type="number" step="0.001" name="quantity" x-model="quantity" @input="updateCost()" class="flex-1 px-3 py-2 border border-border rounded-l-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0.001" required>
                                <span class="px-3 py-2 bg-secondary-100 border border-l-0 border-border rounded-r-lg text-sm text-muted" x-text="unit || 'Unit'"></span>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-text">Unit Cost</label>
                            <div class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg" x-text="'Rp ' + formatNumber(unitCost)"></div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-text">Total Value</label>
                            <div class="mt-1 px-3 py-2 bg-danger-100 rounded-lg font-bold text-danger-700" x-text="'Rp ' + formatNumber(totalValue)"></div>
                        </div>
                    </div>

                    <x-input
                        name="batch_number"
                        label="Batch Number (Optional)"
                        placeholder="e.g., BATCH-2024-001"
                        :value="old('batch_number')"
                    />

                    <x-textarea
                        name="notes"
                        label="Notes"
                        placeholder="Describe the waste incident..."
                        :value="old('notes')"
                        rows="3"
                    />
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('inventory.waste-logs.index') }}" variant="outline-secondary">
                    Cancel
                </x-button>
                <x-button type="submit">
                    Log Waste
                </x-button>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function wasteForm() {
            return {
                selectedItem: '{{ old('inventory_item_id', '') }}',
                quantity: {{ old('quantity', 0) }},
                unitCost: 0,
                unit: '',
                itemCosts: @json($inventoryItems->pluck('cost_price', 'id')),
                itemUnits: @json($inventoryItems->mapWithKeys(fn($item) => [$item->id => $item->unit?->abbreviation ?? ''])),

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
