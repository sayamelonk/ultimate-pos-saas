<x-app-layout>
    <x-slot name="title">New Recipe - Ultimate POS</x-slot>

    @section('page-title', 'New Recipe')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.recipes.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">New Recipe</h2>
                <p class="text-muted mt-1">Create a new recipe with ingredients</p>
            </div>
        </div>
    </x-slot>

    @php
        $itemCostsData = $inventoryItems->pluck('cost_price', 'id')->toArray();
    @endphp

    <form action="{{ route('inventory.recipes.store') }}" method="POST"
          x-data="{
              items: [{ inventory_item_id: '', quantity: 0, cost: 0 }],
              itemCosts: {{ json_encode($itemCostsData) }},

              get totalCost() {
                  return this.items.reduce((sum, item) => sum + (parseFloat(item.cost) || 0), 0);
              },

              get costPerUnit() {
                  const qty = parseFloat(document.querySelector('[name=\'yield_qty\']')?.value) || 1;
                  return qty > 0 ? this.totalCost / qty : 0;
              },

              addItem() {
                  this.items.push({ inventory_item_id: '', quantity: 0, cost: 0 });
              },

              removeItem(index) {
                  this.items.splice(index, 1);
              },

              updateItemCost(index) {
                  const item = this.items[index];
                  const unitCost = this.itemCosts[item.inventory_item_id] || 0;
                  item.cost = (parseFloat(item.quantity) || 0) * unitCost;
              },

              formatNumber(num) {
                  return new Intl.NumberFormat('id-ID').format(num || 0);
              }
          }"
          class="space-y-6">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card title="Recipe Details">
                    <div class="space-y-4">
                        <x-input
                            name="name"
                            label="Recipe Name"
                            placeholder="e.g., Iced Latte"
                            :value="old('name')"
                            required
                        />

                        @if($products->count() > 0)
                        <x-select name="product_id" label="Link to Product (Optional)">
                            <option value="">Select Product</option>
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
                                label="Yield Quantity"
                                :value="old('yield_qty', 1)"
                                min="0.001"
                                required
                            />

                            <x-select name="yield_unit_id" label="Yield Unit" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('yield_unit_id') == $unit->id)>
                                        {{ $unit->name }} ({{ $unit->abbreviation }})
                                    </option>
                                @endforeach
                            </x-select>

                            <div class="flex items-end">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-border text-accent focus:ring-accent">
                                    <span class="text-sm font-medium text-text">Active</span>
                                </label>
                            </div>
                        </div>

                        <x-textarea
                            name="instructions"
                            label="Preparation Instructions"
                            placeholder="Step by step preparation instructions..."
                            :value="old('instructions')"
                            rows="3"
                        />

                        <x-textarea
                            name="notes"
                            label="Notes"
                            placeholder="Additional notes..."
                            :value="old('notes')"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card title="Ingredients">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">Inventory Item</label>
                                <select x-model="item.inventory_item_id" @change="updateItemCost(index)" :name="'items[' + index + '][inventory_item_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">Select Item</option>
                                    @foreach($inventoryItems as $invItem)
                                        <option value="{{ $invItem->id }}" data-cost="{{ $invItem->cost_price }}" data-unit="{{ $invItem->unit_id }}">{{ $invItem->name }} ({{ $invItem->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-28">
                                <label class="text-sm font-medium text-text">Quantity</label>
                                <input type="number" step="0.001" x-model="item.quantity" @input="updateItemCost(index)" :name="'items[' + index + '][quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0.001" required>
                            </div>
                            <div class="w-36">
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
                            Create Recipe
                        </x-button>
                        <x-button href="{{ route('inventory.recipes.index') }}" variant="outline-secondary" class="w-full">
                            Cancel
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>
</x-app-layout>
