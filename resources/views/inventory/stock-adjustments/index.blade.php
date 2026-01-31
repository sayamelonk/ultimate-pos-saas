<x-app-layout>
    <x-slot name="title">Stock Adjustments - Ultimate POS</x-slot>

    @section('page-title', 'Stock Adjustments')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Stock Adjustments</h2>
                <p class="text-muted mt-1">Manage stock adjustments and corrections</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.stock-adjustments.stock-take') }}" variant="outline-secondary" icon="clipboard-list">
                    Stock Take
                </x-button>
                <x-button href="{{ route('inventory.stock-adjustments.create') }}" icon="plus">
                    New Adjustment
                </x-button>
            </div>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.stock-adjustments.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search adjustment number or reference..."
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
                <x-select name="type" class="w-36">
                    <option value="">All Types</option>
                    <option value="addition" @selected(request('type') === 'addition')>Addition</option>
                    <option value="subtraction" @selected(request('type') === 'subtraction')>Subtraction</option>
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">All Status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'type', 'status']))
                    <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($adjustments->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Adjustment #</x-th>
                    <x-th>Date</x-th>
                    <x-th>Outlet</x-th>
                    <x-th>Type</x-th>
                    <x-th>Reason</x-th>
                    <x-th align="right">Items</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($adjustments as $adjustment)
                    <tr>
                        <x-td>
                            <a href="{{ route('inventory.stock-adjustments.show', $adjustment) }}" class="text-accent hover:underline font-medium">
                                {{ $adjustment->adjustment_number }}
                            </a>
                        </x-td>
                        <x-td>{{ $adjustment->adjustment_date->format('M d, Y') }}</x-td>
                        <x-td>{{ $adjustment->outlet->name }}</x-td>
                        <x-td>
                            @if($adjustment->type === 'addition')
                                <x-badge type="success">Addition</x-badge>
                            @else
                                <x-badge type="danger">Subtraction</x-badge>
                            @endif
                        </x-td>
                        <x-td>{{ Str::limit($adjustment->reason, 30) }}</x-td>
                        <x-td align="right">{{ $adjustment->items->count() }}</x-td>
                        <x-td align="center">
                            @switch($adjustment->status)
                                @case('pending')
                                    <x-badge type="warning">Pending</x-badge>
                                    @break
                                @case('approved')
                                    <x-badge type="success">Approved</x-badge>
                                    @break
                                @case('rejected')
                                    <x-badge type="danger">Rejected</x-badge>
                                    @break
                            @endswitch
                        </x-td>
                        <x-td align="right">
                            <x-dropdown align="right">
                                <x-slot name="trigger">
                                    <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                        <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                    </button>
                                </x-slot>

                                <x-dropdown-item href="{{ route('inventory.stock-adjustments.show', $adjustment) }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                    View Details
                                </x-dropdown-item>
                                @if($adjustment->status === 'pending')
                                    <x-dropdown-item href="{{ route('inventory.stock-adjustments.edit', $adjustment) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <form action="{{ route('inventory.stock-adjustments.approve', $adjustment) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button">
                                            <x-icon name="check" class="w-4 h-4" />
                                            Approve
                                        </x-dropdown-item>
                                    </form>
                                    <form action="{{ route('inventory.stock-adjustments.reject', $adjustment) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button" danger>
                                            <x-icon name="x" class="w-4 h-4" />
                                            Reject
                                        </x-dropdown-item>
                                    </form>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('open-delete-modal', {
                                            title: 'Delete Stock Adjustment',
                                            message: 'Are you sure you want to delete adjustment {{ $adjustment->adjustment_number }}? This action cannot be undone.',
                                            action: '{{ route('inventory.stock-adjustments.destroy', $adjustment) }}'
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                @endif
                            </x-dropdown>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$adjustments" />
            </div>
        @else
            <x-empty-state
                title="No stock adjustments found"
                description="Stock adjustments will appear here when you create them."
                icon="clipboard-list"
            >
                <x-button href="{{ route('inventory.stock-adjustments.create') }}" icon="plus">
                    New Adjustment
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
