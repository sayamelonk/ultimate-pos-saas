<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_category') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_category'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.categories.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.add_category_title') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_new_category') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('inventory.categories.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="code"
                        label="{{ __('inventory.category_code') }}"
                        placeholder="{{ __('inventory.category_code_placeholder') }}"
                        required
                    />

                    <x-input
                        name="name"
                        label="{{ __('inventory.category_name') }}"
                        placeholder="{{ __('inventory.category_name_placeholder') }}"
                        required
                    />
                </div>

                <x-select
                    name="parent_id"
                    label="{{ __('inventory.parent_category') }}"
                >
                    <option value="">{{ __('inventory.root_category') }}</option>
                    @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-textarea
                    name="description"
                    label="{{ __('inventory.description') }}"
                    placeholder="{{ __('inventory.description_placeholder') }}"
                    rows="3"
                />

                <x-checkbox
                    name="is_active"
                    label="{{ __('inventory.active') }}"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.categories.index') }}" variant="outline-secondary">
                        {{ __('inventory.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('inventory.create_category') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
