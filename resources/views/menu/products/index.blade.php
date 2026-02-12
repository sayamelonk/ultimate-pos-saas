<x-app-layout>
    <x-slot name="title">Products - Ultimate POS</x-slot>

    @section('page-title', 'Products')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Products</h2>
                <p class="text-muted mt-1">Manage your menu products</p>
            </div>
            <x-button href="{{ route('menu.products.create') }}" icon="plus">
                Add Product
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('menu.products.index') }}" class="flex flex-wrap items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="Search products..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="category_id" class="w-48">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </x-select>
            <x-select name="product_type" class="w-40">
                <option value="">All Types</option>
                <option value="single" @selected(request('product_type') === 'single')>Single</option>
                <option value="variant" @selected(request('product_type') === 'variant')>Variant</option>
                <option value="combo" @selected(request('product_type') === 'combo')>Combo</option>
            </x-select>
            <x-select name="status" class="w-32">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(request()->hasAny(['search', 'category_id', 'product_type', 'status']))
                <x-button href="{{ route('menu.products.index') }}" variant="ghost">Clear</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($products->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Product</x-th>
                    <x-th>SKU</x-th>
                    <x-th>Category</x-th>
                    <x-th>Type</x-th>
                    <x-th align="right">Price</x-th>
                    <x-th align="right">Cost</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($products as $product)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-secondary-100 rounded-lg flex items-center justify-center overflow-hidden">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <x-icon name="cube" class="w-6 h-6 text-muted" />
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $product->name }}</p>
                                    @if($product->description)
                                        <p class="text-xs text-muted truncate max-w-[200px]">{{ $product->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $product->sku }}</code>
                        </x-td>
                        <x-td>
                            @if($product->category)
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $product->category->color ?? '#6b7280' }};"></span>
                                    {{ $product->category->name }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td>
                            @if($product->product_type === 'single')
                                <x-badge type="secondary">Single</x-badge>
                            @elseif($product->product_type === 'variant')
                                <x-badge type="info">Variant</x-badge>
                            @else
                                <x-badge type="warning">Combo</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <span class="font-medium">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                        </x-td>
                        <x-td align="right">
                            @if($product->cost_price)
                                <span class="text-muted">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($product->is_active)
                                <x-badge type="success" dot>Active</x-badge>
                            @else
                                <x-badge type="danger" dot>Inactive</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div x-data>
                                <x-dropdown align="right">
                                    <x-slot name="trigger">
                                        <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                            <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                        </button>
                                    </x-slot>

                                    <x-dropdown-item href="{{ route('menu.products.show', $product) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('menu.products.edit', $product) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        @click="$refs.duplicateForm{{ $loop->index }}.submit()"
                                    >
                                        <x-icon name="document-duplicate" class="w-4 h-4" />
                                        Duplicate
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Product',
                                            message: 'Are you sure you want to delete {{ $product->name }}? This action cannot be undone.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="duplicateForm{{ $loop->index }}" action="{{ route('menu.products.duplicate', $product) }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('menu.products.destroy', $product) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$products" />
            </div>
        @else
            <x-empty-state
                title="No products found"
                description="Get started by creating your first product."
                icon="cube"
            >
                <x-button href="{{ route('menu.products.create') }}" icon="plus">
                    Add Product
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
