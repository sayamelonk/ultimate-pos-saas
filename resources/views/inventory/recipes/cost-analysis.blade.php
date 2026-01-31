<x-app-layout>
    <x-slot name="title">Recipe Cost Analysis - Ultimate POS</x-slot>

    @section('page-title', 'Cost Analysis')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.recipes.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Recipe Cost Analysis</h2>
                <p class="text-muted mt-1">Analyze recipe costs and profitability</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Summary Stats -->
        <div class="grid grid-cols-4 gap-4">
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-text">{{ $recipes->count() }}</p>
                    <p class="text-sm text-muted mt-1">Total Recipes</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-success-600">{{ $recipes->where('is_active', true)->count() }}</p>
                    <p class="text-sm text-muted mt-1">Active Recipes</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-text">Rp {{ number_format($recipes->avg('total_cost'), 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-1">Avg Recipe Cost</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    @php
                        $avgMargin = $recipes->filter(fn($r) => $r->product && $r->product->price > 0 && $r->yield_quantity > 0)
                            ->avg(fn($r) => (($r->product->price - ($r->total_cost / $r->yield_quantity)) / $r->product->price) * 100);
                    @endphp
                    <p class="text-3xl font-bold {{ $avgMargin >= 60 ? 'text-success-600' : ($avgMargin >= 40 ? 'text-warning-600' : 'text-danger-600') }}">
                        {{ number_format($avgMargin ?? 0, 1) }}%
                    </p>
                    <p class="text-sm text-muted mt-1">Avg Gross Margin</p>
                </div>
            </x-card>
        </div>

        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('inventory.recipes.cost-analysis') }}" class="flex flex-wrap gap-4">
                <x-select name="category_id" class="w-48">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="margin_filter" class="w-48">
                    <option value="">All Margins</option>
                    <option value="high" @selected(request('margin_filter') === 'high')>High (≥60%)</option>
                    <option value="medium" @selected(request('margin_filter') === 'medium')>Medium (40-60%)</option>
                    <option value="low" @selected(request('margin_filter') === 'low')>Low (<40%)</option>
                </x-select>
                <x-select name="sort" class="w-48">
                    <option value="name" @selected(request('sort') === 'name')>Sort by Name</option>
                    <option value="cost_asc" @selected(request('sort') === 'cost_asc')>Cost: Low to High</option>
                    <option value="cost_desc" @selected(request('sort') === 'cost_desc')>Cost: High to Low</option>
                    <option value="margin_asc" @selected(request('sort') === 'margin_asc')>Margin: Low to High</option>
                    <option value="margin_desc" @selected(request('sort') === 'margin_desc')>Margin: High to Low</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Apply Filters
                </x-button>
                @if(request()->hasAny(['category_id', 'margin_filter', 'sort']))
                    <x-button href="{{ route('inventory.recipes.cost-analysis') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </x-card>

        <!-- Recipe Cost Table -->
        <x-card title="Recipe Cost Breakdown">
            @if($recipes->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>Recipe</x-th>
                        <x-th>Category</x-th>
                        <x-th align="right">Yield</x-th>
                        <x-th align="right">Total Cost</x-th>
                        <x-th align="right">Unit Cost</x-th>
                        <x-th align="right">Selling Price</x-th>
                        <x-th align="right">Gross Margin</x-th>
                        <x-th align="center">Status</x-th>
                    </x-slot>

                    @foreach($recipes as $recipe)
                        @php
                            $unitCost = $recipe->yield_quantity > 0 ? $recipe->total_cost / $recipe->yield_quantity : 0;
                            $sellingPrice = $recipe->product?->price ?? 0;
                            $margin = $sellingPrice > 0 ? $sellingPrice - $unitCost : 0;
                            $marginPercent = $sellingPrice > 0 ? ($margin / $sellingPrice) * 100 : 0;
                        @endphp
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.recipes.show', $recipe) }}" class="text-accent hover:underline font-medium">
                                    {{ $recipe->name }}
                                </a>
                                <p class="text-xs text-muted">{{ $recipe->ingredients->count() }} ingredients</p>
                            </x-td>
                            <x-td>{{ $recipe->category->name ?? '-' }}</x-td>
                            <x-td align="right">
                                {{ number_format($recipe->yield_quantity, 2) }}
                                {{ $recipe->yieldUnit->abbreviation ?? '' }}
                            </x-td>
                            <x-td align="right">Rp {{ number_format($recipe->total_cost, 0, ',', '.') }}</x-td>
                            <x-td align="right" class="font-medium">Rp {{ number_format($unitCost, 0, ',', '.') }}</x-td>
                            <x-td align="right">
                                @if($sellingPrice > 0)
                                    Rp {{ number_format($sellingPrice, 0, ',', '.') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </x-td>
                            <x-td align="right">
                                @if($sellingPrice > 0)
                                    <span class="{{ $marginPercent >= 60 ? 'text-success-600' : ($marginPercent >= 40 ? 'text-warning-600' : 'text-danger-600') }} font-medium">
                                        {{ number_format($marginPercent, 1) }}%
                                    </span>
                                    <p class="text-xs text-muted">Rp {{ number_format($margin, 0, ',', '.') }}</p>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </x-td>
                            <x-td align="center">
                                @if($recipe->is_active)
                                    <x-badge type="success">Active</x-badge>
                                @else
                                    <x-badge type="secondary">Inactive</x-badge>
                                @endif
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            @else
                <x-empty-state
                    title="No recipes found"
                    description="Create recipes to see cost analysis."
                    icon="beaker"
                />
            @endif
        </x-card>

        <!-- Low Margin Alert -->
        @php
            $lowMarginRecipes = $recipes->filter(function($r) {
                if (!$r->product || $r->product->price <= 0 || $r->yield_quantity <= 0) return false;
                $unitCost = $r->total_cost / $r->yield_quantity;
                $marginPercent = (($r->product->price - $unitCost) / $r->product->price) * 100;
                return $marginPercent < 40;
            });
        @endphp
        @if($lowMarginRecipes->count() > 0)
            <x-card title="Low Margin Alert" class="border-warning-300">
                <div class="flex items-start gap-3 mb-4">
                    <x-icon name="exclamation-triangle" class="w-6 h-6 text-warning-500 flex-shrink-0" />
                    <div>
                        <p class="font-medium text-warning-700">{{ $lowMarginRecipes->count() }} recipes have margins below 40%</p>
                        <p class="text-sm text-muted">Consider reviewing ingredient costs or adjusting selling prices.</p>
                    </div>
                </div>

                <div class="space-y-2">
                    @foreach($lowMarginRecipes->take(5) as $recipe)
                        @php
                            $unitCost = $recipe->total_cost / $recipe->yield_quantity;
                            $marginPercent = (($recipe->product->price - $unitCost) / $recipe->product->price) * 100;
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-warning-50 rounded-lg">
                            <div>
                                <a href="{{ route('inventory.recipes.show', $recipe) }}" class="font-medium text-warning-700 hover:underline">
                                    {{ $recipe->name }}
                                </a>
                                <p class="text-xs text-warning-600">
                                    Cost: Rp {{ number_format($unitCost, 0, ',', '.') }} |
                                    Price: Rp {{ number_format($recipe->product->price, 0, ',', '.') }}
                                </p>
                            </div>
                            <span class="text-lg font-bold text-danger-600">{{ number_format($marginPercent, 1) }}%</span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>
</x-app-layout>
