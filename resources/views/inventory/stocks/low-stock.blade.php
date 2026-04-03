<x-app-layout>
    <x-slot name="title">{{ __('inventory.low_stock') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.low_stock'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stocks.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.low_stock') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.items_low_stock', ['count' => $lowStockItems->count()]) }}</p>
            </div>
        </div>
    </x-slot>

    <x-card>
        @if($lowStockItems->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.sku') }}</x-th>
                    <x-th align="right">{{ __('inventory.current_stock') }}</x-th>
                    <x-th align="right">{{ __('inventory.reorder_level') }}</x-th>
                    <x-th align="right">{{ __('inventory.reorder_quantity') }}</x-th>
                    <x-th align="right">{{ __('inventory.actions') }}</x-th>
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
                                {{ __('inventory.create_po') }}
                            </x-button>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <x-empty-state
                :title="__('inventory.no_items_found')"
                :description="__('inventory.no_stock_description')"
                icon="check-circle"
            />
        @endif
    </x-card>
</x-app-layout>
