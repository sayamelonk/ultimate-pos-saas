<x-app-layout>
    <x-slot name="title">Stock Movement Report - Ultimate POS</x-slot>

    @section('page-title', 'Stock Movement')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Stock Movement Report</h2>
                <p class="text-muted mt-1">Track all stock movements and transactions</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.reports.stock-valuation') }}" variant="outline-secondary" icon="currency-dollar">
                    Valuation Report
                </x-button>
                <x-button href="{{ route('inventory.reports.cogs') }}" variant="outline-secondary" icon="calculator">
                    COGS Report
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('inventory.reports.stock-movement') }}" class="flex flex-wrap gap-4">
                <x-input
                    type="date"
                    name="date_from"
                    :value="$dateFrom"
                    class="w-40"
                />
                <x-input
                    type="date"
                    name="date_to"
                    :value="$dateTo"
                    class="w-40"
                />
                <x-select name="outlet_id" class="w-48">
                    <option value="">All Outlets</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="item_id" class="w-56">
                    <option value="">All Items</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="movement_type" class="w-40">
                    <option value="">All Types</option>
                    <option value="goods_receive" @selected(request('movement_type') === 'goods_receive')>Goods Receive</option>
                    <option value="sale" @selected(request('movement_type') === 'sale')>Sale</option>
                    <option value="transfer_out" @selected(request('movement_type') === 'transfer_out')>Transfer Out</option>
                    <option value="transfer_in" @selected(request('movement_type') === 'transfer_in')>Transfer In</option>
                    <option value="adjustment_add" @selected(request('movement_type') === 'adjustment_add')>Adjustment (+)</option>
                    <option value="adjustment_sub" @selected(request('movement_type') === 'adjustment_sub')>Adjustment (-)</option>
                    <option value="waste" @selected(request('movement_type') === 'waste')>Waste</option>
                    <option value="production" @selected(request('movement_type') === 'production')>Production</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Generate Report
                </x-button>
            </form>
        </x-card>

        <!-- Summary by Type -->
        <div class="grid grid-cols-4 gap-4">
            <x-card>
                <div class="text-center">
                    <p class="text-2xl font-bold text-success-600">+{{ number_format(abs($summaryByType->get('goods_receive')?->total_qty ?? 0), 2) }}</p>
                    <p class="text-sm text-muted mt-1">Goods Received</p>
                    <p class="text-xs text-muted">({{ $summaryByType->get('goods_receive')?->count ?? 0 }} transactions)</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-2xl font-bold text-danger-600">-{{ number_format(abs($summaryByType->get('sale')?->total_qty ?? 0), 2) }}</p>
                    <p class="text-sm text-muted mt-1">Sales</p>
                    <p class="text-xs text-muted">({{ $summaryByType->get('sale')?->count ?? 0 }} transactions)</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-2xl font-bold text-info-600">{{ number_format(abs($summaryByType->get('transfer_out')?->total_qty ?? 0), 2) }}</p>
                    <p class="text-sm text-muted mt-1">Transfers</p>
                    <p class="text-xs text-muted">({{ ($summaryByType->get('transfer_out')?->count ?? 0) + ($summaryByType->get('transfer_in')?->count ?? 0) }} transactions)</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-2xl font-bold text-warning-600">-{{ number_format(abs($summaryByType->get('waste')?->total_qty ?? 0), 2) }}</p>
                    <p class="text-sm text-muted mt-1">Waste</p>
                    <p class="text-xs text-muted">({{ $summaryByType->get('waste')?->count ?? 0 }} transactions)</p>
                </div>
            </x-card>
        </div>

        <!-- Daily Trend Chart -->
        @if($dailyTrend->count() > 0)
            <x-card title="Daily Movement Trend">
                <div class="h-64 flex items-end gap-1">
                    @php
                        $maxIn = $dailyTrend->max(fn($d) => abs($d->get('goods_receive')?->total ?? 0));
                        $maxOut = $dailyTrend->max(fn($d) => abs($d->get('sale')?->total ?? 0));
                        $maxVal = max($maxIn, $maxOut) ?: 1;
                    @endphp
                    @foreach($dailyTrend as $date => $data)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <!-- In (green) -->
                            @php
                                $inVal = abs($data->get('goods_receive')?->total ?? 0);
                                $inHeight = ($inVal / $maxVal) * 50;
                            @endphp
                            <div class="w-full bg-success-400 rounded-t" style="height: {{ max($inHeight, 1) }}%"></div>
                            <!-- Out (red) -->
                            @php
                                $outVal = abs($data->get('sale')?->total ?? 0);
                                $outHeight = ($outVal / $maxVal) * 50;
                            @endphp
                            <div class="w-full bg-danger-400 rounded-b" style="height: {{ max($outHeight, 1) }}%"></div>
                            <span class="text-xs text-muted transform -rotate-45 origin-top-left mt-2">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-center gap-6 mt-4">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-success-400 rounded"></div>
                        <span class="text-sm text-muted">Stock In</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-danger-400 rounded"></div>
                        <span class="text-sm text-muted">Stock Out</span>
                    </div>
                </div>
            </x-card>
        @endif

        <!-- Movement Table -->
        <x-card title="Movement Details">
            @if($movements->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>Date</x-th>
                        <x-th>Item</x-th>
                        <x-th>Outlet</x-th>
                        <x-th>Type</x-th>
                        <x-th align="right">Quantity</x-th>
                        <x-th align="right">Unit Cost</x-th>
                        <x-th>Reference</x-th>
                        <x-th>User</x-th>
                    </x-slot>

                    @foreach($movements as $movement)
                        <tr>
                            <x-td>{{ $movement->created_at->format('M d, Y H:i') }}</x-td>
                            <x-td>
                                <p class="font-medium">{{ $movement->inventoryItem->name }}</p>
                                <p class="text-xs text-muted">{{ $movement->inventoryItem->sku }}</p>
                            </x-td>
                            <x-td>{{ $movement->outlet->name }}</x-td>
                            <x-td>
                                @switch($movement->movement_type)
                                    @case('goods_receive')
                                        <x-badge type="success">Goods Receive</x-badge>
                                        @break
                                    @case('sale')
                                        <x-badge type="danger">Sale</x-badge>
                                        @break
                                    @case('transfer_out')
                                        <x-badge type="info">Transfer Out</x-badge>
                                        @break
                                    @case('transfer_in')
                                        <x-badge type="info">Transfer In</x-badge>
                                        @break
                                    @case('adjustment_add')
                                        <x-badge type="success">Adjustment (+)</x-badge>
                                        @break
                                    @case('adjustment_sub')
                                        <x-badge type="warning">Adjustment (-)</x-badge>
                                        @break
                                    @case('waste')
                                        <x-badge type="danger">Waste</x-badge>
                                        @break
                                    @case('production')
                                        <x-badge type="secondary">Production</x-badge>
                                        @break
                                    @default
                                        <x-badge type="secondary">{{ $movement->movement_type }}</x-badge>
                                @endswitch
                            </x-td>
                            <x-td align="right">
                                <span class="{{ $movement->quantity >= 0 ? 'text-success-600' : 'text-danger-600' }} font-medium">
                                    {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                                </span>
                                {{ $movement->inventoryItem->unit->abbreviation ?? '' }}
                            </x-td>
                            <x-td align="right">Rp {{ number_format($movement->unit_cost ?? 0, 0, ',', '.') }}</x-td>
                            <x-td>
                                @if($movement->reference_type && $movement->reference_id)
                                    <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ class_basename($movement->reference_type) }}#{{ Str::limit($movement->reference_id, 8) }}</code>
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td>{{ $movement->user->name ?? '-' }}</x-td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="mt-6">
                    <x-pagination :paginator="$movements" />
                </div>
            @else
                <x-empty-state
                    title="No movements found"
                    description="No stock movements found for the selected filters."
                    icon="arrows-right-left"
                />
            @endif
        </x-card>
    </div>
</x-app-layout>
