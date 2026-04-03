<x-app-layout>
    <x-slot name="title">Add Combo - Ultimate POS</x-slot>

    @section('page-title', 'Add Combo')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.combos.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Add Combo</h2>
                <p class="text-muted mt-1">Create a new combo meal offering</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.combos.store') }}" method="POST" x-data="comboForm()">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <!-- Basic Information -->
                <x-card title="Combo Information">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="Combo Name" name="name" required>
                                <x-input
                                    name="name"
                                    :value="old('name')"
                                    placeholder="e.g., Lunch Combo, Family Pack"
                                    required
                                    x-model="name"
                                    @input="generateSku()"
                                />
                            </x-form-group>

                            <x-form-group label="SKU" name="sku" required>
                                <x-input
                                    name="sku"
                                    :value="old('sku')"
                                    placeholder="e.g., CMB-001"
                                    required
                                    x-model="sku"
                                />
                            </x-form-group>
                        </div>

                        <x-form-group label="Barcode" name="barcode">
                            <x-input
                                name="barcode"
                                :value="old('barcode')"
                                placeholder="Optional barcode"
                            />
                        </x-form-group>

                        <x-form-group label="Description" name="description">
                            <x-textarea
                                name="description"
                                :value="old('description')"
                                placeholder="Describe what's included in this combo..."
                                rows="2"
                            />
                        </x-form-group>

                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="Category" name="category_id">
                                <x-select name="category_id">
                                    <option value="">No Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </x-select>
                            </x-form-group>

                            <x-form-group>
                                <label class="block text-sm font-medium text-text mb-2">&nbsp;</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="is_active" value="0">
                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            {{ old('is_active', true) ? 'checked' : '' }}
                                            class="rounded border-border text-accent focus:ring-accent"
                                        >
                                        <span class="text-sm font-medium text-text">Active</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="allow_substitutions" value="0">
                                        <input
                                            type="checkbox"
                                            name="allow_substitutions"
                                            value="1"
                                            {{ old('allow_substitutions') ? 'checked' : '' }}
                                            class="rounded border-border text-accent focus:ring-accent"
                                        >
                                        <span class="text-sm font-medium text-text">Allow Substitutions</span>
                                    </label>
                                </div>
                            </x-form-group>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_featured" value="0">
                                <input
                                    type="checkbox"
                                    name="is_featured"
                                    value="1"
                                    {{ old('is_featured') ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">Featured</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="show_in_pos" value="0">
                                <input
                                    type="checkbox"
                                    name="show_in_pos"
                                    value="1"
                                    {{ old('show_in_pos', true) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">Show in POS</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="show_in_menu" value="0">
                                <input
                                    type="checkbox"
                                    name="show_in_menu"
                                    value="1"
                                    {{ old('show_in_menu', true) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">Show in Menu</span>
                            </label>
                        </div>
                    </div>
                </x-card>

                <!-- Pricing -->
                <x-card title="Pricing">
                    <div class="space-y-4">
                        <x-form-group label="Combo Price" name="base_price" required>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">Rp</span>
                                <x-input
                                    type="number"
                                    name="base_price"
                                    :value="old('base_price', 0)"
                                    min="0"
                                    step="100"
                                    class="pl-10"
                                    x-model="basePrice"
                                />
                            </div>
                            <p class="text-xs text-muted mt-1">This is the selling price of the combo</p>
                        </x-form-group>

                        <x-form-group label="Pricing Type" name="pricing_type" required>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="pricing_type"
                                        value="fixed"
                                        x-model="pricingType"
                                        {{ old('pricing_type', 'fixed') === 'fixed' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <p class="font-medium">Fixed Price</p>
                                        <p class="text-xs text-muted mt-1">Use the combo price above</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="pricing_type"
                                        value="sum"
                                        x-model="pricingType"
                                        {{ old('pricing_type') === 'sum' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <p class="font-medium">Sum of Items</p>
                                        <p class="text-xs text-muted mt-1">Total of selected items</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="pricing_type"
                                        value="discount_percent"
                                        x-model="pricingType"
                                        {{ old('pricing_type') === 'discount_percent' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <p class="font-medium">Percentage Discount</p>
                                        <p class="text-xs text-muted mt-1">% off items total</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="pricing_type"
                                        value="discount_amount"
                                        x-model="pricingType"
                                        {{ old('pricing_type') === 'discount_amount' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <p class="font-medium">Fixed Discount</p>
                                        <p class="text-xs text-muted mt-1">Flat amount off items total</p>
                                    </div>
                                </label>
                            </div>
                        </x-form-group>

                        <div x-show="pricingType === 'discount_percent'" x-cloak>
                            <x-form-group label="Discount Percentage" name="discount_value">
                                <div class="relative">
                                    <x-input
                                        type="number"
                                        name="discount_value"
                                        :value="old('discount_value', 10)"
                                        min="0"
                                        max="100"
                                        step="0.1"
                                        class="pr-10"
                                        x-model="discountValue"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted">%</span>
                                </div>
                            </x-form-group>
                        </div>

                        <div x-show="pricingType === 'discount_amount'" x-cloak>
                            <x-form-group label="Discount Amount" name="discount_value">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">Rp</span>
                                    <x-input
                                        type="number"
                                        name="discount_value"
                                        :value="old('discount_value', 0)"
                                        min="0"
                                        step="100"
                                        class="pl-10"
                                        x-model="discountValue"
                                    />
                                </div>
                            </x-form-group>
                        </div>
                    </div>
                </x-card>

                <!-- Combo Items -->
                <x-card title="Combo Items">
                    <p class="text-muted mb-4">Add products or categories that are included in this combo</p>

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex items-start gap-3 p-4 bg-secondary-50 rounded-lg">
                                <div class="flex-1 grid grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-xs text-muted mb-1">Type</label>
                                        <select
                                            :name="`items[${index}][selection_type]`"
                                            x-model="item.selection_type"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                        >
                                            <option value="product">Specific Product</option>
                                            <option value="category">Any from Category</option>
                                        </select>
                                    </div>
                                    <div x-show="item.selection_type === 'product'">
                                        <label class="block text-xs text-muted mb-1">Product</label>
                                        <select
                                            :name="`items[${index}][product_id]`"
                                            x-model="item.product_id"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                        >
                                            <option value="">Select Product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div x-show="item.selection_type === 'category'">
                                        <label class="block text-xs text-muted mb-1">Category</label>
                                        <select
                                            :name="`items[${index}][category_id]`"
                                            x-model="item.category_id"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                        >
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-muted mb-1">Quantity</label>
                                        <input
                                            type="number"
                                            :name="`items[${index}][quantity]`"
                                            x-model="item.quantity"
                                            min="1"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs text-muted mb-1">Sort Order</label>
                                        <input
                                            type="number"
                                            :name="`items[${index}][sort_order]`"
                                            x-model="item.sort_order"
                                            min="0"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                        >
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-2 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                    x-show="items.length > 1"
                                >
                                    <x-icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </template>
                    </div>

                    <x-button type="button" variant="outline-secondary" size="sm" icon="plus" @click="addItem()" class="mt-4">
                        Add Item
                    </x-button>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Preview -->
                <x-card title="Preview">
                    <div class="space-y-4">
                        <div class="aspect-video bg-gradient-to-br from-accent/20 to-warning/20 rounded-lg flex items-center justify-center">
                            <x-icon name="rectangle-stack" class="w-16 h-16 text-accent" />
                        </div>

                        <div>
                            <p class="font-bold text-lg" x-text="name || 'Combo Name'"></p>
                            <p class="text-xs text-muted" x-text="sku || 'SKU'"></p>
                        </div>

                        <div class="border-t border-border pt-4">
                            <p class="text-sm text-muted mb-2">Includes:</p>
                            <ul class="space-y-1">
                                <template x-for="(item, index) in items" :key="index">
                                    <li class="flex items-center gap-2 text-sm">
                                        <x-icon name="check" class="w-4 h-4 text-success" />
                                        <span x-text="item.quantity + 'x ' + getItemName(item)"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div class="border-t border-border pt-4">
                            <div class="text-center">
                                <p class="text-sm text-muted">Combo Price</p>
                                <p class="text-2xl font-bold text-accent" x-text="'Rp ' + formatNumber(basePrice)"></p>
                            </div>
                            <div x-show="pricingType === 'discount_percent'" class="text-center mt-2">
                                <p class="text-xs text-success" x-text="discountValue + '% OFF from items total'"></p>
                            </div>
                            <div x-show="pricingType === 'discount_amount'" class="text-center mt-2">
                                <p class="text-xs text-success" x-text="'Save Rp ' + formatNumber(discountValue) + ' from items total'"></p>
                            </div>
                        </div>
                    </div>
                </x-card>

                <!-- Tips -->
                <x-card title="Tips">
                    <div class="space-y-3 text-sm text-muted">
                        <p><strong>Fixed Price:</strong> Set one price regardless of item choices</p>
                        <p><strong>Sum of Items:</strong> Total is sum of each item's price</p>
                        <p><strong>% Discount:</strong> Apply percentage off the total</p>
                        <p><strong>Fixed Discount:</strong> Subtract fixed amount from total</p>
                    </div>
                </x-card>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <x-button type="submit" icon="check" class="w-full">
                        Create Combo
                    </x-button>
                    <x-button href="{{ route('menu.combos.index') }}" variant="ghost" class="w-full">
                        Cancel
                    </x-button>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    @php
        $productsJson = $products->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'price' => $p->base_price];
        })->keyBy('id');
        $categoriesJson = $categories->map(function($c) {
            return ['id' => $c->id, 'name' => $c->name];
        })->keyBy('id');
        $defaultItems = old('items', [
            ['selection_type' => 'product', 'product_id' => '', 'category_id' => '', 'quantity' => 1, 'sort_order' => 0]
        ]);
    @endphp
    <script>
        function comboForm() {
            return {
                name: '{{ old('name', '') }}',
                sku: '{{ old('sku', '') }}',
                basePrice: {{ old('base_price', 0) }},
                pricingType: '{{ old('pricing_type', 'fixed') }}',
                discountValue: {{ old('discount_value', 10) }},
                products: @json($productsJson),
                categories: @json($categoriesJson),
                items: @json($defaultItems),

                generateSku() {
                    if (!this.sku || this.sku.startsWith('CMB-')) {
                        const slug = this.name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 6);
                        this.sku = 'CMB-' + slug;
                    }
                },

                getItemName(item) {
                    if (item.selection_type === 'product' && item.product_id) {
                        return this.products[item.product_id]?.name || 'Product';
                    } else if (item.selection_type === 'category' && item.category_id) {
                        return 'Any ' + (this.categories[item.category_id]?.name || 'Category');
                    }
                    return item.selection_type === 'product' ? 'Select Product' : 'Select Category';
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                },

                addItem() {
                    this.items.push({
                        selection_type: 'product',
                        product_id: '',
                        category_id: '',
                        quantity: 1,
                        sort_order: this.items.length
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
