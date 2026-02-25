<x-app-layout>
    <x-slot name="title">{{ __('inventory.stock_opname') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.stock_opname'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.stock_opname') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.stock_take_description') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.stock-adjustments.store') }}" method="POST" x-data="stockTakeForm()" class="space-y-6">
        @csrf
        <input type="hidden" name="is_stock_take" value="1">
        <input type="hidden" name="type" value="stock_take">

        <div class="max-w-5xl space-y-6">
            <x-card :title="__('inventory.stock_take_details')">
                <div class="grid grid-cols-3 gap-4">
                    <x-select name="outlet_id" :label="__('admin.outlet')" x-model="selectedOutlet" @change="loadItems()" required>
                        <option value="">{{ __('inventory.select_outlet') }}</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="category_id" :label="__('inventory.category_optional')" x-model="selectedCategory" @change="loadItems()">
                        <option value="">{{ __('inventory.all_categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select>

                    <x-input
                        type="date"
                        name="adjustment_date"
                        :label="__('inventory.stock_take_date')"
                        :value="date('Y-m-d')"
                        required
                    />
                </div>

                <x-textarea
                    name="reason"
                    :label="__('app.notes')"
                    :placeholder="__('inventory.stock_take_notes_placeholder')"
                    rows="2"
                    class="mt-4"
                />
            </x-card>

            <x-card :title="__('inventory.stock_count')">
                <template x-if="!selectedOutlet">
                    <x-empty-state
                        :title="__('inventory.select_outlet_title')"
                        :description="__('inventory.select_outlet_description')"
                        icon="building-storefront"
                    />
                </template>

                <template x-if="selectedOutlet && loading">
                    <div class="flex items-center justify-center py-12">
                        <x-icon name="arrow-path" class="w-8 h-8 text-muted animate-spin" />
                        <span class="ml-2 text-muted">{{ __('app.loading') }}...</span>
                    </div>
                </template>

                <template x-if="selectedOutlet && !loading && items.length === 0">
                    <x-empty-state
                        :title="__('inventory.no_items_found')"
                        :description="__('inventory.no_items_for_outlet_category')"
                        icon="cube"
                    />
                </template>

                <template x-if="selectedOutlet && !loading && items.length > 0">
                    <div>
                        <div class="mb-4 flex items-center gap-4">
                            <x-input
                                type="search"
                                x-model="searchQuery"
                                :placeholder="__('inventory.search_items')"
                                class="flex-1"
                            />
                            <div class="text-sm text-muted">
                                <span x-text="filteredItems.length"></span> {{ __('inventory.items') }}
                            </div>
                        </div>

                        <x-table>
                            <x-slot name="head">
                                <x-th>{{ __('inventory.item') }}</x-th>
                                <x-th>{{ __('inventory.sku') }}</x-th>
                                <x-th align="right">{{ __('inventory.system_stock') }}</x-th>
                                <x-th align="right">{{ __('inventory.physical_stock') }}</x-th>
                                <x-th align="right">{{ __('inventory.difference') }}</x-th>
                            </x-slot>

                            <template x-for="(item, index) in filteredItems" :key="item.id">
                                <tr :class="{ 'bg-warning-50': item.variance !== 0 }">
                                    <x-td>
                                        <input type="hidden" :name="'items[' + index + '][inventory_item_id]'" :value="item.inventory_item_id">
                                        <input type="hidden" :name="'items[' + index + '][system_quantity]'" :value="item.system_qty">
                                        <p class="font-medium" x-text="item.name"></p>
                                    </x-td>
                                    <x-td>
                                        <code class="px-2 py-1 bg-secondary-100 rounded text-xs" x-text="item.sku"></code>
                                    </x-td>
                                    <x-td align="right">
                                        <span x-text="formatNumber(item.system_qty)"></span>
                                        <span class="text-muted text-xs" x-text="item.unit"></span>
                                    </x-td>
                                    <x-td align="right">
                                        <input
                                            type="number"
                                            step="0.001"
                                            :name="'items[' + index + '][actual_quantity]'"
                                            x-model="item.physical_qty"
                                            @input="calculateVariance(item)"
                                            class="w-28 px-2 py-1 border border-border rounded text-right"
                                            min="0"
                                        >
                                    </x-td>
                                    <x-td align="right">
                                        <span
                                            :class="{
                                                'text-success-600': item.variance > 0,
                                                'text-danger-600': item.variance < 0,
                                                'text-muted': item.variance === 0
                                            }"
                                            class="font-medium"
                                            x-text="(item.variance > 0 ? '+' : '') + formatNumber(item.variance)"
                                        ></span>
                                    </x-td>
                                </tr>
                            </template>
                        </x-table>
                    </div>
                </template>
            </x-card>

            <template x-if="selectedOutlet && !loading && items.length > 0">
                <x-card :title="__('inventory.summary')">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-secondary-50 rounded-lg">
                            <p class="text-2xl font-bold text-text" x-text="items.length"></p>
                            <p class="text-sm text-muted">{{ __('inventory.total_items') }}</p>
                        </div>
                        <div class="text-center p-4 bg-success-50 rounded-lg">
                            <p class="text-2xl font-bold text-success-600" x-text="itemsWithPositiveVariance"></p>
                            <p class="text-sm text-muted">{{ __('inventory.over_stock') }}</p>
                        </div>
                        <div class="text-center p-4 bg-danger-50 rounded-lg">
                            <p class="text-2xl font-bold text-danger-600" x-text="itemsWithNegativeVariance"></p>
                            <p class="text-sm text-muted">{{ __('inventory.under_stock') }}</p>
                        </div>
                        <div class="text-center p-4 bg-secondary-50 rounded-lg">
                            <p class="text-2xl font-bold text-text" x-text="itemsWithNoVariance"></p>
                            <p class="text-sm text-muted">{{ __('inventory.matched') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="outline-secondary">
                            {{ __('app.cancel') }}
                        </x-button>
                        <x-button type="submit" :disabled="true" x-bind:disabled="!hasVariance">
                            {{ __('inventory.create_adjustments') }}
                        </x-button>
                    </div>
                </x-card>
            </template>
        </div>
    </form>

    @push('scripts')
    <script>
        function stockTakeForm() {
            return {
                selectedOutlet: '',
                selectedCategory: '',
                searchQuery: '',
                loading: false,
                items: [],

                async loadItems() {
                    if (!this.selectedOutlet) {
                        this.items = [];
                        return;
                    }

                    this.loading = true;

                    try {
                        const params = new URLSearchParams({
                            outlet_id: this.selectedOutlet
                        });

                        if (this.selectedCategory) {
                            params.append('category_id', this.selectedCategory);
                        }

                        const response = await fetch(`{{ route('inventory.stock-adjustments.stock-for-outlet') }}?${params}`);
                        const data = await response.json();

                        this.items = data.map((item, index) => ({
                            id: index,
                            inventory_item_id: item.inventory_item_id,
                            name: item.name,
                            sku: item.sku,
                            unit: item.unit || '',
                            system_qty: parseFloat(item.system_quantity) || 0,
                            physical_qty: parseFloat(item.system_quantity) || 0,
                            variance: 0
                        }));
                    } catch (error) {
                        console.error('Failed to load items:', error);
                        this.items = [];
                    } finally {
                        this.loading = false;
                    }
                },

                get filteredItems() {
                    if (!this.searchQuery) return this.items;
                    const query = this.searchQuery.toLowerCase();
                    return this.items.filter(item =>
                        item.name.toLowerCase().includes(query) ||
                        item.sku.toLowerCase().includes(query)
                    );
                },

                calculateVariance(item) {
                    item.variance = (parseFloat(item.physical_qty) || 0) - item.system_qty;
                },

                get itemsWithPositiveVariance() {
                    return this.items.filter(i => i.variance > 0).length;
                },

                get itemsWithNegativeVariance() {
                    return this.items.filter(i => i.variance < 0).length;
                },

                get itemsWithNoVariance() {
                    return this.items.filter(i => i.variance === 0).length;
                },

                get hasVariance() {
                    return this.items.some(i => i.variance !== 0);
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
