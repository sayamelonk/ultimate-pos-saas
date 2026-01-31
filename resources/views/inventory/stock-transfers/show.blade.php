<x-app-layout>
    <x-slot name="title">{{ $transfer->transfer_number }} - Ultimate POS</x-slot>

    @section('page-title', 'Transfer Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.stock-transfers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $transfer->transfer_number }}</h2>
                    <p class="text-muted mt-1">{{ $transfer->fromOutlet->name }} → {{ $transfer->toOutlet->name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                @switch($transfer->status)
                    @case('draft')
                        <form action="{{ route('inventory.stock-transfers.approve', $transfer) }}" method="POST" class="inline"
                              onsubmit="return confirm('Approve this transfer?')">
                            @csrf
                            <x-button type="submit" variant="success" icon="check">
                                Approve
                            </x-button>
                        </form>
                        <x-button href="{{ route('inventory.stock-transfers.edit', $transfer) }}" variant="outline-secondary" icon="pencil">
                            Edit
                        </x-button>
                        <form action="{{ route('inventory.stock-transfers.cancel', $transfer) }}" method="POST" class="inline"
                              onsubmit="return confirm('Cancel this transfer?')">
                            @csrf
                            <x-button type="submit" variant="outline-danger" icon="x">
                                Cancel
                            </x-button>
                        </form>
                        @break
                    @case('approved')
                        <form action="{{ route('inventory.stock-transfers.ship', $transfer) }}" method="POST" class="inline"
                              onsubmit="return confirm('Mark this transfer as shipped? Stock will be deducted from source outlet.')">
                            @csrf
                            <x-button type="submit" icon="truck">
                                Mark as Shipped
                            </x-button>
                        </form>
                        @break
                    @case('in_transit')
                        <form action="{{ route('inventory.stock-transfers.receive', $transfer) }}" method="POST" class="inline"
                              onsubmit="return confirm('Mark this transfer as received? Stock will be added to destination outlet.')">
                            @csrf
                            <x-button type="submit" variant="success" icon="check-circle">
                                Mark as Received
                            </x-button>
                        </form>
                        @break
                @endswitch
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Status Banner -->
        <div class="p-4 rounded-lg @switch($transfer->status) @case('draft') bg-secondary-100 @break @case('approved') bg-info-100 @break @case('in_transit') bg-warning-100 @break @case('received') bg-success-100 @break @case('cancelled') bg-danger-100 @break @endswitch">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @switch($transfer->status)
                        @case('draft')
                            <x-icon name="document" class="w-6 h-6 text-secondary-600" />
                            <span class="font-medium text-secondary-700">Draft - Pending approval</span>
                            @break
                        @case('approved')
                            <x-icon name="check-circle" class="w-6 h-6 text-info-600" />
                            <span class="font-medium text-info-700">Approved - Ready to ship</span>
                            @break
                        @case('in_transit')
                            <x-icon name="truck" class="w-6 h-6 text-warning-600" />
                            <span class="font-medium text-warning-700">In Transit</span>
                            @break
                        @case('received')
                            <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                            <span class="font-medium text-success-700">Received - Transfer complete</span>
                            @break
                        @case('cancelled')
                            <x-icon name="x-circle" class="w-6 h-6 text-danger-600" />
                            <span class="font-medium text-danger-700">Cancelled</span>
                            @break
                    @endswitch
                </div>
            </div>
        </div>

        <!-- Transfer Progress -->
        @if($transfer->status !== 'cancelled')
            <x-card>
                <div class="flex items-center justify-between">
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ in_array($transfer->status, ['draft', 'approved', 'in_transit', 'received']) ? 'bg-success-500 text-white' : 'bg-secondary-200 text-muted' }}">
                            <x-icon name="document" class="w-5 h-5" />
                        </div>
                        <span class="text-sm mt-2 font-medium">Created</span>
                        <span class="text-xs text-muted">{{ $transfer->created_at->format('M d, H:i') }}</span>
                    </div>
                    <div class="flex-1 h-1 {{ in_array($transfer->status, ['approved', 'in_transit', 'received']) ? 'bg-success-500' : 'bg-secondary-200' }}"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ in_array($transfer->status, ['approved', 'in_transit', 'received']) ? 'bg-success-500 text-white' : 'bg-secondary-200 text-muted' }}">
                            <x-icon name="check" class="w-5 h-5" />
                        </div>
                        <span class="text-sm mt-2 font-medium">Approved</span>
                        <span class="text-xs text-muted">{{ $transfer->approved_at?->format('M d, H:i') ?? '-' }}</span>
                    </div>
                    <div class="flex-1 h-1 {{ in_array($transfer->status, ['in_transit', 'received']) ? 'bg-success-500' : 'bg-secondary-200' }}"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ in_array($transfer->status, ['in_transit', 'received']) ? 'bg-success-500 text-white' : 'bg-secondary-200 text-muted' }}">
                            <x-icon name="truck" class="w-5 h-5" />
                        </div>
                        <span class="text-sm mt-2 font-medium">Shipped</span>
                        <span class="text-xs text-muted">{{ $transfer->shipped_at?->format('M d, H:i') ?? '-' }}</span>
                    </div>
                    <div class="flex-1 h-1 {{ $transfer->status === 'received' ? 'bg-success-500' : 'bg-secondary-200' }}"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $transfer->status === 'received' ? 'bg-success-500 text-white' : 'bg-secondary-200 text-muted' }}">
                            <x-icon name="check-circle" class="w-5 h-5" />
                        </div>
                        <span class="text-sm mt-2 font-medium">Received</span>
                        <span class="text-xs text-muted">{{ $transfer->received_at?->format('M d, H:i') ?? '-' }}</span>
                    </div>
                </div>
            </x-card>
        @endif

        <div class="grid grid-cols-2 gap-6">
            <x-card title="Transfer Information">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">Transfer Number</dt>
                        <dd class="font-medium">{{ $transfer->transfer_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Transfer Date</dt>
                        <dd>{{ $transfer->transfer_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Expected Arrival</dt>
                        <dd>{{ $transfer->expected_date?->format('M d, Y') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Created By</dt>
                        <dd>{{ $transfer->createdBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Outlet Information">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">From Outlet</dt>
                        <dd class="font-medium">{{ $transfer->fromOutlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">To Outlet</dt>
                        <dd class="font-medium">{{ $transfer->toOutlet->name }}</dd>
                    </div>
                    @if($transfer->approvedBy)
                        <div class="flex justify-between">
                            <dt class="text-muted">Approved By</dt>
                            <dd>{{ $transfer->approvedBy->name }}</dd>
                        </div>
                    @endif
                    @if($transfer->receivedBy)
                        <div class="flex justify-between">
                            <dt class="text-muted">Received By</dt>
                            <dd>{{ $transfer->receivedBy->name }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>

        <x-card title="Transfer Items">
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th align="right">Quantity</x-th>
                    <x-th align="right">Received</x-th>
                    <x-th>Notes</x-th>
                </x-slot>

                @foreach($transfer->items as $item)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $item->inventoryItem->name }}</p>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">
                            {{ number_format($item->quantity, 2) }}
                            {{ $item->inventoryItem->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right">
                            @if($transfer->status === 'received')
                                <span class="{{ ($item->quantity_received ?? $item->quantity) < $item->quantity ? 'text-warning-600' : 'text-success-600' }} font-medium">
                                    {{ number_format($item->quantity_received ?? $item->quantity, 2) }}
                                </span>
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td>{{ $item->notes ?? '-' }}</x-td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        @if($transfer->notes)
            <x-card title="Notes">
                <p class="text-text">{{ $transfer->notes }}</p>
            </x-card>
        @endif
    </div>
</x-app-layout>
