<x-app-layout>
    <x-slot name="title">{{ $category->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Category Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.categories.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $category->name }}</h2>
                    <p class="text-muted mt-1">{{ $category->code }}</p>
                </div>
            </div>
            <x-button href="{{ route('inventory.categories.edit', $category) }}" variant="outline-secondary" icon="pencil">
                Edit
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        <x-card title="Category Information">
            <dl class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm text-muted">Name</dt>
                    <dd class="mt-1 font-medium text-text">{{ $category->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Code</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $category->code }}</code>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Parent Category</dt>
                    <dd class="mt-1 text-text">{{ $category->parent->name ?? 'None (Root Category)' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Status</dt>
                    <dd class="mt-1">
                        @if($category->is_active)
                            <x-badge type="success" dot>Active</x-badge>
                        @else
                            <x-badge type="danger" dot>Inactive</x-badge>
                        @endif
                    </dd>
                </div>
                @if($category->description)
                    <div class="col-span-2">
                        <dt class="text-sm text-muted">Description</dt>
                        <dd class="mt-1 text-text">{{ $category->description }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-muted">Created</dt>
                    <dd class="mt-1 text-text">{{ $category->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Updated</dt>
                    <dd class="mt-1 text-text">{{ $category->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        @if($category->children && $category->children->count() > 0)
            <x-card title="Subcategories">
                <x-table>
                    <x-slot name="head">
                        <x-th>Name</x-th>
                        <x-th>Code</x-th>
                        <x-th align="center">Status</x-th>
                    </x-slot>

                    @foreach($category->children as $child)
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.categories.show', $child) }}" class="text-accent hover:underline">
                                    {{ $child->name }}
                                </a>
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $child->code }}</code>
                            </x-td>
                            <x-td align="center">
                                @if($child->is_active)
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

        @if($category->inventoryItems && $category->inventoryItems->count() > 0)
            <x-card title="Items in this Category">
                <x-table>
                    <x-slot name="head">
                        <x-th>Item</x-th>
                        <x-th>SKU</x-th>
                        <x-th>Type</x-th>
                        <x-th align="center">Status</x-th>
                    </x-slot>

                    @foreach($category->inventoryItems as $item)
                        <tr>
                            <x-td>
                                <a href="{{ route('inventory.items.show', $item) }}" class="text-accent hover:underline font-medium">
                                    {{ $item->name }}
                                </a>
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->sku }}</code>
                            </x-td>
                            <x-td>
                                <span class="capitalize">{{ str_replace('_', ' ', $item->type) }}</span>
                            </x-td>
                            <x-td align="center">
                                @if($item->is_active)
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
    </div>
</x-app-layout>
