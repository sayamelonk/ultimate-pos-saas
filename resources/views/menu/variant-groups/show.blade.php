<x-app-layout>
    <x-slot name="title">{{ $variantGroup->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Variant Group Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('menu.variant-groups.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $variantGroup->name }}</h2>
                    <p class="text-muted mt-1">Variant Group Details</p>
                </div>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('menu.variant-groups.edit', $variantGroup) }}" icon="pencil">
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
                        <dd class="mt-1 font-medium">{{ $variantGroup->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Display Type</dt>
                        <dd class="mt-1">
                            @if($variantGroup->display_type === 'button')
                                <x-badge type="secondary">Button</x-badge>
                            @elseif($variantGroup->display_type === 'dropdown')
                                <x-badge type="info">Dropdown</x-badge>
                            @elseif($variantGroup->display_type === 'color')
                                <x-badge type="warning">Color Swatch</x-badge>
                            @else
                                <x-badge type="success">Image</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($variantGroup->is_active)
                                <x-badge type="success">Active</x-badge>
                            @else
                                <x-badge type="danger">Inactive</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Sort Order</dt>
                        <dd class="mt-1 font-medium">{{ $variantGroup->sort_order }}</dd>
                    </div>
                    @if($variantGroup->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">Description</dt>
                            <dd class="mt-1">{{ $variantGroup->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Options -->
            <x-card title="Variant Options">
                @if($variantGroup->options->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>Option</x-th>
                            <x-th align="right">Price Adjustment</x-th>
                            <x-th align="center">Sort</x-th>
                            <x-th align="center">Status</x-th>
                        </x-slot>

                        @foreach($variantGroup->options->sortBy('sort_order') as $option)
                            <tr>
                                <x-td>
                                    <div class="flex items-center gap-3">
                                        @if($variantGroup->display_type === 'color' && $option->color_code)
                                            <div class="w-6 h-6 rounded-full border border-border" style="background-color: {{ $option->color_code }};"></div>
                                        @endif
                                        <span class="font-medium">{{ $option->name }}</span>
                                    </div>
                                </x-td>
                                <x-td align="right">
                                    @if($option->price_adjustment != 0)
                                        <span class="{{ $option->price_adjustment > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $option->price_adjustment > 0 ? '+' : '' }}Rp {{ number_format($option->price_adjustment, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </x-td>
                                <x-td align="center">{{ $option->sort_order }}</x-td>
                                <x-td align="center">
                                    @if($option->is_active)
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
                        title="No options"
                        description="Add options to this variant group."
                        icon="squares-2x2"
                        size="sm"
                    />
                @endif
            </x-card>

            <!-- Products Using This Group -->
            @if($variantGroup->products->count() > 0)
                <x-card title="Products Using This Group">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($variantGroup->products as $product)
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
            <x-card title="Preview">
                <div class="space-y-3">
                    <p class="text-sm text-muted mb-4">How options appear in POS:</p>

                    @if($variantGroup->display_type === 'button')
                        <div class="flex flex-wrap gap-2">
                            @foreach($variantGroup->activeOptions as $index => $option)
                                <button type="button" class="px-4 py-2 border-2 rounded-lg transition-colors {{ $index === 0 ? 'border-accent bg-accent/5' : 'border-border hover:border-accent' }}">
                                    {{ $option->name }}
                                    @if($option->price_adjustment != 0)
                                        <span class="text-xs text-muted ml-1">
                                            ({{ $option->price_adjustment > 0 ? '+' : '' }}Rp {{ number_format($option->price_adjustment, 0, ',', '.') }})
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @elseif($variantGroup->display_type === 'dropdown')
                        <select class="w-full px-3 py-2 border border-border rounded-lg">
                            @foreach($variantGroup->activeOptions as $option)
                                <option>
                                    {{ $option->name }}
                                    @if($option->price_adjustment != 0)
                                        (+Rp {{ number_format($option->price_adjustment, 0, ',', '.') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    @elseif($variantGroup->display_type === 'color')
                        <div class="flex flex-wrap gap-2">
                            @foreach($variantGroup->activeOptions as $index => $option)
                                <button
                                    type="button"
                                    class="w-10 h-10 rounded-full border-2 transition-colors {{ $index === 0 ? 'ring-2 ring-accent ring-offset-2' : 'border-border hover:border-accent' }}"
                                    style="background-color: {{ $option->color_code ?? '#e5e7eb' }};"
                                    title="{{ $option->name }}"
                                ></button>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach($variantGroup->activeOptions as $index => $option)
                                <div class="w-16 h-16 border-2 rounded-lg flex items-center justify-center bg-secondary-100 {{ $index === 0 ? 'border-accent' : 'border-border' }}">
                                    <x-icon name="photo" class="w-6 h-6 text-muted" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Statistics -->
            <x-card title="Statistics">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Total Options</dt>
                        <dd class="font-bold text-lg">{{ $variantGroup->options->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Active Options</dt>
                        <dd class="font-bold text-lg">{{ $variantGroup->activeOptions->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Products Using</dt>
                        <dd class="font-bold text-lg">{{ $variantGroup->products->count() }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Metadata -->
            <x-card title="Information">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Created</dt>
                        <dd>{{ $variantGroup->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Updated</dt>
                        <dd>{{ $variantGroup->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>
