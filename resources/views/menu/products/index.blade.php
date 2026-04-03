<x-app-layout>
    <x-slot name="title">{{ __('products.products') }} - Ultimate POS</x-slot>

    @section('page-title', __('products.products'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('products.products') }}</h2>
                <p class="text-muted mt-1">{{ __('products.manage_products') }}</p>
            </div>
            <x-button href="{{ route('menu.products.create') }}" icon="plus">
                {{ __('products.add_product') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('menu.products.index') }}" class="flex flex-wrap items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('products.search_products') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="category_id" class="w-48">
                <option value="">{{ __('products.all_categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </x-select>
            <x-select name="product_type" class="w-40">
                <option value="">{{ __('products.all_types') }}</option>
                <option value="single" @selected(request('product_type') === 'single')>{{ __('products.single') }}</option>
                <option value="variant" @selected(request('product_type') === 'variant')>{{ __('products.variant') }}</option>
                <option value="combo" @selected(request('product_type') === 'combo')>{{ __('products.combo') }}</option>
            </x-select>
            <x-select name="status" class="w-32">
                <option value="">{{ __('products.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('products.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('products.inactive') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('products.filter') }}</x-button>
            @if(request()->hasAny(['search', 'category_id', 'product_type', 'status']))
                <x-button href="{{ route('menu.products.index') }}" variant="ghost">{{ __('products.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($products->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('products.product') }}</x-th>
                    <x-th>{{ __('products.sku') }}</x-th>
                    <x-th>{{ __('products.category') }}</x-th>
                    <x-th>{{ __('products.type') }}</x-th>
                    <x-th align="right">{{ __('products.price') }}</x-th>
                    <x-th align="right">{{ __('products.cost') }}</x-th>
                    <x-th align="center">{{ __('products.status') }}</x-th>
                    <x-th align="right">{{ __('products.actions') }}</x-th>
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
                                <x-badge type="secondary">{{ __('products.single') }}</x-badge>
                            @elseif($product->product_type === 'variant')
                                <x-badge type="info">{{ __('products.variant') }}</x-badge>
                            @else
                                <x-badge type="warning">{{ __('products.combo') }}</x-badge>
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
                                <x-badge type="success" dot>{{ __('products.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('products.inactive') }}</x-badge>
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
                                        {{ __('products.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('menu.products.edit', $product) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('products.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        @click="$refs.duplicateForm{{ $loop->index }}.submit()"
                                    >
                                        <x-icon name="document-duplicate" class="w-4 h-4" />
                                        {{ __('products.duplicate') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('products.delete_product') }}',
                                            message: '{{ __('products.confirm_delete', ['name' => $product->name]) }}',
                                            confirmText: '{{ __('products.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('products.delete') }}
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
                title="{{ __('products.no_products') }}"
                description="{{ __('products.no_products_desc') }}"
                icon="cube"
            >
                <x-button href="{{ route('menu.products.create') }}" icon="plus">
                    {{ __('products.add_product') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
