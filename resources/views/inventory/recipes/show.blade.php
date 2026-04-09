<x-app-layout>
    <x-slot name="title">{{ $recipe->name }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.recipe_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.recipes.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('inventory.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $recipe->name }}</h2>
                    <p class="text-muted mt-1">{{ $recipe->product->category->name ?? '-' }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <form action="{{ route('inventory.recipes.recalculate', $recipe) }}" method="POST" class="inline">
                    @csrf
                    <x-button type="submit" variant="outline-secondary" icon="calculator">
                        {{ __('inventory.recalculate_cost') }}
                    </x-button>
                </form>
                <form action="{{ route('inventory.recipes.duplicate', $recipe) }}" method="POST" class="inline">
                    @csrf
                    <x-button type="submit" variant="outline-secondary" icon="document-duplicate">
                        {{ __('inventory.duplicate_recipe') }}
                    </x-button>
                </form>
                <x-button href="{{ route('inventory.recipes.edit', $recipe) }}" icon="pencil">
                    {{ __('inventory.edit_recipe') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <div class="grid grid-cols-3 gap-6">
            <x-card :title="__('inventory.recipe_information')" class="col-span-2">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.recipe_name') }}</dt>
                        <dd class="mt-1 font-medium">{{ $recipe->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.linked_product') }}</dt>
                        <dd class="mt-1">
                            @if($recipe->product)
                                <a href="#" class="text-accent hover:underline">{{ $recipe->product->name }}</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.status') }}</dt>
                        <dd class="mt-1">
                            @if($recipe->is_active)
                                <x-badge type="success">{{ __('inventory.active') }}</x-badge>
                            @else
                                <x-badge type="secondary">{{ __('inventory.inactive') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.yield') }}</dt>
                        <dd class="mt-1 font-medium">{{ number_format($recipe->yield_qty, 2) }} {{ $recipe->yieldUnit->abbreviation ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('inventory.updated') }}</dt>
                        <dd class="mt-1">{{ $recipe->updated_at->format('M d, Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card :title="__('inventory.summary')">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.ingredients') }}</dt>
                        <dd class="font-medium">{{ $recipe->items->count() }}</dd>
                    </div>
                    <div class="flex justify-between pt-4 border-t border-border">
                        <dt class="font-bold">{{ __('inventory.total_cost') }}</dt>
                        <dd class="font-bold text-xl text-accent">Rp {{ number_format($recipe->estimated_cost, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('inventory.cost_per_unit') }}</dt>
                        <dd class="font-medium">
                            @if($recipe->yield_qty > 0)
                                Rp {{ number_format($recipe->estimated_cost / $recipe->yield_qty, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    @if($recipe->product && $recipe->product->cost_price > 0)
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="text-muted">{{ __('inventory.suggested_price') }}</dt>
                            <dd class="font-medium">Rp {{ number_format($recipe->product->cost_price, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.cost_per_unit') }}</dt>
                            @php
                                $margin = $recipe->product->cost_price - ($recipe->yield_qty > 0 ? $recipe->estimated_cost / $recipe->yield_qty : 0);
                                $marginPercent = $recipe->product->cost_price > 0 ? ($margin / $recipe->product->cost_price) * 100 : 0;
                            @endphp
                            <dd class="font-medium {{ $marginPercent >= 60 ? 'text-success-600' : ($marginPercent >= 40 ? 'text-warning-600' : 'text-danger-600') }}">
                                {{ number_format($marginPercent, 1) }}%
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>

        <x-card :title="__('inventory.ingredients')">
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.sku') }}</x-th>
                    <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                    <x-th align="right">{{ __('inventory.unit_cost') }}</x-th>
                    <x-th align="right">{{ __('inventory.total_cost') }}</x-th>
                    <x-th align="right">% {{ __('inventory.total') }}</x-th>
                </x-slot>

                @foreach($recipe->items as $item)
                    @php
                        $itemCost = $item->calculateCost();
                        $baseCostPrice = (float) ($item->inventoryItem->cost_price ?? 0);
                        $recipeUnit = $item->unit;
                        $itemUnit = $item->inventoryItem->unit;

                        // Calculate unit cost in recipe unit
                        // If recipe uses g but cost is per kg, convert: cost/kg ÷ 1000 = cost/g
                        if ($recipeUnit && $itemUnit && $recipeUnit->id !== $itemUnit->id) {
                            $recipeConversion = $recipeUnit->conversion_factor ?? 1;
                            $itemConversion = $itemUnit->conversion_factor ?? 1;
                            $unitCostDisplay = $baseCostPrice * ($recipeConversion / $itemConversion);
                        } else {
                            $unitCostDisplay = $baseCostPrice;
                        }

                        $unitLabel = $recipeUnit->abbreviation ?? $itemUnit->abbreviation ?? '';

                        // Format quantity: show decimals only if needed
                        $qty = (float) $item->quantity;
                        $qtyFormatted = $qty == intval($qty) ? number_format($qty, 0) : rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',');
                    @endphp
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $item->inventoryItem->name }}</p>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">
                            {{ $qtyFormatted }} {{ $recipeUnit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right">Rp {{ number_format($unitCostDisplay, 0, ',', '.') }}/{{ $unitLabel }}</x-td>
                        <x-td align="right" class="font-medium">Rp {{ number_format($itemCost, 0, ',', '.') }}</x-td>
                        <x-td align="right">
                            @if($recipe->estimated_cost > 0)
                                {{ number_format(($itemCost / $recipe->estimated_cost) * 100, 1) }}%
                            @else
                                -
                            @endif
                        </x-td>
                    </tr>
                @endforeach

                <tr class="bg-secondary-50 font-bold">
                    <x-td colspan="4" align="right">{{ __('inventory.total') }}</x-td>
                    <x-td align="right">Rp {{ number_format($recipe->estimated_cost, 0, ',', '.') }}</x-td>
                    <x-td align="right">100%</x-td>
                </tr>
            </x-table>
        </x-card>

        @if($recipe->instructions)
            <x-card :title="__('inventory.instructions')">
                <div class="prose prose-sm max-w-none">
                    {!! nl2br(e($recipe->instructions)) !!}
                </div>
            </x-card>
        @endif

        @if($recipe->notes)
            <x-card :title="__('inventory.notes')">
                <p class="text-text">{{ $recipe->notes }}</p>
            </x-card>
        @endif
    </div>
</x-app-layout>
