<x-app-layout>
    <x-slot name="title">{{ __('products.add_variant_title') }} - Ultimate POS</x-slot>

    @section('page-title', __('products.add_variant_title'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.variant-groups.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('products.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('products.add_variant_title') }}</h2>
                <p class="text-muted mt-1">{{ __('products.add_variant_options') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.variant-groups.store') }}" method="POST" x-data="variantGroupForm()">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <!-- Basic Information -->
                <x-card title="{{ __('products.group_information') }}">
                    <div class="space-y-4">
                        <x-form-group label="{{ __('products.group_name') }}" name="name" required>
                            <x-input
                                name="name"
                                :value="old('name')"
                                placeholder="{{ __('products.variant_name_placeholder') }}"
                                required
                            />
                        </x-form-group>

                        <x-form-group label="{{ __('products.description') }}" name="description">
                            <x-textarea
                                name="description"
                                :value="old('description')"
                                placeholder="{{ __('products.description_placeholder') }}"
                                rows="2"
                            />
                        </x-form-group>

                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="{{ __('products.display_type') }}" name="display_type">
                                <x-select name="display_type" x-model="displayType">
                                    <option value="button">{{ __('products.display_button') }}</option>
                                    <option value="dropdown">{{ __('products.display_dropdown') }}</option>
                                    <option value="color">{{ __('products.display_color') }}</option>
                                    <option value="image">{{ __('products.display_image') }}</option>
                                </x-select>
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
                    </div>
                </x-card>

                <!-- Options -->
                <x-card title="{{ __('products.variant_options') }}">
                    <p class="text-muted mb-4">{{ __('products.add_variant_options') }}</p>

                    <div class="space-y-3" x-ref="optionsContainer">
                        <template x-for="(option, index) in options" :key="index">
                            <div class="flex items-start gap-3 p-4 bg-secondary-50 rounded-lg">
                                <div class="flex-1 grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs text-muted mb-1">{{ __('products.option_name_required') }}</label>
                                        <input
                                            type="text"
                                            :name="`options[${index}][name]`"
                                            x-model="option.name"
                                            placeholder="{{ __('products.option_placeholder') }}"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs text-muted mb-1">{{ __('products.price_adjustment') }}</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">Rp</span>
                                            <input
                                                type="number"
                                                :name="`options[${index}][price_adjustment]`"
                                                x-model="option.price_adjustment"
                                                placeholder="0"
                                                step="100"
                                                class="w-full pl-10 pr-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            >
                                        </div>
                                    </div>
                                    <div x-show="displayType === 'color'">
                                        <label class="block text-xs text-muted mb-1">{{ __('products.color') }}</label>
                                        <input
                                            type="color"
                                            :name="`options[${index}][color_code]`"
                                            x-model="option.color_code"
                                            class="w-full h-10 rounded border border-border cursor-pointer"
                                        >
                                    </div>
                                    <div x-show="displayType !== 'color'">
                                        <label class="block text-xs text-muted mb-1">{{ __('products.sort_order') }}</label>
                                        <input
                                            type="number"
                                            :name="`options[${index}][sort_order]`"
                                            x-model="option.sort_order"
                                            placeholder="0"
                                            min="0"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                        >
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeOption(index)"
                                    class="p-2 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                    x-show="options.length > 1"
                                >
                                    <x-icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </template>
                    </div>

                    <x-button type="button" variant="outline-secondary" size="sm" icon="plus" @click="addOption()" class="mt-4">
                        {{ __('products.add_option') }}
                    </x-button>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Preview -->
                <x-card title="{{ __('products.preview') }}">
                    <div class="space-y-3">
                        <p class="text-sm text-muted">{{ __('products.how_options_appear') }}</p>

                        <!-- Button Preview -->
                        <div x-show="displayType === 'button'" class="flex flex-wrap gap-2">
                            <template x-for="(option, index) in options" :key="index">
                                <button type="button" class="px-4 py-2 border-2 border-border rounded-lg hover:border-accent transition-colors" :class="index === 0 ? 'border-accent bg-accent/5' : ''">
                                    <span x-text="option.name || '{{ __('products.option') }} ' + (index + 1)"></span>
                                    <span x-show="option.price_adjustment && option.price_adjustment != 0" class="text-xs text-muted ml-1">
                                        (<span x-text="option.price_adjustment > 0 ? '+' : ''"></span>Rp <span x-text="Number(option.price_adjustment || 0).toLocaleString('id-ID')"></span>)
                                    </span>
                                </button>
                            </template>
                        </div>

                        <!-- Dropdown Preview -->
                        <div x-show="displayType === 'dropdown'">
                            <select class="w-full px-3 py-2 border border-border rounded-lg">
                                <template x-for="(option, index) in options" :key="index">
                                    <option x-text="(option.name || '{{ __('products.option') }} ' + (index + 1)) + (option.price_adjustment && option.price_adjustment != 0 ? ' (+Rp ' + Number(option.price_adjustment).toLocaleString('id-ID') + ')' : '')"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Color Preview -->
                        <div x-show="displayType === 'color'" class="flex flex-wrap gap-2">
                            <template x-for="(option, index) in options" :key="index">
                                <button type="button" class="w-10 h-10 rounded-full border-2 border-border hover:border-accent transition-colors" :class="index === 0 ? 'ring-2 ring-accent ring-offset-2' : ''" :style="`background-color: ${option.color_code || '#e5e7eb'}`" :title="option.name"></button>
                            </template>
                        </div>

                        <!-- Image Preview -->
                        <div x-show="displayType === 'image'" class="flex flex-wrap gap-2">
                            <template x-for="(option, index) in options" :key="index">
                                <div class="w-16 h-16 border-2 border-border rounded-lg flex items-center justify-center bg-secondary-100 hover:border-accent transition-colors" :class="index === 0 ? 'border-accent' : ''">
                                    <x-icon name="photo" class="w-6 h-6 text-muted" />
                                </div>
                            </template>
                        </div>
                    </div>
                </x-card>

                <!-- Help -->
                <x-card title="{{ __('products.tips') }}">
                    <div class="space-y-3 text-sm text-muted">
                        <p><strong>{{ __('products.size_variants_tip') }}</strong></p>
                        <p><strong>{{ __('products.ice_level_tip') }}</strong></p>
                        <p><strong>{{ __('products.sugar_level_tip') }}</strong></p>
                        <p><strong>{{ __('products.temperature_tip') }}</strong></p>
                    </div>
                </x-card>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <x-button type="submit" icon="check" class="w-full">
                        {{ __('products.create_variant_group') }}
                    </x-button>
                    <x-button href="{{ route('menu.variant-groups.index') }}" variant="ghost" class="w-full">
                        {{ __('products.cancel') }}
                    </x-button>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function variantGroupForm() {
            return {
                displayType: '{{ old('display_type', 'button') }}',
                options: @json(old('options', [
                    ['name' => '', 'price_adjustment' => 0, 'color_code' => '#3b82f6', 'sort_order' => 0]
                ])),

                addOption() {
                    this.options.push({
                        name: '',
                        price_adjustment: 0,
                        color_code: '#3b82f6',
                        sort_order: this.options.length
                    });
                },

                removeOption(index) {
                    this.options.splice(index, 1);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
