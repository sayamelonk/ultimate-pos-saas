<x-app-layout>
    <x-slot name="title">Food Cost Report - Ultimate POS</x-slot>

    @section('page-title', 'Food Cost')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Food Cost Analysis Report</h2>
                <p class="text-muted mt-1">Analyze food cost percentages and profitability</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.reports.cogs') }}" variant="outline-secondary" icon="calculator">
                    COGS Report
                </x-button>
                <x-button href="{{ route('inventory.recipes.cost-analysis') }}" variant="outline-secondary" icon="beaker">
                    Recipe Analysis
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('inventory.reports.food-cost') }}" class="flex flex-wrap gap-4">
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
                <x-button type="submit" variant="secondary">
                    Generate Report
                </x-button>
            </form>
        </x-card>

        <!-- Key Metrics -->
        <div class="grid grid-cols-4 gap-4">
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold {{ ($avgFoodCost ?? 0) <= 30 ? 'text-success-600' : (($avgFoodCost ?? 0) <= 35 ? 'text-warning-600' : 'text-danger-600') }}">
                        {{ number_format($avgFoodCost ?? 0, 1) }}%
                    </p>
                    <p class="text-sm text-muted mt-2">Avg Food Cost %</p>
                    <p class="text-xs text-muted">Target: ≤30%</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold {{ ($avgMargin ?? 0) >= 65 ? 'text-success-600' : (($avgMargin ?? 0) >= 60 ? 'text-warning-600' : 'text-danger-600') }}">
                        {{ number_format($avgMargin ?? 0, 1) }}%
                    </p>
                    <p class="text-sm text-muted mt-2">Avg Gross Margin</p>
                    <p class="text-xs text-muted">Target: ≥65%</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-danger-600">Rp {{ number_format($wasteTotal ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-2">Waste Value</p>
                    <p class="text-xs text-muted">{{ $dateFrom }} - {{ $dateTo }}</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-text">Rp {{ number_format($purchaseTotal ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-2">Purchases</p>
                    <p class="text-xs text-muted">{{ $dateFrom }} - {{ $dateTo }}</p>
                </div>
            </x-card>
        </div>

        <!-- Food Cost Gauge -->
        <x-card title="Food Cost Performance">
            <div class="flex items-center justify-center py-8">
                <div class="relative w-64 h-32">
                    <!-- Gauge background -->
                    <div class="absolute inset-0 flex items-end justify-center">
                        <div class="w-64 h-32 rounded-t-full bg-gradient-to-r from-success-400 via-warning-400 to-danger-400 overflow-hidden"></div>
                    </div>
                    <!-- Gauge center cutout -->
                    <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-48 h-24 rounded-t-full bg-card"></div>
                    <!-- Needle -->
                    @php
                        $rotation = min(180, max(0, ($avgFoodCost ?? 0) / 50 * 180)) - 90;
                    @endphp
                    <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 origin-bottom" style="transform: translateX(-50%) rotate({{ $rotation }}deg);">
                        <div class="w-1 h-24 bg-text rounded-full"></div>
                    </div>
                    <!-- Center point -->
                    <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-text rounded-full"></div>
                </div>
            </div>
            <div class="flex justify-between px-8 mt-4">
                <span class="text-sm text-success-600 font-medium">0% (Excellent)</span>
                <span class="text-sm text-warning-600 font-medium">25% (Good)</span>
                <span class="text-sm text-danger-600 font-medium">50%+ (Review)</span>
            </div>
        </x-card>

        <div class="grid grid-cols-2 gap-6">
            <!-- Food Cost by Category -->
            <x-card title="Food Cost by Category">
                @if($costByCategory->count() > 0)
                    <div class="space-y-4">
                        @foreach($costByCategory as $category => $data)
                            <div class="p-4 bg-secondary-50 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium">{{ $category }}</span>
                                    <span class="text-sm text-muted">{{ $data['count'] }} recipes</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <p class="text-xs text-muted">Avg Food Cost</p>
                                        <p class="text-lg font-bold {{ $data['avg_food_cost'] <= 30 ? 'text-success-600' : ($data['avg_food_cost'] <= 35 ? 'text-warning-600' : 'text-danger-600') }}">
                                            {{ number_format($data['avg_food_cost'], 1) }}%
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted">Avg Margin</p>
                                        <p class="text-lg font-bold {{ $data['avg_margin'] >= 65 ? 'text-success-600' : ($data['avg_margin'] >= 60 ? 'text-warning-600' : 'text-danger-600') }}">
                                            {{ number_format($data['avg_margin'], 1) }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="No data"
                        description="No recipes with pricing found."
                        icon="chart-pie"
                    />
                @endif
            </x-card>

            <!-- High Cost Alert -->
            <x-card title="High Food Cost Items (>35%)" class="border-warning-300">
                @if($highCostItems->count() > 0)
                    <div class="space-y-3">
                        @foreach($highCostItems->sortByDesc('food_cost_percent')->take(10) as $item)
                            <div class="flex items-center justify-between p-3 bg-warning-50 rounded-lg">
                                <div>
                                    <a href="{{ route('inventory.recipes.show', $item['recipe']) }}" class="font-medium text-warning-700 hover:underline">
                                        {{ $item['recipe']->name }}
                                    </a>
                                    <p class="text-xs text-warning-600">
                                        Cost: Rp {{ number_format($item['unit_cost'], 0, ',', '.') }} |
                                        Price: Rp {{ number_format($item['selling_price'], 0, ',', '.') }}
                                    </p>
                                </div>
                                <span class="text-lg font-bold text-danger-600">{{ number_format($item['food_cost_percent'], 1) }}%</span>
                            </div>
                        @endforeach
                    </div>
                    @if($highCostItems->count() > 10)
                        <p class="mt-4 text-sm text-muted text-center">{{ $highCostItems->count() - 10 }} more items with high food cost</p>
                    @endif
                @else
                    <div class="flex items-center gap-3 p-4 bg-success-50 rounded-lg">
                        <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                        <div>
                            <p class="font-medium text-success-700">All recipes within target!</p>
                            <p class="text-sm text-success-600">No recipes exceed 35% food cost.</p>
                        </div>
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Recipe Analysis Table -->
        <x-card title="Recipe Food Cost Analysis">
            @if($recipeAnalysis->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>Recipe</x-th>
                        <x-th>Category</x-th>
                        <x-th align="right">Unit Cost</x-th>
                        <x-th align="right">Selling Price</x-th>
                        <x-th align="right">Gross Profit</x-th>
                        <x-th align="right">Food Cost %</x-th>
                        <x-th align="right">Margin %</x-th>
                    </x-slot>

                    @foreach($recipeAnalysis->sortBy('food_cost_percent') as $data)
                        @if($data['selling_price'] > 0)
                            <tr>
                                <x-td>
                                    <a href="{{ route('inventory.recipes.show', $data['recipe']) }}" class="text-accent hover:underline font-medium">
                                        {{ $data['recipe']->name }}
                                    </a>
                                </x-td>
                                <x-td>{{ $data['recipe']->category->name ?? '-' }}</x-td>
                                <x-td align="right">Rp {{ number_format($data['unit_cost'], 0, ',', '.') }}</x-td>
                                <x-td align="right">Rp {{ number_format($data['selling_price'], 0, ',', '.') }}</x-td>
                                <x-td align="right" class="font-medium {{ $data['gross_profit'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                    Rp {{ number_format($data['gross_profit'], 0, ',', '.') }}
                                </x-td>
                                <x-td align="right">
                                    <span class="{{ $data['food_cost_percent'] <= 30 ? 'text-success-600' : ($data['food_cost_percent'] <= 35 ? 'text-warning-600' : 'text-danger-600') }} font-medium">
                                        {{ number_format($data['food_cost_percent'], 1) }}%
                                    </span>
                                </x-td>
                                <x-td align="right">
                                    <span class="{{ $data['gross_margin_percent'] >= 65 ? 'text-success-600' : ($data['gross_margin_percent'] >= 60 ? 'text-warning-600' : 'text-danger-600') }} font-medium">
                                        {{ number_format($data['gross_margin_percent'], 1) }}%
                                    </span>
                                </x-td>
                            </tr>
                        @endif
                    @endforeach
                </x-table>
            @else
                <x-empty-state
                    title="No recipes found"
                    description="Create recipes and link them to products to see food cost analysis."
                    icon="beaker"
                />
            @endif
        </x-card>

        <!-- Food Cost Tips -->
        <x-card title="Optimization Tips">
            <div class="grid grid-cols-3 gap-4">
                <div class="p-4 bg-info-50 rounded-lg">
                    <x-icon name="light-bulb" class="w-8 h-8 text-info-600 mb-3" />
                    <h4 class="font-medium text-info-700">Review High-Cost Items</h4>
                    <p class="text-sm text-info-600 mt-1">Consider adjusting portion sizes or finding alternative ingredients for items with >35% food cost.</p>
                </div>
                <div class="p-4 bg-info-50 rounded-lg">
                    <x-icon name="arrow-trending-down" class="w-8 h-8 text-info-600 mb-3" />
                    <h4 class="font-medium text-info-700">Reduce Waste</h4>
                    <p class="text-sm text-info-600 mt-1">Track waste patterns and implement FIFO/FEFO to minimize spoilage and expired inventory.</p>
                </div>
                <div class="p-4 bg-info-50 rounded-lg">
                    <x-icon name="chart-bar" class="w-8 h-8 text-info-600 mb-3" />
                    <h4 class="font-medium text-info-700">Monitor Regularly</h4>
                    <p class="text-sm text-info-600 mt-1">Update recipe costs when ingredient prices change to maintain accurate food cost calculations.</p>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
