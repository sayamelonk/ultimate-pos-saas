<x-app-layout>
    <x-slot name="title">COGS Report - Ultimate POS</x-slot>

    @section('page-title', 'COGS Report')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Cost of Goods Sold Report</h2>
                <p class="text-muted mt-1">Analyze your cost of goods sold by period</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.reports.stock-valuation') }}" variant="outline-secondary" icon="currency-dollar">
                    Valuation Report
                </x-button>
                <x-button href="{{ route('inventory.reports.food-cost') }}" variant="outline-secondary" icon="beaker">
                    Food Cost Report
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('inventory.reports.cogs') }}" class="flex flex-wrap gap-4">
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
                <x-button type="submit" variant="secondary">
                    Generate Report
                </x-button>
            </form>
        </x-card>

        <!-- Summary Stats -->
        <div class="grid grid-cols-3 gap-4">
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-danger-600">Rp {{ number_format($totalCogs, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-2">Total COGS</p>
                    <p class="text-xs text-muted">{{ $dateFrom }} - {{ $dateTo }}</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-text">{{ $cogsByItem->count() }}</p>
                    <p class="text-sm text-muted mt-2">Items Sold</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    @php
                        $days = \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1;
                        $avgDaily = $days > 0 ? $totalCogs / $days : 0;
                    @endphp
                    <p class="text-4xl font-bold text-text">Rp {{ number_format($avgDaily, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-2">Avg Daily COGS</p>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- COGS by Category -->
            <x-card title="COGS by Category">
                @if($cogsByCategory->count() > 0)
                    <div class="space-y-4">
                        @foreach($cogsByCategory as $category => $data)
                            @php
                                $percentage = $totalCogs > 0 ? ($data['total_cost'] / $totalCogs) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-medium">{{ $category }}</span>
                                        <span class="text-sm text-muted">({{ $data['count'] }} items)</span>
                                    </div>
                                    <span class="font-medium">Rp {{ number_format($data['total_cost'], 0, ',', '.') }}</span>
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
                        description="No sales recorded in this period."
                        icon="chart-bar"
                    />
                @endif
            </x-card>

            <!-- COGS by Outlet -->
            <x-card title="COGS by Outlet">
                @if($cogsByOutlet->count() > 0)
                    <div class="space-y-4">
                        @foreach($cogsByOutlet as $data)
                            @php
                                $percentage = $totalCogs > 0 ? ($data['total_cost'] / $totalCogs) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium">{{ $data['outlet']->name }}</span>
                                    <span class="font-medium">Rp {{ number_format($data['total_cost'], 0, ',', '.') }}</span>
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
                        description="No sales recorded in this period."
                        icon="building-storefront"
                    />
                @endif
            </x-card>
        </div>

        <!-- Daily COGS Trend -->
        @if($dailyCogs->count() > 0)
            <x-card title="Daily COGS Trend">
                <div class="h-48 flex items-end gap-1">
                    @php
                        $maxVal = $dailyCogs->max('total_cost') ?: 1;
                    @endphp
                    @foreach($dailyCogs as $day)
                        @php
                            $height = ($day->total_cost / $maxVal) * 100;
                        @endphp
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-danger-400 rounded-t hover:bg-danger-500 transition-colors cursor-pointer relative group" style="height: {{ max($height, 2) }}%">
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-text text-white text-xs rounded opacity-0 group-hover:opacity-100 whitespace-nowrap transition-opacity z-10">
                                    Rp {{ number_format($day->total_cost, 0, ',', '.') }}
                                </div>
                            </div>
                            <span class="text-xs text-muted mt-1 transform -rotate-45 origin-top-left">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        <!-- COGS by Item Table -->
        <x-card title="COGS by Item">
            @if($cogsByItem->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>Rank</x-th>
                        <x-th>Item</x-th>
                        <x-th>SKU</x-th>
                        <x-th>Category</x-th>
                        <x-th align="right">Qty Sold</x-th>
                        <x-th align="right">Avg Cost</x-th>
                        <x-th align="right">Total COGS</x-th>
                        <x-th align="right">% of Total</x-th>
                    </x-slot>

                    @foreach($cogsByItem->take(20) as $index => $data)
                        <tr>
                            <x-td>
                                <span class="w-8 h-8 rounded-full bg-{{ $index < 3 ? 'danger' : 'secondary' }}-100 text-{{ $index < 3 ? 'danger' : 'secondary' }}-700 inline-flex items-center justify-center font-bold text-sm">
                                    {{ $loop->iteration }}
                                </span>
                            </x-td>
                            <x-td>
                                <p class="font-medium">{{ $data['item']->name }}</p>
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $data['item']->sku }}</code>
                            </x-td>
                            <x-td>{{ $data['item']->category->name ?? '-' }}</x-td>
                            <x-td align="right">
                                {{ number_format($data['quantity'], 2) }}
                                {{ $data['item']->unit->abbreviation ?? '' }}
                            </x-td>
                            <x-td align="right">Rp {{ number_format($data['avg_cost'], 0, ',', '.') }}</x-td>
                            <x-td align="right" class="font-medium text-danger-600">
                                Rp {{ number_format($data['total_cost'], 0, ',', '.') }}
                            </x-td>
                            <x-td align="right">
                                {{ $totalCogs > 0 ? number_format(($data['total_cost'] / $totalCogs) * 100, 1) : 0 }}%
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>

                @if($cogsByItem->count() > 20)
                    <p class="mt-4 text-sm text-muted text-center">Showing top 20 items by COGS. Export report for complete data.</p>
                @endif
            @else
                <x-empty-state
                    title="No sales data"
                    description="No sales recorded in this period."
                    icon="calculator"
                />
            @endif
        </x-card>
    </div>
</x-app-layout>
