<x-app-layout>
    <x-slot name="title">Edit Purchase Order - Ultimate POS</x-slot>

    @section('page-title', 'Edit PO')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.purchase-orders.show', $purchaseOrder) }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Purchase Order</h2>
                <p class="text-muted mt-1">{{ $purchaseOrder->po_number }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.purchase-orders.update', $purchaseOrder) }}" method="POST" x-data="purchaseOrderForm()" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card title="Order Details">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-select name="supplier_id" label="Supplier" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id) == $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </x-select>

                            <x-select name="outlet_id" label="Delivery Outlet" required>
                                <option value="">Select Outlet</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(old('outlet_id', $purchaseOrder->outlet_id) == $outlet->id)>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <x-input
                            type="date"
                            name="expected_date"
                            label="Expected Delivery Date"
                            :value="old('expected_date', $purchaseOrder->expected_date?->format('Y-m-d'))"
                        />

                        <x-textarea
                            name="notes"
                            label="Notes"
                            placeholder="Additional notes for this order..."
                            :value="old('notes', $purchaseOrder->notes)"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card title="Order Items">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">Item</label>
                                <select x-model="item.inventory_item_id" :name="'items[' + index + '][inventory_item_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">Select Item</option>
                                    @foreach($items as $inventoryItem)
                                        <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }} ({{ $inventoryItem->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">Quantity</label>
                                <input type="number" step="0.001" x-model="item.quantity" :name="'items[' + index + '][quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" required>
                            </div>
                            <div class="w-40">
                                <label class="text-sm font-medium text-text">Unit Price (Rp)</label>
                                <input type="number" step="0.01" x-model="item.unit_price" :name="'items[' + index + '][unit_price]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" required>
                            </div>
                            <div class="w-40">
                                <label class="text-sm font-medium text-text">Total</label>
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
                        Add Item
                    </x-button>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Order Summary">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-muted">PO Number</dt>
                            <dd class="font-medium">{{ $purchaseOrder->po_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Items</dt>
                            <dd class="font-medium" x-text="items.length"></dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="font-bold">Total</dt>
                            <dd class="font-bold text-lg" x-text="'Rp ' + formatNumber(total)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            Update Purchase Order
                        </x-button>
                        <x-button href="{{ route('inventory.purchase-orders.show', $purchaseOrder) }}" variant="outline-secondary" class="w-full">
                            Cancel
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
                items: @json($purchaseOrder->items->map(fn($item) => [
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ])),
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
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
