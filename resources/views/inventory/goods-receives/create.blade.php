<x-app-layout>
    <x-slot name="title">New Goods Receive - Ultimate POS</x-slot>

    @section('page-title', 'New GR')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.goods-receives.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">New Goods Receive</h2>
                <p class="text-muted mt-1">Receive inventory from purchase orders</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.goods-receives.store') }}" method="POST" x-data="goodsReceiveForm()" class="space-y-6">
        @csrf

        <div class="max-w-4xl space-y-6">
            <x-card title="Purchase Order">
                <x-select name="purchase_order_id" label="Select Purchase Order" x-model="selectedPO" @change="loadPOItems()" required>
                    <option value="">Select a Purchase Order</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}" @selected($selectedPO?->id == $po->id)>
                            {{ $po->po_number }} - {{ $po->supplier->name }} ({{ $po->outlet->name }})
                        </option>
                    @endforeach
                </x-select>
            </x-card>

            <x-card title="Receive Details">
                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="date"
                        name="receive_date"
                        label="Receive Date"
                        :value="old('receive_date', date('Y-m-d'))"
                        required
                    />
                    <x-input
                        name="invoice_number"
                        label="Supplier Invoice Number"
                        placeholder="e.g., INV-2024-001"
                    />
                </div>

                <x-textarea
                    name="notes"
                    label="Notes"
                    placeholder="Additional notes..."
                    rows="2"
                    class="mt-4"
                />
            </x-card>

            @if($selectedPO)
                <x-card title="Items to Receive">
                    <x-table>
                        <x-slot name="head">
                            <x-th>Item</x-th>
                            <x-th align="right">Ordered</x-th>
                            <x-th align="right">Already Received</x-th>
                            <x-th align="right">Qty to Receive</x-th>
                            <x-th>Batch Number</x-th>
                            <x-th>Expiry Date</x-th>
                        </x-slot>

                        @foreach($selectedPO->items as $index => $item)
                            <tr>
                                <x-td>
                                    <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $item->id }}">
                                    <p class="font-medium">{{ $item->inventoryItem->name }}</p>
                                    <p class="text-xs text-muted">{{ $item->inventoryItem->sku }}</p>
                                </x-td>
                                <x-td align="right">{{ number_format($item->quantity, 2) }} {{ $item->inventoryItem->unit->abbreviation ?? '' }}</x-td>
                                <x-td align="right">{{ number_format($item->quantity_received ?? 0, 2) }}</x-td>
                                <x-td align="right">
                                    <input type="number" step="0.001" name="items[{{ $index }}][quantity_received]" value="{{ $item->quantity - ($item->quantity_received ?? 0) }}" min="0" max="{{ $item->quantity - ($item->quantity_received ?? 0) }}" class="w-24 px-2 py-1 border border-border rounded text-right" required>
                                </x-td>
                                <x-td>
                                    <input type="text" name="items[{{ $index }}][batch_number]" placeholder="Optional" class="w-28 px-2 py-1 border border-border rounded text-sm">
                                </x-td>
                                <x-td>
                                    <input type="date" name="items[{{ $index }}][expiry_date]" class="w-36 px-2 py-1 border border-border rounded text-sm">
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-card>
            @else
                <x-card>
                    <x-empty-state
                        title="Select a Purchase Order"
                        description="Please select a purchase order above to see items available for receiving."
                        icon="document-text"
                    />
                </x-card>
            @endif

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('inventory.goods-receives.index') }}" variant="outline-secondary">
                    Cancel
                </x-button>
                <x-button type="submit" :disabled="!$selectedPO">
                    Create Goods Receive
                </x-button>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function goodsReceiveForm() {
            return {
                selectedPO: '{{ $selectedPO?->id ?? '' }}',
                loadPOItems() {
                    if (this.selectedPO) {
                        window.location.href = '{{ route("inventory.goods-receives.create") }}?purchase_order_id=' + this.selectedPO;
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
