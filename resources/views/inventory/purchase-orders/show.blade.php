<x-app-layout>
    <x-slot name="title">{{ $purchaseOrder->po_number }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.po_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.purchase-orders.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('app.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $purchaseOrder->po_number }}</h2>
                    <p class="text-muted mt-1">{{ $purchaseOrder->supplier->name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                @if($purchaseOrder->status === 'draft')
                    <x-button variant="success" icon="check" @click="$dispatch('open-modal', 'approve-po')">
                        {{ __('app.approve') }}
                    </x-button>
                    <x-button href="{{ route('inventory.purchase-orders.edit', $purchaseOrder) }}" variant="outline-secondary" icon="pencil">
                        {{ __('app.edit') }}
                    </x-button>
                @elseif($purchaseOrder->status === 'approved')
                    <x-button icon="paper-airplane" @click="$dispatch('open-modal', 'send-po')">
                        {{ __('inventory.mark_as_sent') }}
                    </x-button>
                @endif
                @if(in_array($purchaseOrder->status, ['approved', 'sent', 'partial']))
                    <x-button href="{{ route('inventory.goods-receives.create', ['purchase_order_id' => $purchaseOrder->id]) }}" variant="success" icon="truck">
                        {{ __('inventory.receive_goods') }}
                    </x-button>
                @endif
                @if(in_array($purchaseOrder->status, ['draft', 'approved', 'sent']))
                    <x-button variant="outline-danger" icon="x" @click="$dispatch('open-modal', 'cancel-po')">
                        {{ __('app.cancel') }}
                    </x-button>
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
                            <span class="font-medium text-secondary-700">{{ __('inventory.status_draft_pending') }}</span>
                            @break
                        @case('approved')
                            <x-icon name="check-circle" class="w-6 h-6 text-info-600" />
                            <span class="font-medium text-info-700">{{ __('inventory.status_approved_ready') }}</span>
                            @break
                        @case('sent')
                            <x-icon name="paper-airplane" class="w-6 h-6 text-warning-600" />
                            <span class="font-medium text-warning-700">{{ __('inventory.status_sent_awaiting') }}</span>
                            @break
                        @case('partial')
                            <x-icon name="clock" class="w-6 h-6 text-warning-600" />
                            <span class="font-medium text-warning-700">{{ __('inventory.status_partially_received') }}</span>
                            @break
                        @case('received')
                            <x-icon name="check" class="w-6 h-6 text-success-600" />
                            <span class="font-medium text-success-700">{{ __('inventory.status_fully_received') }}</span>
                            @break
                        @case('cancelled')
                            <x-icon name="x-circle" class="w-6 h-6 text-danger-600" />
                            <span class="font-medium text-danger-700">{{ __('inventory.status_cancelled') }}</span>
                            @break
                    @endswitch
                </div>
                @if($purchaseOrder->approvedBy)
                    <span class="text-sm text-muted">{{ __('inventory.approved_by') }} {{ $purchaseOrder->approvedBy->name }} {{ __('app.date') }} {{ $purchaseOrder->approved_at?->translatedFormat('d M Y') }}</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <x-card :title="__('inventory.order_information')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.po_number') }}</dt>
                        <dd class="font-medium">{{ $purchaseOrder->po_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.order_date') }}</dt>
                        <dd>{{ $purchaseOrder->order_date->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.expected_date') }}</dt>
                        <dd>{{ $purchaseOrder->expected_date?->translatedFormat('d M Y') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('admin.outlet') }}</dt>
                        <dd>{{ $purchaseOrder->outlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('app.created_by') }}</dt>
                        <dd>{{ $purchaseOrder->createdBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card :title="__('inventory.supplier_information')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.supplier') }}</dt>
                        <dd class="font-medium">{{ $purchaseOrder->supplier->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.contact_person') }}</dt>
                        <dd>{{ $purchaseOrder->supplier->contact_person ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('app.phone') }}</dt>
                        <dd>{{ $purchaseOrder->supplier->phone ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('app.email') }}</dt>
                        <dd>{{ $purchaseOrder->supplier->email ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.payment_terms') }}</dt>
                        <dd>{{ $purchaseOrder->supplier->payment_terms ? $purchaseOrder->supplier->payment_terms . ' ' . __('app.days') : '-' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <x-card :title="__('inventory.order_items')">
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.sku') }}</x-th>
                    <x-th align="right">{{ __('app.quantity') }}</x-th>
                    <x-th align="right">{{ __('inventory.unit_price') }}</x-th>
                    <x-th align="right">{{ __('app.total') }}</x-th>
                    @if($purchaseOrder->status !== 'draft')
                        <x-th align="right">{{ __('inventory.received') }}</x-th>
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
                        <x-td align="right">Rp {{ number_format($item->total_price ?? $item->total, 0, ',', '.') }}</x-td>
                        @if($purchaseOrder->status !== 'draft')
                            <x-td align="right">
                                @php
                                    $receivedQty = $item->received_qty ?? 0;
                                @endphp
                                <span class="{{ $receivedQty >= $item->quantity ? 'text-success-600' : ($receivedQty > 0 ? 'text-warning-600' : 'text-muted') }}">
                                    {{ number_format($receivedQty, 2) }}
                                </span>
                            </x-td>
                        @endif
                    </tr>
                @endforeach

                <tr class="bg-secondary-50">
                    <x-td colspan="{{ $purchaseOrder->status !== 'draft' ? 4 : 3 }}" class="text-right font-medium">{{ __('app.subtotal') }}</x-td>
                    <x-td align="right" class="font-medium">Rp {{ number_format($purchaseOrder->subtotal, 0, ',', '.') }}</x-td>
                    @if($purchaseOrder->status !== 'draft')
                        <x-td></x-td>
                    @endif
                </tr>
                @if($purchaseOrder->tax_amount > 0)
                    <tr class="bg-secondary-50">
                        <x-td colspan="{{ $purchaseOrder->status !== 'draft' ? 4 : 3 }}" class="text-right">{{ __('app.tax') }}</x-td>
                        <x-td align="right">Rp {{ number_format($purchaseOrder->tax_amount, 0, ',', '.') }}</x-td>
                        @if($purchaseOrder->status !== 'draft')
                            <x-td></x-td>
                        @endif
                    </tr>
                @endif
                <tr class="bg-secondary-100">
                    <x-td colspan="{{ $purchaseOrder->status !== 'draft' ? 4 : 3 }}" class="text-right font-bold">{{ __('app.total') }}</x-td>
                    <x-td align="right" class="font-bold">Rp {{ number_format($purchaseOrder->total ?? $purchaseOrder->subtotal, 0, ',', '.') }}</x-td>
                    @if($purchaseOrder->status !== 'draft')
                        <x-td></x-td>
                    @endif
                </tr>
            </x-table>
        </x-card>

        @if($purchaseOrder->notes)
            <x-card :title="__('app.notes')">
                <p class="text-text">{{ $purchaseOrder->notes }}</p>
            </x-card>
        @endif

        @if($purchaseOrder->goodsReceives && $purchaseOrder->goodsReceives->count() > 0)
            <x-card :title="__('inventory.goods_receives')">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.gr_number') }}</x-th>
                        <x-th>{{ __('app.date') }}</x-th>
                        <x-th>Invoice</x-th>
                        <x-th align="center">{{ __('app.status') }}</x-th>
                        <x-th align="right">{{ __('app.actions') }}</x-th>
                    </x-slot>

                    @foreach($purchaseOrder->goodsReceives as $gr)
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.goods-receives.show', $gr) }}" class="text-accent hover:underline font-medium">
                                    {{ $gr->gr_number }}
                                </a>
                            </x-td>
                            <x-td>{{ $gr->receive_date->translatedFormat('d M Y') }}</x-td>
                            <x-td>{{ $gr->invoice_number ?? '-' }}</x-td>
                            <x-td align="center">
                                @switch($gr->status)
                                    @case('draft')
                                        <x-badge type="secondary">{{ __('app.status_draft') }}</x-badge>
                                        @break
                                    @case('completed')
                                        <x-badge type="success">{{ __('app.status_completed') }}</x-badge>
                                        @break
                                    @case('cancelled')
                                        <x-badge type="danger">{{ __('app.status_cancelled') }}</x-badge>
                                        @break
                                @endswitch
                            </x-td>
                            <x-td align="right">
                                <x-button href="{{ route('inventory.goods-receives.show', $gr) }}" variant="ghost" size="sm" icon="eye">
                                    {{ __('app.view') }}
                                </x-button>
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    </div>

    {{-- Approve Modal --}}
    @if($purchaseOrder->status === 'draft')
    <x-confirm-modal
        name="approve-po"
        :title="__('inventory.approve_po')"
        :message="__('inventory.confirm_approve_po')"
        :confirmText="__('app.approve')"
        type="success"
        form="approve-po-form"
        @click="document.getElementById('approve-po-form').submit()"
    />
    <form id="approve-po-form" action="{{ route('inventory.purchase-orders.approve', $purchaseOrder) }}" method="POST" class="hidden">
        @csrf
    </form>
    @endif

    {{-- Mark as Sent Modal --}}
    @if($purchaseOrder->status === 'approved')
    <x-confirm-modal
        name="send-po"
        :title="__('inventory.mark_as_sent')"
        :message="__('inventory.confirm_send_po')"
        :confirmText="__('inventory.mark_as_sent')"
        type="primary"
        @click="document.getElementById('send-po-form').submit()"
    />
    <form id="send-po-form" action="{{ route('inventory.purchase-orders.send', $purchaseOrder) }}" method="POST" class="hidden">
        @csrf
    </form>
    @endif

    {{-- Cancel Modal --}}
    @if(in_array($purchaseOrder->status, ['draft', 'approved', 'sent']))
    <x-confirm-modal
        name="cancel-po"
        :title="__('inventory.cancel_po')"
        :message="__('inventory.confirm_cancel_po')"
        :confirmText="__('inventory.cancel_po')"
        type="danger"
        @click="document.getElementById('cancel-po-form').submit()"
    />
    <form id="cancel-po-form" action="{{ route('inventory.purchase-orders.cancel', $purchaseOrder) }}" method="POST" class="hidden">
        @csrf
    </form>
    @endif
</x-app-layout>
