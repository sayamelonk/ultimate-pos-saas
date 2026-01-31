<x-app-layout>
    <x-slot name="title">Waste Report - Ultimate POS</x-slot>

    @section('page-title', 'Waste Report')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.waste-logs.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Waste Report</h2>
                <p class="text-muted mt-1">Analyze waste patterns and losses</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('inventory.waste-logs.report') }}" class="flex flex-wrap gap-4">
                <x-select name="outlet_id" class="w-48">
                    <option value="">All Outlets</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-input
                    type="date"
                    name="date_from"
                    :value="request('date_from', now()->startOfMonth()->format('Y-m-d'))"
                    class="w-40"
                />
                <x-input
                    type="date"
                    name="date_to"
                    :value="request('date_to', now()->format('Y-m-d'))"
                    class="w-40"
                />
                <x-button type="submit" variant="secondary">
                    Generate Report
                </x-button>
            </form>
        </x-card>

        <!-- Summary Stats -->
        <div class="grid grid-cols-4 gap-4">
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-text">{{ $totalRecords ?? 0 }}</p>
                    <p class="text-sm text-muted mt-1">Total Records</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-danger-600">Rp {{ number_format($totalValue ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-1">Total Value Lost</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-warning-600">{{ number_format($avgDailyValue ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-1">Avg Daily Loss (Rp)</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-text">{{ $uniqueItems ?? 0 }}</p>
                    <p class="text-sm text-muted mt-1">Items Affected</p>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- Waste by Reason -->
            <x-card title="Waste by Reason">
                @if(isset($wasteByReason) && $wasteByReason->count() > 0)
                    <div class="space-y-4">
                        @foreach($wasteByReason as $reason => $data)
                            @php
                                $percentage = $totalValue > 0 ? ($data['value'] / $totalValue) * 100 : 0;
                                $reasonLabel = match($reason) {
                                    'expired' => 'Expired',
                                    'damaged' => 'Damaged',
                                    'spoiled' => 'Spoiled',
                                    'overproduction' => 'Overproduction',
                                    'quality_issue' => 'Quality Issue',
                                    default => 'Other',
                                };
                                $badgeType = match($reason) {
                                    'expired', 'spoiled' => 'danger',
                                    'damaged', 'quality_issue' => 'warning',
                                    'overproduction' => 'info',
                                    default => 'secondary',
                                };
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <x-badge type="{{ $badgeType }}">{{ $reasonLabel }}</x-badge>
                                        <span class="text-sm text-muted">({{ $data['count'] }} records)</span>
                                    </div>
                                    <span class="font-medium">Rp {{ number_format($data['value'], 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full bg-secondary-200 rounded-full h-2">
                                    <div class="bg-{{ $badgeType === 'danger' ? 'danger' : ($badgeType === 'warning' ? 'warning' : 'accent') }}-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="No data"
                        description="No waste records in selected period."
                        icon="chart-bar"
                    />
                @endif
            </x-card>

            <!-- Waste by Outlet -->
            <x-card title="Waste by Outlet">
                @if(isset($wasteByOutlet) && $wasteByOutlet->count() > 0)
                    <div class="space-y-4">
                        @foreach($wasteByOutlet as $outletData)
                            @php
                                $percentage = $totalValue > 0 ? ($outletData['value'] / $totalValue) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-medium">{{ $outletData['name'] }}</span>
                                        <span class="text-sm text-muted">({{ $outletData['count'] }} records)</span>
                                    </div>
                                    <span class="font-medium">Rp {{ number_format($outletData['value'], 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full bg-secondary-200 rounded-full h-2">
                                    <div class="bg-accent-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="No data"
                        description="No waste records in selected period."
                        icon="building-storefront"
                    />
                @endif
            </x-card>
        </div>

        <!-- Top Wasted Items -->
        <x-card title="Top Wasted Items">
            @if(isset($topWastedItems) && $topWastedItems->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>Rank</x-th>
                        <x-th>Item</x-th>
                        <x-th>SKU</x-th>
                        <x-th align="right">Quantity</x-th>
                        <x-th align="right">Value Lost</x-th>
                        <x-th align="right">% of Total</x-th>
                    </x-slot>

                    @foreach($topWastedItems as $index => $item)
                        <tr>
                            <x-td>
                                <span class="w-8 h-8 rounded-full bg-{{ $index < 3 ? 'danger' : 'secondary' }}-100 text-{{ $index < 3 ? 'danger' : 'secondary' }}-700 inline-flex items-center justify-center font-bold text-sm">
                                    {{ $index + 1 }}
                                </span>
                            </x-td>
                            <x-td>
                                <p class="font-medium">{{ $item['name'] }}</p>
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item['sku'] }}</code>
                            </x-td>
                            <x-td align="right">
                                {{ number_format($item['quantity'], 2) }}
                                {{ $item['unit'] ?? '' }}
                            </x-td>
                            <x-td align="right" class="font-medium text-danger-600">
                                Rp {{ number_format($item['value'], 0, ',', '.') }}
                            </x-td>
                            <x-td align="right">
                                {{ $totalValue > 0 ? number_format(($item['value'] / $totalValue) * 100, 1) : 0 }}%
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            @else
                <x-empty-state
                    title="No data"
                    description="No waste records in selected period."
                    icon="cube"
                />
            @endif
        </x-card>

        <!-- Daily Trend -->
        <x-card title="Daily Waste Trend">
            @if(isset($dailyTrend) && $dailyTrend->count() > 0)
                <div class="h-64 flex items-end gap-1">
                    @php
                        $maxValue = $dailyTrend->max('value') ?: 1;
                    @endphp
                    @foreach($dailyTrend as $day)
                        @php
                            $height = ($day['value'] / $maxValue) * 100;
                        @endphp
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-danger-400 rounded-t hover:bg-danger-500 transition-colors cursor-pointer relative group" style="height: {{ max($height, 2) }}%">
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-text text-white text-xs rounded opacity-0 group-hover:opacity-100 whitespace-nowrap transition-opacity">
                                    Rp {{ number_format($day['value'], 0, ',', '.') }}
                                </div>
                            </div>
                            <span class="text-xs text-muted mt-1 transform -rotate-45 origin-top-left">{{ $day['date'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state
                    title="No data"
                    description="No waste records in selected period."
                    icon="chart-bar"
                />
            @endif
        </x-card>
    </div>
</x-app-layout>
