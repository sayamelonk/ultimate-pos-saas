<x-app-layout>
    <x-slot name="title">{{ __('inventory.purchase_orders') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.purchase_orders'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.purchase_orders') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.manage_purchase_orders') }}</p>
            </div>
            <x-button href="{{ route('inventory.purchase-orders.create') }}" icon="plus">
                {{ __('inventory.create_po') }}
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
                        :placeholder="__('inventory.search_po')"
                        :value="request('search')"
                    />
                </div>
                <x-select name="supplier_id" class="w-48">
                    <option value="">{{ __('inventory.all_suppliers') }}</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="outlet_id" class="w-40">
                    <option value="">{{ __('inventory.all_outlets') }}</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">{{ __('inventory.all_status') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>{{ __('inventory.draft') }}</option>
                    <option value="approved" @selected(request('status') === 'approved')>{{ __('inventory.approved') }}</option>
                    <option value="sent" @selected(request('status') === 'sent')>{{ __('inventory.sent') }}</option>
                    <option value="partial" @selected(request('status') === 'partial')>{{ __('inventory.partial') }}</option>
                    <option value="received" @selected(request('status') === 'received')>{{ __('inventory.completed') }}</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>{{ __('inventory.cancelled') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('inventory.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'supplier_id', 'outlet_id', 'status']))
                    <x-button href="{{ route('inventory.purchase-orders.index') }}" variant="ghost">
                        {{ __('app.clear') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($purchaseOrders->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.po_number') }}</x-th>
                    <x-th>{{ __('inventory.supplier') }}</x-th>
                    <x-th>{{ __('inventory.outlet') }}</x-th>
                    <x-th>{{ __('inventory.date') }}</x-th>
                    <x-th align="right">{{ __('inventory.total') }}</x-th>
                    <x-th align="center">{{ __('inventory.status') }}</x-th>
                    <x-th align="right">{{ __('inventory.actions') }}</x-th>
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
                                    <x-badge type="secondary">{{ __('inventory.draft') }}</x-badge>
                                    @break
                                @case('approved')
                                    <x-badge type="info">{{ __('inventory.approved') }}</x-badge>
                                    @break
                                @case('sent')
                                    <x-badge type="warning">{{ __('inventory.sent') }}</x-badge>
                                    @break
                                @case('partial')
                                    <x-badge type="warning">{{ __('inventory.partial') }}</x-badge>
                                    @break
                                @case('received')
                                    <x-badge type="success">{{ __('inventory.completed') }}</x-badge>
                                    @break
                                @case('cancelled')
                                    <x-badge type="danger">{{ __('inventory.cancelled') }}</x-badge>
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

                                    <x-dropdown-item href="{{ route('inventory.purchase-orders.show', $po) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('inventory.view_details') }}
                                    </x-dropdown-item>
                                    @if($po->status === 'draft')
                                        <x-dropdown-item href="{{ route('inventory.purchase-orders.edit', $po) }}">
                                            <x-icon name="pencil" class="w-4 h-4" />
                                            {{ __('inventory.edit') }}
                                        </x-dropdown-item>
                                    @endif
                                    @if(in_array($po->status, ['approved', 'sent', 'partial']))
                                        <x-dropdown-item href="{{ route('inventory.goods-receives.create', ['purchase_order_id' => $po->id]) }}">
                                            <x-icon name="truck" class="w-4 h-4" />
                                            {{ __('inventory.receive_goods') }}
                                        </x-dropdown-item>
                                    @endif
                                    @if($po->status === 'draft')
                                        <x-dropdown-item
                                            type="button"
                                            danger
                                            @click="$dispatch('confirm', {
                                                title: '{{ __('inventory.delete_po') }}',
                                                message: '{{ __('inventory.confirm_delete_po', ['number' => $po->po_number]) }}',
                                                confirmText: '{{ __('inventory.delete') }}',
                                                variant: 'danger',
                                                onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                            })"
                                        >
                                            <x-icon name="trash" class="w-4 h-4" />
                                            {{ __('inventory.delete') }}
                                        </x-dropdown-item>
                                    @endif
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.purchase-orders.destroy', $po) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$purchaseOrders" />
            </div>
        @else
            <x-empty-state
                :title="__('inventory.no_po_found')"
                :description="__('inventory.no_po_description')"
                icon="document-text"
            >
                <x-button href="{{ route('inventory.purchase-orders.create') }}" icon="plus">
                    {{ __('inventory.create_po') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
