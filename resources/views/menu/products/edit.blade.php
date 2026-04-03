<x-app-layout>
    <x-slot name="title">Edit {{ $product->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Edit Product')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.products.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Product</h2>
                <p class="text-muted mt-1">{{ $product->name }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.products.update', $product) }}" method="POST" enctype="multipart/form-data" x-data="productForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <!-- Basic Information -->
                <x-card title="Basic Information">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="Product Name" name="name" required>
                                <x-input
                                    name="name"
                                    :value="old('name', $product->name)"
                                    placeholder="e.g., Cappuccino"
                                    required
                                />
                            </x-form-group>

                            <x-form-group label="SKU" name="sku" required>
                                <x-input
                                    name="sku"
                                    :value="old('sku', $product->sku)"
                                    placeholder="e.g., CAP-001"
                                    required
                                />
                            </x-form-group>
                        </div>

                        <x-form-group label="Category" name="category_id">
                            <x-select name="category_id">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                        {{ $category->full_path }}
                                    </option>
                                @endforeach
                            </x-select>
                        </x-form-group>

                        <x-form-group label="Description" name="description">
                            <x-textarea
                                name="description"
                                :value="old('description', $product->description)"
                                placeholder="Optional description..."
                                rows="3"
                            />
                        </x-form-group>

                        <x-form-group label="Product Type" name="product_type" required>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="product_type"
                                        value="single"
                                        x-model="productType"
                                        {{ old('product_type', $product->product_type) === 'single' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <x-icon name="cube" class="w-6 h-6 mb-2 mx-auto text-muted peer-checked:text-accent" />
                                        <p class="text-center font-medium">Single</p>
                                        <p class="text-xs text-center text-muted mt-1">Simple product</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="product_type"
                                        value="variant"
                                        x-model="productType"
                                        {{ old('product_type', $product->product_type) === 'variant' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <x-icon name="squares-2x2" class="w-6 h-6 mb-2 mx-auto text-muted peer-checked:text-accent" />
                                        <p class="text-center font-medium">Variant</p>
                                        <p class="text-xs text-center text-muted mt-1">With size/options</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="product_type"
                                        value="combo"
                                        x-model="productType"
                                        {{ old('product_type', $product->product_type) === 'combo' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <x-icon name="rectangle-stack" class="w-6 h-6 mb-2 mx-auto text-muted peer-checked:text-accent" />
                                        <p class="text-center font-medium">Combo</p>
                                        <p class="text-xs text-center text-muted mt-1">Bundle products</p>
                                    </div>
                                </label>
                            </div>
                        </x-form-group>
                    </div>
                </x-card>

                <!-- Pricing -->
                <x-card title="Pricing">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="Base Price" name="base_price" required>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">Rp</span>
                                    <x-input
                                        type="number"
                                        name="base_price"
                                        :value="old('base_price', $product->base_price)"
                                        min="0"
                                        step="100"
                                        class="pl-10"
                                        required
                                    />
                                </div>
                            </x-form-group>

                            <x-form-group label="Cost Price" name="cost_price">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">Rp</span>
                                    <x-input
                                        type="number"
                                        name="cost_price"
                                        :value="old('cost_price', $product->cost_price)"
                                        min="0"
                                        step="100"
                                        class="pl-10"
                                    />
                                </div>
                            </x-form-group>
                        </div>

                        <x-form-group label="Tax Rate (%)" name="tax_rate">
                            <x-input
                                type="number"
                                name="tax_rate"
                                :value="old('tax_rate', $product->tax_rate)"
                                min="0"
                                max="100"
                                step="0.1"
                            />
                        </x-form-group>

                        @if($product->cost_price > 0)
                            @php
                                $margin = $product->base_price > 0 ? (($product->base_price - $product->cost_price) / $product->base_price) * 100 : 0;
                            @endphp
                            <div class="p-3 bg-secondary-50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-muted">Profit Margin</span>
                                    <span class="font-bold {{ $margin >= 30 ? 'text-success' : ($margin >= 15 ? 'text-warning' : 'text-danger') }}">
                                        {{ number_format($margin, 1) }}%
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-card>

                <!-- Variant Groups (shown when product_type = variant) -->
                <div x-show="productType === 'variant'" x-cloak>
                    <x-card title="Variant Groups">
                        <p class="text-muted mb-4">Select variant groups for this product (e.g., Size, Ice Level)</p>
                        <div class="space-y-2">
                            @foreach($variantGroups as $group)
                                <label class="flex items-center gap-3 p-3 bg-secondary-50 rounded-lg cursor-pointer hover:bg-secondary-100 transition-colors">
                                    <input
                                        type="checkbox"
                                        name="variant_groups[]"
                                        value="{{ $group->id }}"
                                        {{ in_array($group->id, old('variant_groups', $product->variantGroups->pluck('id')->toArray())) ? 'checked' : '' }}
                                        class="rounded border-border text-accent focus:ring-accent"
                                    >
                                    <div class="flex-1">
                                        <p class="font-medium">{{ $group->name }}</p>
                                        <p class="text-xs text-muted">
                                            {{ $group->options->pluck('name')->implode(', ') }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @if($variantGroups->isEmpty())
                            <x-empty-state
                                title="No variant groups"
                                description="Create variant groups first."
                                icon="squares-2x2"
                                size="sm"
                            />
                        @endif

                        @if($product->variants->count() > 0)
                            <div class="mt-6 pt-6 border-t border-border">
                                <h4 class="font-medium mb-3">Generated Variants ({{ $product->variants->count() }})</h4>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    @foreach($product->variants as $variant)
                                        <div class="flex items-center justify-between p-2 bg-white border border-border rounded">
                                            <div>
                                                <p class="text-sm font-medium">{{ $variant->name }}</p>
                                                <p class="text-xs text-muted">{{ $variant->sku }}</p>
                                            </div>
                                            <span class="font-medium">Rp {{ number_format($variant->price, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <x-button
                                    type="button"
                                    variant="outline-secondary"
                                    size="sm"
                                    class="mt-3"
                                    @click="if(confirm('Regenerate all variants? This will delete existing variants.')) { document.getElementById('regenerate-form').submit(); }"
                                >
                                    Regenerate Variants
                                </x-button>
                            </div>
                        @endif
                    </x-card>
                </div>

                <!-- Combo Items (shown when product_type = combo) -->
                <div x-show="productType === 'combo'" x-cloak>
                    <x-card title="Combo Items">
                        <p class="text-muted mb-4">Add products that are included in this combo</p>

                        <div class="space-y-3">
                            <template x-for="(item, index) in comboItems" :key="index">
                                <div class="flex items-start gap-3 p-4 bg-secondary-50 rounded-lg">
                                    <div class="flex-1 grid grid-cols-3 gap-3">
                                        <div class="col-span-2">
                                            <label class="block text-xs text-muted mb-1">Product</label>
                                            <select
                                                :name="`combo_items[${index}][product_id]`"
                                                x-model="item.product_id"
                                                @change="updateComboItemPrice(index)"
                                                class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            >
                                                <option value="">Select Product</option>
                                                @foreach($comboProducts ?? [] as $cp)
                                                    <option value="{{ $cp->id }}" data-price="{{ $cp->base_price }}">{{ $cp->name }} - Rp {{ number_format($cp->base_price, 0, ',', '.') }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-muted mb-1">Quantity</label>
                                            <input
                                                type="number"
                                                :name="`combo_items[${index}][quantity]`"
                                                x-model="item.quantity"
                                                @change="calculateComboTotal()"
                                                min="1"
                                                class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            >
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeComboItem(index)"
                                        class="p-2 text-danger hover:bg-danger/10 rounded-lg transition-colors mt-5"
                                        x-show="comboItems.length > 1"
                                    >
                                        <x-icon name="trash" class="w-5 h-5" />
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center justify-between mt-4">
                            <x-button type="button" variant="outline-secondary" size="sm" icon="plus" @click="addComboItem()">
                                Add Product
                            </x-button>
                            <div class="text-sm text-muted">
                                Items Total: <span class="font-medium text-text" x-text="'Rp ' + formatNumber(comboItemsTotal)"></span>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-accent/10 rounded-lg border border-accent/20">
                            <p class="text-sm text-muted">Set the <strong>Base Price</strong> above to define the combo selling price. The cost price will be calculated from the items.</p>
                        </div>
                    </x-card>
                </div>

                <!-- Modifier Groups -->
                <x-card title="Modifier Groups">
                    <p class="text-muted mb-4">Select modifier groups for this product (e.g., Toppings, Extra)</p>
                    <div class="space-y-2">
                        @foreach($modifierGroups as $group)
                            <label class="flex items-center gap-3 p-3 bg-secondary-50 rounded-lg cursor-pointer hover:bg-secondary-100 transition-colors">
                                <input
                                    type="checkbox"
                                    name="modifier_groups[]"
                                    value="{{ $group->id }}"
                                    {{ in_array($group->id, old('modifier_groups', $product->modifierGroups->pluck('id')->toArray())) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <div class="flex-1">
                                    <p class="font-medium">{{ $group->name }}</p>
                                    <p class="text-xs text-muted">
                                        {{ $group->modifiers->pluck('name')->implode(', ') }}
                                    </p>
                                </div>
                                <span class="text-xs text-muted">
                                    {{ $group->selection_type === 'single' ? 'Single select' : 'Multi select' }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @if($modifierGroups->isEmpty())
                        <x-empty-state
                            title="No modifier groups"
                            description="Create modifier groups first."
                            icon="plus-circle"
                            size="sm"
                        />
                    @endif
                </x-card>

                <!-- Inventory & Recipe Link -->
                <x-card title="Inventory & Recipe Link">
                    <div class="space-y-4" x-data="inventoryLink()">
                        <div class="p-3 bg-info-50 border border-info-200 text-info-700 rounded-lg text-sm">
                            <strong>Stock Deduction:</strong> When sold, stock is reduced from:
                            <ul class="list-disc list-inside mt-1">
                                <li>Recipe ingredients (if recipe linked)</li>
                                <li>Or direct inventory item (if item linked)</li>
                            </ul>
                        </div>

                        <x-form-group label="Linked Recipe" name="recipe_id">
                            <x-select name="recipe_id" x-model="selectedRecipeId">
                                <option value="">None - No stock deduction</option>
                                @foreach($recipes as $recipe)
                                    <option value="{{ $recipe->id }}">
                                        {{ $recipe->name }} (Cost: Rp {{ number_format($recipe->estimated_cost, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </x-select>
                            <p class="text-xs text-muted mt-1">Recipe ingredients will be deducted from stock on each sale.</p>
                        </x-form-group>

                        <!-- Recipe Details Preview -->
                        <template x-if="recipeDetails">
                            <div class="p-4 bg-secondary-50 border border-border rounded-lg">
                                <h4 class="font-medium text-text mb-2">Recipe Ingredients</h4>
                                <div class="space-y-1 max-h-40 overflow-y-auto">
                                    <template x-for="item in recipeDetails.items" :key="item.id">
                                        <div class="flex items-center justify-between text-sm py-1 border-b border-border last:border-0">
                                            <span x-text="item.inventory_item?.name || 'Unknown'"></span>
                                            <span class="text-muted" x-text="item.quantity + ' ' + (item.unit?.name || '')"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-3 pt-3 border-t border-border flex items-center justify-between">
                                    <span class="font-medium">Estimated Cost</span>
                                    <span class="font-bold text-primary" x-text="'Rp ' + formatNumber(recipeDetails.estimated_cost)"></span>
                                </div>
                            </div>
                        </template>

                        <div class="border-t border-border pt-4">
                            <x-form-group label="Direct Inventory Item (Alternative)" name="inventory_item_id">
                                <x-select name="inventory_item_id">
                                    <option value="">None</option>
                                    @foreach($inventoryItems as $item)
                                        <option value="{{ $item->id }}" @selected(old('inventory_item_id', $product->inventory_item_id) == $item->id)>
                                            {{ $item->name }} ({{ $item->sku }})
                                        </option>
                                    @endforeach
                                </x-select>
                                <p class="text-xs text-muted mt-1">Use this if product doesn't have a recipe (e.g., selling raw items directly).</p>
                            </x-form-group>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Product Image -->
                <x-card title="Product Image">
                    <div
                        x-data="{ preview: '{{ $product->image_url }}' }"
                        class="space-y-4"
                    >
                        <div class="aspect-square bg-secondary-100 rounded-lg flex items-center justify-center overflow-hidden">
                            <template x-if="preview">
                                <img :src="preview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!preview">
                                <x-icon name="photo" class="w-12 h-12 text-muted" />
                            </template>
                        </div>
                        <input
                            type="file"
                            name="image"
                            accept="image/*"
                            @change="preview = URL.createObjectURL($event.target.files[0])"
                            class="w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-accent file:text-white hover:file:bg-accent/90"
                        >
                    </div>
                </x-card>

                <!-- Settings -->
                <x-card title="Settings">
                    <div class="space-y-4">
                        <x-form-group>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">Active</span>
                            </label>
                        </x-form-group>

                        <x-form-group>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_featured" value="0">
                                <input
                                    type="checkbox"
                                    name="is_featured"
                                    value="1"
                                    {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">Featured</span>
                            </label>
                        </x-form-group>

                        <x-form-group>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="track_stock" value="0">
                                <input
                                    type="checkbox"
                                    name="track_stock"
                                    value="1"
                                    {{ old('track_stock', $product->track_stock) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">Track Stock</span>
                            </label>
                        </x-form-group>

                        <x-form-group label="Sort Order" name="sort_order">
                            <x-input
                                type="number"
                                name="sort_order"
                                :value="old('sort_order', $product->sort_order)"
                                min="0"
                            />
                        </x-form-group>
                    </div>
                </x-card>

                <!-- Metadata -->
                <x-card title="Information">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted">Created</dt>
                            <dd>{{ $product->created_at->format('d M Y H:i') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Updated</dt>
                            <dd>{{ $product->updated_at->format('d M Y H:i') }}</dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <x-button type="submit" icon="check" class="w-full">
                        Update Product
                    </x-button>
                    <x-button href="{{ route('menu.products.index') }}" variant="ghost" class="w-full">
                        Cancel
                    </x-button>
                </div>
            </div>
        </div>
    </form>

    <!-- Hidden regenerate form -->
    <form id="regenerate-form" action="{{ route('menu.products.generate-variants', $product) }}" method="POST" class="hidden">
        @csrf
    </form>

    @php
        $recipesData = $recipes->keyBy('id')->map(function($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'estimated_cost' => $r->estimated_cost,
                'items' => $r->items->map(function($i) {
                    return [
                        'id' => $i->id,
                        'quantity' => $i->quantity,
                        'inventory_item' => $i->inventoryItem ? ['name' => $i->inventoryItem->name] : null,
                        'unit' => $i->unit ? ['name' => $i->unit->name] : null,
                    ];
                }),
            ];
        });

        $comboProductsData = ($comboProducts ?? collect([]))->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'price' => $p->base_price];
        })->keyBy('id')->toArray();

        $existingComboItems = [];
        if ($product->combo && $product->combo->items) {
            $existingComboItems = $product->combo->items->map(function($i) {
                return ['product_id' => $i->product_id, 'quantity' => $i->quantity];
            })->toArray();
        }
        if (empty($existingComboItems)) {
            $existingComboItems = [['product_id' => '', 'quantity' => 1]];
        }
        $defaultComboItems = old('combo_items', $existingComboItems);
    @endphp

    @push('scripts')
    <script>
        function productForm() {
            return {
                productType: '{{ old('product_type', $product->product_type) }}',
                comboProducts: @json($comboProductsData),
                comboItems: @json($defaultComboItems),
                comboItemsTotal: 0,

                init() {
                    this.calculateComboTotal();
                },

                addComboItem() {
                    this.comboItems.push({
                        product_id: '',
                        quantity: 1
                    });
                },

                removeComboItem(index) {
                    this.comboItems.splice(index, 1);
                    this.calculateComboTotal();
                },

                updateComboItemPrice(index) {
                    this.calculateComboTotal();
                },

                calculateComboTotal() {
                    let total = 0;
                    this.comboItems.forEach(item => {
                        if (item.product_id && this.comboProducts[item.product_id]) {
                            total += this.comboProducts[item.product_id].price * (item.quantity || 1);
                        }
                    });
                    this.comboItemsTotal = total;
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                }
            }
        }

        function inventoryLink() {
            return {
                selectedRecipeId: '{{ old('recipe_id', $product->recipe_id) }}',
                recipeDetails: null,
                recipesData: @json($recipesData),

                init() {
                    this.loadRecipeDetails();
                    this.$watch('selectedRecipeId', () => this.loadRecipeDetails());
                },

                loadRecipeDetails() {
                    if (this.selectedRecipeId && this.recipesData[this.selectedRecipeId]) {
                        this.recipeDetails = this.recipesData[this.selectedRecipeId];
                    } else {
                        this.recipeDetails = null;
                    }
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
