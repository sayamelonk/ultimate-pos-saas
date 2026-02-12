<x-app-layout>
    <x-slot name="title">{{ $category->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Category Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('menu.categories.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $category->name }}</h2>
                    <p class="text-muted mt-1">{{ $category->full_path }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('menu.categories.edit', $category) }}" icon="pencil">
                    Edit
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            <x-card title="Category Information">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">Name</dt>
                        <dd class="mt-1 font-medium">{{ $category->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Code</dt>
                        <dd class="mt-1">
                            @if($category->code)
                                <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $category->code }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Parent Category</dt>
                        <dd class="mt-1">{{ $category->parent->name ?? 'None (Root)' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($category->is_active)
                                <x-badge type="success">Active</x-badge>
                            @else
                                <x-badge type="danger">Inactive</x-badge>
                            @endif
                        </dd>
                    </div>
                    @if($category->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">Description</dt>
                            <dd class="mt-1">{{ $category->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            @if($category->products->count() > 0)
                <x-card title="Products in this Category">
                    <div class="space-y-2">
                        @foreach($category->products as $product)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                        <x-icon name="cube" class="w-5 h-5 text-muted" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ $product->name }}</p>
                                        <p class="text-xs text-muted">{{ $product->sku }}</p>
                                    </div>
                                </div>
                                <span class="font-medium">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if($category->products->count() >= 10)
                        <div class="mt-4 text-center">
                            <x-button href="{{ route('menu.products.index', ['category_id' => $category->id]) }}" variant="outline-secondary" size="sm">
                                View All Products
                            </x-button>
                        </div>
                    @endif
                </x-card>
            @endif

            @if($category->children->count() > 0)
                <x-card title="Subcategories">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($category->children as $child)
                            <a href="{{ route('menu.categories.show', $child) }}" class="flex items-center gap-3 p-3 bg-secondary-50 rounded-lg hover:bg-secondary-100 transition-colors">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $child->color ?? '#e5e7eb' }}20;">
                                    <x-icon name="folder" class="w-5 h-5" style="color: {{ $child->color ?? '#6b7280' }};" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ $child->name }}</p>
                                    <p class="text-xs text-muted">{{ $child->products_count ?? 0 }} products</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card title="Display Settings">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Color</dt>
                        <dd class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded" style="background-color: {{ $category->color ?? '#e5e7eb' }};"></div>
                            <code class="text-xs">{{ $category->color ?? '-' }}</code>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Sort Order</dt>
                        <dd class="font-medium">{{ $category->sort_order }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Show in POS</dt>
                        <dd>
                            @if($category->show_in_pos)
                                <x-badge type="success" size="sm">Yes</x-badge>
                            @else
                                <x-badge type="secondary" size="sm">No</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Show in Menu</dt>
                        <dd>
                            @if($category->show_in_menu)
                                <x-badge type="success" size="sm">Yes</x-badge>
                            @else
                                <x-badge type="secondary" size="sm">No</x-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Statistics">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Total Products</dt>
                        <dd class="font-bold text-lg">{{ $category->products->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Subcategories</dt>
                        <dd class="font-bold text-lg">{{ $category->children->count() }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>
