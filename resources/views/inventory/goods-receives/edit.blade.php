<x-app-layout>
    <x-slot name="title">Edit Goods Receive - Ultimate POS</x-slot>

    @section('page-title', 'Edit GR')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.goods-receives.show', $goodsReceive) }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Goods Receive</h2>
                <p class="text-muted mt-1">{{ $goodsReceive->gr_number }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.goods-receives.update', $goodsReceive) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="max-w-4xl space-y-6">
            <x-card title="Purchase Order">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">PO Number</dt>
                        <dd class="mt-1 font-medium">{{ $goodsReceive->purchaseOrder->po_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Supplier</dt>
                        <dd class="mt-1">{{ $goodsReceive->purchaseOrder->supplier->name }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Receive Details">
                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="date"
                        name="receive_date"
                        label="Receive Date"
                        :value="old('receive_date', $goodsReceive->receive_date->format('Y-m-d'))"
                        required
                    />
                    <x-input
                        name="invoice_number"
                        label="Supplier Invoice Number"
                        placeholder="e.g., INV-2024-001"
                        :value="old('invoice_number', $goodsReceive->invoice_number)"
                    />
                </div>

                <x-textarea
                    name="notes"
                    label="Notes"
                    placeholder="Additional notes..."
                    :value="old('notes', $goodsReceive->notes)"
                    rows="2"
                    class="mt-4"
                />
            </x-card>

            <x-card title="Items">
                <x-table>
                    <x-slot name="head">
                        <x-th>Item</x-th>
                        <x-th align="right">Ordered</x-th>
                        <x-th align="right">Qty to Receive</x-th>
                        <x-th>Batch Number</x-th>
                        <x-th>Expiry Date</x-th>
                    </x-slot>

                    @foreach($goodsReceive->items as $index => $item)
                        <tr>
                            <x-td>
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <p class="font-medium">{{ $item->purchaseOrderItem->inventoryItem->name }}</p>
                                <p class="text-xs text-muted">{{ $item->purchaseOrderItem->inventoryItem->sku }}</p>
                            </x-td>
                            <x-td align="right">{{ number_format($item->purchaseOrderItem->quantity, 2) }} {{ $item->purchaseOrderItem->inventoryItem->unit->abbreviation ?? '' }}</x-td>
                            <x-td align="right">
                                <input type="number" step="0.001" name="items[{{ $index }}][quantity_received]" value="{{ old("items.{$index}.quantity_received", $item->quantity_received) }}" min="0" class="w-24 px-2 py-1 border border-border rounded text-right" required>
                            </x-td>
                            <x-td>
                                <input type="text" name="items[{{ $index }}][batch_number]" value="{{ old("items.{$index}.batch_number", $item->batch_number) }}" placeholder="Optional" class="w-28 px-2 py-1 border border-border rounded text-sm">
                            </x-td>
                            <x-td>
                                <input type="date" name="items[{{ $index }}][expiry_date]" value="{{ old("items.{$index}.expiry_date", $item->expiry_date?->format('Y-m-d')) }}" class="w-36 px-2 py-1 border border-border rounded text-sm">
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('inventory.goods-receives.show', $goodsReceive) }}" variant="outline-secondary">
                    Cancel
                </x-button>
                <x-button type="submit">
                    Update Goods Receive
                </x-button>
            </div>
        </div>
    </form>
</x-app-layout>
