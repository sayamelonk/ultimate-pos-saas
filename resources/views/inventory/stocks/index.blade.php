<x-app-layout>
    <x-slot name="title">Stock Levels - Ultimate POS</x-slot>

    @section('page-title', 'Stock Levels')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Stock Levels</h2>
                <p class="text-muted mt-1">Monitor inventory stock across outlets</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.stocks.low') }}" variant="outline-secondary" icon="alert-triangle">
                    Low Stock
                </x-button>
                <x-button href="{{ route('inventory.stocks.expiring') }}" variant="outline-secondary" icon="clock">
                    Expiring Items
                </x-button>
            </div>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.stocks.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search items..."
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
                <x-select name="status" class="w-40">
                    <option value="">All Status</option>
                    <option value="low" @selected(request('status') === 'low')>Low Stock</option>
                    <option value="out" @selected(request('status') === 'out')>Out of Stock</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'status']))
                    <x-button href="{{ route('inventory.stocks.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($stocks->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>Outlet</x-th>
                    <x-th align="right">Quantity</x-th>
                    <x-th align="right">Reserved</x-th>
                    <x-th align="right">Available</x-th>
                    <x-th align="right">Avg Cost</x-th>
                    <x-th align="right">Value</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($stocks as $stock)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="cube" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $stock->inventoryItem->name }}</p>
                                    <p class="text-xs text-muted">{{ $stock->inventoryItem->sku }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>{{ $stock->outlet->name }}</x-td>
                        <x-td align="right">
                            {{ number_format($stock->quantity, 2) }} {{ $stock->inventoryItem->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right">{{ number_format($stock->reserved_quantity, 2) }}</x-td>
                        <x-td align="right">
                            @php
                                $available = $stock->quantity - $stock->reserved_quantity;
                                $reorderLevel = $stock->inventoryItem->reorder_level;
                            @endphp
                            <span class="{{ $reorderLevel && $available <= $reorderLevel ? 'text-danger-600 font-medium' : '' }}">
                                {{ number_format($available, 2) }}
                            </span>
                        </x-td>
                        <x-td align="right">Rp {{ number_format($stock->avg_cost, 0, ',', '.') }}</x-td>
                        <x-td align="right">Rp {{ number_format($stock->quantity * $stock->avg_cost, 0, ',', '.') }}</x-td>
                        <x-td align="right">
                            <x-button href="{{ route('inventory.stocks.show', $stock) }}" variant="ghost" size="sm" icon="eye">
                                View
                            </x-button>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$stocks" />
            </div>
        @else
            <x-empty-state
                title="No stock records found"
                description="Stock will appear here once you receive inventory."
                icon="cube"
            >
                <x-button href="{{ route('inventory.purchase-orders.create') }}" icon="plus">
                    Create Purchase Order
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
