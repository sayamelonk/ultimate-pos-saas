<x-app-layout>
    <x-slot name="title">{{ $modifierGroup->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Modifier Group Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('menu.modifier-groups.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $modifierGroup->name }}</h2>
                    <p class="text-muted mt-1">Modifier Group Details</p>
                </div>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('menu.modifier-groups.edit', $modifierGroup) }}" icon="pencil">
                    Edit
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            <!-- Group Information -->
            <x-card title="Group Information">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">Name</dt>
                        <dd class="mt-1 font-medium">{{ $modifierGroup->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Selection Type</dt>
                        <dd class="mt-1">
                            @if($modifierGroup->selection_type === 'single')
                                <x-badge type="secondary">Single Select</x-badge>
                            @else
                                <x-badge type="info">Multi Select</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($modifierGroup->is_active)
                                <x-badge type="success">Active</x-badge>
                            @else
                                <x-badge type="danger">Inactive</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Sort Order</dt>
                        <dd class="mt-1 font-medium">{{ $modifierGroup->sort_order }}</dd>
                    </div>
                    @if($modifierGroup->selection_type === 'multiple')
                        <div>
                            <dt class="text-sm text-muted">Min Selections</dt>
                            <dd class="mt-1 font-medium">{{ $modifierGroup->min_selections ?? 0 }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted">Max Selections</dt>
                            <dd class="mt-1 font-medium">{{ $modifierGroup->max_selections ?? 'Unlimited' }}</dd>
                        </div>
                    @endif
                    @if($modifierGroup->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">Description</dt>
                            <dd class="mt-1">{{ $modifierGroup->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Modifiers -->
            <x-card title="Modifiers">
                @if($modifierGroup->modifiers->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>Modifier</x-th>
                            <x-th align="right">Price</x-th>
                            <x-th>Inventory Link</x-th>
                            <x-th align="center">Status</x-th>
                        </x-slot>

                        @foreach($modifierGroup->modifiers->sortBy('sort_order') as $modifier)
                            <tr>
                                <x-td>
                                    <span class="font-medium">{{ $modifier->name }}</span>
                                </x-td>
                                <x-td align="right">
                                    @if($modifier->price > 0)
                                        <span class="text-success font-medium">+Rp {{ number_format($modifier->price, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">Free</span>
                                    @endif
                                </x-td>
                                <x-td>
                                    @if($modifier->inventoryItem)
                                        <span class="inline-flex items-center gap-1 text-sm">
                                            <x-icon name="cube" class="w-4 h-4 text-muted" />
                                            {{ $modifier->inventoryItem->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </x-td>
                                <x-td align="center">
                                    @if($modifier->is_active)
                                        <x-badge type="success" size="sm">Active</x-badge>
                                    @else
                                        <x-badge type="danger" size="sm">Inactive</x-badge>
                                    @endif
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <x-empty-state
                        title="No modifiers"
                        description="Add modifiers to this group."
                        icon="plus-circle"
                        size="sm"
                    />
                @endif
            </x-card>

            <!-- Products Using This Group -->
            @if($modifierGroup->products->count() > 0)
                <x-card title="Products Using This Group">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($modifierGroup->products as $product)
                            <a href="{{ route('menu.products.show', $product) }}" class="flex items-center gap-3 p-3 bg-secondary-50 rounded-lg hover:bg-secondary-100 transition-colors">
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm overflow-hidden">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <x-icon name="cube" class="w-5 h-5 text-muted" />
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium">{{ $product->name }}</p>
                                    <p class="text-xs text-muted">{{ $product->sku }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Preview -->
            <x-card title="POS Preview">
                <div class="p-4 bg-secondary-50 rounded-lg">
                    <p class="font-medium mb-3">{{ $modifierGroup->name }}</p>

                    @if($modifierGroup->selection_type === 'single')
                        <div class="space-y-2">
                            @foreach($modifierGroup->activeModifiers as $index => $modifier)
                                <label class="flex items-center gap-2 cursor-pointer p-2 bg-white rounded border border-transparent hover:border-accent transition-colors {{ $index === 0 ? 'border-accent' : '' }}">
                                    <input type="radio" name="preview" {{ $index === 0 ? 'checked' : '' }} class="text-accent focus:ring-accent">
                                    <span>{{ $modifier->name }}</span>
                                    @if($modifier->price > 0)
                                        <span class="text-xs text-muted ml-auto">+Rp {{ number_format($modifier->price, 0, ',', '.') }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($modifierGroup->activeModifiers as $modifier)
                                <label class="flex items-center gap-2 cursor-pointer p-2 bg-white rounded border border-transparent hover:border-accent transition-colors">
                                    <input type="checkbox" class="rounded text-accent focus:ring-accent">
                                    <span>{{ $modifier->name }}</span>
                                    @if($modifier->price > 0)
                                        <span class="text-xs text-muted ml-auto">+Rp {{ number_format($modifier->price, 0, ',', '.') }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @if($modifierGroup->min_selections > 0 || $modifierGroup->max_selections)
                            <p class="text-xs text-muted mt-3">
                                @if($modifierGroup->min_selections > 0)
                                    Min: {{ $modifierGroup->min_selections }}
                                @endif
                                @if($modifierGroup->min_selections > 0 && $modifierGroup->max_selections)
                                    |
                                @endif
                                @if($modifierGroup->max_selections)
                                    Max: {{ $modifierGroup->max_selections }}
                                @endif
                            </p>
                        @endif
                    @endif
                </div>
            </x-card>

            <!-- Statistics -->
            <x-card title="Statistics">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Total Modifiers</dt>
                        <dd class="font-bold text-lg">{{ $modifierGroup->modifiers->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Active Modifiers</dt>
                        <dd class="font-bold text-lg">{{ $modifierGroup->activeModifiers->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Products Using</dt>
                        <dd class="font-bold text-lg">{{ $modifierGroup->products->count() }}</dd>
                    </div>
                    @php
                        $avgPrice = $modifierGroup->modifiers->avg('price');
                    @endphp
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Avg. Price</dt>
                        <dd class="font-bold">Rp {{ number_format($avgPrice ?? 0, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Metadata -->
            <x-card title="Information">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Created</dt>
                        <dd>{{ $modifierGroup->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Updated</dt>
                        <dd>{{ $modifierGroup->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>
