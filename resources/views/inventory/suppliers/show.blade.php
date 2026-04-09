<x-app-layout>
    <x-slot name="title">{{ $supplier->name }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.supplier_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.suppliers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('inventory.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $supplier->name }}</h2>
                    <p class="text-muted mt-1">{{ $supplier->code }}</p>
                </div>
            </div>
            <x-button href="{{ route('inventory.suppliers.edit', $supplier) }}" variant="outline-secondary" icon="pencil">
                {{ __('inventory.edit') }}
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        <x-card :title="__('inventory.supplier_information')">
            <dl class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.name') }}</dt>
                    <dd class="mt-1 font-medium text-text">{{ $supplier->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.supplier_code') }}</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $supplier->code }}</code>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.contact_person') }}</dt>
                    <dd class="mt-1 text-text">{{ $supplier->contact_person ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.status') }}</dt>
                    <dd class="mt-1">
                        @if($supplier->is_active)
                            <x-badge type="success" dot>{{ __('inventory.active') }}</x-badge>
                        @else
                            <x-badge type="danger" dot>{{ __('inventory.inactive') }}</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.email') }}</dt>
                    <dd class="mt-1 text-text">{{ $supplier->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.phone') }}</dt>
                    <dd class="mt-1 text-text">{{ $supplier->phone ?? '-' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm text-muted">{{ __('inventory.address') }}</dt>
                    <dd class="mt-1 text-text">{{ $supplier->address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.city') }}</dt>
                    <dd class="mt-1 text-text">{{ $supplier->city ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.tax_number') }}</dt>
                    <dd class="mt-1 text-text">{{ $supplier->tax_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.payment_terms') }}</dt>
                    <dd class="mt-1 text-text">{{ $supplier->payment_terms ? __('inventory.lead_time_days', ['days' => $supplier->payment_terms]) : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.created') }}</dt>
                    <dd class="mt-1 text-text">{{ $supplier->created_at->format('M d, Y H:i') }}</dd>
                </div>
                @if($supplier->notes)
                    <div class="col-span-2">
                        <dt class="text-sm text-muted">{{ __('inventory.notes') }}</dt>
                        <dd class="mt-1 text-text">{{ $supplier->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        @if($supplier->supplierItems && $supplier->supplierItems->count() > 0)
            <x-card :title="__('inventory.supplied_items')">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.item') }}</x-th>
                        <x-th>{{ __('inventory.sku') }}</x-th>
                        <x-th align="right">{{ __('inventory.last_price') }}</x-th>
                        <x-th>{{ __('inventory.lead_time') }}</x-th>
                    </x-slot>

                    @foreach($supplier->supplierItems as $supplierItem)
                        <tr>
                            <x-td>
                                <p class="font-medium">{{ $supplierItem->inventoryItem->name }}</p>
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $supplierItem->inventoryItem->sku }}</code>
                            </x-td>
                            <x-td align="right">
                                Rp {{ number_format($supplierItem->last_price, 0, ',', '.') }}
                            </x-td>
                            <x-td>
                                {{ $supplierItem->lead_time_days ? __('inventory.lead_time_days', ['days' => $supplierItem->lead_time_days]) : '-' }}
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($supplier->purchaseOrders && $supplier->purchaseOrders->count() > 0)
            <x-card :title="__('inventory.recent_purchase_orders')">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.po_number') }}</x-th>
                        <x-th>{{ __('inventory.date') }}</x-th>
                        <x-th align="right">{{ __('inventory.total') }}</x-th>
                        <x-th align="center">{{ __('inventory.status') }}</x-th>
                    </x-slot>

                    @foreach($supplier->purchaseOrders as $po)
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.purchase-orders.show', $po) }}" class="text-accent hover:underline">
                                    {{ $po->po_number }}
                                </a>
                            </x-td>
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
                                        <x-badge type="success">{{ __('inventory.received') }}</x-badge>
                                        @break
                                    @case('cancelled')
                                        <x-badge type="danger">{{ __('inventory.cancelled') }}</x-badge>
                                        @break
                                @endswitch
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    </div>
</x-app-layout>
