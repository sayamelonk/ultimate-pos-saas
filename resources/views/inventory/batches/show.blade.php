<x-app-layout>
    <x-slot name="title">Batch {{ $batch->batch_number }} - Ultimate POS</x-slot>

    @section('page-title', 'Batch Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.batches.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $batch->batch_number }}</h2>
                    <p class="text-muted mt-1">{{ $batch->inventoryItem->name }}</p>
                </div>
            </div>
            @if($batch->status === 'active')
                <div class="flex items-center gap-2">
                    <form action="{{ route('inventory.batches.mark-expired', $batch) }}" method="POST"
                          onsubmit="return confirm('Mark this batch as expired? This will set quantity to 0.')">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="danger" icon="x-circle">
                            Mark Expired
                        </x-button>
                    </form>
                    <form action="{{ route('inventory.batches.dispose', $batch) }}" method="POST"
                          onsubmit="return confirm('Dispose this batch? This will set quantity to 0.')">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="warning" icon="trash">
                            Dispose
                        </x-button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Batch Details Card -->
            <x-card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <x-icon name="cube" class="w-5 h-5 text-primary" />
                    </div>
                    <h3 class="font-semibold text-lg">Batch Information</h3>
                </div>

                <dl class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">Batch Number</dt>
                        <dd class="font-semibold text-lg mt-0.5">{{ $batch->batch_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">Item</dt>
                        <dd class="font-medium mt-0.5">{{ $batch->inventoryItem->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">Outlet</dt>
                        <dd class="font-medium mt-0.5">{{ $batch->outlet->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">Production Date</dt>
                        <dd class="font-medium mt-0.5">
                            {{ $batch->production_date ? $batch->production_date->format('d M Y') : '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">Expiry Date</dt>
                        <dd class="mt-0.5">
                            @if($batch->expiry_date)
                                <span class="font-medium {{ $batch->isExpired() ? 'text-danger' : '' }}">
                                    {{ $batch->expiry_date->format('d M Y') }}
                                </span>
                                <x-badge type="{{ $batch->getExpiryBadgeType() }}" class="ml-1">
                                    @php $days = $batch->daysUntilExpiry(); @endphp
                                    @if($days < 0)
                                        Expired
                                    @elseif($days === 0)
                                        Today
                                    @else
                                        {{ $days }} days
                                    @endif
                                </x-badge>
                            @else
                                <span class="text-muted">No expiry</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">Status</dt>
                        <dd class="mt-0.5">
                            @php
                                $statusColors = [
                                    'active' => 'success',
                                    'depleted' => 'secondary',
                                    'expired' => 'danger',
                                    'disposed' => 'warning',
                                ];
                            @endphp
                            <x-badge type="{{ $statusColors[$batch->status] ?? 'secondary' }}" dot>
                                {{ ucfirst($batch->status) }}
                            </x-badge>
                        </dd>
                    </div>
                    @if($batch->supplier_batch_number)
                        <div>
                            <dt class="text-xs text-muted uppercase tracking-wide">Supplier Batch</dt>
                            <dd class="font-medium mt-0.5">{{ $batch->supplier_batch_number }}</dd>
                        </div>
                    @endif
                    @if($batch->goodsReceiveItem)
                        <div>
                            <dt class="text-xs text-muted uppercase tracking-wide">Goods Receive</dt>
                            <dd class="mt-0.5">
                                <a href="{{ route('inventory.goods-receives.show', $batch->goodsReceiveItem->goods_receive_id) }}"
                                   class="text-primary hover:underline">
                                    {{ $batch->goodsReceiveItem->goodsReceive->gr_number ?? 'View' }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>

                @if($batch->notes)
                    <div class="mt-4 pt-4 border-t border-border">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">Notes</dt>
                        <dd class="text-sm bg-secondary-50 rounded-lg p-3">{{ $batch->notes }}</dd>
                    </div>
                @endif
            </x-card>

            <!-- Movement History -->
            <x-card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                        <x-icon name="arrows-right-left" class="w-5 h-5 text-info" />
                    </div>
                    <h3 class="font-semibold text-lg">Movement History</h3>
                </div>

                @if($movements->count() > 0)
                    <div class="overflow-x-auto">
                        <x-table>
                            <x-slot name="head">
                                <x-th>Date</x-th>
                                <x-th>Type</x-th>
                                <x-th align="right">Quantity</x-th>
                                <x-th align="right">Balance</x-th>
                                <x-th>Reference</x-th>
                                <x-th>User</x-th>
                            </x-slot>

                            @foreach($movements as $movement)
                                <tr>
                                    <x-td>
                                        <div class="text-sm">
                                            <p>{{ $movement->created_at->format('d M Y') }}</p>
                                            <p class="text-xs text-muted">{{ $movement->created_at->format('H:i') }}</p>
                                        </div>
                                    </x-td>
                                    <x-td>
                                        <x-badge type="{{ \App\Models\StockBatchMovement::getTypeColor($movement->type) }}">
                                            {{ \App\Models\StockBatchMovement::getTypeLabel($movement->type) }}
                                        </x-badge>
                                    </x-td>
                                    <x-td align="right">
                                        <span class="font-medium {{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                                        </span>
                                    </x-td>
                                    <x-td align="right">
                                        <span class="font-medium">{{ number_format($movement->balance_after, 2) }}</span>
                                    </x-td>
                                    <x-td>
                                        <div class="text-sm">
                                            @if($movement->reference_number)
                                                <p class="font-medium">{{ $movement->reference_number }}</p>
                                            @endif
                                            @if($movement->notes)
                                                <p class="text-xs text-muted">{{ Str::limit($movement->notes, 30) }}</p>
                                            @endif
                                        </div>
                                    </x-td>
                                    <x-td>
                                        <span class="text-sm">{{ $movement->user?->name ?? '-' }}</span>
                                    </x-td>
                                </tr>
                            @endforeach
                        </x-table>
                    </div>

                    <div class="mt-4">
                        <x-pagination :paginator="$movements" />
                    </div>
                @else
                    <p class="text-muted text-center py-6">No movement history yet.</p>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quantity Card -->
            <x-card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                        <x-icon name="archive-box" class="w-5 h-5 text-success" />
                    </div>
                    <h3 class="font-semibold text-lg">Quantity</h3>
                </div>

                <dl class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-muted">Initial</span>
                        <span class="font-medium">{{ number_format($batch->initial_quantity, 2) }} {{ $batch->inventoryItem->unit->abbreviation }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted">Current</span>
                        <span class="font-bold text-xl">{{ number_format($batch->current_quantity, 2) }} {{ $batch->inventoryItem->unit->abbreviation }}</span>
                    </div>
                    @if($batch->reserved_quantity > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-muted">Reserved</span>
                            <span class="font-medium text-warning">{{ number_format($batch->reserved_quantity, 2) }} {{ $batch->inventoryItem->unit->abbreviation }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-border">
                            <span class="font-medium">Available</span>
                            <span class="font-bold text-success">{{ number_format($batch->getAvailableQuantity(), 2) }} {{ $batch->inventoryItem->unit->abbreviation }}</span>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Cost Card -->
            <x-card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                        <x-icon name="currency-dollar" class="w-5 h-5 text-warning" />
                    </div>
                    <h3 class="font-semibold text-lg">Cost</h3>
                </div>

                <dl class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-muted">Unit Cost</span>
                        <span class="font-medium">Rp {{ number_format($batch->unit_cost, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-border">
                        <span class="font-medium">Total Value</span>
                        <span class="font-bold text-lg">Rp {{ number_format($batch->current_quantity * $batch->unit_cost, 0, ',', '.') }}</span>
                    </div>
                </dl>
            </x-card>

            <!-- Adjust Quantity -->
            @if($batch->status === 'active')
                <x-card>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-secondary-100 flex items-center justify-center">
                            <x-icon name="adjustments-horizontal" class="w-5 h-5 text-secondary-600" />
                        </div>
                        <h3 class="font-semibold text-lg">Adjust Quantity</h3>
                    </div>

                    <form action="{{ route('inventory.batches.adjust', $batch) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-text mb-1">Type</label>
                                <x-select name="adjustment_type" required>
                                    <option value="add">Add (+)</option>
                                    <option value="subtract">Subtract (-)</option>
                                </x-select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-text mb-1">Quantity</label>
                                <x-input type="number" name="quantity" step="0.01" min="0.01" required placeholder="0.00" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-text mb-1">Reason</label>
                                <textarea
                                    name="reason"
                                    rows="2"
                                    required
                                    placeholder="Reason for adjustment..."
                                    class="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                                ></textarea>
                            </div>

                            <x-button type="submit" class="w-full">
                                Apply Adjustment
                            </x-button>
                        </div>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
