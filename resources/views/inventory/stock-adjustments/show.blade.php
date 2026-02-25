<x-app-layout>
    <x-slot name="title">{{ $stockAdjustment->adjustment_number }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.adjustment_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('app.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $stockAdjustment->adjustment_number }}</h2>
                    <p class="text-muted mt-1">{{ __('inventory.type_' . $stockAdjustment->type) }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                @if($stockAdjustment->status === 'draft')
                    <x-button variant="success" icon="check" @click="$dispatch('open-modal', 'approve-adjustment')">
                        {{ __('inventory.approve_adjustment') }}
                    </x-button>
                    <x-button href="{{ route('inventory.stock-adjustments.edit', $stockAdjustment) }}" variant="outline-secondary" icon="pencil">
                        {{ __('app.edit') }}
                    </x-button>
                    <x-button variant="outline-danger" icon="trash" @click="$dispatch('open-modal', 'delete-adjustment')">
                        {{ __('app.delete') }}
                    </x-button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <!-- Status Banner -->
        <div class="p-4 rounded-lg @switch($stockAdjustment->status) @case('draft') bg-secondary-100 @break @case('approved') bg-success-100 @break @case('rejected') bg-danger-100 @break @case('cancelled') bg-warning-100 @break @endswitch">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @switch($stockAdjustment->status)
                        @case('draft')
                            <x-icon name="document" class="w-6 h-6 text-secondary-600" />
                            <span class="font-medium text-secondary-700">{{ __('app.status_draft') }} - {{ __('inventory.status_draft_pending') }}</span>
                            @break
                        @case('approved')
                            <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                            <span class="font-medium text-success-700">{{ __('app.status_approved') }} - {{ __('inventory.adjustment_approved') }}</span>
                            @break
                        @case('rejected')
                            <x-icon name="x-circle" class="w-6 h-6 text-danger-600" />
                            <span class="font-medium text-danger-700">{{ __('app.status_rejected') }}</span>
                            @break
                        @case('cancelled')
                            <x-icon name="x-circle" class="w-6 h-6 text-warning-600" />
                            <span class="font-medium text-warning-700">{{ __('app.status_cancelled') }}</span>
                            @break
                    @endswitch
                </div>
                @if($stockAdjustment->approvedBy)
                    <span class="text-sm text-muted">
                        {{ __('inventory.approved_by') }} {{ $stockAdjustment->approvedBy->name }}
                        - {{ $stockAdjustment->approved_at?->translatedFormat('d M Y H:i') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <x-card :title="__('inventory.adjustment_details')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.adjustment_number') }}</dt>
                        <dd class="font-medium">{{ $stockAdjustment->adjustment_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.adjustment_type') }}</dt>
                        <dd>
                            @switch($stockAdjustment->type)
                                @case('stock_take')
                                    <x-badge type="info">{{ __('inventory.type_stock_take') }}</x-badge>
                                    @break
                                @case('correction')
                                    <x-badge type="warning">{{ __('inventory.type_correction') }}</x-badge>
                                    @break
                                @case('damage')
                                    <x-badge type="danger">{{ __('inventory.type_damage') }}</x-badge>
                                    @break
                                @case('loss')
                                    <x-badge type="danger">{{ __('inventory.type_loss') }}</x-badge>
                                    @break
                                @case('found')
                                    <x-badge type="success">{{ __('inventory.type_found') }}</x-badge>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.adjustment_date') }}</dt>
                        <dd>{{ $stockAdjustment->adjustment_date->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('admin.outlet') }}</dt>
                        <dd>{{ $stockAdjustment->outlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('app.created_by') }}</dt>
                        <dd>{{ $stockAdjustment->createdBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card :title="__('inventory.summary')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('app.status') }}</dt>
                        <dd>
                            @switch($stockAdjustment->status)
                                @case('draft')
                                    <x-badge type="secondary">{{ __('app.status_draft') }}</x-badge>
                                    @break
                                @case('approved')
                                    <x-badge type="success">{{ __('app.status_approved') }}</x-badge>
                                    @break
                                @case('rejected')
                                    <x-badge type="danger">{{ __('app.status_rejected') }}</x-badge>
                                    @break
                                @case('cancelled')
                                    <x-badge type="warning">{{ __('app.status_cancelled') }}</x-badge>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.items') }}</dt>
                        <dd class="font-medium">{{ $stockAdjustment->items->count() }}</dd>
                    </div>
                    <div class="flex justify-between pt-4 border-t border-border">
                        <dt class="font-bold">{{ __('inventory.total_variance') }}</dt>
                        <dd class="font-bold text-lg">{{ number_format($stockAdjustment->total_variance ?: $stockAdjustment->items->sum(fn($i) => abs($i->difference))) }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        @if($stockAdjustment->reason)
            <x-card :title="__('inventory.reason')">
                <p class="text-text">{{ $stockAdjustment->reason }}</p>
            </x-card>
        @endif

        <x-card :title="__('inventory.adjustment_items')">
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.sku') }}</x-th>
                    <x-th align="right">{{ __('inventory.system_stock') }}</x-th>
                    <x-th align="right">{{ __('inventory.actual_stock') }}</x-th>
                    <x-th align="right">{{ __('inventory.difference') }}</x-th>
                    <x-th>{{ __('app.notes') }}</x-th>
                </x-slot>

                @foreach($stockAdjustment->items as $item)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $item->inventoryItem->name }}</p>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">
                            {{ number_format($item->system_qty, 2) }} {{ $item->inventoryItem->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right">
                            {{ number_format($item->actual_qty, 2) }} {{ $item->inventoryItem->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right">
                            <span class="{{ $item->difference > 0 ? 'text-success-600' : ($item->difference < 0 ? 'text-danger-600' : 'text-muted') }} font-medium">
                                {{ $item->difference > 0 ? '+' : '' }}{{ number_format($item->difference, 2) }}
                            </span>
                        </x-td>
                        <x-td>{{ $item->notes ?? '-' }}</x-td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    </div>

    {{-- Approve Adjustment Modal --}}
    @if($stockAdjustment->status === 'draft')
    <x-confirm-modal
        name="approve-adjustment"
        :title="__('inventory.approve_adjustment')"
        :message="__('inventory.confirm_approve_adjustment')"
        :confirmText="__('inventory.approve_adjustment')"
        type="success"
        @click="document.getElementById('approve-adjustment-form').submit()"
    />
    <form id="approve-adjustment-form" action="{{ route('inventory.stock-adjustments.approve', $stockAdjustment) }}" method="POST" class="hidden">
        @csrf
    </form>

    {{-- Delete Adjustment Modal --}}
    <x-confirm-modal
        name="delete-adjustment"
        :title="__('inventory.delete_adjustment')"
        :message="__('inventory.confirm_delete_adjustment', ['number' => $stockAdjustment->adjustment_number])"
        :confirmText="__('app.delete')"
        type="danger"
        @click="document.getElementById('delete-adjustment-form').submit()"
    />
    <form id="delete-adjustment-form" action="{{ route('inventory.stock-adjustments.destroy', $stockAdjustment) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endif
</x-app-layout>
