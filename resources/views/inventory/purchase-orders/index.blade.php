<x-app-layout>
    <x-slot name="title">Purchase Orders - Ultimate POS</x-slot>

    @section('page-title', 'Purchase Orders')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Purchase Orders</h2>
                <p class="text-muted mt-1">Manage your purchase orders to suppliers</p>
            </div>
            <x-button href="{{ route('inventory.purchase-orders.create') }}" icon="plus">
                Create PO
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.purchase-orders.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search PO number or supplier..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="supplier_id" class="w-48">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="outlet_id" class="w-40">
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
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="sent" @selected(request('status') === 'sent')>Sent</option>
                    <option value="partial" @selected(request('status') === 'partial')>Partial</option>
                    <option value="received" @selected(request('status') === 'received')>Received</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'supplier_id', 'outlet_id', 'status']))
                    <x-button href="{{ route('inventory.purchase-orders.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($purchaseOrders->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>PO Number</x-th>
                    <x-th>Supplier</x-th>
                    <x-th>Outlet</x-th>
                    <x-th>Date</x-th>
                    <x-th align="right">Total</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($purchaseOrders as $po)
                    <tr>
                        <x-td>
                            <a href="{{ route('inventory.purchase-orders.show', $po) }}" class="text-accent hover:underline font-medium">
                                {{ $po->po_number }}
                            </a>
                        </x-td>
                        <x-td>{{ $po->supplier->name }}</x-td>
                        <x-td>{{ $po->outlet->name }}</x-td>
                        <x-td>{{ $po->order_date->format('M d, Y') }}</x-td>
                        <x-td align="right">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</x-td>
                        <x-td align="center">
                            @switch($po->status)
                                @case('draft')
                                    <x-badge type="secondary">Draft</x-badge>
                                    @break
                                @case('approved')
                                    <x-badge type="info">Approved</x-badge>
                                    @break
                                @case('sent')
                                    <x-badge type="warning">Sent</x-badge>
                                    @break
                                @case('partial')
                                    <x-badge type="warning">Partial</x-badge>
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

                                <x-dropdown-item href="{{ route('inventory.purchase-orders.show', $po) }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                    View Details
                                </x-dropdown-item>
                                @if($po->status === 'draft')
                                    <x-dropdown-item href="{{ route('inventory.purchase-orders.edit', $po) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                @endif
                                @if(in_array($po->status, ['approved', 'sent', 'partial']))
                                    <x-dropdown-item href="{{ route('inventory.goods-receives.create', ['purchase_order_id' => $po->id]) }}">
                                        <x-icon name="truck" class="w-4 h-4" />
                                        Receive Goods
                                    </x-dropdown-item>
                                @endif
                                @if($po->status === 'draft')
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('open-delete-modal', {
                                            title: 'Delete Purchase Order',
                                            message: 'Are you sure you want to delete PO {{ $po->po_number }}? This action cannot be undone.',
                                            action: '{{ route('inventory.purchase-orders.destroy', $po) }}'
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
                <x-pagination :paginator="$purchaseOrders" />
            </div>
        @else
            <x-empty-state
                title="No purchase orders found"
                description="Get started by creating your first purchase order."
                icon="document-text"
            >
                <x-button href="{{ route('inventory.purchase-orders.create') }}" icon="plus">
                    Create PO
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
