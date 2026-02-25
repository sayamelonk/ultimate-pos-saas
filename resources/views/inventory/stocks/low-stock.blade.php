<x-app-layout>
    <x-slot name="title">Low Stock Items - Ultimate POS</x-slot>

    @section('page-title', 'Low Stock')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stocks.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Low Stock Items</h2>
                <p class="text-muted mt-1">Items below reorder level</p>
            </div>
        </div>
    </x-slot>

    <x-card>
        @if($lowStockItems->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th align="right">Total Stock</x-th>
                    <x-th align="right">Reorder Level</x-th>
                    <x-th align="right">Reorder Qty</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($lowStockItems as $item)
                    @php
                        $totalStock = $item->stocks->sum('quantity');
                    @endphp
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 {{ $totalStock <= 0 ? 'bg-danger-100' : 'bg-warning-100' }} rounded-lg flex items-center justify-center">
                                    <x-icon name="alert-triangle" class="w-5 h-5 {{ $totalStock <= 0 ? 'text-danger-600' : 'text-warning-600' }}" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $item->name }}</p>
                                    <p class="text-xs text-muted">{{ $item->unit->abbreviation ?? '' }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->sku }}</code>
                        </x-td>
                        <x-td align="right">
                            <span class="{{ $totalStock <= 0 ? 'text-danger-600' : 'text-warning-600' }} font-medium">
                                {{ number_format($totalStock, 2) }}
                            </span>
                        </x-td>
                        <x-td align="right">{{ number_format($item->reorder_point, 2) }}</x-td>
                        <x-td align="right">{{ number_format($item->reorder_qty ?? 0, 2) }}</x-td>
                        <x-td align="right">
                            <x-button href="{{ route('inventory.purchase-orders.create') }}" variant="outline-secondary" size="sm">
                                Create PO
                            </x-button>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <x-empty-state
                title="No low stock items"
                description="All items are above their reorder levels."
                icon="check-circle"
            />
        @endif
    </x-card>
</x-app-layout>
