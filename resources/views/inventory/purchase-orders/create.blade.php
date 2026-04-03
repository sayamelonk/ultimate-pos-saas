<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_po') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_po'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.purchase-orders.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.add_po_title') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_new_po') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.purchase-orders.store') }}" method="POST" x-data="purchaseOrderForm()" class="space-y-6">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card :title="__('inventory.order_information')">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-select name="supplier_id" :label="__('inventory.supplier')" required>
                                <option value="">{{ __('inventory.select_supplier') }}</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </x-select>

                            <x-select name="outlet_id" :label="__('inventory.outlet')" required>
                                <option value="">{{ __('inventory.select_outlet') }}</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <x-input
                            type="date"
                            name="expected_date"
                            :label="__('inventory.expected_date')"
                            :value="old('expected_date')"
                        />

                        <x-textarea
                            name="notes"
                            :label="__('inventory.notes')"
                            :placeholder="__('inventory.notes')"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card :title="__('inventory.order_items')">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">{{ __('inventory.item') }}</label>
                                <select x-model="item.inventory_item_id" :name="'items[' + index + '][inventory_item_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">{{ __('inventory.select_item') }}</option>
                                    @foreach($items as $inventoryItem)
                                        <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }} ({{ $inventoryItem->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">{{ __('inventory.quantity') }}</label>
                                <input type="number" step="0.001" x-model="item.quantity" :name="'items[' + index + '][quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" required>
                            </div>
                            <div class="w-40">
                                <label class="text-sm font-medium text-text">{{ __('inventory.unit_price') }} (Rp)</label>
                                <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                                <input type="text"
                                       :value="formatNumber(item.unit_price)"
                                       @input="item.unit_price = parseFormattedNumber($event.target.value); $event.target.value = formatNumber(item.unit_price)"
                                       @blur="$event.target.value = formatNumber(item.unit_price)"
                                       class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent text-right"
                                       placeholder="0"
                                       required>
                            </div>
                            <div class="w-40">
                                <label class="text-sm font-medium text-text">{{ __('inventory.total') }}</label>
                                <div class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg font-medium" x-text="'Rp ' + formatNumber(item.quantity * item.unit_price)"></div>
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removeItem(index)" class="p-2 text-danger-600 hover:bg-danger-50 rounded-lg" x-show="items.length > 1">
                                    <x-icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <x-button type="button" @click="addItem()" variant="outline-secondary" icon="plus" class="mt-4">
                        {{ __('inventory.add_item') }}
                    </x-button>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card :title="__('inventory.po_summary')">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.items') }}</dt>
                            <dd class="font-medium" x-text="items.length"></dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="font-bold">{{ __('inventory.total') }}</dt>
                            <dd class="font-bold text-lg" x-text="'Rp ' + formatNumber(total)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            {{ __('inventory.create_po') }}
                        </x-button>
                        <x-button href="{{ route('inventory.purchase-orders.index') }}" variant="outline-secondary" class="w-full">
                            {{ __('inventory.cancel') }}
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function purchaseOrderForm() {
            return {
                items: [{ inventory_item_id: '', quantity: 0, unit_price: 0 }],
                get total() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0), 0);
                },
                addItem() {
                    this.items.push({ inventory_item_id: '', quantity: 0, unit_price: 0 });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                },
                parseFormattedNumber(str) {
                    // Remove thousand separators (dots for id-ID locale)
                    const cleaned = str.replace(/\./g, '').replace(/,/g, '.');
                    return parseFloat(cleaned) || 0;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
