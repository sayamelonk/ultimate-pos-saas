<x-app-layout>
    <x-slot name="title">Waste Log Details - Ultimate POS</x-slot>

    @section('page-title', 'Waste Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.waste-logs.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">Waste Log Details</h2>
                    <p class="text-muted mt-1">{{ $wasteLog->waste_date->format('M d, Y') }}</p>
                </div>
            </div>
            <x-button
                variant="outline-danger"
                icon="trash"
                @click="$dispatch('open-delete-modal', {
                    title: 'Delete Waste Log',
                    message: 'Are you sure you want to delete this waste log? This action cannot be undone.',
                    action: '{{ route('inventory.waste-logs.destroy', $wasteLog) }}'
                })"
            >
                Delete
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <x-card title="Item Information">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">Item Name</dt>
                        <dd class="font-medium">{{ $wasteLog->inventoryItem->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">SKU</dt>
                        <dd>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $wasteLog->inventoryItem->sku }}</code>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Category</dt>
                        <dd>{{ $wasteLog->inventoryItem->category->name ?? '-' }}</dd>
                    </div>
                    @if($wasteLog->batch_number)
                        <div class="flex justify-between">
                            <dt class="text-muted">Batch Number</dt>
                            <dd>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $wasteLog->batch_number }}</code>
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-card title="Waste Details">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">Waste Date</dt>
                        <dd class="font-medium">{{ $wasteLog->waste_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Outlet</dt>
                        <dd>{{ $wasteLog->outlet->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Reason</dt>
                        <dd>
                            @switch($wasteLog->reason)
                                @case('expired')
                                    <x-badge type="danger">Expired</x-badge>
                                    @break
                                @case('damaged')
                                    <x-badge type="warning">Damaged</x-badge>
                                    @break
                                @case('spoiled')
                                    <x-badge type="danger">Spoiled</x-badge>
                                    @break
                                @case('overproduction')
                                    <x-badge type="info">Overproduction</x-badge>
                                    @break
                                @case('quality_issue')
                                    <x-badge type="warning">Quality Issue</x-badge>
                                    @break
                                @default
                                    <x-badge type="secondary">Other</x-badge>
                            @endswitch
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Logged By</dt>
                        <dd>{{ $wasteLog->loggedBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <x-card title="Value Impact">
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-4 bg-secondary-50 rounded-lg">
                    <p class="text-2xl font-bold text-text">{{ number_format($wasteLog->quantity, 2) }}</p>
                    <p class="text-sm text-muted">Quantity {{ $wasteLog->inventoryItem->unit->abbreviation ?? '' }}</p>
                </div>
                <div class="text-center p-4 bg-secondary-50 rounded-lg">
                    <p class="text-2xl font-bold text-text">Rp {{ number_format($wasteLog->inventoryItem->cost_price, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted">Unit Cost</p>
                </div>
                <div class="text-center p-4 bg-danger-50 rounded-lg">
                    <p class="text-2xl font-bold text-danger-600">Rp {{ number_format($wasteLog->value, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted">Total Value Lost</p>
                </div>
            </div>
        </x-card>

        @if($wasteLog->notes)
            <x-card title="Notes">
                <p class="text-text">{{ $wasteLog->notes }}</p>
            </x-card>
        @endif

        <x-card title="Audit Information">
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-muted">Created At</dt>
                    <dd class="mt-1">{{ $wasteLog->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Last Updated</dt>
                    <dd class="mt-1">{{ $wasteLog->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-layout>
