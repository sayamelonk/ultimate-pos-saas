<x-app-layout>
    <x-slot name="title">Edit Category - Ultimate POS</x-slot>

    @section('page-title', 'Edit Category')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.categories.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Category</h2>
                <p class="text-muted mt-1">{{ $category->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('inventory.categories.update', $category) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="code"
                        label="Category Code"
                        placeholder="e.g., RAW-MEAT"
                        :value="$category->code"
                        required
                    />

                    <x-input
                        name="name"
                        label="Category Name"
                        placeholder="e.g., Raw Meat"
                        :value="$category->name"
                        required
                    />
                </div>

                <x-select
                    name="parent_id"
                    label="Parent Category"
                    hint="Leave empty for root category"
                >
                    <option value="">None (Root Category)</option>
                    @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-textarea
                    name="description"
                    label="Description"
                    placeholder="Brief description of this category"
                    :value="$category->description"
                    rows="3"
                />

                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive categories won't appear in selections"
                    :checked="$category->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.categories.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Update Category
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
