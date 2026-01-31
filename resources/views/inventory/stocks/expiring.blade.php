<x-app-layout>
    <x-slot name="title">Expiring Items - Ultimate POS</x-slot>

    @section('page-title', 'Expiring Items')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stocks.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Expiring Items</h2>
                <p class="text-muted mt-1">Items expiring within 30 days</p>
            </div>
        </div>
    </x-slot>

    <x-card>
        @if($expiringBatches->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>Batch</x-th>
                    <x-th>Outlet</x-th>
                    <x-th align="right">Remaining Qty</x-th>
                    <x-th>Expiry Date</x-th>
                    <x-th align="right">Value</x-th>
                </x-slot>

                @foreach($expiringBatches as $batch)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 {{ $batch->expiry_date->isPast() ? 'bg-danger-100' : 'bg-warning-100' }} rounded-lg flex items-center justify-center">
                                    <x-icon name="clock" class="w-5 h-5 {{ $batch->expiry_date->isPast() ? 'text-danger-600' : 'text-warning-600' }}" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $batch->inventoryItem->name }}</p>
                                    <p class="text-xs text-muted">{{ $batch->inventoryItem->sku }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $batch->batch_number }}</code>
                        </x-td>
                        <x-td>{{ $batch->outlet->name }}</x-td>
                        <x-td align="right">{{ number_format($batch->current_qty, 2) }} {{ $batch->inventoryItem->unit->abbreviation ?? '' }}</x-td>
                        <x-td>
                            @if($batch->expiry_date->isPast())
                                <x-badge type="danger">{{ $batch->expiry_date->format('M d, Y') }} - Expired</x-badge>
                            @elseif($batch->expiry_date->diffInDays(now()) <= 7)
                                <x-badge type="danger">{{ $batch->expiry_date->format('M d, Y') }} ({{ $batch->expiry_date->diffInDays(now()) }} days)</x-badge>
                            @else
                                <x-badge type="warning">{{ $batch->expiry_date->format('M d, Y') }} ({{ $batch->expiry_date->diffInDays(now()) }} days)</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">Rp {{ number_format($batch->current_qty * $batch->cost_price, 0, ',', '.') }}</x-td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <x-empty-state
                title="No expiring items"
                description="No batches are expiring within the next 30 days."
                icon="check-circle"
            />
        @endif
    </x-card>
</x-app-layout>
