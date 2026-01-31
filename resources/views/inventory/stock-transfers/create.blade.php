<x-app-layout>
    <x-slot name="title">New Stock Transfer - Ultimate POS</x-slot>

    @section('page-title', 'New Transfer')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stock-transfers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">New Stock Transfer</h2>
                <p class="text-muted mt-1">Transfer stock between outlets</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.stock-transfers.store') }}" method="POST" x-data="transferForm()" class="space-y-6">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card title="Transfer Details">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-select name="from_outlet_id" label="From Outlet" x-model="fromOutlet" required>
                                <option value="">Select Source Outlet</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(old('from_outlet_id') == $outlet->id)>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </x-select>

                            <x-select name="to_outlet_id" label="To Outlet" x-model="toOutlet" required>
                                <option value="">Select Destination Outlet</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(old('to_outlet_id') == $outlet->id) x-bind:disabled="fromOutlet == '{{ $outlet->id }}'">
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-input
                                type="date"
                                name="transfer_date"
                                label="Transfer Date"
                                :value="old('transfer_date', date('Y-m-d'))"
                                required
                            />

                            <x-input
                                type="date"
                                name="expected_date"
                                label="Expected Arrival Date"
                                :value="old('expected_date')"
                            />
                        </div>

                        <x-textarea
                            name="notes"
                            label="Notes"
                            placeholder="Additional notes for this transfer..."
                            :value="old('notes')"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card title="Transfer Items">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">Item</label>
                                <select x-model="item.inventory_item_id" :name="'items[' + index + '][inventory_item_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">Select Item</option>
                                    @foreach($inventoryItems as $inventoryItem)
                                        <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }} ({{ $inventoryItem->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">Quantity</label>
                                <input type="number" step="0.001" x-model="item.quantity" :name="'items[' + index + '][quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0.001" required>
                            </div>
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">Notes</label>
                                <input type="text" x-model="item.notes" :name="'items[' + index + '][notes]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="Optional notes">
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
                <x-card title="Summary">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-muted">From</dt>
                            <dd class="font-medium" x-text="fromOutlet ? outlets[fromOutlet] : '-'"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">To</dt>
                            <dd class="font-medium" x-text="toOutlet ? outlets[toOutlet] : '-'"></dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="font-bold">Total Items</dt>
                            <dd class="font-bold text-lg" x-text="items.length"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Total Quantity</dt>
                            <dd class="font-medium" x-text="formatNumber(totalQuantity)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            Create Transfer
                        </x-button>
                        <x-button href="{{ route('inventory.stock-transfers.index') }}" variant="outline-secondary" class="w-full">
                            Cancel
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function transferForm() {
            return {
                fromOutlet: '{{ old('from_outlet_id', '') }}',
                toOutlet: '{{ old('to_outlet_id', '') }}',
                outlets: @json($outlets->pluck('name', 'id')),
                items: [{ inventory_item_id: '', quantity: 0, notes: '' }],
                get totalQuantity() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
                },
                addItem() {
                    this.items.push({ inventory_item_id: '', quantity: 0, notes: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
