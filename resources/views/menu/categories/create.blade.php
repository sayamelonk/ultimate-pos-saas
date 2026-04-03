<x-app-layout>
    <x-slot name="title">{{ __('products.add_menu_category') }} - Ultimate POS</x-slot>

    @section('page-title', __('products.add_menu_category'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.categories.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('products.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('products.add_menu_category') }}</h2>
                <p class="text-muted mt-1">{{ __('products.create_category_for_menu') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.categories.store') }}" method="POST" class="max-w-2xl">
        @csrf

        <x-card title="{{ __('products.category_information') }}">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-form-group label="{{ __('products.category_name') }}" name="name" required>
                        <x-input
                            name="name"
                            :value="old('name')"
                            placeholder="{{ __('products.category_name_placeholder') }}"
                            required
                        />
                    </x-form-group>

                    <x-form-group label="{{ __('products.code') }}" name="code">
                        <x-input
                            name="code"
                            :value="old('code')"
                            placeholder="{{ __('products.category_code_placeholder') }}"
                        />
                    </x-form-group>
                </div>

                <x-form-group label="{{ __('products.parent_category') }}" name="parent_id">
                    <x-select name="parent_id">
                        <option value="">{{ __('products.none_root') }}</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </x-select>
                </x-form-group>

                <x-form-group label="{{ __('products.description') }}" name="description">
                    <x-textarea
                        name="description"
                        :value="old('description')"
                        placeholder="{{ __('products.description_placeholder') }}"
                        rows="3"
                    />
                </x-form-group>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-group label="{{ __('products.color') }}" name="color">
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                name="color"
                                value="{{ old('color', '#3b82f6') }}"
                                class="w-12 h-10 rounded border border-border cursor-pointer"
                            />
                            <x-input
                                type="text"
                                name="color_text"
                                :value="old('color', '#3b82f6')"
                                placeholder="#3b82f6"
                                class="flex-1"
                                x-data
                                @input="$el.previousElementSibling.previousElementSibling.value = $el.value"
                            />
                        </div>
                    </x-form-group>

                    <x-form-group label="{{ __('products.sort_order') }}" name="sort_order">
                        <x-input
                            type="number"
                            name="sort_order"
                            :value="old('sort_order', 0)"
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
                                {{ old('is_active', true) ? 'checked' : '' }}
                                class="rounded border-border text-accent focus:ring-accent"
                            >
                            <span class="text-sm font-medium text-text">{{ __('products.active') }}</span>
                        </label>
                    </x-form-group>

                    <x-form-group>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="show_in_pos" value="0">
                            <input
                                type="checkbox"
                                name="show_in_pos"
                                value="1"
                                {{ old('show_in_pos', true) ? 'checked' : '' }}
                                class="rounded border-border text-accent focus:ring-accent"
                            >
                            <span class="text-sm font-medium text-text">{{ __('products.show_in_pos') }}</span>
                        </label>
                    </x-form-group>

                    <x-form-group>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="show_in_menu" value="0">
                            <input
                                type="checkbox"
                                name="show_in_menu"
                                value="1"
                                {{ old('show_in_menu', true) ? 'checked' : '' }}
                                class="rounded border-border text-accent focus:ring-accent"
                            >
                            <span class="text-sm font-medium text-text">{{ __('products.show_in_menu') }}</span>
                        </label>
                    </x-form-group>
                </div>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-3">
                    <x-button href="{{ route('menu.categories.index') }}" variant="ghost">{{ __('products.cancel') }}</x-button>
                    <x-button type="submit" icon="check">{{ __('products.create_category') }}</x-button>
                </div>
            </x-slot>
        </x-card>
    </form>
</x-app-layout>
