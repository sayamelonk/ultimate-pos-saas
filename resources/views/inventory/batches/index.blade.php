<x-app-layout>
    <x-slot name="title">Stock Batches - Ultimate POS</x-slot>

    @section('page-title', __('inventory.batches'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.batches') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.manage_batches') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button href="{{ route('inventory.batches.settings') }}" variant="secondary" icon="cog-6-tooth">
                    {{ __('inventory.settings') }}
                </x-button>
                <x-button href="{{ route('inventory.batches.expiry-report') }}" variant="warning" icon="exclamation-triangle">
                    {{ __('inventory.expiry_report') }}
                </x-button>
                <x-button href="{{ route('inventory.batches.create') }}" icon="plus">
                    {{ __('inventory.add_batch') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            :title="__('inventory.active') . ' ' . __('inventory.batches')"
            :value="$stats['total_batches']"
            icon="cube"
        />
        <x-stat-card
            :title="__('inventory.expiring_soon')"
            :value="$stats['expiring_soon']"
            icon="clock"
            :color="$stats['expiring_soon'] > 0 ? 'warning' : 'secondary'"
        />
        <x-stat-card
            :title="__('inventory.expiring_batches') . ' (' . $settings->expiry_critical_days . ' ' . __('inventory.days_until_expiry') . ')'"
            :value="$stats['critical']"
            icon="exclamation-triangle"
            :color="$stats['critical'] > 0 ? 'danger' : 'secondary'"
        />
        <x-stat-card
            :title="__('inventory.expired')"
            :value="$stats['expired']"
            icon="x-circle"
            :color="$stats['expired'] > 0 ? 'danger' : 'secondary'"
        />
    </div>

    <!-- Filters -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('inventory.batches.index') }}">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px] max-w-xs">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('inventory.search') }}</label>
                    <x-input
                        name="search"
                        :placeholder="__('inventory.search_batches')"
                        :value="request('search')"
                    />
                </div>

                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('inventory.outlet') }}</label>
                    <x-select name="outlet_id">
                        <option value="">{{ __('inventory.all_outlets') }}</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="w-44">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('inventory.item') }}</label>
                    <x-select name="item_id">
                        <option value="">{{ __('inventory.all_items') }}</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="w-36">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('inventory.status') }}</label>
                    <x-select name="status">
                        <option value="">{{ __('inventory.all_status') }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ __('inventory.active') }}</option>
                        <option value="depleted" @selected(request('status') === 'depleted')>{{ ucfirst('depleted') }}</option>
                        <option value="expired" @selected(request('status') === 'expired')>{{ __('inventory.expired') }}</option>
                        <option value="disposed" @selected(request('status') === 'disposed')>{{ ucfirst('disposed') }}</option>
                    </x-select>
                </div>

                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('inventory.expiry_status') }}</label>
                    <x-select name="expiry_filter">
                        <option value="">{{ __('inventory.all_expiry') }}</option>
                        <option value="expired" @selected(request('expiry_filter') === 'expired')>{{ __('inventory.expired') }}</option>
                        <option value="critical" @selected(request('expiry_filter') === 'critical')>{{ ucfirst('critical') }}</option>
                        <option value="warning" @selected(request('expiry_filter') === 'warning')>{{ ucfirst('warning') }}</option>
                        <option value="no_expiry" @selected(request('expiry_filter') === 'no_expiry')>{{ __('inventory.none') }}</option>
                    </x-select>
                </div>

                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" icon="search">
                        {{ __('inventory.filter') }}
                    </x-button>
                    @if(request()->hasAny(['search', 'outlet_id', 'item_id', 'status', 'expiry_filter']))
                        <x-button href="{{ route('inventory.batches.index') }}" variant="ghost">
                            {{ ucfirst('clear') }}
                        </x-button>
                    @endif
                </div>
            </div>
        </form>
    </x-card>

    <!-- Batches Table -->
    <x-card>
        @if($batches->count() > 0)
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.batch') }} / {{ __('inventory.item') }}</x-th>
                        <x-th>{{ __('inventory.outlet') }}</x-th>
                        <x-th align="center">{{ __('inventory.expiry_date') }}</x-th>
                        <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                        <x-th align="right">{{ __('inventory.unit_cost') }}</x-th>
                        <x-th align="center">{{ __('inventory.status') }}</x-th>
                        <x-th align="center">{{ __('inventory.actions') }}</x-th>
                    </x-slot>

                    @foreach($batches as $batch)
                        <tr class="hover:bg-secondary-50/50 transition-colors">
                            <x-td>
                                <div>
                                    <a href="{{ route('inventory.batches.show', $batch) }}" class="font-medium text-primary hover:underline">
                                        {{ $batch->batch_number }}
                                    </a>
                                    <p class="text-sm text-muted">{{ $batch->inventoryItem->name }}</p>
                                    @if($batch->supplier_batch_number)
                                        <p class="text-xs text-muted">{{ __('inventory.supplier') }}: {{ $batch->supplier_batch_number }}</p>
                                    @endif
                                </div>
                            </x-td>
                            <x-td>
                                <span class="text-sm">{{ $batch->outlet->name }}</span>
                            </x-td>
                            <x-td align="center">
                                @if($batch->expiry_date)
                                    <div>
                                        <p class="font-medium {{ $batch->isExpired() ? 'text-danger' : '' }}">
                                            {{ $batch->expiry_date->format('d M Y') }}
                                        </p>
                                        @php $days = $batch->daysUntilExpiry(); @endphp
                                        @if($days !== null)
                                            <x-badge type="{{ $batch->getExpiryBadgeType() }}">
                                                @if($days < 0)
                                                    {{ __('inventory.expired') }} {{ abs($days) }} {{ __('inventory.days_until_expiry') }}
                                                @elseif($days === 0)
                                                    {{ __('inventory.expired') }} {{ __('inventory.date') }}
                                                @else
                                                    {{ $days }} {{ __('inventory.days_until_expiry') }}
                                                @endif
                                            </x-badge>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">{{ __('inventory.none') }}</span>
                                @endif
                            </x-td>
                            <x-td align="right">
                                <div>
                                    <p class="font-semibold">{{ number_format($batch->current_quantity, 2) }}</p>
                                    <p class="text-xs text-muted">{{ $batch->inventoryItem->unit->abbreviation }}</p>
                                </div>
                            </x-td>
                            <x-td align="right">
                                <span class="font-medium">Rp {{ number_format($batch->unit_cost, 0, ',', '.') }}</span>
                            </x-td>
                            <x-td align="center">
                                @php
                                    $statusColors = [
                                        'active' => 'success',
                                        'depleted' => 'secondary',
                                        'expired' => 'danger',
                                        'disposed' => 'warning',
                                    ];
                                @endphp
                                <x-badge type="{{ $statusColors[$batch->status] ?? 'secondary' }}" dot>
                                    {{ ucfirst($batch->status) }}
                                </x-badge>
                            </x-td>
                            <x-td align="center">
                                <div class="flex items-center justify-center gap-1">
                                    <x-button href="{{ route('inventory.batches.show', $batch) }}" size="sm" variant="ghost" icon="eye" />
                                    @if($batch->status === 'active')
                                        <form action="{{ route('inventory.batches.mark-expired', $batch) }}" method="POST" class="inline"
                                              onsubmit="return confirm('{{ __('inventory.confirm_delete_batch') }}')">
                                            @csrf
                                            @method('PATCH')
                                            <x-button type="submit" size="sm" variant="danger" icon="x-circle" :title="__('inventory.expired')" />
                                        </form>
                                    @endif
                                </div>
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </div>

            <div class="mt-6">
                <x-pagination :paginator="$batches" />
            </div>
        @else
            <x-empty-state
                :title="__('inventory.no_batches_found')"
                :description="__('inventory.batches_description')"
                icon="cube"
            >
                <x-button href="{{ route('inventory.batches.create') }}" icon="plus">
                    {{ __('inventory.add_batch') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
