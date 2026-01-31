<x-app-layout>
    <x-slot name="title">{{ $purchaseOrder->po_number }} - Ultimate POS</x-slot>

    @section('page-title', 'Purchase Order Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.purchase-orders.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $purchaseOrder->po_number }}</h2>
                    <p class="text-muted mt-1">{{ $purchaseOrder->supplier->name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                @if($purchaseOrder->status === 'draft')
                    <form action="{{ route('inventory.purchase-orders.approve', $purchaseOrder) }}" method="POST" class="inline">
                        @csrf
                        <x-button type="submit" variant="success" icon="check">
                            Approve
                        </x-button>
                    </form>
                    <x-button href="{{ route('inventory.purchase-orders.edit', $purchaseOrder) }}" variant="outline-secondary" icon="pencil">
                        Edit
                    </x-button>
                @elseif($purchaseOrder->status === 'approved')
                    <form action="{{ route('inventory.purchase-orders.send', $purchaseOrder) }}" method="POST" class="inline">
                        @csrf
                        <x-button type="submit" icon="paper-airplane">
                            Mark as Sent
                        </x-button>
                    </form>
                @endif
                @if(in_array($purchaseOrder->status, ['approved', 'sent', 'partial']))
                    <x-button href="{{ route('inventory.goods-receives.create', ['purchase_order_id' => $purchaseOrder->id]) }}" variant="success" icon="truck">
                        Receive Goods
                    </x-button>
                @endif
                @if(in_array($purchaseOrder->status, ['draft', 'approved', 'sent']))
                    <form action="{{ route('inventory.purchase-orders.cancel', $purchaseOrder) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to cancel this PO?')">
                        @csrf
                        <x-button type="submit" variant="outline-danger" icon="x">
                            Cancel
                        </x-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Status Banner -->
        <div class="p-4 rounded-lg @switch($purchaseOrder->status) @case('draft') bg-secondary-100 @break @case('approved') bg-info-100 @break @case('sent') bg-warning-100 @break @case('partial') bg-warning-100 @break @case('received') bg-success-100 @break @case('cancelled') bg-danger-100 @break @endswitch">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @switch($purchaseOrder->status)
                        @case('draft')
                            <x-icon name="document" class="w-6 h-6 text-secondary-600" />
                            <span class="font-medium text-secondary-700">Draft - Pending approval</span>
                            @break
                        @case('approved')
                            <x-icon name="check-circle" class="w-6 h-6 text-info-600" />
                            <span class="font-medium text-info-700">Approved - Ready to send</span>
                            @break
                        @case('sent')
                            <x-icon name="paper-airplane" class="w-6 h-6 text-warning-600" />
                            <span class="font-medium text-warning-700">Sent - Awaiting delivery</span>
                            @break
                        @case('partial')
                            <x-icon name="clock" class="w-6 h-6 text-warning-600" />
                            <span class="font-medium text-warning-700">Partially Received</span>
                            @break
                        @case('received')
                            <x-icon name="check" class="w-6 h-6 text-success-600" />
                            <span class="font-medium text-success-700">Fully Received</span>
                            @break
                        @case('cancelled')
                            <x-icon name="x-circle" class="w-6 h-6 text-danger-600" />
                            <span class="font-medium text-danger-700">Cancelled</span>
                            @break
                    @endswitch
                </div>
                @if($purchaseOrder->approvedBy)
                    <span class="text-sm text-muted">Approved by {{ $purchaseOrder->approvedBy->name }} on {{ $purchaseOrder->approved_at?->format('M d, Y') }}</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <x-card title="Order Information">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">PO Number</dt>
                        <dd class="font-medium">{{ $purchaseOrder->po_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Order Date</dt>
                        <dd>{{ $purchaseOrder->order_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Expected Date</dt>
                        <dd>{{ $purchaseOrder->expected_date?->format('M d, Y') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Outlet</dt>
                        <dd>{{ $purchaseOrder->outlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Created By</dt>
                        <dd>{{ $purchaseOrder->createdBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Supplier Information">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">Supplier</dt>
                        <dd class="font-medium">{{ $purchaseOrder->supplier->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Contact</dt>
                        <dd>{{ $purchaseOrder->supplier->contact_person ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Phone</dt>
                        <dd>{{ $purchaseOrder->supplier->phone ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Email</dt>
                        <dd>{{ $purchaseOrder->supplier->email ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Payment Terms</dt>
                        <dd>{{ $purchaseOrder->supplier->payment_terms ? $purchaseOrder->supplier->payment_terms . ' days' : '-' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <x-card title="Order Items">
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th align="right">Qty</x-th>
                    <x-th align="right">Unit Price</x-th>
                    <x-th align="right">Total</x-th>
                    @if($purchaseOrder->status !== 'draft')
                        <x-th align="right">Received</x-th>
                    @endif
                </x-slot>

                @foreach($purchaseOrder->items as $item)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $item->inventoryItem->name }}</p>
                            @if($item->notes)
                                <p class="text-xs text-muted">{{ $item->notes }}</p>
                            @endif
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">{{ number_format($item->quantity, 2) }} {{ $item->inventoryItem->unit->abbreviation ?? '' }}</x-td>
                        <x-td align="right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</x-td>
                        <x-td align="right">Rp {{ number_format($item->total_price, 0, ',', '.') }}</x-td>
                        @if($purchaseOrder->status !== 'draft')
                            <x-td align="right">
                                @php
                                    $receivedQty = $item->quantity_received ?? 0;
                                @endphp
                                <span class="{{ $receivedQty >= $item->quantity ? 'text-success-600' : ($receivedQty > 0 ? 'text-warning-600' : 'text-muted') }}">
                                    {{ number_format($receivedQty, 2) }}
                                </span>
                            </x-td>
                        @endif
                    </tr>
                @endforeach

                <tr class="bg-secondary-50">
                    <x-td colspan="{{ $purchaseOrder->status !== 'draft' ? 4 : 3 }}" class="text-right font-medium">Subtotal</x-td>
                    <x-td align="right" class="font-medium">Rp {{ number_format($purchaseOrder->subtotal, 0, ',', '.') }}</x-td>
                    @if($purchaseOrder->status !== 'draft')
                        <x-td></x-td>
                    @endif
                </tr>
                @if($purchaseOrder->tax_amount > 0)
                    <tr class="bg-secondary-50">
                        <x-td colspan="{{ $purchaseOrder->status !== 'draft' ? 4 : 3 }}" class="text-right">Tax</x-td>
                        <x-td align="right">Rp {{ number_format($purchaseOrder->tax_amount, 0, ',', '.') }}</x-td>
                        @if($purchaseOrder->status !== 'draft')
                            <x-td></x-td>
                        @endif
                    </tr>
                @endif
                <tr class="bg-secondary-100">
                    <x-td colspan="{{ $purchaseOrder->status !== 'draft' ? 4 : 3 }}" class="text-right font-bold">Total</x-td>
                    <x-td align="right" class="font-bold">Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</x-td>
                    @if($purchaseOrder->status !== 'draft')
                        <x-td></x-td>
                    @endif
                </tr>
            </x-table>
        </x-card>

        @if($purchaseOrder->notes)
            <x-card title="Notes">
                <p class="text-text">{{ $purchaseOrder->notes }}</p>
            </x-card>
        @endif

        @if($purchaseOrder->goodsReceives && $purchaseOrder->goodsReceives->count() > 0)
            <x-card title="Goods Receives">
                <x-table>
                    <x-slot name="head">
                        <x-th>GR Number</x-th>
                        <x-th>Date</x-th>
                        <x-th>Invoice</x-th>
                        <x-th align="center">Status</x-th>
                        <x-th align="right">Actions</x-th>
                    </x-slot>

                    @foreach($purchaseOrder->goodsReceives as $gr)
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.goods-receives.show', $gr) }}" class="text-accent hover:underline font-medium">
                                    {{ $gr->gr_number }}
                                </a>
                            </x-td>
                            <x-td>{{ $gr->receive_date->format('M d, Y') }}</x-td>
                            <x-td>{{ $gr->invoice_number ?? '-' }}</x-td>
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
                                <x-button href="{{ route('inventory.goods-receives.show', $gr) }}" variant="ghost" size="sm" icon="eye">
                                    View
                                </x-button>
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    </div>
</x-app-layout>
