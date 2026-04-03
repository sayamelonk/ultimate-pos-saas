<x-app-layout>
    <x-slot name="title">{{ $product->name }} - Ultimate POS</x-slot>

    @section('page-title', __('products.product_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('menu.products.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('products.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $product->name }}</h2>
                    <p class="text-muted mt-1">{{ $product->sku }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('menu.products.edit', $product) }}" icon="pencil">
                    {{ __('products.edit') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            <!-- Basic Information -->
            <x-card title="{{ __('products.product_information') }}">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.name') }}</dt>
                        <dd class="mt-1 font-medium">{{ $product->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.sku') }}</dt>
                        <dd class="mt-1">
                            <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $product->sku }}</code>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.category') }}</dt>
                        <dd class="mt-1">
                            @if($product->category)
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $product->category->color ?? '#6b7280' }};"></span>
                                    {{ $product->category->full_path }}
                                </span>
                            @else
                                <span class="text-muted">{{ __('products.uncategorized') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.type') }}</dt>
                        <dd class="mt-1">
                            @if($product->product_type === 'single')
                                <x-badge type="secondary">{{ __('products.single_product') }}</x-badge>
                            @elseif($product->product_type === 'variant')
                                <x-badge type="info">{{ __('products.variant_product') }}</x-badge>
                            @else
                                <x-badge type="warning">{{ __('products.combo_product') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.status') }}</dt>
                        <dd class="mt-1">
                            @if($product->is_active)
                                <x-badge type="success">{{ __('products.active') }}</x-badge>
                            @else
                                <x-badge type="danger">{{ __('products.inactive') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.is_featured') }}</dt>
                        <dd class="mt-1">
                            @if($product->is_featured)
                                <x-badge type="warning">{{ __('products.yes') }}</x-badge>
                            @else
                                <x-badge type="secondary">{{ __('products.no') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    @if($product->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">{{ __('products.description') }}</dt>
                            <dd class="mt-1">{{ $product->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Pricing -->
            <x-card title="{{ __('products.pricing') }}">
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-muted mb-1">{{ __('products.base_price') }}</p>
                        <p class="text-2xl font-bold text-accent">Rp {{ number_format($product->base_price, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-muted mb-1">{{ __('products.cost_price') }}</p>
                        <p class="text-2xl font-bold">Rp {{ number_format($product->cost_price ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-muted mb-1">{{ __('products.profit_margin') }}</p>
                        @php
                            $margin = $product->base_price > 0 && $product->cost_price > 0
                                ? (($product->base_price - $product->cost_price) / $product->base_price) * 100
                                : 0;
                        @endphp
                        <p class="text-2xl font-bold {{ $margin >= 30 ? 'text-success' : ($margin >= 15 ? 'text-warning' : 'text-danger') }}">
                            {{ number_format($margin, 1) }}%
                        </p>
                    </div>
                </div>
                @if($product->tax_rate > 0)
                    <div class="mt-4 pt-4 border-t border-border">
                        <div class="flex items-center justify-between">
                            <span class="text-muted">{{ __('products.tax_rate') }}</span>
                            <span class="font-medium">{{ $product->tax_rate }}%</span>
                        </div>
                    </div>
                @endif
            </x-card>

            <!-- Variants (if variant product) -->
            @if($product->product_type === 'variant' && $product->variants->count() > 0)
                <x-card title="{{ __('products.product_variants') }}">
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('products.variant') }}</x-th>
                            <x-th>{{ __('products.sku') }}</x-th>
                            <x-th align="right">{{ __('products.price') }}</x-th>
                            <x-th align="center">{{ __('products.status') }}</x-th>
                        </x-slot>

                        @foreach($product->variants as $variant)
                            <tr>
                                <x-td>
                                    <p class="font-medium">{{ $variant->name }}</p>
                                </x-td>
                                <x-td>
                                    <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $variant->sku }}</code>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-medium">Rp {{ number_format($variant->price, 0, ',', '.') }}</span>
                                </x-td>
                                <x-td align="center">
                                    @if($variant->is_active)
                                        <x-badge type="success" size="sm">{{ __('products.active') }}</x-badge>
                                    @else
                                        <x-badge type="danger" size="sm">{{ __('products.inactive') }}</x-badge>
                                    @endif
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-card>
            @endif

            <!-- Variant Groups (if variant product) -->
            @if($product->product_type === 'variant' && $product->variantGroups->count() > 0)
                <x-card title="{{ __('products.variant_groups') }}">
                    <div class="space-y-4">
                        @foreach($product->variantGroups as $group)
                            <div class="p-4 bg-secondary-50 rounded-lg">
                                <h4 class="font-medium mb-2">{{ $group->name }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($group->options as $option)
                                        <span class="px-3 py-1 bg-white border border-border rounded-full text-sm">
                                            {{ $option->name }}
                                            @if($option->price_adjustment != 0)
                                                <span class="text-muted text-xs">
                                                    ({{ $option->price_adjustment > 0 ? '+' : '' }}Rp {{ number_format($option->price_adjustment, 0, ',', '.') }})
                                                </span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Modifier Groups -->
            @if($product->modifierGroups->count() > 0)
                <x-card title="{{ __('products.modifier_groups') }}">
                    <div class="space-y-4">
                        @foreach($product->modifierGroups as $group)
                            <div class="p-4 bg-secondary-50 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium">{{ $group->name }}</h4>
                                    <x-badge type="{{ $group->selection_type === 'single' ? 'secondary' : 'info' }}" size="sm">
                                        {{ $group->selection_type === 'single' ? __('products.single_select') : __('products.multi_select') }}
                                    </x-badge>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($group->modifiers as $modifier)
                                        <span class="px-3 py-1 bg-white border border-border rounded-full text-sm">
                                            {{ $modifier->name }}
                                            @if($modifier->price > 0)
                                                <span class="text-muted text-xs">(+Rp {{ number_format($modifier->price, 0, ',', '.') }})</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Inventory Link -->
            @if($product->inventoryItem)
                <x-card title="{{ __('products.linked_inventory_item') }}">
                    <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                        <div>
                            <dt class="text-sm text-muted">{{ __('products.inventory_item') }}</dt>
                            <dd class="font-medium">{{ $product->inventoryItem->name }}</dd>
                        </div>
                        <x-button href="{{ route('inventory.items.show', $product->inventoryItem) }}" variant="outline-secondary" size="sm">
                            {{ __('products.view_item') }}
                        </x-button>
                    </div>
                </x-card>
            @endif

            <!-- Recipe & Ingredients -->
            <x-card>
                <x-slot name="title">
                    <div class="flex items-center justify-between">
                        <span>{{ __('products.recipe_and_ingredients') }}</span>
                        @if($product->recipe)
                            <div class="flex gap-2">
                                <x-button href="{{ route('inventory.recipes.edit', $product->recipe) }}" variant="outline-secondary" size="sm" icon="pencil">
                                    {{ __('products.edit_recipe') }}
                                </x-button>
                                <x-button href="{{ route('inventory.recipes.show', $product->recipe) }}" variant="outline-secondary" size="sm" icon="eye">
                                    {{ __('products.view_full') }}
                                </x-button>
                            </div>
                        @endif
                    </div>
                </x-slot>

                @if($product->recipe)
                    <!-- Recipe Info -->
                    <div class="mb-4 p-3 bg-accent/5 border border-accent/20 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-accent">{{ $product->recipe->name }}</p>
                                <p class="text-sm text-muted">
                                    {{ __('products.yield') }}: {{ $product->recipe->yield_qty }} {{ $product->recipe->yieldUnit->name ?? 'unit' }}
                                    @if($product->recipe->estimated_cost)
                                        • {{ __('products.cost') }}: Rp {{ number_format($product->recipe->estimated_cost, 0, ',', '.') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Ingredients Table -->
                    @if($product->recipe->items->count() > 0)
                        <x-table>
                            <x-slot name="head">
                                <x-th>{{ __('products.ingredient') }}</x-th>
                                <x-th align="right">{{ __('products.quantity') }}</x-th>
                                <x-th align="right">{{ __('products.unit_cost') }}</x-th>
                                <x-th align="right">{{ __('products.total') }}</x-th>
                            </x-slot>

                            @php $totalCost = 0; @endphp
                            @foreach($product->recipe->items as $item)
                                @php
                                    $unitCost = $item->inventoryItem->cost_price ?? 0;
                                    $wasteFactor = 1 + (($item->waste_percentage ?? 0) / 100);
                                    $itemCost = $unitCost * $item->quantity * $wasteFactor;
                                    $totalCost += $itemCost;
                                @endphp
                                <tr>
                                    <x-td>
                                        <div>
                                            <p class="font-medium">{{ $item->inventoryItem->name ?? 'Unknown' }}</p>
                                            @if($item->waste_percentage > 0)
                                                <p class="text-xs text-muted">+{{ $item->waste_percentage }}% {{ __('products.waste') }}</p>
                                            @endif
                                        </div>
                                    </x-td>
                                    <x-td align="right">
                                        {{ number_format($item->quantity, 2) }} {{ $item->unit->abbreviation ?? $item->unit->name ?? '' }}
                                    </x-td>
                                    <x-td align="right">
                                        Rp {{ number_format($unitCost, 0, ',', '.') }}
                                    </x-td>
                                    <x-td align="right">
                                        <span class="font-medium">Rp {{ number_format($itemCost, 0, ',', '.') }}</span>
                                    </x-td>
                                </tr>
                            @endforeach
                            <tr class="border-t-2 border-border bg-secondary-50">
                                <x-td colspan="3" class="text-right font-medium">{{ __('products.total_ingredient_cost') }}</x-td>
                                <x-td align="right">
                                    <span class="font-bold text-accent">Rp {{ number_format($totalCost, 0, ',', '.') }}</span>
                                </x-td>
                            </tr>
                        </x-table>
                    @else
                        <x-empty-state
                            icon="beaker"
                            title="{{ __('products.no_ingredients') }}"
                            description="{{ __('products.no_ingredients_desc') }}"
                        >
                            <x-button href="{{ route('inventory.recipes.edit', $product->recipe) }}" icon="plus" size="sm">
                                {{ __('products.add_ingredients') }}
                            </x-button>
                        </x-empty-state>
                    @endif
                @else
                    <!-- No Recipe - Show options to link or create -->
                    <x-empty-state
                        icon="beaker"
                        title="{{ __('products.no_recipe_linked') }}"
                        description="{{ __('products.no_recipe_linked_desc') }}"
                    >
                        <div class="flex flex-col gap-3">
                            @if($availableRecipes->count() > 0)
                                <form action="{{ route('menu.products.link-recipe', $product) }}" method="POST" class="flex gap-2">
                                    @csrf
                                    <select name="recipe_id" class="form-select rounded-lg border-border text-sm flex-1">
                                        <option value="">{{ __('products.select_existing_recipe') }}</option>
                                        @foreach($availableRecipes as $recipe)
                                            <option value="{{ $recipe->id }}">{{ $recipe->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-button type="submit" variant="outline-secondary" size="sm" icon="link">
                                        {{ __('products.link') }}
                                    </x-button>
                                </form>
                                <div class="text-center text-muted text-sm">or</div>
                            @endif
                            <x-button href="{{ route('inventory.recipes.create', ['product_id' => $product->id]) }}" icon="plus">
                                {{ __('products.create_new_recipe') }}
                            </x-button>
                        </div>
                    </x-empty-state>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Product Image -->
            <x-card title="{{ __('products.product_image') }}">
                <div class="aspect-square bg-secondary-100 rounded-lg flex items-center justify-center overflow-hidden">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    @else
                        <x-icon name="photo" class="w-16 h-16 text-muted" />
                    @endif
                </div>
            </x-card>

            <!-- Quick Stats -->
            <x-card title="{{ __('products.statistics') }}">
                <dl class="space-y-4">
                    @if($product->product_type === 'variant')
                        <div class="flex items-center justify-between">
                            <dt class="text-muted">{{ __('products.variants') }}</dt>
                            <dd class="font-bold text-lg">{{ $product->variants->count() }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">{{ __('products.modifier_groups') }}</dt>
                        <dd class="font-bold text-lg">{{ $product->modifierGroups->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">{{ __('products.track_stock') }}</dt>
                        <dd>
                            @if($product->track_stock)
                                <x-badge type="success" size="sm">{{ __('products.yes') }}</x-badge>
                            @else
                                <x-badge type="secondary" size="sm">{{ __('products.no') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <!-- Outlet Availability -->
            @if($product->productOutlets->count() > 0)
                <x-card title="{{ __('products.outlet_availability') }}">
                    <div class="space-y-2">
                        @foreach($product->productOutlets as $productOutlet)
                            <div class="flex items-center justify-between p-2 bg-secondary-50 rounded">
                                <span class="text-sm">{{ $productOutlet->outlet->name ?? 'Unknown Outlet' }}</span>
                                @if($productOutlet->is_available)
                                    <x-badge type="success" size="sm">{{ __('products.available') }}</x-badge>
                                @else
                                    <x-badge type="danger" size="sm">{{ __('products.unavailable') }}</x-badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Metadata -->
            <x-card title="{{ __('products.information') }}">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('products.sort_order') }}</dt>
                        <dd>{{ $product->sort_order }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('products.created') }}</dt>
                        <dd>{{ $product->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('products.updated') }}</dt>
                        <dd>{{ $product->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Actions -->
            <div class="flex flex-col gap-3">
                <x-button href="{{ route('menu.products.edit', $product) }}" icon="pencil" class="w-full">
                    {{ __('products.edit_product') }}
                </x-button>
                <form action="{{ route('menu.products.duplicate', $product) }}" method="POST">
                    @csrf
                    <x-button type="submit" variant="outline-secondary" icon="document-duplicate" class="w-full">
                        {{ __('products.duplicate_product') }}
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
