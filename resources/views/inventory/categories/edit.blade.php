<x-app-layout>
    <x-slot name="title">{{ __('inventory.edit_category') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.edit_category'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.categories.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.edit_category') }}</h2>
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
                        label="{{ __('inventory.category_code') }}"
                        placeholder="{{ __('inventory.category_code_placeholder') }}"
                        :value="$category->code"
                        required
                    />

                    <x-input
                        name="name"
                        label="{{ __('inventory.category_name') }}"
                        placeholder="{{ __('inventory.category_name_placeholder') }}"
                        :value="$category->name"
                        required
                    />
                </div>

                <x-select
                    name="parent_id"
                    label="{{ __('inventory.parent_category') }}"
                >
                    <option value="">{{ __('inventory.root_category') }}</option>
                    @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-textarea
                    name="description"
                    label="{{ __('inventory.description') }}"
                    placeholder="{{ __('inventory.description_placeholder') }}"
                    :value="$category->description"
                    rows="3"
                />

                <x-checkbox
                    name="is_active"
                    label="{{ __('inventory.active') }}"
                    :checked="$category->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.categories.index') }}" variant="outline-secondary">
                        {{ __('inventory.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('inventory.update') }} {{ __('inventory.category') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
