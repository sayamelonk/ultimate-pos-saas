<x-app-layout>
    <x-slot name="title">Edit Stock Adjustment - Ultimate POS</x-slot>

    @section('page-title', 'Edit Adjustment')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stock-adjustments.show', $adjustment) }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Stock Adjustment</h2>
                <p class="text-muted mt-1">{{ $adjustment->adjustment_number }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.stock-adjustments.update', $adjustment) }}" method="POST" x-data="adjustmentForm()" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card title="Adjustment Details">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-text">Outlet</label>
                                <p class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg">{{ $adjustment->outlet->name }}</p>
                            </div>

                            <x-input
                                type="date"
                                name="adjustment_date"
                                label="Adjustment Date"
                                :value="old('adjustment_date', $adjustment->adjustment_date->format('Y-m-d'))"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-text">Adjustment Type</label>
                                <p class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg">
                                    @if($adjustment->type === 'addition')
                                        Addition (Increase Stock)
                                    @else
                                        Subtraction (Decrease Stock)
                                    @endif
                                </p>
                            </div>

                            <x-input
                                name="reference"
                                label="Reference"
                                placeholder="e.g., Stock Count #123"
                                :value="old('reference', $adjustment->reference)"
                            />
                        </div>

                        <x-textarea
                            name="reason"
                            label="Reason"
                            placeholder="Explain the reason for this adjustment..."
                            :value="old('reason', $adjustment->reason)"
                            rows="2"
                            required
                        />
                    </div>
                </x-card>

                <x-card title="Adjustment Items">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <input type="hidden" :name="'items[' + index + '][id]'" :value="item.id">
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
                            <div class="w-40">
                                <label class="text-sm font-medium text-text">Unit Cost (Rp)</label>
                                <input type="number" step="0.01" x-model="item.unit_cost" :name="'items[' + index + '][unit_cost]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0">
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
                            <dt class="text-muted">Adjustment #</dt>
                            <dd class="font-medium">{{ $adjustment->adjustment_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Type</dt>
                            <dd>
                                @if($adjustment->type === 'addition')
                                    <x-badge type="success">Addition</x-badge>
                                @else
                                    <x-badge type="danger">Subtraction</x-badge>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Items</dt>
                            <dd class="font-medium" x-text="items.length"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Total Quantity</dt>
                            <dd class="font-medium" x-text="formatNumber(totalQuantity)"></dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="font-bold">Total Value</dt>
                            <dd class="font-bold text-lg" x-text="'Rp ' + formatNumber(totalValue)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            Update Adjustment
                        </x-button>
                        <x-button href="{{ route('inventory.stock-adjustments.show', $adjustment) }}" variant="outline-secondary" class="w-full">
                            Cancel
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function adjustmentForm() {
            return {
                items: @json($adjustment->items->map(fn($item) => [
                    'id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'notes' => $item->notes,
                ])),
                get totalQuantity() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
                },
                get totalValue() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_cost) || 0), 0);
                },
                addItem() {
                    this.items.push({ id: null, inventory_item_id: '', quantity: 0, unit_cost: 0, notes: '' });
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
