<x-app-layout>
    <x-slot name="title">{{ __('products.add_product') }} - Ultimate POS</x-slot>

    @section('page-title', __('products.add_product'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.products.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('products.add_product') }}</h2>
                <p class="text-muted mt-1">{{ __('products.create_new_product') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.products.store') }}" method="POST" enctype="multipart/form-data" x-data="productForm()" @submit="clearAutosave()">
        @csrf

        {{-- Autosave indicator --}}
        <div x-show="hasAutosave" x-cloak class="fixed bottom-4 right-4 z-50">
            <div class="bg-info/10 border border-info/20 text-info px-4 py-3 rounded-xl shadow-lg flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                <span class="text-sm font-medium">{{ __('products.draft_saved') }}</span>
                <button type="button" @click="discardAutosave()" class="text-xs underline hover:no-underline">
                    {{ __('products.discard_draft') }}
                </button>
            </div>
        </div>

        {{-- Autosave status --}}
        <div x-show="autosaveStatus" x-cloak class="fixed bottom-4 left-4 z-50 transition-opacity" :class="{ 'opacity-0': !autosaveStatus }">
            <div class="bg-surface border border-border px-3 py-2 rounded-lg shadow-sm text-sm text-muted flex items-center gap-2">
                <template x-if="autosaveStatus === 'saving'">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ __('products.saving') }}
                    </span>
                </template>
                <template x-if="autosaveStatus === 'saved'">
                    <span class="flex items-center gap-2 text-success">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('products.draft_saved_status') }}
                    </span>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <!-- Basic Information -->
                <x-card title="{{ __('products.basic_information') }}">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="{{ __('products.product_name') }}" name="name" required>
                                <x-input
                                    name="name"
                                    :value="old('name')"
                                    placeholder="{{ __('products.product_name_placeholder') }}"
                                    required
                                />
                            </x-form-group>

                            <x-form-group label="{{ __('products.sku') }}" name="sku" required>
                                <x-input
                                    name="sku"
                                    :value="old('sku')"
                                    placeholder="{{ __('products.sku_placeholder') }}"
                                    required
                                />
                            </x-form-group>
                        </div>

                        <x-form-group label="{{ __('products.category') }}" name="category_id">
                            <x-select name="category_id">
                                <option value="">{{ __('products.select_category') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                        {{ $category->full_path }}
                                    </option>
                                @endforeach
                            </x-select>
                        </x-form-group>

                        <x-form-group label="{{ __('products.description') }}" name="description">
                            <x-textarea
                                name="description"
                                :value="old('description')"
                                placeholder="{{ __('products.description_placeholder') }}"
                                rows="3"
                            />
                        </x-form-group>

                        <x-form-group label="{{ __('products.product_type') }}" name="product_type" required>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="product_type"
                                        value="single"
                                        x-model="productType"
                                        {{ old('product_type', 'single') === 'single' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <x-icon name="cube" class="w-6 h-6 mb-2 mx-auto text-muted peer-checked:text-accent" />
                                        <p class="text-center font-medium">{{ __('products.single') }}</p>
                                        <p class="text-xs text-center text-muted mt-1">{{ __('products.simple_product') }}</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="product_type"
                                        value="variant"
                                        x-model="productType"
                                        {{ old('product_type') === 'variant' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <x-icon name="squares-2x2" class="w-6 h-6 mb-2 mx-auto text-muted peer-checked:text-accent" />
                                        <p class="text-center font-medium">{{ __('products.variant') }}</p>
                                        <p class="text-xs text-center text-muted mt-1">{{ __('products.with_options') }}</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="product_type"
                                        value="combo"
                                        x-model="productType"
                                        {{ old('product_type') === 'combo' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <x-icon name="rectangle-stack" class="w-6 h-6 mb-2 mx-auto text-muted peer-checked:text-accent" />
                                        <p class="text-center font-medium">{{ __('products.combo') }}</p>
                                        <p class="text-xs text-center text-muted mt-1">{{ __('products.bundle_products') }}</p>
                                    </div>
                                </label>
                            </div>
                        </x-form-group>
                    </div>
                </x-card>

                <!-- Pricing -->
                <x-card title="{{ __('products.pricing') }}">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="{{ __('products.base_price') }}" name="base_price" required>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">Rp</span>
                                    <x-input
                                        type="number"
                                        name="base_price"
                                        :value="old('base_price', 0)"
                                        min="0"
                                        step="100"
                                        class="pl-10"
                                        required
                                    />
                                </div>
                            </x-form-group>

                            <x-form-group label="{{ __('products.cost_price') }}" name="cost_price">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">Rp</span>
                                    <x-input
                                        type="number"
                                        name="cost_price"
                                        :value="old('cost_price', 0)"
                                        min="0"
                                        step="100"
                                        class="pl-10"
                                    />
                                </div>
                            </x-form-group>
                        </div>

                        <x-form-group label="{{ __('products.tax_rate') }}" name="tax_rate">
                            <x-input
                                type="number"
                                name="tax_rate"
                                :value="old('tax_rate', 0)"
                                min="0"
                                max="100"
                                step="0.1"
                            />
                        </x-form-group>
                    </div>
                </x-card>

                <!-- Variant Groups (shown when product_type = variant) -->
                <div x-show="productType === 'variant'" x-cloak>
                    <x-card title="{{ __('products.variant_groups') }}">
                        <p class="text-muted mb-4">{{ __('products.variant_groups_desc') }}</p>
                        <div class="space-y-2">
                            @foreach($variantGroups as $group)
                                <label class="flex items-center gap-3 p-3 bg-secondary-50 rounded-lg cursor-pointer hover:bg-secondary-100 transition-colors">
                                    <input
                                        type="checkbox"
                                        name="variant_groups[]"
                                        value="{{ $group->id }}"
                                        {{ in_array($group->id, old('variant_groups', [])) ? 'checked' : '' }}
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
                                title="{{ __('products.no_variant_groups') }}"
                                description="{{ __('products.no_variant_groups_desc') }}"
                                icon="squares-2x2"
                                size="sm"
                            >
                                <x-button href="{{ route('menu.variant-groups.create') }}" variant="outline-secondary" size="sm" icon="plus">
                                    {{ __('products.create_variant_group') }}
                                </x-button>
                            </x-empty-state>
                        @endif
                    </x-card>
                </div>

                <!-- Combo Items (shown when product_type = combo) -->
                <div x-show="productType === 'combo'" x-cloak>
                    <x-card title="{{ __('products.combo_items') }}">
                        <p class="text-muted mb-4">{{ __('products.combo_items_desc') }}</p>

                        <div class="space-y-3">
                            <template x-for="(item, index) in comboItems" :key="index">
                                <div class="flex items-start gap-3 p-4 bg-secondary-50 rounded-lg">
                                    <div class="flex-1 grid grid-cols-3 gap-3">
                                        <div class="col-span-2">
                                            <label class="block text-xs text-muted mb-1">{{ __('products.product') }}</label>
                                            <select
                                                :name="`combo_items[${index}][product_id]`"
                                                x-model="item.product_id"
                                                @change="updateComboItemPrice(index)"
                                                class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            >
                                                <option value="">{{ __('products.select_product') }}</option>
                                                @foreach($comboProducts ?? $products ?? [] as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->base_price }}">{{ $product->name }} - Rp {{ number_format($product->base_price, 0, ',', '.') }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-muted mb-1">{{ __('products.quantity') }}</label>
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
                                {{ __('products.add_product_to_combo') }}
                            </x-button>
                            <div class="text-sm text-muted">
                                {{ __('products.items_total') }}: <span class="font-medium text-text" x-text="'Rp ' + formatNumber(comboItemsTotal)"></span>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-accent/10 rounded-lg border border-accent/20">
                            <p class="text-sm text-muted">{!! __('products.combo_price_hint') !!}</p>
                        </div>
                    </x-card>
                </div>

                <!-- Modifier Groups -->
                <x-card title="{{ __('products.modifier_groups') }}">
                    <p class="text-muted mb-4">{{ __('products.modifier_groups_desc') }}</p>
                    <div class="space-y-2">
                        @foreach($modifierGroups as $group)
                            <label class="flex items-center gap-3 p-3 bg-secondary-50 rounded-lg cursor-pointer hover:bg-secondary-100 transition-colors">
                                <input
                                    type="checkbox"
                                    name="modifier_groups[]"
                                    value="{{ $group->id }}"
                                    {{ in_array($group->id, old('modifier_groups', [])) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <div class="flex-1">
                                    <p class="font-medium">{{ $group->name }}</p>
                                    <p class="text-xs text-muted">
                                        {{ $group->modifiers->pluck('name')->implode(', ') }}
                                    </p>
                                </div>
                                <span class="text-xs text-muted">
                                    {{ $group->selection_type === 'single' ? __('products.single_select') : __('products.multi_select') }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @if($modifierGroups->isEmpty())
                        <x-empty-state
                            title="{{ __('products.no_modifier_groups') }}"
                            description="{{ __('products.no_modifier_groups_desc') }}"
                            icon="plus-circle"
                            size="sm"
                        >
                            <x-button href="{{ route('menu.modifier-groups.create') }}" variant="outline-secondary" size="sm" icon="plus">
                                {{ __('products.create_modifier_group') }}
                            </x-button>
                        </x-empty-state>
                    @endif
                </x-card>

                <!-- Inventory Link -->
                <x-card title="{{ __('products.inventory_link') }}">
                    <div class="space-y-4">
                        <x-form-group label="{{ __('products.linked_inventory_item') }}" name="inventory_item_id">
                            <x-select name="inventory_item_id">
                                <option value="">{{ __('products.none') }}</option>
                                @foreach($inventoryItems as $item)
                                    <option value="{{ $item->id }}" @selected(old('inventory_item_id') == $item->id)>
                                        {{ $item->name }} ({{ $item->sku }})
                                    </option>
                                @endforeach
                            </x-select>
                            <p class="text-xs text-muted mt-1">{{ __('products.link_to_track_stock') }}</p>
                        </x-form-group>

                        <x-form-group label="{{ __('products.linked_recipe') }}" name="recipe_id">
                            <x-select name="recipe_id">
                                <option value="">{{ __('products.none') }}</option>
                                @foreach($recipes as $recipe)
                                    <option value="{{ $recipe->id }}" @selected(old('recipe_id') == $recipe->id)>
                                        {{ $recipe->name }}
                                    </option>
                                @endforeach
                            </x-select>
                            <p class="text-xs text-muted mt-1">{{ __('products.link_to_calculate_cost') }}</p>
                        </x-form-group>
                    </div>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Product Image -->
                <x-card title="{{ __('products.product_image') }}">
                    <div
                        x-data="{ preview: null }"
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

                <!-- Outlet Availability -->
                @if($outlets->count() > 0)
                <x-card title="{{ __('products.outlet_availability') }}">
                    <p class="text-muted text-sm mb-4">{{ __('products.outlet_availability_desc') }}</p>
                    <div class="space-y-3" x-data="outletAvailability()">
                        @foreach($outlets as $index => $outlet)
                            <div class="p-3 bg-secondary-50 rounded-lg border border-border">
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        :checked="outlets[{{ $index }}].enabled"
                                        @change="outlets[{{ $index }}].enabled = $event.target.checked"
                                        class="rounded border-border text-accent focus:ring-accent"
                                    >
                                    <div class="flex-1">
                                        <p class="font-medium text-sm">{{ $outlet->name }}</p>
                                        <p class="text-xs text-muted">{{ $outlet->code }}</p>
                                    </div>
                                </div>
                                <template x-if="outlets[{{ $index }}].enabled">
                                    <div class="mt-3 pt-3 border-t border-border">
                                        <input type="hidden" name="outlets[{{ $index }}][outlet_id]" value="{{ $outlet->id }}">
                                        <input type="hidden" name="outlets[{{ $index }}][is_available]" value="1">
                                        <label class="block text-xs text-muted mb-1">{{ __('products.custom_price') }}</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">Rp</span>
                                            <input
                                                type="number"
                                                name="outlets[{{ $index }}][custom_price]"
                                                x-model="outlets[{{ $index }}].customPrice"
                                                placeholder="{{ __('products.use_base_price') }}"
                                                min="0"
                                                step="100"
                                                class="w-full pl-10 pr-3 py-2 text-sm border border-border rounded-lg focus:ring-1 focus:ring-accent focus:border-accent"
                                            >
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endforeach
                    </div>
                </x-card>
                @endif

                <!-- Settings -->
                <x-card title="{{ __('products.settings') }}">
                    <div class="space-y-4">
                        <x-form-group>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">{{ __('products.active') }}</span>
                            </label>
                            <p class="text-xs text-muted mt-1">{{ __('products.product_visible_pos') }}</p>
                        </x-form-group>

                        <x-form-group>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_featured" value="0">
                                <input
                                    type="checkbox"
                                    name="is_featured"
                                    value="1"
                                    {{ old('is_featured') ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">{{ __('products.is_featured') }}</span>
                            </label>
                            <p class="text-xs text-muted mt-1">{{ __('products.show_featured') }}</p>
                        </x-form-group>

                        <x-form-group>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="track_stock" value="0">
                                <input
                                    type="checkbox"
                                    name="track_stock"
                                    value="1"
                                    {{ old('track_stock') ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">{{ __('products.track_stock') }}</span>
                            </label>
                            <p class="text-xs text-muted mt-1">{{ __('products.enable_stock_tracking') }}</p>
                        </x-form-group>

                        <x-form-group label="{{ __('products.sort_order') }}" name="sort_order">
                            <x-input
                                type="number"
                                name="sort_order"
                                :value="old('sort_order', 0)"
                                min="0"
                            />
                        </x-form-group>
                    </div>
                </x-card>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <x-button type="submit" icon="check" class="w-full">
                        {{ __('products.create_product') }}
                    </x-button>
                    <x-button href="{{ route('menu.products.index') }}" variant="ghost" class="w-full">
                        {{ __('app.cancel') }}
                    </x-button>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    @php
        $comboProductsData = ($comboProducts ?? $products ?? collect([]))->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'price' => $p->base_price];
        })->keyBy('id')->toArray();

        $defaultComboItems = old('combo_items', [
            ['product_id' => '', 'quantity' => 1]
        ]);

        $outletsData = $outlets->map(function($outlet, $index) {
            $oldOutlets = old('outlets', []);
            $isEnabled = isset($oldOutlets[$index]);
            $customPrice = $oldOutlets[$index]['custom_price'] ?? null;
            return [
                'id' => $outlet->id,
                'enabled' => $isEnabled || count($oldOutlets) === 0,
                'customPrice' => $customPrice,
            ];
        })->values();
    @endphp
    <script>
        function productForm() {
            return {
                productType: '{{ old('product_type', 'single') }}',
                comboProducts: @json($comboProductsData),
                comboItems: @json($defaultComboItems),
                comboItemsTotal: 0,

                // Autosave properties
                autosaveKey: 'product_create_draft',
                autosaveTimeout: null,
                hasAutosave: false,
                autosaveStatus: null,
                autosaveDelay: 3000, // 3 seconds debounce

                init() {
                    this.calculateComboTotal();
                    this.loadAutosave();
                    this.setupAutosaveListeners();
                },

                // Autosave methods
                setupAutosaveListeners() {
                    const form = this.$el;
                    const inputs = form.querySelectorAll('input, select, textarea');

                    inputs.forEach(input => {
                        input.addEventListener('input', () => this.scheduleAutosave());
                        input.addEventListener('change', () => this.scheduleAutosave());
                    });
                },

                scheduleAutosave() {
                    if (this.autosaveTimeout) {
                        clearTimeout(this.autosaveTimeout);
                    }

                    this.autosaveTimeout = setTimeout(() => {
                        this.saveAutosave();
                    }, this.autosaveDelay);
                },

                saveAutosave() {
                    this.autosaveStatus = 'saving';

                    const form = this.$el;
                    const formData = new FormData(form);
                    const data = {};

                    for (let [key, value] of formData.entries()) {
                        // Skip CSRF token and file inputs
                        if (key === '_token' || key === 'image') continue;

                        // Handle array inputs (checkboxes, etc.)
                        if (key.endsWith('[]')) {
                            const baseKey = key.slice(0, -2);
                            if (!data[baseKey]) data[baseKey] = [];
                            data[baseKey].push(value);
                        } else {
                            data[key] = value;
                        }
                    }

                    // Add combo items from Alpine state
                    data.comboItems = this.comboItems;
                    data.productType = this.productType;
                    data.savedAt = new Date().toISOString();

                    try {
                        localStorage.setItem(this.autosaveKey, JSON.stringify(data));
                        this.hasAutosave = true;
                        this.autosaveStatus = 'saved';

                        // Hide status after 2 seconds
                        setTimeout(() => {
                            this.autosaveStatus = null;
                        }, 2000);
                    } catch (e) {
                        console.error('Autosave failed:', e);
                        this.autosaveStatus = null;
                    }
                },

                loadAutosave() {
                    try {
                        const saved = localStorage.getItem(this.autosaveKey);
                        if (!saved) return;

                        const data = JSON.parse(saved);

                        // Check if draft is older than 24 hours
                        const savedAt = new Date(data.savedAt);
                        const now = new Date();
                        const hoursDiff = (now - savedAt) / (1000 * 60 * 60);
                        if (hoursDiff > 24) {
                            this.clearAutosave();
                            return;
                        }

                        // Don't restore if there's validation error (old() values present)
                        @if(old('name'))
                        return;
                        @endif

                        this.hasAutosave = true;

                        // Restore form values
                        const form = this.$el;
                        Object.keys(data).forEach(key => {
                            if (key === 'savedAt' || key === 'comboItems' || key === 'productType') return;

                            const input = form.querySelector(`[name="${key}"]`);
                            if (!input) return;

                            if (input.type === 'checkbox') {
                                input.checked = data[key] === '1' || data[key] === true;
                            } else if (input.type === 'radio') {
                                const radios = form.querySelectorAll(`[name="${key}"]`);
                                radios.forEach(radio => {
                                    radio.checked = radio.value === data[key];
                                });
                            } else {
                                input.value = data[key];
                            }
                        });

                        // Restore Alpine state
                        if (data.productType) {
                            this.productType = data.productType;
                        }
                        if (data.comboItems && data.comboItems.length) {
                            this.comboItems = data.comboItems;
                            this.calculateComboTotal();
                        }
                    } catch (e) {
                        console.error('Failed to load autosave:', e);
                    }
                },

                clearAutosave() {
                    localStorage.removeItem(this.autosaveKey);
                    this.hasAutosave = false;
                },

                discardAutosave() {
                    this.clearAutosave();
                    location.reload();
                },

                // Existing methods
                addComboItem() {
                    this.comboItems.push({
                        product_id: '',
                        quantity: 1
                    });
                    this.scheduleAutosave();
                },

                removeComboItem(index) {
                    this.comboItems.splice(index, 1);
                    this.calculateComboTotal();
                    this.scheduleAutosave();
                },

                updateComboItemPrice(index) {
                    this.calculateComboTotal();
                    this.scheduleAutosave();
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

        function outletAvailability() {
            return {
                outlets: @json($outletsData)
            }
        }
    </script>
    @endpush
</x-app-layout>
