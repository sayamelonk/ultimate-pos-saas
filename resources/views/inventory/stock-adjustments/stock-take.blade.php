<x-app-layout>
    <x-slot name="title">Stock Take - Ultimate POS</x-slot>

    @section('page-title', 'Stock Take')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Stock Take</h2>
                <p class="text-muted mt-1">Compare physical stock with system stock</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.stock-adjustments.store') }}" method="POST" x-data="stockTakeForm()" class="space-y-6">
        @csrf
        <input type="hidden" name="is_stock_take" value="1">

        <div class="max-w-5xl space-y-6">
            <x-card title="Stock Take Details">
                <div class="grid grid-cols-3 gap-4">
                    <x-select name="outlet_id" label="Outlet" x-model="selectedOutlet" @change="loadItems()" required>
                        <option value="">Select Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="category_id" label="Category (Optional)" x-model="selectedCategory" @change="loadItems()">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select>

                    <x-input
                        type="date"
                        name="adjustment_date"
                        label="Stock Take Date"
                        :value="date('Y-m-d')"
                        required
                    />
                </div>

                <x-textarea
                    name="reason"
                    label="Notes"
                    placeholder="Stock take notes..."
                    rows="2"
                    class="mt-4"
                />
            </x-card>

            <x-card title="Stock Count">
                <template x-if="!selectedOutlet">
                    <x-empty-state
                        title="Select an Outlet"
                        description="Please select an outlet to load items for stock take."
                        icon="building-storefront"
                    />
                </template>

                <template x-if="selectedOutlet && loading">
                    <div class="flex items-center justify-center py-12">
                        <x-icon name="arrow-path" class="w-8 h-8 text-muted animate-spin" />
                        <span class="ml-2 text-muted">Loading items...</span>
                    </div>
                </template>

                <template x-if="selectedOutlet && !loading && items.length === 0">
                    <x-empty-state
                        title="No Items Found"
                        description="No inventory items found for this outlet/category."
                        icon="cube"
                    />
                </template>

                <template x-if="selectedOutlet && !loading && items.length > 0">
                    <div>
                        <div class="mb-4 flex items-center gap-4">
                            <x-input
                                type="search"
                                x-model="searchQuery"
                                placeholder="Search items..."
                                class="flex-1"
                            />
                            <div class="text-sm text-muted">
                                <span x-text="filteredItems.length"></span> items
                            </div>
                        </div>

                        <x-table>
                            <x-slot name="head">
                                <x-th>Item</x-th>
                                <x-th>SKU</x-th>
                                <x-th align="right">System Qty</x-th>
                                <x-th align="right">Physical Qty</x-th>
                                <x-th align="right">Variance</x-th>
                            </x-slot>

                            <template x-for="(item, index) in filteredItems" :key="item.id">
                                <tr :class="{ 'bg-warning-50': item.variance !== 0 }">
                                    <x-td>
                                        <input type="hidden" :name="'items[' + index + '][inventory_item_id]'" :value="item.inventory_item_id">
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
                                            :name="'items[' + index + '][physical_qty]'"
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
                <x-card title="Summary">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-secondary-50 rounded-lg">
                            <p class="text-2xl font-bold text-text" x-text="items.length"></p>
                            <p class="text-sm text-muted">Total Items</p>
                        </div>
                        <div class="text-center p-4 bg-success-50 rounded-lg">
                            <p class="text-2xl font-bold text-success-600" x-text="itemsWithPositiveVariance"></p>
                            <p class="text-sm text-muted">Over Stock</p>
                        </div>
                        <div class="text-center p-4 bg-danger-50 rounded-lg">
                            <p class="text-2xl font-bold text-danger-600" x-text="itemsWithNegativeVariance"></p>
                            <p class="text-sm text-muted">Under Stock</p>
                        </div>
                        <div class="text-center p-4 bg-secondary-50 rounded-lg">
                            <p class="text-2xl font-bold text-text" x-text="itemsWithNoVariance"></p>
                            <p class="text-sm text-muted">Matched</p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="outline-secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit" :disabled="true" x-bind:disabled="!hasVariance">
                            Create Adjustments
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
                            outlet_id: this.selectedOutlet,
                            category_id: this.selectedCategory || ''
                        });

                        const response = await fetch(`{{ route('inventory.stocks.index') }}?${params}&format=json`);
                        const data = await response.json();

                        this.items = data.items.map(item => ({
                            id: item.id,
                            inventory_item_id: item.inventory_item_id,
                            name: item.inventory_item.name,
                            sku: item.inventory_item.sku,
                            unit: item.inventory_item.unit?.abbreviation || '',
                            system_qty: parseFloat(item.quantity) || 0,
                            physical_qty: parseFloat(item.quantity) || 0,
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
