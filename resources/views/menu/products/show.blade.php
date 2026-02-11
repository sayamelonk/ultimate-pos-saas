<x-app-layout>
    <x-slot name="title">{{ $product->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Product Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('menu.products.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $product->name }}</h2>
                    <p class="text-muted mt-1">{{ $product->sku }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('menu.products.edit', $product) }}" icon="pencil">
                    Edit
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            <!-- Basic Information -->
            <x-card title="Product Information">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">Name</dt>
                        <dd class="mt-1 font-medium">{{ $product->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">SKU</dt>
                        <dd class="mt-1">
                            <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $product->sku }}</code>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Category</dt>
                        <dd class="mt-1">
                            @if($product->category)
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $product->category->color ?? '#6b7280' }};"></span>
                                    {{ $product->category->full_path }}
                                </span>
                            @else
                                <span class="text-muted">Uncategorized</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Type</dt>
                        <dd class="mt-1">
                            @if($product->product_type === 'single')
                                <x-badge type="secondary">Single Product</x-badge>
                            @elseif($product->product_type === 'variant')
                                <x-badge type="info">Variant Product</x-badge>
                            @else
                                <x-badge type="warning">Combo Product</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($product->is_active)
                                <x-badge type="success">Active</x-badge>
                            @else
                                <x-badge type="danger">Inactive</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Featured</dt>
                        <dd class="mt-1">
                            @if($product->is_featured)
                                <x-badge type="warning">Yes</x-badge>
                            @else
                                <x-badge type="secondary">No</x-badge>
                            @endif
                        </dd>
                    </div>
                    @if($product->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">Description</dt>
                            <dd class="mt-1">{{ $product->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Pricing -->
            <x-card title="Pricing">
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-muted mb-1">Base Price</p>
                        <p class="text-2xl font-bold text-accent">Rp {{ number_format($product->base_price, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-muted mb-1">Cost Price</p>
                        <p class="text-2xl font-bold">Rp {{ number_format($product->cost_price ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-center p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-muted mb-1">Profit Margin</p>
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
                            <span class="text-muted">Tax Rate</span>
                            <span class="font-medium">{{ $product->tax_rate }}%</span>
                        </div>
                    </div>
                @endif
            </x-card>

            <!-- Variants (if variant product) -->
            @if($product->product_type === 'variant' && $product->variants->count() > 0)
                <x-card title="Product Variants">
                    <x-table>
                        <x-slot name="head">
                            <x-th>Variant</x-th>
                            <x-th>SKU</x-th>
                            <x-th align="right">Price</x-th>
                            <x-th align="center">Status</x-th>
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
                                        <x-badge type="success" size="sm">Active</x-badge>
                                    @else
                                        <x-badge type="danger" size="sm">Inactive</x-badge>
                                    @endif
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-card>
            @endif

            <!-- Variant Groups (if variant product) -->
            @if($product->product_type === 'variant' && $product->variantGroups->count() > 0)
                <x-card title="Variant Groups">
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
                <x-card title="Modifier Groups">
                    <div class="space-y-4">
                        @foreach($product->modifierGroups as $group)
                            <div class="p-4 bg-secondary-50 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium">{{ $group->name }}</h4>
                                    <x-badge type="{{ $group->selection_type === 'single' ? 'secondary' : 'info' }}" size="sm">
                                        {{ $group->selection_type === 'single' ? 'Single Select' : 'Multi Select' }}
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
            @if($product->inventoryItem || $product->recipe)
                <x-card title="Inventory & Recipe">
                    <dl class="space-y-4">
                        @if($product->inventoryItem)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div>
                                    <dt class="text-sm text-muted">Linked Inventory Item</dt>
                                    <dd class="font-medium">{{ $product->inventoryItem->name }}</dd>
                                </div>
                                <x-button href="{{ route('inventory.items.show', $product->inventoryItem) }}" variant="outline-secondary" size="sm">
                                    View Item
                                </x-button>
                            </div>
                        @endif
                        @if($product->recipe)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div>
                                    <dt class="text-sm text-muted">Linked Recipe</dt>
                                    <dd class="font-medium">{{ $product->recipe->name }}</dd>
                                </div>
                                <x-button href="{{ route('inventory.recipes.show', $product->recipe) }}" variant="outline-secondary" size="sm">
                                    View Recipe
                                </x-button>
                            </div>
                        @endif
                    </dl>
                </x-card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Product Image -->
            <x-card title="Product Image">
                <div class="aspect-square bg-secondary-100 rounded-lg flex items-center justify-center overflow-hidden">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    @else
                        <x-icon name="photo" class="w-16 h-16 text-muted" />
                    @endif
                </div>
            </x-card>

            <!-- Quick Stats -->
            <x-card title="Statistics">
                <dl class="space-y-4">
                    @if($product->product_type === 'variant')
                        <div class="flex items-center justify-between">
                            <dt class="text-muted">Variants</dt>
                            <dd class="font-bold text-lg">{{ $product->variants->count() }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Modifier Groups</dt>
                        <dd class="font-bold text-lg">{{ $product->modifierGroups->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Track Stock</dt>
                        <dd>
                            @if($product->track_stock)
                                <x-badge type="success" size="sm">Yes</x-badge>
                            @else
                                <x-badge type="secondary" size="sm">No</x-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <!-- Outlet Availability -->
            @if($product->productOutlets->count() > 0)
                <x-card title="Outlet Availability">
                    <div class="space-y-2">
                        @foreach($product->productOutlets as $productOutlet)
                            <div class="flex items-center justify-between p-2 bg-secondary-50 rounded">
                                <span class="text-sm">{{ $productOutlet->outlet->name ?? 'Unknown Outlet' }}</span>
                                @if($productOutlet->is_available)
                                    <x-badge type="success" size="sm">Available</x-badge>
                                @else
                                    <x-badge type="danger" size="sm">Unavailable</x-badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Metadata -->
            <x-card title="Information">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Sort Order</dt>
                        <dd>{{ $product->sort_order }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Created</dt>
                        <dd>{{ $product->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Updated</dt>
                        <dd>{{ $product->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Actions -->
            <div class="flex flex-col gap-3">
                <x-button href="{{ route('menu.products.edit', $product) }}" icon="pencil" class="w-full">
                    Edit Product
                </x-button>
                <form action="{{ route('menu.products.duplicate', $product) }}" method="POST">
                    @csrf
                    <x-button type="submit" variant="outline-secondary" icon="document-duplicate" class="w-full">
                        Duplicate Product
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
