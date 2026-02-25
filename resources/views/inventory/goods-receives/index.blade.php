<x-app-layout>
    <x-slot name="title">Goods Receives - Ultimate POS</x-slot>

    @section('page-title', 'Goods Receives')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Goods Receives</h2>
                <p class="text-muted mt-1">Manage received inventory from purchase orders</p>
            </div>
            <x-button href="{{ route('inventory.goods-receives.create') }}" icon="plus">
                New Goods Receive
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.goods-receives.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search GR or PO number..."
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
                <x-select name="status" class="w-36">
                    <option value="">All Status</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'status']))
                    <x-button href="{{ route('inventory.goods-receives.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($goodsReceives->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>GR Number</x-th>
                    <x-th>PO Number</x-th>
                    <x-th>Supplier</x-th>
                    <x-th>Outlet</x-th>
                    <x-th>Date</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($goodsReceives as $gr)
                    <tr>
                        <x-td>
                            <a href="{{ route('inventory.goods-receives.show', $gr) }}" class="text-accent hover:underline font-medium">
                                {{ $gr->gr_number }}
                            </a>
                        </x-td>
                        <x-td>
                            <a href="{{ route('inventory.purchase-orders.show', $gr->purchaseOrder) }}" class="text-accent hover:underline">
                                {{ $gr->purchaseOrder->po_number }}
                            </a>
                        </x-td>
                        <x-td>{{ $gr->purchaseOrder->supplier->name }}</x-td>
                        <x-td>{{ $gr->outlet->name }}</x-td>
                        <x-td>{{ $gr->receive_date->format('M d, Y') }}</x-td>
                        <x-td align="center">
                            @switch($gr->status)
                                @case('draft')
                                    <x-badge type="secondary">Draft</x-badge>
                                    @break
                                @case('completed')
                                    <x-badge type="success">Completed</x-badge>
                                    @break
                                @case('cancelled')
                                    <x-badge type="danger">Cancelled</x-badge>
                                    @break
                            @endswitch
                        </x-td>
                        <x-td align="right">
                            <div x-data>
                                <x-dropdown align="right">
                                    <x-slot name="trigger">
                                        <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                            <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                        </button>
                                    </x-slot>

                                    <x-dropdown-item href="{{ route('inventory.goods-receives.show', $gr) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    @if($gr->status === 'draft')
                                        <x-dropdown-item href="{{ route('inventory.goods-receives.edit', $gr) }}">
                                            <x-icon name="pencil" class="w-4 h-4" />
                                            Edit
                                        </x-dropdown-item>
                                        <form action="{{ route('inventory.goods-receives.complete', $gr) }}" method="POST" class="w-full">
                                            @csrf
                                            <x-dropdown-item type="button">
                                                <x-icon name="check" class="w-4 h-4" />
                                                Complete
                                            </x-dropdown-item>
                                        </form>
                                        <x-dropdown-item
                                            type="button"
                                            danger
                                            @click="$dispatch('confirm', {
                                                title: 'Delete Goods Receive',
                                                message: 'Are you sure you want to delete GR {{ $gr->gr_number }}? This action cannot be undone.',
                                                confirmText: 'Delete',
                                                variant: 'danger',
                                                onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                            })"
                                        >
                                            <x-icon name="trash" class="w-4 h-4" />
                                            Delete
                                        </x-dropdown-item>
                                    @endif
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.goods-receives.destroy', $gr) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$goodsReceives" />
            </div>
        @else
            <x-empty-state
                title="No goods receives found"
                description="Goods receives will appear here when you receive inventory from purchase orders."
                icon="truck"
            >
                <x-button href="{{ route('inventory.goods-receives.create') }}" icon="plus">
                    New Goods Receive
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
