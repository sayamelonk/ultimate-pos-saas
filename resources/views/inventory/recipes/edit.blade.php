<x-app-layout>
    <x-slot name="title">Edit Recipe - Ultimate POS</x-slot>

    @section('page-title', 'Edit Recipe')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.recipes.show', $recipe) }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Recipe</h2>
                <p class="text-muted mt-1">{{ $recipe->name }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.recipes.update', $recipe) }}" method="POST" x-data="recipeForm()" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card title="Recipe Details">
                    <div class="space-y-4">
                        <x-input
                            name="name"
                            label="Recipe Name"
                            placeholder="e.g., Iced Latte"
                            :value="old('name', $recipe->name)"
                            required
                        />

                        @if($products->count() > 0)
                        <x-select name="product_id" label="Link to Product (Optional)">
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id', $recipe->product_id) == $product->id)>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </x-select>
                        @endif

                        <div class="grid grid-cols-3 gap-4">
                            <x-input
                                type="number"
                                step="0.001"
                                name="yield_qty"
                                label="Yield Quantity"
                                :value="old('yield_qty', $recipe->yield_qty)"
                                min="0.001"
                                required
                            />

                            <x-select name="yield_unit_id" label="Yield Unit" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('yield_unit_id', $recipe->yield_unit_id) == $unit->id)>
                                        {{ $unit->name }} ({{ $unit->abbreviation }})
                                    </option>
                                @endforeach
                            </x-select>

                            <div class="flex items-end">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $recipe->is_active)) class="rounded border-border text-accent focus:ring-accent">
                                    <span class="text-sm font-medium text-text">Active</span>
                                </label>
                            </div>
                        </div>

                        <x-textarea
                            name="instructions"
                            label="Preparation Instructions"
                            placeholder="Step by step preparation instructions..."
                            :value="old('instructions', $recipe->instructions)"
                            rows="3"
                        />

                        <x-textarea
                            name="description"
                            label="Description"
                            placeholder="Recipe description..."
                            :value="old('description', $recipe->description)"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card title="Ingredients">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">Inventory Item</label>
                                <select x-model="item.inventory_item_id" @change="onItemChange(index)" :name="'items[' + index + '][inventory_item_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">Select Item</option>
                                    @foreach($inventoryItems as $invItem)
                                        <option value="{{ $invItem->id }}">{{ $invItem->name }} ({{ $invItem->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-24">
                                <label class="text-sm font-medium text-text">Quantity</label>
                                <input type="number" step="0.001" x-model="item.quantity" @input="updateItemCost(index)" :name="'items[' + index + '][quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0.001" required>
                            </div>
                            <div class="w-28">
                                <label class="text-sm font-medium text-text">Unit</label>
                                <select x-model="item.unit_id" @change="updateItemCost(index)" :name="'items[' + index + '][unit_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <template x-for="unit in getAvailableUnits(item.inventory_item_id)" :key="unit.id">
                                        <option :value="unit.id" x-text="unit.abbreviation" :selected="unit.id === item.unit_id"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">Cost</label>
                                <div class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg text-right font-medium" x-text="'Rp ' + formatNumber(item.cost)"></div>
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removeItem(index)" class="p-2 text-danger-600 hover:bg-danger-50 rounded-lg" x-show="items.length > 1">
                                    <x-icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <x-button type="button" @click="addItem()" variant="outline-secondary" icon="plus" class="mt-4">
                        Add Ingredient
                    </x-button>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Cost Summary">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-muted">Ingredients</dt>
                            <dd class="font-medium" x-text="items.length"></dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="font-bold">Total Cost</dt>
                            <dd class="font-bold text-lg" x-text="'Rp ' + formatNumber(totalCost)"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Cost per Unit</dt>
                            <dd class="font-medium" x-text="'Rp ' + formatNumber(costPerUnit)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            Update Recipe
                        </x-button>
                        <x-button href="{{ route('inventory.recipes.show', $recipe) }}" variant="outline-secondary" class="w-full">
                            Cancel
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>

    @php
        $itemsData = $recipe->items->map(function($item) {
            return [
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => (float) $item->quantity,
                'unit_id' => $item->unit_id,
                'cost' => (float) $item->calculateCost(),
            ];
        });

        // Item data: cost_price per base unit, base unit_id
        $itemDataMap = $inventoryItems->mapWithKeys(function($item) {
            return [$item->id => [
                'cost_price' => (float) ($item->cost_price ?? 0),
                'unit_id' => $item->unit_id,
            ]];
        });

        // Units with conversion data
        $unitsData = $units->mapWithKeys(function($unit) {
            return [$unit->id => [
                'id' => $unit->id,
                'name' => $unit->name,
                'abbreviation' => $unit->abbreviation,
                'base_unit_id' => $unit->base_unit_id,
                'conversion_factor' => (float) ($unit->conversion_factor ?? 1),
            ]];
        });
    @endphp

    @push('scripts')
    <script>
        function recipeForm() {
            return {
                items: @json($itemsData),
                itemData: @json($itemDataMap),
                units: @json($unitsData),

                init() {
                    // Watch for changes in items and recalculate costs
                    this.$watch('items', (items) => {
                        items.forEach((item, index) => {
                            if (item.inventory_item_id && item.quantity > 0) {
                                this.updateItemCost(index);
                            }
                        });
                    }, { deep: true });
                },

                get totalCost() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.cost) || 0), 0);
                },

                get costPerUnit() {
                    const qty = parseFloat(document.querySelector('[name="yield_qty"]')?.value) || 1;
                    return qty > 0 ? this.totalCost / qty : 0;
                },

                // Get available units for an inventory item (base unit + related units)
                getAvailableUnits(inventoryItemId) {
                    if (!inventoryItemId || !this.itemData[inventoryItemId]) return [];

                    const baseUnitId = this.itemData[inventoryItemId].unit_id;
                    const availableUnits = [];

                    // Add base unit first
                    if (this.units[baseUnitId]) {
                        availableUnits.push(this.units[baseUnitId]);
                    }

                    // Add units that convert to this base unit
                    Object.values(this.units).forEach(unit => {
                        if (unit.base_unit_id === baseUnitId) {
                            availableUnits.push(unit);
                        }
                    });

                    return availableUnits;
                },

                // When inventory item changes, set default unit
                onItemChange(index) {
                    const item = this.items[index];
                    if (item.inventory_item_id && this.itemData[item.inventory_item_id]) {
                        item.unit_id = this.itemData[item.inventory_item_id].unit_id;
                    }
                    this.updateItemCost(index);
                },

                addItem() {
                    this.items.push({ inventory_item_id: '', quantity: 0, unit_id: '', cost: 0 });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                updateItemCost(index) {
                    const item = this.items[index];
                    if (!item.inventory_item_id || !this.itemData[item.inventory_item_id]) {
                        item.cost = 0;
                        return;
                    }

                    const invItem = this.itemData[item.inventory_item_id];
                    const baseUnitCost = invItem.cost_price; // Cost per base unit (e.g., per kg)
                    const quantity = parseFloat(item.quantity) || 0;

                    // Get conversion factor for selected unit
                    let conversionFactor = 1;
                    if (item.unit_id && this.units[item.unit_id]) {
                        const selectedUnit = this.units[item.unit_id];
                        // If selected unit has a conversion factor (e.g., gram to kg = 0.001)
                        if (selectedUnit.conversion_factor && selectedUnit.conversion_factor !== 1) {
                            conversionFactor = selectedUnit.conversion_factor;
                        }
                    }

                    // Cost = quantity * conversion_factor * base_unit_cost
                    // e.g., 18 gram * 0.001 (kg/g) * 180000 (Rp/kg) = 3240
                    item.cost = quantity * conversionFactor * baseUnitCost;
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(Math.round(num) || 0);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
