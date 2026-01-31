<x-app-layout>
    <x-slot name="title">Stock Batches - Ultimate POS</x-slot>

    @section('page-title', 'Stock Batches')

    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-text">Stock Batches</h2>
            <p class="text-muted mt-1">Track inventory by batch/lot number</p>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.stocks.batches') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search batch or item..."
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
                <x-select name="expiry_status" class="w-40">
                    <option value="">All Batches</option>
                    <option value="expired" @selected(request('expiry_status') === 'expired')>Expired</option>
                    <option value="expiring_soon" @selected(request('expiry_status') === 'expiring_soon')>Expiring Soon</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'expiry_status']))
                    <x-button href="{{ route('inventory.stocks.batches') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($batches->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Batch Number</x-th>
                    <x-th>Item</x-th>
                    <x-th>Outlet</x-th>
                    <x-th align="right">Original Qty</x-th>
                    <x-th align="right">Remaining</x-th>
                    <x-th>Expiry Date</x-th>
                    <x-th align="right">Cost Price</x-th>
                </x-slot>

                @foreach($batches as $batch)
                    <tr>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $batch->batch_number }}</code>
                        </x-td>
                        <x-td>
                            <div>
                                <p class="font-medium text-text">{{ $batch->inventoryItem->name }}</p>
                                <p class="text-xs text-muted">{{ $batch->inventoryItem->sku }}</p>
                            </div>
                        </x-td>
                        <x-td>{{ $batch->outlet->name }}</x-td>
                        <x-td align="right">{{ number_format($batch->quantity, 2) }} {{ $batch->inventoryItem->unit->abbreviation ?? '' }}</x-td>
                        <x-td align="right">{{ number_format($batch->current_qty, 2) }}</x-td>
                        <x-td>
                            @if($batch->expiry_date)
                                @if($batch->expiry_date->isPast())
                                    <x-badge type="danger">{{ $batch->expiry_date->format('M d, Y') }} - Expired</x-badge>
                                @elseif($batch->expiry_date->diffInDays(now()) <= 30)
                                    <x-badge type="warning">{{ $batch->expiry_date->format('M d, Y') }}</x-badge>
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

            <div class="mt-6">
                <x-pagination :paginator="$batches" />
            </div>
        @else
            <x-empty-state
                title="No batches found"
                description="Batch records will appear here for items with batch tracking enabled."
                icon="collection"
            />
        @endif
    </x-card>
</x-app-layout>
