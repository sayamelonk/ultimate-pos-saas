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
                    :value="request('date_from', \Carbon\Carbon::parse($dateFrom)->format('Y-m-d'))"
                    class="w-40"
                />
                <x-input
                    type="date"
                    name="date_to"
                    :value="request('date_to', \Carbon\Carbon::parse($dateTo)->format('Y-m-d'))"
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
                    <p class="text-3xl font-bold text-text">{{ $wasteByReason->sum('count') }}</p>
                    <p class="text-sm text-muted mt-1">Total Records</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-danger-600">Rp {{ number_format($totalWasteValue ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-1">Total Value Lost</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    @php
                        $dayCount = max(1, $dailyTrend->count());
                        $avgDaily = $totalWasteValue / $dayCount;
                    @endphp
                    <p class="text-3xl font-bold text-warning-600">Rp {{ number_format($avgDaily, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-1">Avg Daily Loss</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-text">{{ $wasteByItem->count() }}</p>
                    <p class="text-sm text-muted mt-1">Items Affected</p>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- Waste by Reason -->
            <x-card title="Waste by Reason">
                @if($wasteByReason->count() > 0)
                    <div class="space-y-4">
                        @foreach($wasteByReason as $data)
                            @php
                                $percentage = $totalWasteValue > 0 ? ($data->total_cost / $totalWasteValue) * 100 : 0;
                                $reasonLabel = match($data->reason) {
                                    'expired' => 'Expired',
                                    'damaged' => 'Damaged',
                                    'spillage' => 'Spillage',
                                    'overproduction' => 'Overproduction',
                                    'quality_issue' => 'Quality Issue',
                                    default => 'Other',
                                };
                                $badgeType = match($data->reason) {
                                    'expired' => 'danger',
                                    'damaged', 'quality_issue' => 'warning',
                                    'spillage' => 'info',
                                    'overproduction' => 'secondary',
                                    default => 'secondary',
                                };
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <x-badge type="{{ $badgeType }}">{{ $reasonLabel }}</x-badge>
                                        <span class="text-sm text-muted">({{ $data->count }} records)</span>
                                    </div>
                                    <span class="font-medium">Rp {{ number_format($data->total_cost, 0, ',', '.') }}</span>
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
                        icon="chart-bar"
                    />
                @endif
            </x-card>

            <!-- Top Wasted Items -->
            <x-card title="Top Wasted Items">
                @if($wasteByItem->count() > 0)
                    <div class="space-y-4">
                        @foreach($wasteByItem->take(10) as $item)
                            @php
                                $percentage = $totalWasteValue > 0 ? ($item->total_cost / $totalWasteValue) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-medium">{{ $item->inventoryItem->name ?? 'Unknown' }}</span>
                                        <span class="text-sm text-muted">({{ number_format($item->total_quantity, 2) }} {{ $item->inventoryItem->unit->abbreviation ?? '' }})</span>
                                    </div>
                                    <span class="font-medium text-danger-600">Rp {{ number_format($item->total_cost, 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full bg-secondary-200 rounded-full h-2">
                                    <div class="bg-danger-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="No data"
                        description="No waste records in selected period."
                        icon="cube"
                    />
                @endif
            </x-card>
        </div>

        <!-- Daily Trend -->
        <x-card title="Daily Waste Trend">
            @if($dailyTrend->count() > 0)
                <div class="h-64 flex items-end gap-1">
                    @php
                        $maxValue = $dailyTrend->max('total_cost') ?: 1;
                    @endphp
                    @foreach($dailyTrend as $day)
                        @php
                            $height = ($day->total_cost / $maxValue) * 100;
                        @endphp
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-danger-400 rounded-t hover:bg-danger-500 transition-colors cursor-pointer relative group" style="height: {{ max($height, 2) }}%">
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-text text-white text-xs rounded opacity-0 group-hover:opacity-100 whitespace-nowrap transition-opacity">
                                    Rp {{ number_format($day->total_cost, 0, ',', '.') }}
                                </div>
                            </div>
                            <span class="text-xs text-muted mt-1 transform -rotate-45 origin-top-left">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
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
