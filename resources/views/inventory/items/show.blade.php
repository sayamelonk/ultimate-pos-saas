<x-app-layout>
    <x-slot name="title">{{ $item->name }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.item_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.items.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('inventory.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $item->name }}</h2>
                    <p class="text-muted mt-1">{{ $item->sku }}</p>
                </div>
            </div>
            <x-button href="{{ route('inventory.items.edit', $item) }}" variant="outline-secondary" icon="pencil">
                {{ __('inventory.edit') }}
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <div class="grid grid-cols-3 gap-6">
            <x-card title="{{ __('inventory.basic_information') }}" class="col-span-2">
                <dl class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.name') }}</dt>
                        <dd class="mt-1 font-medium text-text">{{ $item->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.sku') }}</dt>
                        <dd class="mt-1">
                            <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $item->sku }}</code>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.category') }}</dt>
                        <dd class="mt-1 text-text">{{ $item->category->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.unit') }}</dt>
                        <dd class="mt-1 text-text">{{ $item->unit->name ?? '-' }} ({{ $item->unit->abbreviation ?? '' }})</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.item_type') }}</dt>
                        <dd class="mt-1 text-text capitalize">{{ __('inventory.' . $item->type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.cost_price') }}</dt>
                        <dd class="mt-1 font-medium text-text">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.barcode') }}</dt>
                        <dd class="mt-1 text-text">{{ $item->barcode ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.status') }}</dt>
                        <dd class="mt-1">
                            @if($item->is_active)
                                <x-badge type="success" dot>{{ __('inventory.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('inventory.inactive') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    @if($item->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">{{ __('inventory.description') }}</dt>
                            <dd class="mt-1 text-text">{{ $item->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-card title="{{ __('inventory.stock_settings') }}">
                @php
                    $totalStock = $item->stocks->sum('quantity');
                    $totalReserved = $item->stocks->sum('reserved_quantity');
                @endphp
                <dl class="space-y-4">
                    <div class="p-3 rounded-lg bg-accent/10 border border-accent/20">
                        <dt class="text-sm text-muted">{{ __('inventory.current_stock') }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-accent">{{ number_format($totalStock, 0, ',', '.') }} <span class="text-sm font-normal">{{ $item->unit->abbreviation ?? '' }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.reorder_level') }}</dt>
                        <dd class="mt-1 font-medium text-text">{{ $item->reorder_point ? number_format($item->reorder_point, 0, ',', '.') : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.reorder_quantity') }}</dt>
                        <dd class="mt-1 text-text">{{ $item->reorder_qty ? number_format($item->reorder_qty, 0, ',', '.') : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.max_stock_level') }}</dt>
                        <dd class="mt-1 text-text">{{ $item->max_stock ? number_format($item->max_stock, 0, ',', '.') : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.shelf_life_days') }}</dt>
                        <dd class="mt-1 text-text">{{ $item->shelf_life_days ? $item->shelf_life_days . ' ' . __('inventory.date') : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.storage_location') }}</dt>
                        <dd class="mt-1 text-text">{{ $item->storage_location ?? '-' }}</dd>
                    </div>
                    <div class="pt-4 border-t border-border space-y-2">
                        <div class="flex items-center gap-2">
                            @if($item->is_perishable)
                                <x-badge type="warning" size="sm">{{ __('inventory.is_perishable') }}</x-badge>
                            @endif
                            @if($item->track_batches)
                                <x-badge type="info" size="sm">{{ __('inventory.batch_tracking') }}</x-badge>
                            @endif
                        </div>
                    </div>
                </dl>
            </x-card>
        </div>

        @if($item->stocks && $item->stocks->count() > 0)
            <x-card title="{{ __('inventory.stock_levels') }}">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.outlet') }}</x-th>
                        <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                        <x-th align="right">{{ __('inventory.reserved') }}</x-th>
                        <x-th align="right">{{ __('inventory.available') }}</x-th>
                        <x-th align="right">{{ __('inventory.avg_cost') }}</x-th>
                        <x-th align="right">{{ __('inventory.value') }}</x-th>
                    </x-slot>

                    @foreach($item->stocks as $stock)
                        <tr>
                            <x-td>{{ $stock->outlet->name }}</x-td>
                            <x-td align="right">{{ number_format($stock->quantity, 2) }} {{ $item->unit->abbreviation ?? '' }}</x-td>
                            <x-td align="right">{{ number_format($stock->reserved_quantity, 2) }}</x-td>
                            <x-td align="right">{{ number_format($stock->quantity - $stock->reserved_quantity, 2) }}</x-td>
                            <x-td align="right">Rp {{ number_format($stock->avg_cost, 0, ',', '.') }}</x-td>
                            <x-td align="right">Rp {{ number_format($stock->quantity * $stock->avg_cost, 0, ',', '.') }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($item->stockBatches && $item->stockBatches->count() > 0)
            <x-card title="{{ __('inventory.batches') }}">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.batch_number') }}</x-th>
                        <x-th>{{ __('inventory.outlet') }}</x-th>
                        <x-th align="right">{{ __('inventory.remaining_quantity') }}</x-th>
                        <x-th>{{ __('inventory.expiry_date') }}</x-th>
                        <x-th align="right">{{ __('inventory.cost_price') }}</x-th>
                    </x-slot>

                    @foreach($item->stockBatches as $batch)
                        <tr>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $batch->batch_number }}</code>
                            </x-td>
                            <x-td>{{ $batch->outlet->name ?? '-' }}</x-td>
                            <x-td align="right">{{ number_format($batch->current_quantity, 2) }}</x-td>
                            <x-td>
                                @if($batch->expiry_date)
                                    @if($batch->expiry_date->isPast())
                                        <span class="text-danger-600">{{ $batch->expiry_date->format('M d, Y') }} ({{ __('inventory.expired') }})</span>
                                    @elseif($batch->expiry_date->diffInDays(now()) <= 7)
                                        <span class="text-warning-600">{{ $batch->expiry_date->format('M d, Y') }}</span>
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
            </x-card>
        @endif

        @if($item->supplierItems && $item->supplierItems->count() > 0)
            <x-card title="{{ __('inventory.suppliers') }}">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.supplier') }}</x-th>
                        <x-th>{{ __('inventory.supplier_code') }}</x-th>
                        <x-th align="right">{{ __('inventory.unit_price') }}</x-th>
                        <x-th>{{ __('inventory.payment_terms_days') }}</x-th>
                        <x-th>{{ __('inventory.status') }}</x-th>
                    </x-slot>

                    @foreach($item->supplierItems as $supplierItem)
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.suppliers.show', $supplierItem->supplier) }}" class="text-accent hover:underline">
                                    {{ $supplierItem->supplier->name }}
                                </a>
                            </x-td>
                            <x-td>{{ $supplierItem->supplier_item_code ?? '-' }}</x-td>
                            <x-td align="right">Rp {{ number_format($supplierItem->last_price, 0, ',', '.') }}</x-td>
                            <x-td>{{ $supplierItem->lead_time_days ? $supplierItem->lead_time_days . ' ' . __('inventory.date') : '-' }}</x-td>
                            <x-td>
                                @if($supplierItem->is_preferred)
                                    <x-badge type="success" size="sm">{{ __('inventory.active') }}</x-badge>
                                @else
                                    -
                                @endif
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        <!-- Stock Movement History -->
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-text">{{ __('inventory.stock_history') }}</h3>
                <a href="{{ route('inventory.stocks.movements', ['search' => $item->name]) }}" class="text-sm text-accent hover:underline">
                    {{ __('inventory.view_all_movements') }}
                </a>
            </div>

            @if($recentMovements->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.date') }}</x-th>
                        <x-th>{{ __('inventory.outlet') }}</x-th>
                        <x-th>{{ __('inventory.movement_type') }}</x-th>
                        <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                        <x-th align="right">{{ __('inventory.closing_stock') }}</x-th>
                        <x-th>{{ __('inventory.notes') }}</x-th>
                        <x-th>{{ __('inventory.created') }}</x-th>
                    </x-slot>

                    @foreach($recentMovements as $movement)
                        @php
                            $qty = $movement->quantity;
                            $decimals = (abs($qty) < 1) ? 4 : 2;
                        @endphp
                        <tr>
                            <x-td>{{ $movement->created_at->format('d M Y H:i') }}</x-td>
                            <x-td>{{ $movement->outlet->name ?? '-' }}</x-td>
                            <x-td>
                                @switch($movement->type)
                                    @case('in')
                                        <x-badge type="success">{{ __('inventory.movement_in') }}</x-badge>
                                        @break
                                    @case('out')
                                        <x-badge type="danger">{{ __('inventory.movement_out') }}</x-badge>
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
                                    @default
                                        <x-badge type="secondary">{{ strtoupper($movement->type) }}</x-badge>
                                @endswitch
                            </x-td>
                            <x-td align="right">
                                @if($qty > 0)
                                    <span class="text-success-600 font-medium">+{{ number_format($qty, $decimals) }}</span>
                                @elseif($qty < 0)
                                    <span class="text-danger-600 font-medium">{{ number_format($qty, $decimals) }}</span>
                                @else
                                    <span class="font-medium">{{ number_format($qty, $decimals) }}</span>
                                @endif
                                {{ $item->unit->abbreviation ?? '' }}
                            </x-td>
                            <x-td align="right">{{ number_format($movement->stock_after, 2) }} {{ $item->unit->abbreviation ?? '' }}</x-td>
                            <x-td class="max-w-xs truncate" title="{{ $movement->notes }}">{{ Str::limit($movement->notes, 30) ?? '-' }}</x-td>
                            <x-td>{{ $movement->createdBy->name ?? '-' }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
            @else
                <x-empty-state
                    title="{{ __('inventory.no_movements_yet') }}"
                    description="{{ __('inventory.no_stock_description') }}"
                    icon="switch-horizontal"
                />
            @endif
        </x-card>
    </div>
</x-app-layout>
