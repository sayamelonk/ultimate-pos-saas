<x-app-layout>
    <x-slot name="title">Stock Transfers - Ultimate POS</x-slot>

    @section('page-title', 'Stock Transfers')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Stock Transfers</h2>
                <p class="text-muted mt-1">Transfer stock between outlets</p>
            </div>
            <x-button href="{{ route('inventory.stock-transfers.create') }}" icon="plus">
                New Transfer
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.stock-transfers.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search transfer number..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="from_outlet_id" class="w-48">
                    <option value="">All Source Outlets</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('from_outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="to_outlet_id" class="w-48">
                    <option value="">All Destination Outlets</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('to_outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">All Status</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="in_transit" @selected(request('status') === 'in_transit')>In Transit</option>
                    <option value="received" @selected(request('status') === 'received')>Received</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'from_outlet_id', 'to_outlet_id', 'status']))
                    <x-button href="{{ route('inventory.stock-transfers.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($transfers->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Transfer #</x-th>
                    <x-th>Date</x-th>
                    <x-th>From</x-th>
                    <x-th>To</x-th>
                    <x-th align="right">Items</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($transfers as $transfer)
                    <tr>
                        <x-td>
                            <a href="{{ route('inventory.stock-transfers.show', $transfer) }}" class="text-accent hover:underline font-medium">
                                {{ $transfer->transfer_number }}
                            </a>
                        </x-td>
                        <x-td>{{ $transfer->transfer_date->format('M d, Y') }}</x-td>
                        <x-td>{{ $transfer->fromOutlet->name }}</x-td>
                        <x-td>{{ $transfer->toOutlet->name }}</x-td>
                        <x-td align="right">{{ $transfer->items->count() }}</x-td>
                        <x-td align="center">
                            @switch($transfer->status)
                                @case('draft')
                                    <x-badge type="secondary">Draft</x-badge>
                                    @break
                                @case('approved')
                                    <x-badge type="info">Approved</x-badge>
                                    @break
                                @case('in_transit')
                                    <x-badge type="warning">In Transit</x-badge>
                                    @break
                                @case('received')
                                    <x-badge type="success">Received</x-badge>
                                    @break
                                @case('cancelled')
                                    <x-badge type="danger">Cancelled</x-badge>
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

                                <x-dropdown-item href="{{ route('inventory.stock-transfers.show', $transfer) }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                    View Details
                                </x-dropdown-item>
                                @if($transfer->status === 'draft')
                                    <x-dropdown-item href="{{ route('inventory.stock-transfers.edit', $transfer) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <form action="{{ route('inventory.stock-transfers.approve', $transfer) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button">
                                            <x-icon name="check" class="w-4 h-4" />
                                            Approve
                                        </x-dropdown-item>
                                    </form>
                                    <form action="{{ route('inventory.stock-transfers.cancel', $transfer) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button" danger>
                                            <x-icon name="x" class="w-4 h-4" />
                                            Cancel
                                        </x-dropdown-item>
                                    </form>
                                @endif
                                @if($transfer->status === 'approved')
                                    <form action="{{ route('inventory.stock-transfers.ship', $transfer) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button">
                                            <x-icon name="truck" class="w-4 h-4" />
                                            Mark as Shipped
                                        </x-dropdown-item>
                                    </form>
                                @endif
                                @if($transfer->status === 'in_transit')
                                    <form action="{{ route('inventory.stock-transfers.receive', $transfer) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button">
                                            <x-icon name="check-circle" class="w-4 h-4" />
                                            Mark as Received
                                        </x-dropdown-item>
                                    </form>
                                @endif
                            </x-dropdown>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$transfers" />
            </div>
        @else
            <x-empty-state
                title="No stock transfers found"
                description="Stock transfers will appear here when you create them."
                icon="arrows-right-left"
            >
                <x-button href="{{ route('inventory.stock-transfers.create') }}" icon="plus">
                    New Transfer
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
