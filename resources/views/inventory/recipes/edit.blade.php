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

                        <div class="grid grid-cols-2 gap-4">
                            <x-select name="category_id" label="Category">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $recipe->category_id) == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </x-select>

                            <x-select name="product_id" label="Link to Product (Optional)">
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id', $recipe->product_id) == $product->id)>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <x-input
                                type="number"
                                step="0.001"
                                name="yield_quantity"
                                label="Yield Quantity"
                                :value="old('yield_quantity', $recipe->yield_quantity)"
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
                            name="notes"
                            label="Notes"
                            placeholder="Additional notes..."
                            :value="old('notes', $recipe->notes)"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card title="Ingredients">
                    <template x-for="(ingredient, index) in ingredients" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <input type="hidden" :name="'ingredients[' + index + '][id]'" :value="ingredient.id">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">Inventory Item</label>
                                <select x-model="ingredient.inventory_item_id" @change="updateIngredientCost(index)" :name="'ingredients[' + index + '][inventory_item_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">Select Item</option>
                                    @foreach($inventoryItems as $item)
                                        <option value="{{ $item->id }}" data-cost="{{ $item->cost_price }}">{{ $item->name }} ({{ $item->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-28">
                                <label class="text-sm font-medium text-text">Quantity</label>
                                <input type="number" step="0.001" x-model="ingredient.quantity" @input="updateIngredientCost(index)" :name="'ingredients[' + index + '][quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0.001" required>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">Unit</label>
                                <select x-model="ingredient.unit_id" :name="'ingredients[' + index + '][unit_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">Select</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->abbreviation }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-36">
                                <label class="text-sm font-medium text-text">Cost</label>
                                <div class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg text-right font-medium" x-text="'Rp ' + formatNumber(ingredient.cost)"></div>
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removeIngredient(index)" class="p-2 text-danger-600 hover:bg-danger-50 rounded-lg" x-show="ingredients.length > 1">
                                    <x-icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <x-button type="button" @click="addIngredient()" variant="outline-secondary" icon="plus" class="mt-4">
                        Add Ingredient
                    </x-button>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Cost Summary">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-muted">Ingredients</dt>
                            <dd class="font-medium" x-text="ingredients.length"></dd>
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

    @push('scripts')
    <script>
        function recipeForm() {
            return {
                ingredients: @json($recipe->ingredients->map(fn($ing) => [
                    'id' => $ing->id,
                    'inventory_item_id' => $ing->inventory_item_id,
                    'quantity' => $ing->quantity,
                    'unit_id' => $ing->unit_id,
                    'cost' => $ing->cost,
                ])),
                itemCosts: @json($inventoryItems->pluck('cost_price', 'id')),

                get totalCost() {
                    return this.ingredients.reduce((sum, ing) => sum + (parseFloat(ing.cost) || 0), 0);
                },

                get costPerUnit() {
                    const qty = parseFloat(document.querySelector('[name="yield_quantity"]')?.value) || 1;
                    return qty > 0 ? this.totalCost / qty : 0;
                },

                addIngredient() {
                    this.ingredients.push({ id: null, inventory_item_id: '', quantity: 0, unit_id: '', cost: 0 });
                },

                removeIngredient(index) {
                    this.ingredients.splice(index, 1);
                },

                updateIngredientCost(index) {
                    const ing = this.ingredients[index];
                    const unitCost = this.itemCosts[ing.inventory_item_id] || 0;
                    ing.cost = (parseFloat(ing.quantity) || 0) * unitCost;
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
