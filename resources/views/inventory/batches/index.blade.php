<x-app-layout>
    <x-slot name="title">Stock Batches - Ultimate POS</x-slot>

    @section('page-title', 'Stock Batches')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Stock Batches</h2>
                <p class="text-muted mt-1">Track batch numbers and expiry dates</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button href="{{ route('inventory.batches.settings') }}" variant="secondary" icon="cog-6-tooth">
                    Settings
                </x-button>
                <x-button href="{{ route('inventory.batches.expiry-report') }}" variant="warning" icon="exclamation-triangle">
                    Expiry Report
                </x-button>
                <x-button href="{{ route('inventory.batches.create') }}" icon="plus">
                    Add Batch
                </x-button>
            </div>
        </div>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            title="Active Batches"
            :value="$stats['total_batches']"
            icon="cube"
        />
        <x-stat-card
            title="Expiring Soon"
            :value="$stats['expiring_soon']"
            icon="clock"
            :color="$stats['expiring_soon'] > 0 ? 'warning' : 'secondary'"
        />
        <x-stat-card
            title="Critical ({{ $settings->expiry_critical_days }} days)"
            :value="$stats['critical']"
            icon="exclamation-triangle"
            :color="$stats['critical'] > 0 ? 'danger' : 'secondary'"
        />
        <x-stat-card
            title="Already Expired"
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
                    <label class="block text-xs font-medium text-muted mb-1">Search</label>
                    <x-input
                        name="search"
                        placeholder="Batch number, item name..."
                        :value="request('search')"
                    />
                </div>

                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">Outlet</label>
                    <x-select name="outlet_id">
                        <option value="">All Outlets</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="w-44">
                    <label class="block text-xs font-medium text-muted mb-1">Item</label>
                    <x-select name="item_id">
                        <option value="">All Items</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="w-36">
                    <label class="block text-xs font-medium text-muted mb-1">Status</label>
                    <x-select name="status">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="depleted" @selected(request('status') === 'depleted')>Depleted</option>
                        <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                        <option value="disposed" @selected(request('status') === 'disposed')>Disposed</option>
                    </x-select>
                </div>

                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">Expiry</label>
                    <x-select name="expiry_filter">
                        <option value="">All</option>
                        <option value="expired" @selected(request('expiry_filter') === 'expired')>Expired</option>
                        <option value="critical" @selected(request('expiry_filter') === 'critical')>Critical</option>
                        <option value="warning" @selected(request('expiry_filter') === 'warning')>Warning</option>
                        <option value="no_expiry" @selected(request('expiry_filter') === 'no_expiry')>No Expiry</option>
                    </x-select>
                </div>

                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" icon="search">
                        Filter
                    </x-button>
                    @if(request()->hasAny(['search', 'outlet_id', 'item_id', 'status', 'expiry_filter']))
                        <x-button href="{{ route('inventory.batches.index') }}" variant="ghost">
                            Clear
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
                        <x-th>Batch / Item</x-th>
                        <x-th>Outlet</x-th>
                        <x-th align="center">Expiry Date</x-th>
                        <x-th align="right">Quantity</x-th>
                        <x-th align="right">Unit Cost</x-th>
                        <x-th align="center">Status</x-th>
                        <x-th align="center">Actions</x-th>
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
                                        <p class="text-xs text-muted">Supplier: {{ $batch->supplier_batch_number }}</p>
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
                                                    Expired {{ abs($days) }} days ago
                                                @elseif($days === 0)
                                                    Expires today
                                                @else
                                                    {{ $days }} days left
                                                @endif
                                            </x-badge>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">No expiry</span>
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
                                              onsubmit="return confirm('Mark this batch as expired?')">
                                            @csrf
                                            @method('PATCH')
                                            <x-button type="submit" size="sm" variant="danger" icon="x-circle" title="Mark Expired" />
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
                title="No batches found"
                description="No stock batches match your filters."
                icon="cube"
            >
                <x-button href="{{ route('inventory.batches.create') }}" icon="plus">
                    Add First Batch
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
