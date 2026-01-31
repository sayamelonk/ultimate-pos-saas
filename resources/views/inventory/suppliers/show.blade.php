<x-app-layout>
    <x-slot name="title">{{ $supplier->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Supplier Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.suppliers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $supplier->name }}</h2>
                    <p class="text-muted mt-1">{{ $supplier->code }}</p>
                </div>
            </div>
            <x-button href="{{ route('inventory.suppliers.edit', $supplier) }}" variant="outline-secondary" icon="pencil">
                Edit
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        <x-card title="Supplier Information">
            <dl class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm text-muted">Name</dt>
                    <dd class="mt-1 font-medium text-text">{{ $supplier->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Code</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $supplier->code }}</code>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Contact Person</dt>
                    <dd class="mt-1 text-text">{{ $supplier->contact_person ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Status</dt>
                    <dd class="mt-1">
                        @if($supplier->is_active)
                            <x-badge type="success" dot>Active</x-badge>
                        @else
                            <x-badge type="danger" dot>Inactive</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Email</dt>
                    <dd class="mt-1 text-text">{{ $supplier->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Phone</dt>
                    <dd class="mt-1 text-text">{{ $supplier->phone ?? '-' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm text-muted">Address</dt>
                    <dd class="mt-1 text-text">{{ $supplier->address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">City</dt>
                    <dd class="mt-1 text-text">{{ $supplier->city ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Tax Number</dt>
                    <dd class="mt-1 text-text">{{ $supplier->tax_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Payment Terms</dt>
                    <dd class="mt-1 text-text">{{ $supplier->payment_terms ? $supplier->payment_terms . ' days' : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Created</dt>
                    <dd class="mt-1 text-text">{{ $supplier->created_at->format('M d, Y H:i') }}</dd>
                </div>
                @if($supplier->notes)
                    <div class="col-span-2">
                        <dt class="text-sm text-muted">Notes</dt>
                        <dd class="mt-1 text-text">{{ $supplier->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        @if($supplier->supplierItems && $supplier->supplierItems->count() > 0)
            <x-card title="Supplied Items">
                <x-table>
                    <x-slot name="head">
                        <x-th>Item</x-th>
                        <x-th>SKU</x-th>
                        <x-th align="right">Last Price</x-th>
                        <x-th>Lead Time</x-th>
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
                                {{ $supplierItem->lead_time_days ? $supplierItem->lead_time_days . ' days' : '-' }}
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($supplier->purchaseOrders && $supplier->purchaseOrders->count() > 0)
            <x-card title="Recent Purchase Orders">
                <x-table>
                    <x-slot name="head">
                        <x-th>PO Number</x-th>
                        <x-th>Date</x-th>
                        <x-th align="right">Total</x-th>
                        <x-th align="center">Status</x-th>
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
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    </div>
</x-app-layout>
