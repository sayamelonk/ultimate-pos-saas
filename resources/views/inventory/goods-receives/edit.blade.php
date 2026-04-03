<x-app-layout>
    <x-slot name="title">{{ __('inventory.edit_gr') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.edit_gr'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.goods-receives.show', $goodsReceive) }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.edit_gr') }}</h2>
                <p class="text-muted mt-1">{{ $goodsReceive->gr_number }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.goods-receives.update', $goodsReceive) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="max-w-4xl space-y-6">
            <x-card :title="__('inventory.purchase_order')">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.po_number') }}</dt>
                        <dd class="mt-1 font-medium">{{ $goodsReceive->purchaseOrder->po_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.supplier') }}</dt>
                        <dd class="mt-1">{{ $goodsReceive->purchaseOrder->supplier->name }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card :title="__('inventory.receive_information')">
                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="date"
                        name="receive_date"
                        :label="__('inventory.receive_date')"
                        :value="old('receive_date', $goodsReceive->receive_date->format('Y-m-d'))"
                        required
                    />
                    <x-input
                        name="invoice_number"
                        :label="__('inventory.invoice_number')"
                        placeholder="e.g., INV-2024-001"
                        :value="old('invoice_number', $goodsReceive->invoice_number)"
                    />
                </div>

                <x-textarea
                    name="notes"
                    :label="__('inventory.notes')"
                    :placeholder="__('inventory.optional_notes')"
                    :value="old('notes', $goodsReceive->notes)"
                    rows="2"
                    class="mt-4"
                />
            </x-card>

            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg">{{ __('inventory.gr_items_title') }}</h3>
                    <div class="flex items-center gap-2 text-sm text-muted">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                            {{ __('inventory.batch_tracking') }}
                        </span>
                    </div>
                </div>

                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.item') }}</x-th>
                        <x-th align="right">{{ __('inventory.qty_ordered') }}</x-th>
                        <x-th align="right">{{ __('inventory.receiving') }}</x-th>
                        <x-th>{{ __('inventory.batch_number') }}</x-th>
                    </x-slot>

                    @foreach($goodsReceive->items as $index => $item)
                        @php
                            $trackBatches = $item->purchaseOrderItem->inventoryItem->track_batches ?? false;
                        @endphp
                        <tr class="{{ $trackBatches ? 'bg-primary/5' : '' }}">
                            <x-td>
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <div class="flex items-center gap-2">
                                    @if($trackBatches)
                                        <span class="w-2 h-2 rounded-full bg-primary shrink-0" title="Batch Tracked"></span>
                                    @endif
                                    <div>
                                        <p class="font-medium">{{ $item->purchaseOrderItem->inventoryItem->name }}</p>
                                        <p class="text-xs text-muted">{{ $item->purchaseOrderItem->inventoryItem->sku }}</p>
                                    </div>
                                </div>
                            </x-td>
                            <x-td align="right">
                                {{ number_format($item->purchaseOrderItem->quantity, 2) }}
                                <span class="text-muted text-sm">{{ $item->purchaseOrderItem->inventoryItem->unit->abbreviation ?? '' }}</span>
                            </x-td>
                            <x-td align="right">
                                <input
                                    type="number"
                                    step="0.001"
                                    name="items[{{ $index }}][quantity_received]"
                                    value="{{ old("items.{$index}.quantity_received", $item->quantity_received) }}"
                                    min="0"
                                    class="w-24 px-2 py-1 border border-border rounded text-right focus:ring-1 focus:ring-primary focus:border-primary"
                                    required
                                >
                            </x-td>
                            <x-td>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        name="items[{{ $index }}][batch_number]"
                                        value="{{ old("items.{$index}.batch_number", $item->batch_number) }}"
                                        :placeholder="$trackBatches ? __('inventory.batch_number') : __('inventory.optional_notes')"
                                        class="w-28 px-2 py-1 border border-border rounded text-sm focus:ring-1 focus:ring-primary focus:border-primary {{ $trackBatches ? 'border-primary/50' : '' }}"
                                        {{ $trackBatches ? 'required' : '' }}
                                    >
                                    <input
                                        type="date"
                                        name="items[{{ $index }}][expiry_date]"
                                        value="{{ old("items.{$index}.expiry_date", $item->expiry_date?->format('Y-m-d')) }}"
                                        class="w-36 px-2 py-1 border border-border rounded text-sm focus:ring-1 focus:ring-primary focus:border-primary {{ $trackBatches ? 'border-primary/50' : '' }}"
                                        :title="__('inventory.expiry_date')"
                                    >
                                </div>
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="mt-4 p-3 bg-info/10 border border-info/20 rounded-lg">
                    <p class="text-sm text-info-700">
                        <x-icon name="information-circle" class="w-4 h-4 inline mr-1" />
                        {{ __('inventory.batch_number_auto') }}
                    </p>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('inventory.goods-receives.show', $goodsReceive) }}" variant="outline-secondary">
                    {{ __('inventory.cancel') }}
                </x-button>
                <x-button type="submit">
                    {{ __('inventory.update_gr') }}
                </x-button>
            </div>
        </div>
    </form>
</x-app-layout>
