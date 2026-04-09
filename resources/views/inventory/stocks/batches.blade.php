<x-app-layout>
    <x-slot name="title">{{ __('inventory.batches') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.batches'))

    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-text">{{ __('inventory.batches') }}</h2>
            <p class="text-muted mt-1">{{ __('inventory.manage_batches') }}</p>
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
                        :placeholder="__('inventory.search_batches')"
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
                <x-select name="expiry_status" class="w-40">
                    <option value="">{{ __('inventory.all_expiry') }}</option>
                    <option value="expired" @selected(request('expiry_status') === 'expired')>{{ __('inventory.expired') }}</option>
                    <option value="expiring_soon" @selected(request('expiry_status') === 'expiring_soon')>{{ __('inventory.expiring_soon') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('inventory.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'expiry_status']))
                    <x-button href="{{ route('inventory.stocks.batches') }}" variant="ghost">
                        {{ __('inventory.cancel') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($batches->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.batch_number') }}</x-th>
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.outlet') }}</x-th>
                    <x-th align="right">{{ __('inventory.initial_quantity') }}</x-th>
                    <x-th align="right">{{ __('inventory.remaining_quantity') }}</x-th>
                    <x-th>{{ __('inventory.expiry_date') }}</x-th>
                    <x-th align="right">{{ __('inventory.cost_price') }}</x-th>
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
                        <x-td align="right">{{ number_format($batch->current_quantity, 2) }}</x-td>
                        <x-td>
                            @if($batch->expiry_date)
                                @if($batch->expiry_date->isPast())
                                    <x-badge type="danger">{{ $batch->expiry_date->format('M d, Y') }} - {{ __('inventory.expired') }}</x-badge>
                                @elseif($batch->expiry_date->diffInDays(now()) <= 30)
                                    <x-badge type="warning">{{ $batch->expiry_date->format('M d, Y') }}</x-badge>
                                @else
                                    {{ $batch->expiry_date->format('M d, Y') }}
                                @endif
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td align="right">Rp {{ number_format($batch->unit_cost, 0, ',', '.') }}</x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$batches" />
            </div>
        @else
            <x-empty-state
                :title="__('inventory.no_batches_found')"
                :description="__('inventory.batches_description')"
                icon="collection"
            />
        @endif
    </x-card>
</x-app-layout>
