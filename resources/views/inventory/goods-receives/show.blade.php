<x-app-layout>
    <x-slot name="title">{{ $goodsReceive->gr_number }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.gr_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.goods-receives.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('app.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $goodsReceive->gr_number }}</h2>
                    <p class="text-muted mt-1">{{ $goodsReceive->purchaseOrder?->po_number ?? '-' }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                @if($goodsReceive->status === 'draft')
                    <x-button variant="success" icon="check" @click="$dispatch('open-modal', 'complete-gr')">
                        {{ __('inventory.complete_gr') }}
                    </x-button>
                    <x-button href="{{ route('inventory.goods-receives.edit', $goodsReceive) }}" variant="outline-secondary" icon="pencil">
                        {{ __('app.edit') }}
                    </x-button>
                    <x-button variant="outline-danger" icon="x" @click="$dispatch('open-modal', 'cancel-gr')">
                        {{ __('app.cancel') }}
                    </x-button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Status Banner -->
        <div class="p-4 rounded-lg @switch($goodsReceive->status) @case('draft') bg-secondary-100 @break @case('completed') bg-success-100 @break @case('cancelled') bg-danger-100 @break @endswitch">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @switch($goodsReceive->status)
                        @case('draft')
                            <x-icon name="document" class="w-6 h-6 text-secondary-600" />
                            <span class="font-medium text-secondary-700">{{ __('inventory.status_gr_draft') }}</span>
                            @break
                        @case('completed')
                            <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                            <span class="font-medium text-success-700">{{ __('inventory.status_gr_completed') }}</span>
                            @break
                        @case('cancelled')
                            <x-icon name="x-circle" class="w-6 h-6 text-danger-600" />
                            <span class="font-medium text-danger-700">{{ __('app.status_cancelled') }}</span>
                            @break
                    @endswitch
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <x-card :title="__('inventory.receive_information')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.gr_number') }}</dt>
                        <dd class="font-medium">{{ $goodsReceive->gr_number }}</dd>
                    </div>
                    @if($goodsReceive->purchaseOrder)
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.po_number') }}</dt>
                        <dd>
                            <a href="{{ route('inventory.purchase-orders.show', $goodsReceive->purchaseOrder) }}" class="text-accent hover:underline">
                                {{ $goodsReceive->purchaseOrder->po_number }}
                            </a>
                        </dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.receive_date') }}</dt>
                        <dd>{{ $goodsReceive->receive_date->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.invoice_number') }}</dt>
                        <dd>{{ $goodsReceive->invoice_number ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('admin.outlet') }}</dt>
                        <dd>{{ $goodsReceive->outlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.received_by') }}</dt>
                        <dd>{{ $goodsReceive->receivedBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card :title="__('inventory.supplier_information')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.supplier') }}</dt>
                        <dd class="font-medium">{{ $goodsReceive->purchaseOrder?->supplier?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.contact_person') }}</dt>
                        <dd>{{ $goodsReceive->purchaseOrder?->supplier?->contact_person ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('app.phone') }}</dt>
                        <dd>{{ $goodsReceive->purchaseOrder?->supplier?->phone ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <x-card :title="__('inventory.received_items')">
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.sku') }}</x-th>
                    <x-th align="right">{{ __('inventory.ordered_quantity') }}</x-th>
                    <x-th align="right">{{ __('inventory.received') }}</x-th>
                    <x-th>{{ __('inventory.batch') }}</x-th>
                    <x-th>{{ __('inventory.expiry_date') }}</x-th>
                </x-slot>

                @foreach($goodsReceive->items as $item)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $item->purchaseOrderItem->inventoryItem->name }}</p>
                            @if($item->notes)
                                <p class="text-xs text-muted">{{ $item->notes }}</p>
                            @endif
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->purchaseOrderItem->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">{{ number_format($item->purchaseOrderItem->quantity, 2) }} {{ $item->purchaseOrderItem->inventoryItem->unit->abbreviation ?? '' }}</x-td>
                        <x-td align="right">
                            <span class="{{ $item->quantity < $item->purchaseOrderItem->quantity ? 'text-warning-600' : 'text-success-600' }} font-medium">
                                {{ number_format($item->quantity, 2) }}
                            </span>
                        </x-td>
                        <x-td>
                            @if($item->batch_number)
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->batch_number }}</code>
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td>
                            @if($item->expiry_date)
                                {{ $item->expiry_date->translatedFormat('d M Y') }}
                            @else
                                -
                            @endif
                        </x-td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        @if($goodsReceive->notes)
            <x-card :title="__('app.notes')">
                <p class="text-text">{{ $goodsReceive->notes }}</p>
            </x-card>
        @endif
    </div>

    {{-- Complete GR Modal --}}
    @if($goodsReceive->status === 'draft')
    <x-confirm-modal
        name="complete-gr"
        :title="__('inventory.complete_gr')"
        :message="__('inventory.confirm_complete_gr')"
        :confirmText="__('inventory.complete_gr')"
        type="success"
        @click="document.getElementById('complete-gr-form').submit()"
    />
    <form id="complete-gr-form" action="{{ route('inventory.goods-receives.complete', $goodsReceive) }}" method="POST" class="hidden">
        @csrf
    </form>

    {{-- Cancel GR Modal --}}
    <x-confirm-modal
        name="cancel-gr"
        :title="__('inventory.cancel_gr')"
        :message="__('inventory.confirm_cancel_gr')"
        :confirmText="__('inventory.cancel_gr')"
        type="danger"
        @click="document.getElementById('cancel-gr-form').submit()"
    />
    <form id="cancel-gr-form" action="{{ route('inventory.goods-receives.cancel', $goodsReceive) }}" method="POST" class="hidden">
        @csrf
    </form>
    @endif
</x-app-layout>
