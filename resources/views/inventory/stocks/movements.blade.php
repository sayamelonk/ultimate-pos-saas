<x-app-layout>
    <x-slot name="title">{{ __('inventory.stock_movements') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.stock_movements'))

    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-text">{{ __('inventory.stock_movements') }}</h2>
            <p class="text-muted mt-1">{{ __('inventory.manage_movements') }}</p>
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
                        :placeholder="__('inventory.search_movements')"
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
                <x-select name="type" class="w-40">
                    <option value="">{{ __('inventory.all_types') }}</option>
                    <option value="in" @selected(request('type') === 'in')>{{ __('inventory.movement_in') }}</option>
                    <option value="out" @selected(request('type') === 'out')>{{ __('inventory.movement_out') }}</option>
                    <option value="adjustment" @selected(request('type') === 'adjustment')>{{ __('inventory.stock_adjustment') }}</option>
                    <option value="transfer_in" @selected(request('type') === 'transfer_in')>{{ __('inventory.transfers_in') }}</option>
                    <option value="transfer_out" @selected(request('type') === 'transfer_out')>{{ __('inventory.transfers_out') }}</option>
                </x-select>
                <x-input
                    type="date"
                    name="date_from"
                    :placeholder="__('inventory.from_date')"
                    :value="request('date_from')"
                    class="w-40"
                />
                <x-input
                    type="date"
                    name="date_to"
                    :placeholder="__('inventory.to_date')"
                    :value="request('date_to')"
                    class="w-40"
                />
                <x-button type="submit" variant="secondary">
                    {{ __('inventory.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'type', 'date_from', 'date_to']))
                    <x-button href="{{ route('inventory.stocks.movements') }}" variant="ghost">
                        {{ __('inventory.cancel') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($movements->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.date') }}</x-th>
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.outlet') }}</x-th>
                    <x-th>{{ __('inventory.movement_type') }}</x-th>
                    <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                    <x-th align="right">{{ __('inventory.cost_price') }}</x-th>
                    <x-th>{{ __('inventory.reference') }}</x-th>
                    <x-th>{{ __('inventory.logged_by') }}</x-th>
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
                                    <x-badge type="success">{{ __('inventory.type_in') }}</x-badge>
                                    @break
                                @case('out')
                                    <x-badge type="danger">{{ __('inventory.type_out') }}</x-badge>
                                    @break
                                @case('adjustment')
                                    <x-badge type="warning">{{ __('inventory.stock_adjustment') }}</x-badge>
                                    @break
                                @case('transfer_in')
                                    <x-badge type="info">{{ __('inventory.transfers_in') }}</x-badge>
                                    @break
                                @case('transfer_out')
                                    <x-badge type="warning">{{ __('inventory.transfers_out') }}</x-badge>
                                    @break
                            @endswitch
                        </x-td>
                        <x-td align="right">
                            @php
                                $qty = $movement->quantity;
                                $decimals = (abs($qty) < 1) ? 4 : 2;
                            @endphp
                            @if($qty > 0)
                                <span class="text-success-600 font-medium">+{{ number_format($qty, $decimals) }}</span>
                            @elseif($qty < 0)
                                <span class="text-danger-600 font-medium">{{ number_format($qty, $decimals) }}</span>
                            @else
                                <span class="font-medium">{{ number_format($qty, $decimals) }}</span>
                            @endif
                        </x-td>
                        <x-td align="right">Rp {{ number_format(abs($movement->quantity) * $movement->cost_price, 0, ',', '.') }}</x-td>
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
                :title="__('inventory.no_movements_found')"
                :description="__('inventory.no_movements_description')"
                icon="switch-horizontal"
            />
        @endif
    </x-card>
</x-app-layout>
