<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_gr') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_gr'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.goods-receives.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.add_gr_title') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_new_gr') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.goods-receives.store') }}" method="POST" x-data="goodsReceiveForm()" class="space-y-6">
        @csrf

        <div class="max-w-4xl space-y-6">
            <x-card :title="__('inventory.purchase_order')">
                <x-select name="purchase_order_id" :label="__('inventory.select_po')" x-model="selectedPO" @change="loadPOItems()" required>
                    <option value="">{{ __('inventory.select_po') }}</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}" @selected($selectedPO?->id == $po->id)>
                            {{ $po->po_number }} - {{ $po->supplier->name }} ({{ $po->outlet->name }})
                        </option>
                    @endforeach
                </x-select>
            </x-card>

            <x-card :title="__('inventory.receive_information')">
                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="date"
                        name="receive_date"
                        :label="__('inventory.receive_date')"
                        :value="old('receive_date', date('Y-m-d'))"
                        required
                    />
                    <x-input
                        name="invoice_number"
                        :label="__('inventory.invoice_number')"
                        placeholder="e.g., INV-2024-001"
                    />
                </div>

                <x-textarea
                    name="notes"
                    :label="__('inventory.notes')"
                    :placeholder="__('inventory.optional_notes')"
                    rows="2"
                    class="mt-4"
                />
            </x-card>

            @if($selectedPO)
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
                            <x-th align="right">{{ __('inventory.qty_received') }}</x-th>
                            <x-th align="right">{{ __('inventory.receiving') }}</x-th>
                            <x-th>{{ __('inventory.batch_number') }}</x-th>
                        </x-slot>

                        @foreach($selectedPO->items as $index => $item)
                            @php
                                $trackBatches = $item->inventoryItem->track_batches ?? false;
                                $remaining = $item->quantity - ($item->quantity_received ?? 0);
                            @endphp
                            <tr class="{{ $trackBatches ? 'bg-primary/5' : '' }}">
                                <x-td>
                                    <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $item->id }}">
                                    <div class="flex items-center gap-2">
                                        @if($trackBatches)
                                            <span class="w-2 h-2 rounded-full bg-primary shrink-0" title="Batch Tracked"></span>
                                        @endif
                                        <div>
                                            <p class="font-medium">{{ $item->inventoryItem->name }}</p>
                                            <p class="text-xs text-muted">{{ $item->inventoryItem->sku }}</p>
                                        </div>
                                    </div>
                                </x-td>
                                <x-td align="right">
                                    {{ number_format($item->quantity, 2) }}
                                    <span class="text-muted text-sm">{{ $item->inventoryItem->unit->abbreviation ?? '' }}</span>
                                </x-td>
                                <x-td align="right">{{ number_format($item->quantity_received ?? 0, 2) }}</x-td>
                                <x-td align="right">
                                    <input
                                        type="number"
                                        step="0.001"
                                        name="items[{{ $index }}][quantity_received]"
                                        value="{{ $remaining }}"
                                        min="0"
                                        max="{{ $remaining }}"
                                        class="w-24 px-2 py-1 border border-border rounded text-right focus:ring-1 focus:ring-primary focus:border-primary"
                                        required
                                    >
                                </x-td>
                                <x-td>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="text"
                                            name="items[{{ $index }}][batch_number]"
                                            :placeholder="$trackBatches ? __('inventory.batch_number') : __('inventory.optional_notes')"
                                            class="w-28 px-2 py-1 border border-border rounded text-sm focus:ring-1 focus:ring-primary focus:border-primary {{ $trackBatches ? 'border-primary/50' : '' }}"
                                            {{ $trackBatches ? 'required' : '' }}
                                        >
                                        <input
                                            type="date"
                                            name="items[{{ $index }}][expiry_date]"
                                            class="w-36 px-2 py-1 border border-border rounded text-sm focus:ring-1 focus:ring-primary focus:border-primary {{ $trackBatches ? 'border-primary/50' : '' }}"
                                            :title="__('inventory.expiry_date')"
                                        >
                                    </div>
                                    @if($trackBatches && $item->inventoryItem->shelf_life_days)
                                        <p class="text-xs text-muted mt-1">
                                            {{ __('inventory.shelf_life_days') }}: {{ $item->inventoryItem->shelf_life_days }} {{ __('app.days') }}
                                        </p>
                                    @endif
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
            @else
                <x-card>
                    <x-empty-state
                        :title="__('inventory.select_po')"
                        :description="__('inventory.no_pending_po')"
                        icon="document-text"
                    />
                </x-card>
            @endif

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('inventory.goods-receives.index') }}" variant="outline-secondary">
                    {{ __('inventory.cancel') }}
                </x-button>
                <x-button type="submit" :disabled="!$selectedPO">
                    {{ __('inventory.create_gr') }}
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
