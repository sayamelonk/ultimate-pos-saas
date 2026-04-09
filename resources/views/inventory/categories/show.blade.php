<x-app-layout>
    <x-slot name="title">{{ $category->name }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.category_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.categories.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('inventory.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $category->name }}</h2>
                    <p class="text-muted mt-1">{{ $category->code }}</p>
                </div>
            </div>
            <x-button href="{{ route('inventory.categories.edit', $category) }}" variant="outline-secondary" icon="pencil">
                {{ __('inventory.edit') }}
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        <x-card title="{{ __('inventory.information') }}">
            <dl class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.name') }}</dt>
                    <dd class="mt-1 font-medium text-text">{{ $category->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.category_code') }}</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $category->code }}</code>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.parent_category') }}</dt>
                    <dd class="mt-1 text-text">{{ $category->parent->name ?? __('inventory.root_category') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.status') }}</dt>
                    <dd class="mt-1">
                        @if($category->is_active)
                            <x-badge type="success" dot>{{ __('inventory.active') }}</x-badge>
                        @else
                            <x-badge type="danger" dot>{{ __('inventory.inactive') }}</x-badge>
                        @endif
                    </dd>
                </div>
                @if($category->description)
                    <div class="col-span-2">
                        <dt class="text-sm text-muted">{{ __('inventory.description') }}</dt>
                        <dd class="mt-1 text-text">{{ $category->description }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.created') }}</dt>
                    <dd class="mt-1 text-text">{{ $category->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('inventory.updated') }}</dt>
                    <dd class="mt-1 text-text">{{ $category->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        @if($category->children && $category->children->count() > 0)
            <x-card title="{{ __('inventory.categories') }}">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.name') }}</x-th>
                        <x-th>{{ __('inventory.category_code') }}</x-th>
                        <x-th align="center">{{ __('inventory.status') }}</x-th>
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
                                    <x-badge type="success" size="sm">{{ __('inventory.active') }}</x-badge>
                                @else
                                    <x-badge type="danger" size="sm">{{ __('inventory.inactive') }}</x-badge>
                                @endif
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($category->inventoryItems && $category->inventoryItems->count() > 0)
            <x-card title="{{ __('inventory.items_in_category') }}">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.item') }}</x-th>
                        <x-th>{{ __('inventory.sku') }}</x-th>
                        <x-th>{{ __('inventory.item_type') }}</x-th>
                        <x-th align="center">{{ __('inventory.status') }}</x-th>
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
                                <span class="capitalize">{{ __('inventory.' . $item->type) }}</span>
                            </x-td>
                            <x-td align="center">
                                @if($item->is_active)
                                    <x-badge type="success" size="sm">{{ __('inventory.active') }}</x-badge>
                                @else
                                    <x-badge type="danger" size="sm">{{ __('inventory.inactive') }}</x-badge>
                                @endif
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    </div>
</x-app-layout>
