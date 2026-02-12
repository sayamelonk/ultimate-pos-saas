<x-app-layout>
    <x-slot name="title">Edit {{ $category->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Edit Category')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.categories.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Category</h2>
                <p class="text-muted mt-1">{{ $category->name }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.categories.update', $category) }}" method="POST" class="max-w-2xl">
        @csrf
        @method('PUT')

        <x-card title="Category Information">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-form-group label="Category Name" name="name" required>
                        <x-input
                            name="name"
                            :value="old('name', $category->name)"
                            placeholder="e.g., Beverages"
                            required
                        />
                    </x-form-group>

                    <x-form-group label="Code" name="code">
                        <x-input
                            name="code"
                            :value="old('code', $category->code)"
                            placeholder="e.g., BEV"
                        />
                    </x-form-group>
                </div>

                <x-form-group label="Parent Category" name="parent_id">
                    <x-select name="parent_id">
                        <option value="">None (Root Category)</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </x-select>
                </x-form-group>

                <x-form-group label="Description" name="description">
                    <x-textarea
                        name="description"
                        :value="old('description', $category->description)"
                        placeholder="Optional description..."
                        rows="3"
                    />
                </x-form-group>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-group label="Color" name="color">
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                name="color"
                                value="{{ old('color', $category->color ?? '#3b82f6') }}"
                                class="w-12 h-10 rounded border border-border cursor-pointer"
                            />
                            <x-input
                                type="text"
                                name="color_text"
                                :value="old('color', $category->color ?? '#3b82f6')"
                                placeholder="#3b82f6"
                                class="flex-1"
                            />
                        </div>
                    </x-form-group>

                    <x-form-group label="Sort Order" name="sort_order">
                        <x-input
                            type="number"
                            name="sort_order"
                            :value="old('sort_order', $category->sort_order)"
                            min="0"
                        />
                    </x-form-group>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <x-form-group>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                                class="rounded border-border text-accent focus:ring-accent"
                            >
                            <span class="text-sm font-medium text-text">Active</span>
                        </label>
                    </x-form-group>

                    <x-form-group>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="show_in_pos" value="0">
                            <input
                                type="checkbox"
                                name="show_in_pos"
                                value="1"
                                {{ old('show_in_pos', $category->show_in_pos) ? 'checked' : '' }}
                                class="rounded border-border text-accent focus:ring-accent"
                            >
                            <span class="text-sm font-medium text-text">Show in POS</span>
                        </label>
                    </x-form-group>

                    <x-form-group>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="show_in_menu" value="0">
                            <input
                                type="checkbox"
                                name="show_in_menu"
                                value="1"
                                {{ old('show_in_menu', $category->show_in_menu) ? 'checked' : '' }}
                                class="rounded border-border text-accent focus:ring-accent"
                            >
                            <span class="text-sm font-medium text-text">Show in Menu</span>
                        </label>
                    </x-form-group>
                </div>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-3">
                    <x-button href="{{ route('menu.categories.index') }}" variant="ghost">Cancel</x-button>
                    <x-button type="submit" icon="check">Update Category</x-button>
                </div>
            </x-slot>
        </x-card>
    </form>
</x-app-layout>
