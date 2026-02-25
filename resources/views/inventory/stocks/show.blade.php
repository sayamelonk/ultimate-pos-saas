<x-app-layout>
    <x-slot name="title">Stock Details - Ultimate POS</x-slot>

    @section('page-title', 'Stock Details')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stocks.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ $stock->inventoryItem->name }}</h2>
                <p class="text-muted mt-1">{{ $stock->outlet->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-4 gap-4">
            <x-stat-card
                title="Total Quantity"
                :value="number_format($stock->quantity, 2) . ' ' . ($stock->inventoryItem->unit->abbreviation ?? '')"
                icon="cube"
            />
            <x-stat-card
                title="Reserved"
                :value="number_format($stock->reserved_quantity, 2)"
                icon="lock"
            />
            <x-stat-card
                title="Available"
                :value="number_format($stock->quantity - $stock->reserved_quantity, 2)"
                icon="check-circle"
            />
            <x-stat-card
                title="Total Value"
                :value="'Rp ' . number_format($stock->quantity * $stock->avg_cost, 0, ',', '.')"
                icon="currency-dollar"
            />
        </div>

        <x-card title="Stock Information">
            <dl class="grid grid-cols-3 gap-6">
                <div>
                    <dt class="text-sm text-muted">Item</dt>
                    <dd class="mt-1 font-medium text-text">{{ $stock->inventoryItem->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">SKU</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $stock->inventoryItem->sku }}</code>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Outlet</dt>
                    <dd class="mt-1 text-text">{{ $stock->outlet->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Average Cost</dt>
                    <dd class="mt-1 text-text">Rp {{ number_format($stock->avg_cost, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Reorder Level</dt>
                    <dd class="mt-1 text-text">{{ $stock->inventoryItem->reorder_point ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Last Updated</dt>
                    <dd class="mt-1 text-text">{{ $stock->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        @if($batches->count() > 0)
            <x-card title="Active Batches">
                <x-table>
                    <x-slot name="head">
                        <x-th>Batch Number</x-th>
                        <x-th align="right">Remaining Qty</x-th>
                        <x-th>Expiry Date</x-th>
                        <x-th align="right">Cost Price</x-th>
                        <x-th>Received Date</x-th>
                    </x-slot>

                    @foreach($batches as $batch)
                        <tr>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $batch->batch_number }}</code>
                            </x-td>
                            <x-td align="right">{{ number_format($batch->current_qty, 2) }}</x-td>
                            <x-td>
                                @if($batch->expiry_date)
                                    @if($batch->expiry_date->isPast())
                                        <x-badge type="danger">{{ $batch->expiry_date->format('M d, Y') }} - Expired</x-badge>
                                    @elseif($batch->expiry_date->diffInDays(now()) <= 7)
                                        <x-badge type="warning">{{ $batch->expiry_date->format('M d, Y') }}</x-badge>
                                    @else
                                        {{ $batch->expiry_date->format('M d, Y') }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td align="right">Rp {{ number_format($batch->cost_price, 0, ',', '.') }}</x-td>
                            <x-td>{{ $batch->created_at->format('M d, Y') }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($movements->count() > 0)
            <x-card title="Recent Movements">
                <x-table>
                    <x-slot name="head">
                        <x-th>Date</x-th>
                        <x-th>Type</x-th>
                        <x-th align="right">Quantity</x-th>
                        <x-th>Reference</x-th>
                        <x-th>User</x-th>
                        <x-th>Reason</x-th>
                    </x-slot>

                    @foreach($movements as $movement)
                        <tr>
                            <x-td>{{ $movement->created_at->format('M d, Y H:i') }}</x-td>
                            <x-td>
                                @switch($movement->type)
                                    @case('in')
                                        <x-badge type="success">IN</x-badge>
                                        @break
                                    @case('out')
                                        <x-badge type="danger">OUT</x-badge>
                                        @break
                                    @case('adjustment')
                                        <x-badge type="warning">ADJ</x-badge>
                                        @break
                                    @case('transfer_in')
                                        <x-badge type="info">TRANSFER IN</x-badge>
                                        @break
                                    @case('transfer_out')
                                        <x-badge type="warning">TRANSFER OUT</x-badge>
                                        @break
                                @endswitch
                            </x-td>
                            <x-td align="right">
                                @if(in_array($movement->type, ['in', 'transfer_in', 'adjustment']))
                                    <span class="text-success-600">+{{ number_format($movement->quantity, 2) }}</span>
                                @else
                                    <span class="text-danger-600">-{{ number_format($movement->quantity, 2) }}</span>
                                @endif
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $movement->reference_number ?? '-' }}</code>
                            </x-td>
                            <x-td>{{ $movement->createdBy->name ?? '-' }}</x-td>
                            <x-td class="max-w-[200px] truncate">{{ $movement->reason ?? '-' }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
                <div class="mt-4">
                    <x-button href="{{ route('inventory.stocks.movements', ['search' => $stock->inventoryItem->sku]) }}" variant="outline-secondary" size="sm">
                        View All Movements
                    </x-button>
                </div>
            </x-card>
        @endif
    </div>
</x-app-layout>
