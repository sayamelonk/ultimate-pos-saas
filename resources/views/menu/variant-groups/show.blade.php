<x-app-layout>
    <x-slot name="title">{{ $variantGroup->name }} - Ultimate POS</x-slot>

    @section('page-title', __('products.variant_group_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('menu.variant-groups.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('products.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $variantGroup->name }}</h2>
                    <p class="text-muted mt-1">{{ __('products.variant_group_details') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('menu.variant-groups.edit', $variantGroup) }}" icon="pencil">
                    {{ __('products.edit') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            <!-- Group Information -->
            <x-card title="{{ __('products.group_information') }}">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.name') }}</dt>
                        <dd class="mt-1 font-medium">{{ $variantGroup->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.display_type') }}</dt>
                        <dd class="mt-1">
                            @if($variantGroup->display_type === 'button')
                                <x-badge type="secondary">{{ __('products.display_button') }}</x-badge>
                            @elseif($variantGroup->display_type === 'dropdown')
                                <x-badge type="info">{{ __('products.display_dropdown') }}</x-badge>
                            @elseif($variantGroup->display_type === 'color')
                                <x-badge type="warning">{{ __('products.display_color') }}</x-badge>
                            @else
                                <x-badge type="success">{{ __('products.display_image') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.status') }}</dt>
                        <dd class="mt-1">
                            @if($variantGroup->is_active)
                                <x-badge type="success">{{ __('products.active') }}</x-badge>
                            @else
                                <x-badge type="danger">{{ __('products.inactive') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('products.sort_order') }}</dt>
                        <dd class="mt-1 font-medium">{{ $variantGroup->sort_order }}</dd>
                    </div>
                    @if($variantGroup->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">{{ __('products.description') }}</dt>
                            <dd class="mt-1">{{ $variantGroup->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Options -->
            <x-card title="{{ __('products.variant_options') }}">
                @if($variantGroup->options->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('products.option') }}</x-th>
                            <x-th align="right">{{ __('products.price_adjustment') }}</x-th>
                            <x-th align="center">{{ __('products.sort') }}</x-th>
                            <x-th align="center">{{ __('products.status') }}</x-th>
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
                                        <x-badge type="success" size="sm">{{ __('products.active') }}</x-badge>
                                    @else
                                        <x-badge type="danger" size="sm">{{ __('products.inactive') }}</x-badge>
                                    @endif
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <x-empty-state
                        title="{{ __('products.no_options_empty') }}"
                        description="{{ __('products.add_options_to_group') }}"
                        icon="squares-2x2"
                        size="sm"
                    />
                @endif
            </x-card>

            <!-- Products Using This Group -->
            @if($variantGroup->products->count() > 0)
                <x-card title="{{ __('products.products_using_group') }}">
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
            <x-card title="{{ __('products.preview') }}">
                <div class="space-y-3">
                    <p class="text-sm text-muted mb-4">{{ __('products.how_appears_pos') }}</p>

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
            <x-card title="{{ __('products.statistics') }}">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">{{ __('products.total_options') }}</dt>
                        <dd class="font-bold text-lg">{{ $variantGroup->options->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">{{ __('products.active_options') }}</dt>
                        <dd class="font-bold text-lg">{{ $variantGroup->activeOptions->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">{{ __('products.products_using') }}</dt>
                        <dd class="font-bold text-lg">{{ $variantGroup->products->count() }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Metadata -->
            <x-card title="{{ __('products.information') }}">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('products.created') }}</dt>
                        <dd>{{ $variantGroup->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">{{ __('products.updated') }}</dt>
                        <dd>{{ $variantGroup->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>
