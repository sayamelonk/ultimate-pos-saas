<x-app-layout>
    <x-slot name="title">Recipes - Ultimate POS</x-slot>

    @section('page-title', 'Recipes')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Recipes</h2>
                <p class="text-muted mt-1">Manage product recipes and ingredients</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.recipes.cost-analysis') }}" variant="outline-secondary" icon="chart-bar">
                    Cost Analysis
                </x-button>
                <x-button href="{{ route('inventory.recipes.create') }}" icon="plus">
                    New Recipe
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
                        placeholder="Search recipe name..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="category_id" class="w-48">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'category_id', 'status']))
                    <x-button href="{{ route('inventory.recipes.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($recipes->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Recipe Name</x-th>
                    <x-th>Category</x-th>
                    <x-th align="right">Ingredients</x-th>
                    <x-th align="right">Yield</x-th>
                    <x-th align="right">Cost</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($recipes as $recipe)
                    <tr>
                        <x-td>
                            <a href="{{ route('inventory.recipes.show', $recipe) }}" class="text-accent hover:underline font-medium">
                                {{ $recipe->name }}
                            </a>
                            @if($recipe->product)
                                <p class="text-xs text-muted">Linked to: {{ $recipe->product->name }}</p>
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
                                <x-badge type="success">Active</x-badge>
                            @else
                                <x-badge type="secondary">Inactive</x-badge>
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
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('inventory.recipes.edit', $recipe) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <form action="{{ route('inventory.recipes.duplicate', $recipe) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button">
                                            <x-icon name="document-duplicate" class="w-4 h-4" />
                                            Duplicate
                                        </x-dropdown-item>
                                    </form>
                                    <form action="{{ route('inventory.recipes.recalculate', $recipe) }}" method="POST" class="w-full">
                                        @csrf
                                        <x-dropdown-item type="button">
                                            <x-icon name="calculator" class="w-4 h-4" />
                                            Recalculate Cost
                                        </x-dropdown-item>
                                    </form>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Recipe',
                                            message: 'Are you sure you want to delete {{ $recipe->name }}? This action cannot be undone.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
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
                title="No recipes found"
                description="Recipes help you track ingredient costs and manage production."
                icon="beaker"
            >
                <x-button href="{{ route('inventory.recipes.create') }}" icon="plus">
                    New Recipe
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
