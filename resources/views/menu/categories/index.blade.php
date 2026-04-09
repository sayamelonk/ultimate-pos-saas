<x-app-layout>
    <x-slot name="title">{{ __('products.menu_categories') }} - Ultimate POS</x-slot>

    @section('page-title', __('products.menu_categories'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('products.menu_categories') }}</h2>
                <p class="text-muted mt-1">{{ __('products.manage_categories') }}</p>
            </div>
            <x-button href="{{ route('menu.categories.create') }}" icon="plus">
                {{ __('products.add_category') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('menu.categories.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('products.search_categories') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="parent_id" class="w-48">
                <option value="">{{ __('products.all_categories') }}</option>
                <option value="root" @selected(request('parent_id') === 'root')>{{ __('products.root_only') }}</option>
                @foreach($parentCategories as $parent)
                    <option value="{{ $parent->id }}" @selected(request('parent_id') == $parent->id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </x-select>
            <x-select name="status" class="w-32">
                <option value="">{{ __('products.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('products.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('products.inactive') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('products.filter') }}</x-button>
            @if(request()->hasAny(['search', 'parent_id', 'status']))
                <x-button href="{{ route('menu.categories.index') }}" variant="ghost">{{ __('products.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($categories->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('products.category') }}</x-th>
                    <x-th>{{ __('products.code') }}</x-th>
                    <x-th>{{ __('products.parent') }}</x-th>
                    <x-th align="right">{{ __('products.products') }}</x-th>
                    <x-th align="center">{{ __('products.pos') }}</x-th>
                    <x-th align="center">{{ __('products.status') }}</x-th>
                    <x-th align="right">{{ __('products.actions') }}</x-th>
                </x-slot>

                @foreach($categories as $category)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $category->color ?? '#e5e7eb' }}20;">
                                    @if($category->icon)
                                        <x-icon :name="$category->icon" class="w-5 h-5" style="color: {{ $category->color ?? '#6b7280' }};" />
                                    @else
                                        <x-icon name="squares-2x2" class="w-5 h-5" style="color: {{ $category->color ?? '#6b7280' }};" />
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $category->name }}</p>
                                    @if($category->description)
                                        <p class="text-xs text-muted truncate max-w-[200px]">{{ $category->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            @if($category->code)
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $category->code }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td>{{ $category->parent->name ?? '-' }}</x-td>
                        <x-td align="right">{{ $category->products_count }}</x-td>
                        <x-td align="center">
                            @if($category->show_in_pos)
                                <x-badge type="success" size="sm">{{ __('products.yes') }}</x-badge>
                            @else
                                <x-badge type="secondary" size="sm">{{ __('products.no') }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($category->is_active)
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

                                    <x-dropdown-item href="{{ route('menu.categories.show', $category) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('products.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('menu.categories.edit', $category) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('products.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('products.delete_category') }}',
                                            message: '{{ __('products.confirm_delete', ['name' => $category->name]) }}',
                                            confirmText: '{{ __('products.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('products.delete') }}
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('menu.categories.destroy', $category) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$categories" />
            </div>
        @else
            <x-empty-state
                title="{{ __('products.no_categories') }}"
                description="{{ __('products.no_categories_desc') }}"
                icon="squares-2x2"
            >
                <x-button href="{{ route('menu.categories.create') }}" icon="plus">
                    {{ __('products.add_category') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
