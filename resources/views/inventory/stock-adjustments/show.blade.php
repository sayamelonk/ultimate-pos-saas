<x-app-layout>
    <x-slot name="title">{{ $adjustment->adjustment_number }} - Ultimate POS</x-slot>

    @section('page-title', 'Adjustment Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $adjustment->adjustment_number }}</h2>
                    <p class="text-muted mt-1">{{ $adjustment->type === 'addition' ? 'Stock Addition' : 'Stock Subtraction' }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                @if($adjustment->status === 'pending')
                    <form action="{{ route('inventory.stock-adjustments.approve', $adjustment) }}" method="POST" class="inline"
                          onsubmit="return confirm('Approve this adjustment? This will update the stock levels.')">
                        @csrf
                        <x-button type="submit" variant="success" icon="check">
                            Approve
                        </x-button>
                    </form>
                    <form action="{{ route('inventory.stock-adjustments.reject', $adjustment) }}" method="POST" class="inline"
                          onsubmit="return confirm('Reject this adjustment?')">
                        @csrf
                        <x-button type="submit" variant="outline-danger" icon="x">
                            Reject
                        </x-button>
                    </form>
                    <x-button href="{{ route('inventory.stock-adjustments.edit', $adjustment) }}" variant="outline-secondary" icon="pencil">
                        Edit
                    </x-button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Status Banner -->
        <div class="p-4 rounded-lg @switch($adjustment->status) @case('pending') bg-warning-100 @break @case('approved') bg-success-100 @break @case('rejected') bg-danger-100 @break @endswitch">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @switch($adjustment->status)
                        @case('pending')
                            <x-icon name="clock" class="w-6 h-6 text-warning-600" />
                            <span class="font-medium text-warning-700">Pending Approval</span>
                            @break
                        @case('approved')
                            <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                            <span class="font-medium text-success-700">Approved - Stock updated</span>
                            @break
                        @case('rejected')
                            <x-icon name="x-circle" class="w-6 h-6 text-danger-600" />
                            <span class="font-medium text-danger-700">Rejected</span>
                            @break
                    @endswitch
                </div>
                @if($adjustment->approvedBy)
                    <span class="text-sm text-muted">
                        {{ $adjustment->status === 'approved' ? 'Approved' : 'Rejected' }} by {{ $adjustment->approvedBy->name }}
                        on {{ $adjustment->approved_at?->format('M d, Y H:i') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <x-card title="Adjustment Information">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">Adjustment Number</dt>
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
                        <dt class="text-muted">Adjustment Date</dt>
                        <dd>{{ $adjustment->adjustment_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Reference</dt>
                        <dd>{{ $adjustment->reference ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Outlet</dt>
                        <dd>{{ $adjustment->outlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Created By</dt>
                        <dd>{{ $adjustment->createdBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Summary">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">Total Items</dt>
                        <dd class="font-medium">{{ $adjustment->items->count() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Total Quantity</dt>
                        <dd class="font-medium">{{ number_format($adjustment->items->sum('quantity'), 2) }}</dd>
                    </div>
                    <div class="flex justify-between pt-4 border-t border-border">
                        <dt class="font-bold">Total Value</dt>
                        <dd class="font-bold text-lg">Rp {{ number_format($adjustment->items->sum(fn($i) => $i->quantity * $i->unit_cost), 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        @if($adjustment->reason)
            <x-card title="Reason">
                <p class="text-text">{{ $adjustment->reason }}</p>
            </x-card>
        @endif

        <x-card title="Adjustment Items">
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th align="right">Quantity</x-th>
                    <x-th align="right">Unit Cost</x-th>
                    <x-th align="right">Total</x-th>
                    <x-th>Notes</x-th>
                </x-slot>

                @foreach($adjustment->items as $item)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $item->inventoryItem->name }}</p>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">
                            <span class="{{ $adjustment->type === 'addition' ? 'text-success-600' : 'text-danger-600' }} font-medium">
                                {{ $adjustment->type === 'addition' ? '+' : '-' }}{{ number_format($item->quantity, 2) }}
                            </span>
                            {{ $item->inventoryItem->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</x-td>
                        <x-td align="right">Rp {{ number_format($item->quantity * $item->unit_cost, 0, ',', '.') }}</x-td>
                        <x-td>{{ $item->notes ?? '-' }}</x-td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    </div>
</x-app-layout>
