<x-app-layout>
    <x-slot name="title">{{ __('inventory.categories') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.categories'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.categories') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_new_category') }}</p>
            </div>
            <x-button href="{{ route('inventory.categories.create') }}" icon="plus">
                {{ __('inventory.create_category') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('inventory.categories.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('inventory.search') }}..."
                :value="request('search')"
                class="w-64"
            />
            <x-select name="parent_id" class="w-48">
                <option value="">{{ __('inventory.all_categories') }}</option>
                <option value="root" @selected(request('parent_id') === 'root')>{{ __('inventory.root_category') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('inventory.filter') }}</x-button>
            @if(request()->hasAny(['search', 'parent_id']))
                <x-button href="{{ route('inventory.categories.index') }}" variant="ghost">{{ __('app.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($categories->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.category') }}</x-th>
                    <x-th>{{ __('inventory.category_code') }}</x-th>
                    <x-th>{{ __('inventory.parent_category') }}</x-th>
                    <x-th align="center">{{ __('inventory.status') }}</x-th>
                    <x-th align="right">{{ __('inventory.actions') }}</x-th>
                </x-slot>

                @foreach($categories as $category)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="folder" class="w-5 h-5 text-accent" />
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
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $category->code }}</code>
                        </x-td>
                        <x-td>
                            {{ $category->parent->name ?? '-' }}
                        </x-td>
                        <x-td align="center">
                            @if($category->is_active)
                                <x-badge type="success" dot>{{ __('inventory.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('inventory.inactive') }}</x-badge>
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

                                    <x-dropdown-item href="{{ route('inventory.categories.show', $category) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('app.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('inventory.categories.edit', $category) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('inventory.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('inventory.delete') }} {{ __('inventory.category') }}',
                                            message: '{{ __('app.confirm_delete', ['name' => $category->name]) }}',
                                            confirmText: '{{ __('inventory.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('inventory.delete') }}
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.categories.destroy', $category) }}" method="POST" class="hidden">
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
                title="{{ __('inventory.no_items_found') }}"
                description="{{ __('inventory.no_items_in_category') }}"
                icon="folder"
            >
                <x-button href="{{ route('inventory.categories.create') }}" icon="plus">
                    {{ __('inventory.create_category') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
