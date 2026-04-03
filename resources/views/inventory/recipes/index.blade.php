<x-app-layout>
    <x-slot name="title">{{ __('inventory.recipes') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.recipes'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.recipes') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.manage_recipes') }}</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.recipes.cost-analysis') }}" variant="outline-secondary" icon="chart-bar">
                    {{ __('inventory.cost_analysis') }}
                </x-button>
                <x-button href="{{ route('inventory.recipes.create') }}" icon="plus">
                    {{ __('inventory.create_recipe') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.recipes.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        :placeholder="__('inventory.search_recipes')"
                        :value="request('search')"
                    />
                </div>
                <x-select name="category_id" class="w-48">
                    <option value="">{{ __('inventory.all_categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">{{ __('inventory.all_status') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('inventory.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('inventory.inactive') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('inventory.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'category_id', 'status']))
                    <x-button href="{{ route('inventory.recipes.index') }}" variant="ghost">
                        {{ __('inventory.cancel') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($recipes->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.recipe_name') }}</x-th>
                    <x-th>{{ __('inventory.category') }}</x-th>
                    <x-th align="right">{{ __('inventory.ingredients') }}</x-th>
                    <x-th align="right">{{ __('inventory.yield') }}</x-th>
                    <x-th align="right">{{ __('inventory.total_cost') }}</x-th>
                    <x-th align="center">{{ __('inventory.status') }}</x-th>
                    <x-th align="right">{{ __('inventory.actions') }}</x-th>
                </x-slot>

                @foreach($recipes as $recipe)
                    <tr>
                        <x-td>
                            <a href="{{ route('inventory.recipes.show', $recipe) }}" class="text-accent hover:underline font-medium">
                                {{ $recipe->name }}
                            </a>
                            @if($recipe->product)
                                <p class="text-xs text-muted">{{ __('inventory.linked_product') }}: {{ $recipe->product->name }}</p>
                            @endif
                        </x-td>
                        <x-td>{{ $recipe->product->category->name ?? '-' }}</x-td>
                        <x-td align="right">{{ $recipe->items->count() }}</x-td>
                        <x-td align="right">
                            {{ number_format($recipe->yield_qty, 2) }}
                            {{ $recipe->yieldUnit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right">
                            <span class="font-medium">Rp {{ number_format($recipe->estimated_cost, 0, ',', '.') }}</span>
                            @if($recipe->yield_qty > 0)
                                <p class="text-xs text-muted">
                                    Rp {{ number_format($recipe->estimated_cost / $recipe->yield_qty, 0, ',', '.') }}/unit
                                </p>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($recipe->is_active)
                                <x-badge type="success">{{ __('inventory.active') }}</x-badge>
                            @else
                                <x-badge type="secondary">{{ __('inventory.inactive') }}</x-badge>
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

                                    <x-dropdown-item href="{{ route('inventory.recipes.show', $recipe) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('inventory.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('inventory.recipes.edit', $recipe) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('inventory.edit') }}
                                    </x-dropdown-item>
                                    <form action="{{ route('inventory.recipes.duplicate', $recipe) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button">
                                            <x-icon name="document-duplicate" class="w-4 h-4" />
                                            {{ __('inventory.duplicate_recipe') }}
                                        </x-dropdown-item>
                                    </form>
                                    <form action="{{ route('inventory.recipes.recalculate', $recipe) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button">
                                            <x-icon name="calculator" class="w-4 h-4" />
                                            {{ __('inventory.recalculate_cost') }}
                                        </x-dropdown-item>
                                    </form>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('inventory.delete_recipe') }}',
                                            message: '{{ __('inventory.confirm_delete_recipe', ['name' => $recipe->name]) }}',
                                            confirmText: '{{ __('inventory.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('inventory.delete') }}
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.recipes.destroy', $recipe) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$recipes" />
            </div>
        @else
            <x-empty-state
                :title="__('inventory.no_recipes_found')"
                :description="__('inventory.no_recipes_description')"
                icon="beaker"
            >
                <x-button href="{{ route('inventory.recipes.create') }}" icon="plus">
                    {{ __('inventory.create_recipe') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
