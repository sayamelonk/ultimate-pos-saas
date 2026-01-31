<x-app-layout>
    <x-slot name="title">Stock Valuation Report - Ultimate POS</x-slot>

    @section('page-title', 'Stock Valuation')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Stock Valuation Report</h2>
                <p class="text-muted mt-1">View current inventory value by location and category</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.reports.stock-movement') }}" variant="outline-secondary" icon="arrows-right-left">
                    Movement Report
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
            <form method="GET" action="{{ route('inventory.reports.stock-valuation') }}" class="flex flex-wrap gap-4">
                <x-select name="outlet_id" class="w-48">
                    <option value="">All Outlets</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="category_id" class="w-48">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-button type="submit" variant="secondary">
                    Generate Report
                </x-button>
                @if(request()->hasAny(['outlet_id', 'category_id']))
                    <x-button href="{{ route('inventory.reports.stock-valuation') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </x-card>

        <!-- Summary Stats -->
        <div class="grid grid-cols-3 gap-4">
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-accent">Rp {{ number_format($totalValue, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-2">Total Inventory Value</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-text">{{ number_format($totalItems) }}</p>
                    <p class="text-sm text-muted mt-2">Total Stock Records</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-text">{{ $byCategory->count() }}</p>
                    <p class="text-sm text-muted mt-2">Categories</p>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- By Category -->
            <x-card title="Value by Category">
                @if($byCategory->count() > 0)
                    <div class="space-y-4">
                        @foreach($byCategory->sortByDesc('value') as $category => $data)
                            @php
                                $percentage = $totalValue > 0 ? ($data['value'] / $totalValue) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-medium">{{ $category }}</span>
                                        <span class="text-sm text-muted">({{ $data['count'] }} items)</span>
                                    </div>
                                    <span class="font-medium">Rp {{ number_format($data['value'], 0, ',', '.') }}</span>
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
                        description="No stock records found."
                        icon="cube"
                    />
                @endif
            </x-card>

            <!-- By Outlet -->
            <x-card title="Value by Outlet">
                @if($byOutlet->count() > 0)
                    <div class="space-y-4">
                        @foreach($byOutlet->sortByDesc('value') as $outlet => $data)
                            @php
                                $percentage = $totalValue > 0 ? ($data['value'] / $totalValue) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-medium">{{ $outlet }}</span>
                                        <span class="text-sm text-muted">({{ $data['count'] }} items)</span>
                                    </div>
                                    <span class="font-medium">Rp {{ number_format($data['value'], 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full bg-secondary-200 rounded-full h-2">
                                    <div class="bg-success-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="No data"
                        description="No stock records found."
                        icon="building-storefront"
                    />
                @endif
            </x-card>
        </div>

        <!-- Detail Table -->
        <x-card title="Stock Valuation Details">
            @if($valuationData->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>Item</x-th>
                        <x-th>SKU</x-th>
                        <x-th>Category</x-th>
                        <x-th>Outlet</x-th>
                        <x-th align="right">Quantity</x-th>
                        <x-th align="right">Avg Cost</x-th>
                        <x-th align="right">Total Value</x-th>
                        <x-th align="right">% of Total</x-th>
                    </x-slot>

                    @foreach($valuationData->sortByDesc('value')->take(50) as $data)
                        <tr>
                            <x-td>
                                <p class="font-medium">{{ $data['item']->name }}</p>
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $data['item']->sku }}</code>
                            </x-td>
                            <x-td>{{ $data['item']->category->name ?? '-' }}</x-td>
                            <x-td>{{ $data['outlet']->name }}</x-td>
                            <x-td align="right">
                                {{ number_format($data['quantity'], 2) }}
                                {{ $data['item']->unit->abbreviation ?? '' }}
                            </x-td>
                            <x-td align="right">Rp {{ number_format($data['avg_cost'], 0, ',', '.') }}</x-td>
                            <x-td align="right" class="font-medium">Rp {{ number_format($data['value'], 0, ',', '.') }}</x-td>
                            <x-td align="right">
                                {{ $totalValue > 0 ? number_format(($data['value'] / $totalValue) * 100, 1) : 0 }}%
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>

                @if($valuationData->count() > 50)
                    <p class="mt-4 text-sm text-muted text-center">Showing top 50 items by value. Export report for complete data.</p>
                @endif
            @else
                <x-empty-state
                    title="No stock data"
                    description="No stock records found for the selected filters."
                    icon="cube"
                />
            @endif
        </x-card>
    </div>
</x-app-layout>
