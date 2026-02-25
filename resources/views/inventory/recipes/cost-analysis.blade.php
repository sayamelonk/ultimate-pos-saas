<x-app-layout>
    <x-slot name="title">{{ __('inventory.cost_analysis') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.cost_analysis'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.recipes.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.cost_analysis') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.analyze_recipe_costs') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Summary Stats -->
        <div class="grid grid-cols-4 gap-4">
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-text">{{ $recipes->count() }}</p>
                    <p class="text-sm text-muted mt-1">{{ __('inventory.total_recipes') }}</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-success-600">{{ $recipes->where('is_active', true)->count() }}</p>
                    <p class="text-sm text-muted mt-1">{{ __('inventory.active_recipes') }}</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-text">Rp {{ number_format($recipes->avg('estimated_cost'), 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-1">{{ __('inventory.avg_recipe_cost') }}</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-3xl font-bold text-text">{{ $recipes->sum(fn($r) => $r->items->count()) }}</p>
                    <p class="text-sm text-muted mt-1">{{ __('inventory.total_ingredients') }}</p>
                </div>
            </x-card>
        </div>

        <!-- Recipe Cost Table -->
        <x-card :title="__('inventory.recipe_cost_breakdown')">
            @if($recipes->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.recipe') }}</x-th>
                        <x-th align="right">{{ __('inventory.yield') }}</x-th>
                        <x-th align="right">{{ __('inventory.ingredients') }}</x-th>
                        <x-th align="right">{{ __('inventory.total_cost') }}</x-th>
                        <x-th align="right">{{ __('inventory.cost_per_unit') }}</x-th>
                        <x-th align="right">{{ __('inventory.suggested_price') }} (30%)</x-th>
                        <x-th align="right">{{ __('inventory.suggested_price') }} (35%)</x-th>
                    </x-slot>

                    @foreach($recipes as $recipe)
                        @php
                            $totalCost = $recipe->estimated_cost ?? 0;
                            $yieldQty = $recipe->yield_qty ?? 1;
                            $costPerUnit = $yieldQty > 0 ? $totalCost / $yieldQty : 0;
                            // Suggested selling price = Cost / (1 - margin%)
                            $suggestedPrice30 = $costPerUnit > 0 ? $costPerUnit / 0.70 : 0;
                            $suggestedPrice35 = $costPerUnit > 0 ? $costPerUnit / 0.65 : 0;
                        @endphp
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.recipes.show', $recipe) }}" class="text-accent hover:underline font-medium">
                                    {{ $recipe->name }}
                                </a>
                                @if($recipe->description)
                                    <p class="text-xs text-muted">{{ Str::limit($recipe->description, 50) }}</p>
                                @endif
                            </x-td>
                            <x-td align="right">
                                {{ $yieldQty == intval($yieldQty) ? number_format($yieldQty, 0) : number_format($yieldQty, 2) }}
                                {{ $recipe->yieldUnit->abbreviation ?? '' }}
                            </x-td>
                            <x-td align="right">{{ $recipe->items->count() }}</x-td>
                            <x-td align="right">Rp {{ number_format($totalCost, 0, ',', '.') }}</x-td>
                            <x-td align="right" class="font-medium">Rp {{ number_format($costPerUnit, 0, ',', '.') }}</x-td>
                            <x-td align="right">Rp {{ number_format($suggestedPrice30, 0, ',', '.') }}</x-td>
                            <x-td align="right">Rp {{ number_format($suggestedPrice35, 0, ',', '.') }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
            @else
                <x-empty-state
                    :title="__('inventory.no_recipes_found')"
                    :description="__('inventory.create_recipes_for_analysis')"
                    icon="beaker"
                />
            @endif
        </x-card>

        <!-- Price Guide -->
        <x-card :title="__('inventory.pricing_guide')">
            <div class="prose prose-sm max-w-none text-muted">
                <p>{{ __('inventory.pricing_guide_description') }}</p>
                <ul class="mt-2 space-y-1">
                    <li><strong>30% margin:</strong> {{ __('inventory.margin_30_description') }}</li>
                    <li><strong>35% margin:</strong> {{ __('inventory.margin_35_description') }}</li>
                </ul>
            </div>
        </x-card>
    </div>
</x-app-layout>
