<x-app-layout>
    <x-slot name="title">{{ __('inventory.waste_details') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.waste_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.waste-logs.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('inventory.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ __('inventory.waste_details') }}</h2>
                    <p class="text-muted mt-1">{{ $wasteLog->waste_date->format('M d, Y') }}</p>
                </div>
            </div>
            <div x-data>
                <x-button
                    variant="outline-danger"
                    icon="trash"
                    @click="$dispatch('confirm', {
                        title: '{{ __('inventory.delete_waste') }}',
                        message: '{{ __('inventory.confirm_delete_waste') }}',
                        confirmText: '{{ __('inventory.delete') }}',
                        variant: 'danger',
                        onConfirm: () => $refs.deleteForm.submit()
                    })"
                >
                    {{ __('inventory.delete') }}
                </x-button>
                <form x-ref="deleteForm" action="{{ route('inventory.waste-logs.destroy', $wasteLog) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <x-card :title="__('inventory.item_information')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.item_name') }}</dt>
                        <dd class="font-medium">{{ $wasteLog->inventoryItem->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.sku') }}</dt>
                        <dd>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $wasteLog->inventoryItem->sku }}</code>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.category') }}</dt>
                        <dd>{{ $wasteLog->inventoryItem->category->name ?? '-' }}</dd>
                    </div>
                    @if($wasteLog->batch_number)
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.batch_number') }}</dt>
                            <dd>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $wasteLog->batch_number }}</code>
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-card :title="__('inventory.waste_details')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.waste_date') }}</dt>
                        <dd class="font-medium">{{ $wasteLog->waste_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.outlet') }}</dt>
                        <dd>{{ $wasteLog->outlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.reason') }}</dt>
                        <dd>
                            @switch($wasteLog->reason)
                                @case('expired')
                                    <x-badge type="danger">{{ __('inventory.waste_expired') }}</x-badge>
                                    @break
                                @case('damaged')
                                    <x-badge type="warning">{{ __('inventory.waste_damaged') }}</x-badge>
                                    @break
                                @case('spoiled')
                                    <x-badge type="danger">{{ __('inventory.waste_spoiled') }}</x-badge>
                                    @break
                                @case('overproduction')
                                    <x-badge type="info">{{ __('inventory.waste_overproduction') }}</x-badge>
                                    @break
                                @case('quality_issue')
                                    <x-badge type="warning">{{ __('inventory.waste_quality_issue') }}</x-badge>
                                    @break
                                @default
                                    <x-badge type="secondary">{{ __('inventory.waste_other') }}</x-badge>
                            @endswitch
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.logged_by') }}</dt>
                        <dd>{{ $wasteLog->loggedBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <x-card :title="__('app.value_impact')">
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-4 bg-secondary-50 rounded-lg">
                    <p class="text-2xl font-bold text-text">{{ number_format($wasteLog->quantity, 2) }}</p>
                    <p class="text-sm text-muted">{{ __('inventory.quantity') }} {{ $wasteLog->inventoryItem->unit->abbreviation ?? '' }}</p>
                </div>
                <div class="text-center p-4 bg-secondary-50 rounded-lg">
                    <p class="text-2xl font-bold text-text">Rp {{ number_format($wasteLog->inventoryItem->cost_price, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted">{{ __('inventory.unit_cost') }}</p>
                </div>
                <div class="text-center p-4 bg-danger-50 rounded-lg">
                    <p class="text-2xl font-bold text-danger-600">Rp {{ number_format($wasteLog->value, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted">{{ __('app.total_value_lost') }}</p>
                </div>
            </div>
        </x-card>

        @if($wasteLog->notes)
            <x-card :title="__('inventory.notes')">
                <p class="text-text">{{ $wasteLog->notes }}</p>
            </x-card>
        @endif

        <x-card :title="__('app.audit_information')">
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-muted">{{ __('app.created_at') }}</dt>
                    <dd class="mt-1">{{ $wasteLog->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('app.last_updated') }}</dt>
                    <dd class="mt-1">{{ $wasteLog->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-layout>
