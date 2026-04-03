<x-app-layout>
    <x-slot name="title">{{ __('inventory.stock_levels') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.stock_levels'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.stock_levels') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.monitor_stock') }}</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.stocks.low') }}" variant="outline-secondary" icon="alert-triangle">
                    {{ __('inventory.low_stock') }}
                </x-button>
                <x-button href="{{ route('inventory.stocks.expiring') }}" variant="outline-secondary" icon="clock">
                    {{ __('inventory.expiring_items') }}
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
                        placeholder="{{ __('inventory.search_items') }}"
                        :value="request('search')"
                    />
                </div>
                <x-select name="outlet_id" class="w-48">
                    <option value="">{{ __('inventory.all_outlets') }}</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-40">
                    <option value="">{{ __('inventory.all_status') }}</option>
                    <option value="low" @selected(request('status') === 'low')>{{ __('inventory.low_stock') }}</option>
                    <option value="out" @selected(request('status') === 'out')>{{ __('inventory.out_of_stock') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('app.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'status']))
                    <x-button href="{{ route('inventory.stocks.index') }}" variant="ghost">
                        {{ __('app.clear') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($stocks->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.outlet') }}</x-th>
                    <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                    <x-th align="right">{{ __('inventory.reserved') }}</x-th>
                    <x-th align="right">{{ __('inventory.available') }}</x-th>
                    <x-th align="right">{{ __('inventory.avg_cost') }}</x-th>
                    <x-th align="right">{{ __('inventory.value') }}</x-th>
                    <x-th align="right">{{ __('app.actions') }}</x-th>
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
                                $reorderPoint = $stock->inventoryItem->reorder_point;
                            @endphp
                            <span class="{{ $reorderPoint && $available <= $reorderPoint ? 'text-danger-600 font-medium' : '' }}">
                                {{ number_format($available, 2) }}
                            </span>
                        </x-td>
                        <x-td align="right">Rp {{ number_format($stock->avg_cost, 0, ',', '.') }}</x-td>
                        <x-td align="right">Rp {{ number_format($stock->quantity * $stock->avg_cost, 0, ',', '.') }}</x-td>
                        <x-td align="right">
                            <x-button href="{{ route('inventory.stocks.show', $stock) }}" variant="ghost" size="sm" icon="eye">
                                {{ __('app.view') }}
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
                title="{{ __('inventory.no_stock_records') }}"
                description="{{ __('inventory.no_stock_description') }}"
                icon="cube"
            >
                <x-button href="{{ route('inventory.purchase-orders.create') }}" icon="plus">
                    {{ __('inventory.create_po') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
