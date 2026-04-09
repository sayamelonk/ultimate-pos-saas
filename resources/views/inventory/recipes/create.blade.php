<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_recipe') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_recipe'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            @if(isset($linkedProduct))
                <x-button href="{{ route('menu.products.show', $linkedProduct) }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('inventory.back') }}
                </x-button>
            @else
                <x-button href="{{ route('inventory.recipes.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('inventory.back') }}
                </x-button>
            @endif
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.create_recipe') }}</h2>
                @if(isset($linkedProduct))
                    <p class="text-muted mt-1">{{ __('inventory.linked_product') }}: <span class="text-accent font-medium">{{ $linkedProduct->name }}</span></p>
                @else
                    <p class="text-muted mt-1">{{ __('inventory.create_new_recipe') }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    @php
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

    <form action="{{ route('inventory.recipes.store') }}" method="POST" x-data="recipeForm()" class="space-y-6">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card :title="__('inventory.recipe_details')">
                    <div class="space-y-4">
                        <x-input
                            name="name"
                            :label="__('inventory.recipe_name')"
                            :placeholder="__('inventory.recipe_name_placeholder')"
                            :value="old('name')"
                            required
                        />

                        @if(isset($linkedProduct))
                            <input type="hidden" name="product_id" value="{{ $linkedProduct->id }}">
                            <div class="p-3 bg-accent/5 border border-accent/20 rounded-lg">
                                <p class="text-sm text-muted">{{ __('inventory.linked_product') }}</p>
                                <p class="font-medium text-accent">{{ $linkedProduct->name }}</p>
                            </div>
                        @elseif($products->count() > 0)
                            <x-select name="product_id" :label="__('inventory.link_to_product')">
                                <option value="">{{ __('inventory.select_item') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
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
                                :label="__('inventory.yield_quantity')"
                                :value="old('yield_qty', 1)"
                                min="0.001"
                                required
                            />

                            <x-select name="yield_unit_id" :label="__('inventory.yield_unit')" required>
                                <option value="">{{ __('inventory.select_item') }}</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('yield_unit_id') == $unit->id)>
                                        {{ $unit->name }} ({{ $unit->abbreviation }})
                                    </option>
                                @endforeach
                            </x-select>

                            <div class="flex items-end">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-border text-accent focus:ring-accent">
                                    <span class="text-sm font-medium text-text">{{ __('inventory.active') }}</span>
                                </label>
                            </div>
                        </div>

                        <x-textarea
                            name="instructions"
                            :label="__('inventory.instructions')"
                            :placeholder="__('inventory.instructions')"
                            :value="old('instructions')"
                            rows="3"
                        />

                        <x-textarea
                            name="notes"
                            :label="__('inventory.notes')"
                            :placeholder="__('inventory.notes')"
                            :value="old('notes')"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card :title="__('inventory.ingredients')">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">{{ __('inventory.inventory_item') }}</label>
                                <select x-model="item.inventory_item_id" @change="onItemChange(index)" :name="'items[' + index + '][inventory_item_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">{{ __('inventory.select_item') }}</option>
                                    @foreach($inventoryItems as $invItem)
                                        <option value="{{ $invItem->id }}">{{ $invItem->name }} ({{ $invItem->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-24">
                                <label class="text-sm font-medium text-text">{{ __('inventory.quantity') }}</label>
                                <input type="number" step="0.001" x-model="item.quantity" @input="updateItemCost(index)" :name="'items[' + index + '][quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0.001" required>
                            </div>
                            <div class="w-28">
                                <label class="text-sm font-medium text-text">{{ __('inventory.unit') }}</label>
                                <select x-model="item.unit_id" @change="updateItemCost(index)" :name="'items[' + index + '][unit_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <template x-for="unit in getAvailableUnits(item.inventory_item_id)" :key="unit.id">
                                        <option :value="unit.id" x-text="unit.abbreviation" :selected="unit.id === item.unit_id"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">{{ __('inventory.total_cost') }}</label>
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
                        {{ __('inventory.add_ingredient') }}
                    </x-button>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card :title="__('inventory.summary')">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.ingredients') }}</dt>
                            <dd class="font-medium" x-text="items.length"></dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="font-bold">{{ __('inventory.total_cost') }}</dt>
                            <dd class="font-bold text-lg" x-text="'Rp ' + formatNumber(totalCost)"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.cost_per_unit') }}</dt>
                            <dd class="font-medium" x-text="'Rp ' + formatNumber(costPerUnit)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            {{ __('inventory.create_recipe') }}
                        </x-button>
                        <x-button href="{{ route('inventory.recipes.index') }}" variant="outline-secondary" class="w-full">
                            {{ __('inventory.cancel') }}
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function recipeForm() {
            return {
                items: [{ inventory_item_id: '', quantity: 0, unit_id: '', cost: 0 }],
                itemData: @json($itemDataMap),
                units: @json($unitsData),

                init() {
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
                    const baseUnitCost = invItem.cost_price;
                    const quantity = parseFloat(item.quantity) || 0;

                    // Get conversion factor for selected unit
                    let conversionFactor = 1;
                    if (item.unit_id && this.units[item.unit_id]) {
                        const selectedUnit = this.units[item.unit_id];
                        if (selectedUnit.conversion_factor && selectedUnit.conversion_factor !== 1) {
                            conversionFactor = selectedUnit.conversion_factor;
                        }
                    }

                    // Cost = quantity * conversion_factor * base_unit_cost
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
