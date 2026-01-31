<x-app-layout>
    <x-slot name="title">Waste Logs - Ultimate POS</x-slot>

    @section('page-title', 'Waste Logs')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Waste Logs</h2>
                <p class="text-muted mt-1">Track and manage inventory waste</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.waste-logs.report') }}" variant="outline-secondary" icon="chart-bar">
                    Waste Report
                </x-button>
                <x-button href="{{ route('inventory.waste-logs.create') }}" icon="plus">
                    Log Waste
                </x-button>
            </div>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.waste-logs.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search item name..."
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
                <x-select name="reason" class="w-40">
                    <option value="">All Reasons</option>
                    <option value="expired" @selected(request('reason') === 'expired')>Expired</option>
                    <option value="damaged" @selected(request('reason') === 'damaged')>Damaged</option>
                    <option value="spoiled" @selected(request('reason') === 'spoiled')>Spoiled</option>
                    <option value="overproduction" @selected(request('reason') === 'overproduction')>Overproduction</option>
                    <option value="quality_issue" @selected(request('reason') === 'quality_issue')>Quality Issue</option>
                    <option value="other" @selected(request('reason') === 'other')>Other</option>
                </x-select>
                <x-input
                    type="date"
                    name="date_from"
                    :value="request('date_from')"
                    class="w-40"
                />
                <x-input
                    type="date"
                    name="date_to"
                    :value="request('date_to')"
                    class="w-40"
                />
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'reason', 'date_from', 'date_to']))
                    <x-button href="{{ route('inventory.waste-logs.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="p-4 bg-secondary-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-text">{{ $wasteLogs->total() }}</p>
                <p class="text-sm text-muted">Total Records</p>
            </div>
            <div class="p-4 bg-danger-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-danger-600">Rp {{ number_format($totalValue ?? 0, 0, ',', '.') }}</p>
                <p class="text-sm text-muted">Total Value Lost</p>
            </div>
            <div class="p-4 bg-warning-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-warning-600">{{ number_format($totalQuantity ?? 0, 2) }}</p>
                <p class="text-sm text-muted">Total Qty Wasted</p>
            </div>
            <div class="p-4 bg-secondary-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-text">{{ $topReason ?? '-' }}</p>
                <p class="text-sm text-muted">Top Reason</p>
            </div>
        </div>

        <!-- Table -->
        @if($wasteLogs->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Date</x-th>
                    <x-th>Item</x-th>
                    <x-th>Outlet</x-th>
                    <x-th align="right">Quantity</x-th>
                    <x-th align="right">Value</x-th>
                    <x-th>Reason</x-th>
                    <x-th>Logged By</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($wasteLogs as $log)
                    <tr>
                        <x-td>{{ $log->waste_date->format('M d, Y') }}</x-td>
                        <x-td>
                            <p class="font-medium">{{ $log->inventoryItem->name }}</p>
                            <p class="text-xs text-muted">{{ $log->inventoryItem->sku }}</p>
                        </x-td>
                        <x-td>{{ $log->outlet->name }}</x-td>
                        <x-td align="right">
                            <span class="text-danger-600 font-medium">-{{ number_format($log->quantity, 2) }}</span>
                            {{ $log->inventoryItem->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right" class="font-medium text-danger-600">
                            Rp {{ number_format($log->value, 0, ',', '.') }}
                        </x-td>
                        <x-td>
                            @switch($log->reason)
                                @case('expired')
                                    <x-badge type="danger">Expired</x-badge>
                                    @break
                                @case('damaged')
                                    <x-badge type="warning">Damaged</x-badge>
                                    @break
                                @case('spoiled')
                                    <x-badge type="danger">Spoiled</x-badge>
                                    @break
                                @case('overproduction')
                                    <x-badge type="info">Overproduction</x-badge>
                                    @break
                                @case('quality_issue')
                                    <x-badge type="warning">Quality Issue</x-badge>
                                    @break
                                @default
                                    <x-badge type="secondary">Other</x-badge>
                            @endswitch
                        </x-td>
                        <x-td>{{ $log->loggedBy->name ?? '-' }}</x-td>
                        <x-td align="right">
                            <x-dropdown align="right">
                                <x-slot name="trigger">
                                    <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                        <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                    </button>
                                </x-slot>

                                <x-dropdown-item href="{{ route('inventory.waste-logs.show', $log) }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                    View Details
                                </x-dropdown-item>
                                <x-dropdown-item
                                    type="button"
                                    danger
                                    @click="$dispatch('open-delete-modal', {
                                        title: 'Delete Waste Log',
                                        message: 'Are you sure you want to delete this waste log? This action cannot be undone.',
                                        action: '{{ route('inventory.waste-logs.destroy', $log) }}'
                                    })"
                                >
                                    <x-icon name="trash" class="w-4 h-4" />
                                    Delete
                                </x-dropdown-item>
                            </x-dropdown>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$wasteLogs" />
            </div>
        @else
            <x-empty-state
                title="No waste logs found"
                description="Waste logs help you track and analyze inventory losses."
                icon="trash"
            >
                <x-button href="{{ route('inventory.waste-logs.create') }}" icon="plus">
                    Log Waste
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
