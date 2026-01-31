<x-app-layout>
    <x-slot name="title">Stock Movements - Ultimate POS</x-slot>

    @section('page-title', 'Stock Movements')

    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-text">Stock Movements</h2>
            <p class="text-muted mt-1">Track all inventory movements</p>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.stocks.movements') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search by item or reference..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="outlet_id" class="w-48">
                    <option value="">All Outlets</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="type" class="w-40">
                    <option value="">All Types</option>
                    <option value="in" @selected(request('type') === 'in')>Stock In</option>
                    <option value="out" @selected(request('type') === 'out')>Stock Out</option>
                    <option value="adjustment" @selected(request('type') === 'adjustment')>Adjustment</option>
                    <option value="transfer_in" @selected(request('type') === 'transfer_in')>Transfer In</option>
                    <option value="transfer_out" @selected(request('type') === 'transfer_out')>Transfer Out</option>
                </x-select>
                <x-input
                    type="date"
                    name="date_from"
                    placeholder="From Date"
                    :value="request('date_from')"
                    class="w-40"
                />
                <x-input
                    type="date"
                    name="date_to"
                    placeholder="To Date"
                    :value="request('date_to')"
                    class="w-40"
                />
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'type', 'date_from', 'date_to']))
                    <x-button href="{{ route('inventory.stocks.movements') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($movements->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Date</x-th>
                    <x-th>Item</x-th>
                    <x-th>Outlet</x-th>
                    <x-th>Type</x-th>
                    <x-th align="right">Quantity</x-th>
                    <x-th align="right">Cost</x-th>
                    <x-th>Reference</x-th>
                    <x-th>User</x-th>
                </x-slot>

                @foreach($movements as $movement)
                    <tr>
                        <x-td>{{ $movement->created_at->format('M d, Y H:i') }}</x-td>
                        <x-td>
                            <div>
                                <p class="font-medium text-text">{{ $movement->inventoryItem->name }}</p>
                                <p class="text-xs text-muted">{{ $movement->inventoryItem->sku }}</p>
                            </div>
                        </x-td>
                        <x-td>{{ $movement->outlet->name }}</x-td>
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
                            @if(in_array($movement->type, ['in', 'transfer_in']))
                                <span class="text-success-600 font-medium">+{{ number_format($movement->quantity, 2) }}</span>
                            @elseif($movement->type === 'adjustment')
                                <span class="font-medium">{{ number_format($movement->quantity, 2) }}</span>
                            @else
                                <span class="text-danger-600 font-medium">-{{ number_format($movement->quantity, 2) }}</span>
                            @endif
                        </x-td>
                        <x-td align="right">Rp {{ number_format($movement->cost_price, 0, ',', '.') }}</x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $movement->reference_number ?? '-' }}</code>
                        </x-td>
                        <x-td>{{ $movement->createdBy->name ?? '-' }}</x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$movements" />
            </div>
        @else
            <x-empty-state
                title="No movements found"
                description="Stock movements will appear here once inventory is received or issued."
                icon="switch-horizontal"
            />
        @endif
    </x-card>
</x-app-layout>
