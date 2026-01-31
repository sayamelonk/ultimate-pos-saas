<x-app-layout>
    <x-slot name="title">{{ $item->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Item Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.items.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $item->name }}</h2>
                    <p class="text-muted mt-1">{{ $item->sku }}</p>
                </div>
            </div>
            <x-button href="{{ route('inventory.items.edit', $item) }}" variant="outline-secondary" icon="pencil">
                Edit
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <div class="grid grid-cols-3 gap-6">
            <x-card title="Basic Information" class="col-span-2">
                <dl class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm text-muted">Name</dt>
                        <dd class="mt-1 font-medium text-text">{{ $item->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">SKU</dt>
                        <dd class="mt-1">
                            <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $item->sku }}</code>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Category</dt>
                        <dd class="mt-1 text-text">{{ $item->category->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Unit</dt>
                        <dd class="mt-1 text-text">{{ $item->unit->name ?? '-' }} ({{ $item->unit->abbreviation ?? '' }})</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Type</dt>
                        <dd class="mt-1 text-text capitalize">{{ str_replace('_', ' ', $item->type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Cost Price</dt>
                        <dd class="mt-1 font-medium text-text">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Barcode</dt>
                        <dd class="mt-1 text-text">{{ $item->barcode ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($item->is_active)
                                <x-badge type="success" dot>Active</x-badge>
                            @else
                                <x-badge type="danger" dot>Inactive</x-badge>
                            @endif
                        </dd>
                    </div>
                    @if($item->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">Description</dt>
                            <dd class="mt-1 text-text">{{ $item->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-card title="Stock Settings">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-muted">Reorder Level</dt>
                        <dd class="mt-1 font-medium text-text">{{ $item->reorder_level ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Reorder Quantity</dt>
                        <dd class="mt-1 text-text">{{ $item->reorder_quantity ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Max Stock Level</dt>
                        <dd class="mt-1 text-text">{{ $item->max_stock_level ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Shelf Life</dt>
                        <dd class="mt-1 text-text">{{ $item->shelf_life_days ? $item->shelf_life_days . ' days' : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Storage Location</dt>
                        <dd class="mt-1 text-text">{{ $item->storage_location ?? '-' }}</dd>
                    </div>
                    <div class="pt-4 border-t border-border space-y-2">
                        <div class="flex items-center gap-2">
                            @if($item->is_perishable)
                                <x-badge type="warning" size="sm">Perishable</x-badge>
                            @endif
                            @if($item->track_batches)
                                <x-badge type="info" size="sm">Batch Tracked</x-badge>
                            @endif
                        </div>
                    </div>
                </dl>
            </x-card>
        </div>

        @if($item->stocks && $item->stocks->count() > 0)
            <x-card title="Stock by Outlet">
                <x-table>
                    <x-slot name="head">
                        <x-th>Outlet</x-th>
                        <x-th align="right">Quantity</x-th>
                        <x-th align="right">Reserved</x-th>
                        <x-th align="right">Available</x-th>
                        <x-th align="right">Avg Cost</x-th>
                        <x-th align="right">Total Value</x-th>
                    </x-slot>

                    @foreach($item->stocks as $stock)
                        <tr>
                            <x-td>{{ $stock->outlet->name }}</x-td>
                            <x-td align="right">{{ number_format($stock->quantity, 2) }} {{ $item->unit->abbreviation ?? '' }}</x-td>
                            <x-td align="right">{{ number_format($stock->reserved_quantity, 2) }}</x-td>
                            <x-td align="right">{{ number_format($stock->quantity - $stock->reserved_quantity, 2) }}</x-td>
                            <x-td align="right">Rp {{ number_format($stock->avg_cost, 0, ',', '.') }}</x-td>
                            <x-td align="right">Rp {{ number_format($stock->quantity * $stock->avg_cost, 0, ',', '.') }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($item->stockBatches && $item->stockBatches->count() > 0)
            <x-card title="Active Batches">
                <x-table>
                    <x-slot name="head">
                        <x-th>Batch Number</x-th>
                        <x-th>Outlet</x-th>
                        <x-th align="right">Remaining Qty</x-th>
                        <x-th>Expiry Date</x-th>
                        <x-th align="right">Cost Price</x-th>
                    </x-slot>

                    @foreach($item->stockBatches as $batch)
                        <tr>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $batch->batch_number }}</code>
                            </x-td>
                            <x-td>{{ $batch->outlet->name ?? '-' }}</x-td>
                            <x-td align="right">{{ number_format($batch->current_qty, 2) }}</x-td>
                            <x-td>
                                @if($batch->expiry_date)
                                    @if($batch->expiry_date->isPast())
                                        <span class="text-danger-600">{{ $batch->expiry_date->format('M d, Y') }} (Expired)</span>
                                    @elseif($batch->expiry_date->diffInDays(now()) <= 7)
                                        <span class="text-warning-600">{{ $batch->expiry_date->format('M d, Y') }}</span>
                                    @else
                                        {{ $batch->expiry_date->format('M d, Y') }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td align="right">Rp {{ number_format($batch->cost_price, 0, ',', '.') }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($item->supplierItems && $item->supplierItems->count() > 0)
            <x-card title="Suppliers">
                <x-table>
                    <x-slot name="head">
                        <x-th>Supplier</x-th>
                        <x-th>Supplier Code</x-th>
                        <x-th align="right">Last Price</x-th>
                        <x-th>Lead Time</x-th>
                        <x-th>Preferred</x-th>
                    </x-slot>

                    @foreach($item->supplierItems as $supplierItem)
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.suppliers.show', $supplierItem->supplier) }}" class="text-accent hover:underline">
                                    {{ $supplierItem->supplier->name }}
                                </a>
                            </x-td>
                            <x-td>{{ $supplierItem->supplier_item_code ?? '-' }}</x-td>
                            <x-td align="right">Rp {{ number_format($supplierItem->last_price, 0, ',', '.') }}</x-td>
                            <x-td>{{ $supplierItem->lead_time_days ? $supplierItem->lead_time_days . ' days' : '-' }}</x-td>
                            <x-td>
                                @if($supplierItem->is_preferred)
                                    <x-badge type="success" size="sm">Preferred</x-badge>
                                @else
                                    -
                                @endif
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    </div>
</x-app-layout>
