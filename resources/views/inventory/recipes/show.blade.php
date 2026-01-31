<x-app-layout>
    <x-slot name="title">{{ $recipe->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Recipe Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.recipes.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $recipe->name }}</h2>
                    <p class="text-muted mt-1">{{ $recipe->category->name ?? 'Uncategorized' }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <form action="{{ route('inventory.recipes.recalculate-cost', $recipe) }}" method="POST" class="inline">
                    @csrf
                    <x-button type="submit" variant="outline-secondary" icon="calculator">
                        Recalculate Cost
                    </x-button>
                </form>
                <form action="{{ route('inventory.recipes.duplicate', $recipe) }}" method="POST" class="inline">
                    @csrf
                    <x-button type="submit" variant="outline-secondary" icon="document-duplicate">
                        Duplicate
                    </x-button>
                </form>
                <x-button href="{{ route('inventory.recipes.edit', $recipe) }}" icon="pencil">
                    Edit Recipe
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        <div class="grid grid-cols-3 gap-6">
            <x-card title="Recipe Information" class="col-span-2">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">Recipe Name</dt>
                        <dd class="mt-1 font-medium">{{ $recipe->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Category</dt>
                        <dd class="mt-1">{{ $recipe->category->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Linked Product</dt>
                        <dd class="mt-1">
                            @if($recipe->product)
                                <a href="#" class="text-accent hover:underline">{{ $recipe->product->name }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($recipe->is_active)
                                <x-badge type="success">Active</x-badge>
                            @else
                                <x-badge type="secondary">Inactive</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Yield</dt>
                        <dd class="mt-1 font-medium">{{ number_format($recipe->yield_quantity, 2) }} {{ $recipe->yieldUnit->abbreviation ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Last Updated</dt>
                        <dd class="mt-1">{{ $recipe->updated_at->format('M d, Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Cost Summary">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-muted">Ingredients</dt>
                        <dd class="font-medium">{{ $recipe->ingredients->count() }}</dd>
                    </div>
                    <div class="flex justify-between pt-4 border-t border-border">
                        <dt class="font-bold">Total Cost</dt>
                        <dd class="font-bold text-xl text-accent">Rp {{ number_format($recipe->total_cost, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Cost per Unit</dt>
                        <dd class="font-medium">
                            @if($recipe->yield_quantity > 0)
                                Rp {{ number_format($recipe->total_cost / $recipe->yield_quantity, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    @if($recipe->product && $recipe->product->price > 0)
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="text-muted">Selling Price</dt>
                            <dd class="font-medium">Rp {{ number_format($recipe->product->price, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Gross Margin</dt>
                            @php
                                $margin = $recipe->product->price - ($recipe->yield_quantity > 0 ? $recipe->total_cost / $recipe->yield_quantity : 0);
                                $marginPercent = $recipe->product->price > 0 ? ($margin / $recipe->product->price) * 100 : 0;
                            @endphp
                            <dd class="font-medium {{ $marginPercent >= 60 ? 'text-success-600' : ($marginPercent >= 40 ? 'text-warning-600' : 'text-danger-600') }}">
                                {{ number_format($marginPercent, 1) }}%
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>

        <x-card title="Ingredients">
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th align="right">Quantity</x-th>
                    <x-th align="right">Unit Cost</x-th>
                    <x-th align="right">Total Cost</x-th>
                    <x-th align="right">% of Total</x-th>
                </x-slot>

                @foreach($recipe->ingredients as $ingredient)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $ingredient->inventoryItem->name }}</p>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $ingredient->inventoryItem->sku }}</code>
                        </x-td>
                        <x-td align="right">
                            {{ number_format($ingredient->quantity, 3) }}
                            {{ $ingredient->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right">Rp {{ number_format($ingredient->inventoryItem->cost_price, 0, ',', '.') }}</x-td>
                        <x-td align="right" class="font-medium">Rp {{ number_format($ingredient->cost, 0, ',', '.') }}</x-td>
                        <x-td align="right">
                            @if($recipe->total_cost > 0)
                                {{ number_format(($ingredient->cost / $recipe->total_cost) * 100, 1) }}%
                            @else
                                -
                            @endif
                        </x-td>
                    </tr>
                @endforeach

                <tr class="bg-secondary-50 font-bold">
                    <x-td colspan="4" align="right">Total</x-td>
                    <x-td align="right">Rp {{ number_format($recipe->total_cost, 0, ',', '.') }}</x-td>
                    <x-td align="right">100%</x-td>
                </tr>
            </x-table>
        </x-card>

        @if($recipe->instructions)
            <x-card title="Preparation Instructions">
                <div class="prose prose-sm max-w-none">
                    {!! nl2br(e($recipe->instructions)) !!}
                </div>
            </x-card>
        @endif

        @if($recipe->notes)
            <x-card title="Notes">
                <p class="text-text">{{ $recipe->notes }}</p>
            </x-card>
        @endif
    </div>
</x-app-layout>
