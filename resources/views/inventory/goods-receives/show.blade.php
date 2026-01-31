<x-app-layout>
    <x-slot name="title">{{ $goodsReceive->gr_number }} - Ultimate POS</x-slot>

    @section('page-title', 'Goods Receive Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.goods-receives.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $goodsReceive->gr_number }}</h2>
                    <p class="text-muted mt-1">{{ $goodsReceive->purchaseOrder->po_number }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                @if($goodsReceive->status === 'draft')
                    <form action="{{ route('inventory.goods-receives.complete', $goodsReceive) }}" method="POST" class="inline"
                          onsubmit="return confirm('Complete this GR? This will add items to stock.')">
                        @csrf
                        <x-button type="submit" variant="success" icon="check">
                            Complete GR
                        </x-button>
                    </form>
                    <x-button href="{{ route('inventory.goods-receives.edit', $goodsReceive) }}" variant="outline-secondary" icon="pencil">
                        Edit
                    </x-button>
                    <form action="{{ route('inventory.goods-receives.cancel', $goodsReceive) }}" method="POST" class="inline"
                          onsubmit="return confirm('Cancel this GR?')">
                        @csrf
                        <x-button type="submit" variant="outline-danger" icon="x">
                            Cancel
                        </x-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Status Banner -->
        <div class="p-4 rounded-lg @switch($goodsReceive->status) @case('draft') bg-secondary-100 @break @case('completed') bg-success-100 @break @case('cancelled') bg-danger-100 @break @endswitch">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @switch($goodsReceive->status)
                        @case('draft')
                            <x-icon name="document" class="w-6 h-6 text-secondary-600" />
                            <span class="font-medium text-secondary-700">Draft - Pending completion</span>
                            @break
                        @case('completed')
                            <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                            <span class="font-medium text-success-700">Completed - Stock updated</span>
                            @break
                        @case('cancelled')
                            <x-icon name="x-circle" class="w-6 h-6 text-danger-600" />
                            <span class="font-medium text-danger-700">Cancelled</span>
                            @break
                    @endswitch
                </div>
                @if($goodsReceive->completedBy)
                    <span class="text-sm text-muted">Completed by {{ $goodsReceive->completedBy->name }} on {{ $goodsReceive->completed_at?->format('M d, Y H:i') }}</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <x-card title="Receive Information">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">GR Number</dt>
                        <dd class="font-medium">{{ $goodsReceive->gr_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">PO Number</dt>
                        <dd>
                            <a href="{{ route('inventory.purchase-orders.show', $goodsReceive->purchaseOrder) }}" class="text-accent hover:underline">
                                {{ $goodsReceive->purchaseOrder->po_number }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Receive Date</dt>
                        <dd>{{ $goodsReceive->receive_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Invoice Number</dt>
                        <dd>{{ $goodsReceive->invoice_number ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Outlet</dt>
                        <dd>{{ $goodsReceive->outlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Received By</dt>
                        <dd>{{ $goodsReceive->receivedBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Supplier Information">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">Supplier</dt>
                        <dd class="font-medium">{{ $goodsReceive->purchaseOrder->supplier->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Contact</dt>
                        <dd>{{ $goodsReceive->purchaseOrder->supplier->contact_person ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Phone</dt>
                        <dd>{{ $goodsReceive->purchaseOrder->supplier->phone ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <x-card title="Received Items">
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th align="right">Ordered</x-th>
                    <x-th align="right">Received</x-th>
                    <x-th>Batch</x-th>
                    <x-th>Expiry</x-th>
                </x-slot>

                @foreach($goodsReceive->items as $item)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $item->purchaseOrderItem->inventoryItem->name }}</p>
                            @if($item->notes)
                                <p class="text-xs text-muted">{{ $item->notes }}</p>
                            @endif
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->purchaseOrderItem->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">{{ number_format($item->purchaseOrderItem->quantity, 2) }} {{ $item->purchaseOrderItem->inventoryItem->unit->abbreviation ?? '' }}</x-td>
                        <x-td align="right">
                            <span class="{{ $item->quantity_received < $item->purchaseOrderItem->quantity ? 'text-warning-600' : 'text-success-600' }} font-medium">
                                {{ number_format($item->quantity_received, 2) }}
                            </span>
                        </x-td>
                        <x-td>
                            @if($item->batch_number)
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->batch_number }}</code>
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td>
                            @if($item->expiry_date)
                                {{ $item->expiry_date->format('M d, Y') }}
                            @else
                                -
                            @endif
                        </x-td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        @if($goodsReceive->notes)
            <x-card title="Notes">
                <p class="text-text">{{ $goodsReceive->notes }}</p>
            </x-card>
        @endif
    </div>
</x-app-layout>
