<x-app-layout>
    <x-slot name="title">{{ $stockTransfer->transfer_number }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.transfer_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.stock-transfers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('app.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $stockTransfer->transfer_number }}</h2>
                    <p class="text-muted mt-1">{{ $stockTransfer->fromOutlet->name }} → {{ $stockTransfer->toOutlet->name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                @switch($stockTransfer->status)
                    @case('draft')
                        <x-button variant="success" icon="check" @click="$dispatch('open-modal', 'approve-transfer')">
                            {{ __('app.approve') }}
                        </x-button>
                        <x-button href="{{ route('inventory.stock-transfers.edit', $stockTransfer) }}" variant="outline-secondary" icon="pencil">
                            {{ __('app.edit') }}
                        </x-button>
                        <x-button variant="outline-danger" icon="x" @click="$dispatch('open-modal', 'cancel-transfer')">
                            {{ __('app.cancel') }}
                        </x-button>
                        @break
                    @case('approved')
                        <x-button icon="check-circle" @click="$dispatch('open-modal', 'receive-transfer')">
                            {{ __('inventory.receive_transfer') }}
                        </x-button>
                        @break
                @endswitch
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Status Banner -->
        <div class="p-4 rounded-lg @switch($stockTransfer->status) @case('draft') bg-secondary-100 @break @case('approved') bg-info-100 @break @case('in_transit') bg-warning-100 @break @case('received') bg-success-100 @break @case('cancelled') bg-danger-100 @break @endswitch">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @switch($stockTransfer->status)
                        @case('draft')
                            <x-icon name="document" class="w-6 h-6 text-secondary-600" />
                            <span class="font-medium text-secondary-700">{{ __('app.status_draft') }} - {{ __('inventory.status_draft_pending') }}</span>
                            @break
                        @case('approved')
                            <x-icon name="check-circle" class="w-6 h-6 text-info-600" />
                            <span class="font-medium text-info-700">{{ __('app.status_approved') }} - {{ __('inventory.ready_to_receive') }}</span>
                            @break
                        @case('in_transit')
                            <x-icon name="truck" class="w-6 h-6 text-warning-600" />
                            <span class="font-medium text-warning-700">{{ __('inventory.in_transit') }}</span>
                            @break
                        @case('received')
                            <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                            <span class="font-medium text-success-700">{{ __('inventory.transfer_received') }} - {{ __('inventory.transfer_complete') }}</span>
                            @break
                        @case('cancelled')
                            <x-icon name="x-circle" class="w-6 h-6 text-danger-600" />
                            <span class="font-medium text-danger-700">{{ __('app.status_cancelled') }}</span>
                            @break
                    @endswitch
                </div>
            </div>
        </div>

        <!-- Transfer Progress -->
        @if($stockTransfer->status !== 'cancelled')
            <x-card>
                <div class="flex items-center justify-between">
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ in_array($stockTransfer->status, ['draft', 'approved', 'in_transit', 'received']) ? 'bg-success-500 text-white' : 'bg-secondary-200 text-muted' }}">
                            <x-icon name="document" class="w-5 h-5" />
                        </div>
                        <span class="text-sm mt-2 font-medium">{{ __('app.created') }}</span>
                        <span class="text-xs text-muted">{{ $stockTransfer->created_at->translatedFormat('d M, H:i') }}</span>
                    </div>
                    <div class="flex-1 h-1 {{ in_array($stockTransfer->status, ['approved', 'in_transit', 'received']) ? 'bg-success-500' : 'bg-secondary-200' }}"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ in_array($stockTransfer->status, ['approved', 'in_transit', 'received']) ? 'bg-success-500 text-white' : 'bg-secondary-200 text-muted' }}">
                            <x-icon name="check" class="w-5 h-5" />
                        </div>
                        <span class="text-sm mt-2 font-medium">{{ __('app.status_approved') }}</span>
                        <span class="text-xs text-muted">{{ $stockTransfer->approved_at?->translatedFormat('d M, H:i') ?? '-' }}</span>
                    </div>
                    <div class="flex-1 h-1 {{ $stockTransfer->status === 'received' ? 'bg-success-500' : 'bg-secondary-200' }}"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $stockTransfer->status === 'received' ? 'bg-success-500 text-white' : 'bg-secondary-200 text-muted' }}">
                            <x-icon name="check-circle" class="w-5 h-5" />
                        </div>
                        <span class="text-sm mt-2 font-medium">{{ __('inventory.received') }}</span>
                        <span class="text-xs text-muted">{{ $stockTransfer->received_at?->translatedFormat('d M, H:i') ?? '-' }}</span>
                    </div>
                </div>
            </x-card>
        @endif

        <div class="grid grid-cols-2 gap-6">
            <x-card :title="__('inventory.transfer_details')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.transfer_number') }}</dt>
                        <dd class="font-medium">{{ $stockTransfer->transfer_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.transfer_date') }}</dt>
                        <dd>{{ $stockTransfer->transfer_date->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('app.created_by') }}</dt>
                        <dd>{{ $stockTransfer->createdBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card :title="__('inventory.outlet_information')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.from_outlet') }}</dt>
                        <dd class="font-medium">{{ $stockTransfer->fromOutlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.to_outlet') }}</dt>
                        <dd class="font-medium">{{ $stockTransfer->toOutlet->name }}</dd>
                    </div>
                    @if($stockTransfer->approvedBy)
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.approved_by') }}</dt>
                            <dd>{{ $stockTransfer->approvedBy->name }}</dd>
                        </div>
                    @endif
                    @if($stockTransfer->receivedBy)
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.received_by') }}</dt>
                            <dd>{{ $stockTransfer->receivedBy->name }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>

        <x-card :title="__('inventory.transfer_items')">
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.sku') }}</x-th>
                    <x-th align="right">{{ __('app.quantity') }}</x-th>
                    <x-th>{{ __('app.notes') }}</x-th>
                </x-slot>

                @foreach($stockTransfer->items as $item)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $item->inventoryItem->name }}</p>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">
                            {{ number_format($item->quantity, 2) }}
                            {{ $item->inventoryItem->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td>{{ $item->notes ?? '-' }}</x-td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        @if($stockTransfer->notes)
            <x-card :title="__('app.notes')">
                <p class="text-text">{{ $stockTransfer->notes }}</p>
            </x-card>
        @endif
    </div>

    {{-- Approve Transfer Modal --}}
    @if($stockTransfer->status === 'draft')
    <x-confirm-modal
        name="approve-transfer"
        :title="__('inventory.approve_transfer')"
        :message="__('inventory.confirm_approve_transfer')"
        :confirmText="__('app.approve')"
        type="success"
        @click="document.getElementById('approve-transfer-form').submit()"
    />
    <form id="approve-transfer-form" action="{{ route('inventory.stock-transfers.approve', $stockTransfer) }}" method="POST" class="hidden">
        @csrf
    </form>

    {{-- Cancel Transfer Modal --}}
    <x-confirm-modal
        name="cancel-transfer"
        :title="__('inventory.cancel_transfer')"
        :message="__('inventory.confirm_cancel_transfer')"
        :confirmText="__('app.cancel')"
        type="danger"
        @click="document.getElementById('cancel-transfer-form').submit()"
    />
    <form id="cancel-transfer-form" action="{{ route('inventory.stock-transfers.cancel', $stockTransfer) }}" method="POST" class="hidden">
        @csrf
    </form>
    @endif

    {{-- Receive Transfer Modal --}}
    @if($stockTransfer->status === 'approved')
    <x-confirm-modal
        name="receive-transfer"
        :title="__('inventory.receive_transfer')"
        :message="__('inventory.confirm_receive_transfer')"
        :confirmText="__('inventory.receive_transfer')"
        type="success"
        @click="document.getElementById('receive-transfer-form').submit()"
    />
    <form id="receive-transfer-form" action="{{ route('inventory.stock-transfers.receive', $stockTransfer) }}" method="POST" class="hidden">
        @csrf
    </form>
    @endif
</x-app-layout>
