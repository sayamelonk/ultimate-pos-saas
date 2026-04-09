<x-app-layout>
    <x-slot name="title">{{ __('inventory.stock') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.stock'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stocks.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ $stock->inventoryItem->name }}</h2>
                <p class="text-muted mt-1">{{ $stock->outlet->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-4 gap-4">
            <x-stat-card
                :title="__('inventory.quantity')"
                :value="number_format($stock->quantity, 2) . ' ' . ($stock->inventoryItem->unit->abbreviation ?? '')"
                icon="cube"
            />
            <x-stat-card
                :title="__('inventory.reserved')"
                :value="number_format($stock->reserved_quantity, 2)"
                icon="lock"
            />
            <x-stat-card
                :title="__('inventory.available')"
                :value="number_format($stock->quantity - $stock->reserved_quantity, 2)"
                icon="check-circle"
            />
            <x-stat-card
                :title="__('inventory.total_value')"
                :value="'Rp ' . number_format($stock->quantity * $stock->avg_cost, 0, ',', '.')"
                icon="currency-dollar"
            />
        </div>

        <x-card :title="__('inventory.information')">
            <dl class="grid grid-cols-3 gap-6">
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.item') }}</dt>
                    <dd class="mt-1 font-medium text-text">{{ $stock->inventoryItem->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.sku') }}</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $stock->inventoryItem->sku }}</code>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.outlet') }}</dt>
                    <dd class="mt-1 text-text">{{ $stock->outlet->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.avg_cost') }}</dt>
                    <dd class="mt-1 text-text">Rp {{ number_format($stock->avg_cost, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.reorder_level') }}</dt>
                    <dd class="mt-1 text-text">{{ $stock->inventoryItem->reorder_point ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.updated') }}</dt>
                    <dd class="mt-1 text-text">{{ $stock->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        @if($batches->count() > 0)
            <x-card :title="__('inventory.batches')">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.batch_number') }}</x-th>
                        <x-th align="right">{{ __('inventory.remaining_quantity') }}</x-th>
                        <x-th>{{ __('inventory.expiry_date') }}</x-th>
                        <x-th align="right">{{ __('inventory.cost_price') }}</x-th>
                        <x-th>{{ __('inventory.receive_date') }}</x-th>
                    </x-slot>

                    @foreach($batches as $batch)
                        <tr>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $batch->batch_number }}</code>
                            </x-td>
                            <x-td align="right">{{ number_format($batch->current_quantity, 2) }}</x-td>
                            <x-td>
                                @if($batch->expiry_date)
                                    @if($batch->expiry_date->isPast())
                                        <x-badge type="danger">{{ $batch->expiry_date->format('M d, Y') }} - {{ __('inventory.expired') }}</x-badge>
                                    @elseif($batch->expiry_date->diffInDays(now()) <= 7)
                                        <x-badge type="warning">{{ $batch->expiry_date->format('M d, Y') }}</x-badge>
                                    @else
                                        {{ $batch->expiry_date->format('M d, Y') }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td align="right">Rp {{ number_format($batch->unit_cost, 0, ',', '.') }}</x-td>
                            <x-td>{{ $batch->created_at->format('M d, Y') }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($movements->count() > 0)
            <x-card :title="__('inventory.recent_movements')">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.date') }}</x-th>
                        <x-th>{{ __('inventory.movement_type') }}</x-th>
                        <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                        <x-th>{{ __('inventory.reference') }}</x-th>
                        <x-th>{{ __('inventory.logged_by') }}</x-th>
                        <x-th>{{ __('inventory.reason') }}</x-th>
                    </x-slot>

                    @foreach($movements as $movement)
                        <tr>
                            <x-td>{{ $movement->created_at->format('M d, Y H:i') }}</x-td>
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
                                @if(in_array($movement->type, ['in', 'transfer_in', 'adjustment']))
                                    <span class="text-success-600">+{{ number_format($movement->quantity, 2) }}</span>
                                @else
                                    <span class="text-danger-600">-{{ number_format($movement->quantity, 2) }}</span>
                                @endif
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $movement->reference_number ?? '-' }}</code>
                            </x-td>
                            <x-td>{{ $movement->createdBy->name ?? '-' }}</x-td>
                            <x-td class="max-w-[200px] truncate">{{ $movement->reason ?? '-' }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
                <div class="mt-4">
                    <x-button href="{{ route('inventory.stocks.movements', ['search' => $stock->inventoryItem->sku]) }}" variant="outline-secondary" size="sm">
                        {{ __('inventory.view_all_movements') }}
                    </x-button>
                </div>
            </x-card>
        @endif
    </div>
</x-app-layout>
