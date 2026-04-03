<x-app-layout>
    <x-slot name="title">{{ __('admin.add_plan') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.add_plan'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.subscription-plans.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('admin.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.add_plan') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.create_new_plan') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl" x-data="{
        features: [],
        addFeature() {
            this.features.push({ key: '', value: '' });
        },
        removeFeature(index) {
            this.features.splice(index, 1);
        }
    }">
        <x-card>
            <form action="{{ route('admin.subscription-plans.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-text">{{ __('admin.basic_information') }}</h3>

                    <x-input
                        name="name"
                        label="{{ __('admin.name') }}"
                        placeholder="{{ __('admin.plan_name_placeholder') }}"
                        required
                    />

                    <x-input
                        name="slug"
                        label="{{ __('admin.slug') }}"
                        placeholder="{{ __('admin.plan_slug_placeholder') }}"
                        hint="{{ __('admin.plan_slug_hint') }}"
                        required
                    />

                    <div>
                        <label class="block text-sm font-medium text-text mb-2">{{ __('admin.description') }}</label>
                        <textarea
                            name="description"
                            rows="3"
                            class="w-full rounded-lg border-border bg-input text-text placeholder-muted focus:border-primary focus:ring-2 focus:ring-primary/20"
                            placeholder="{{ __('admin.plan_description_placeholder') }}"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Pricing -->
                <div class="space-y-4 pt-6 border-t border-border">
                    <h3 class="text-lg font-semibold text-text">{{ __('admin.pricing') }}</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <x-input
                            type="number"
                            name="price_monthly"
                            label="{{ __('admin.price_monthly') }}"
                            placeholder="99000"
                            hint="{{ __('admin.price_in_idr') }}"
                            step="1000"
                            min="0"
                            required
                        />

                        <x-input
                            type="number"
                            name="price_yearly"
                            label="{{ __('admin.price_yearly') }}"
                            placeholder="990000"
                            hint="{{ __('admin.price_in_idr') }}"
                            step="1000"
                            min="0"
                            required
                        />
                    </div>
                </div>

                <!-- Limits -->
                <div class="space-y-4 pt-6 border-t border-border">
                    <h3 class="text-lg font-semibold text-text">{{ __('admin.limits') }}</h3>
                    <p class="text-sm text-muted">{{ __('admin.limits_hint') }}</p>

                    <div class="grid grid-cols-3 gap-4">
                        <x-input
                            type="number"
                            name="max_outlets"
                            label="{{ __('admin.max_outlets') }}"
                            placeholder="-1"
                            hint="{{ __('admin.unlimited_hint') }}"
                            value="-1"
                            required
                        />

                        <x-input
                            type="number"
                            name="max_users"
                            label="{{ __('admin.max_users') }}"
                            placeholder="-1"
                            hint="{{ __('admin.unlimited_hint') }}"
                            value="-1"
                            required
                        />

                        <x-input
                            type="number"
                            name="max_products"
                            label="{{ __('admin.max_products') }}"
                            placeholder="-1"
                            hint="{{ __('admin.unlimited_hint') }}"
                            value="-1"
                            required
                        />
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-4 pt-6 border-t border-border">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-text">{{ __('admin.features') }}</h3>
                            <p class="text-sm text-muted mt-1">{{ __('admin.features_hint') }}</p>
                        </div>
                        <x-button type="button" variant="secondary" size="sm" @click="addFeature()">
                            {{ __('admin.add_feature') }}
                        </x-button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(feature, index) in features" :key="index">
                            <div class="flex items-start gap-3">
                                <div class="flex-1 grid grid-cols-2 gap-3">
                                    <input
                                        type="text"
                                        :name="'features[' + index + '][key]'"
                                        x-model="feature.key"
                                        placeholder="{{ __('admin.feature_key') }}"
                                        class="rounded-lg border-border bg-input text-text placeholder-muted focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                    <input
                                        type="text"
                                        :name="'features[' + index + '][value]'"
                                        x-model="feature.value"
                                        placeholder="{{ __('admin.feature_value') }}"
                                        class="rounded-lg border-border bg-input text-text placeholder-muted focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="removeFeature(index)"
                                    class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors mt-1"
                                >
                                    <x-icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </template>

                        <div x-show="features.length === 0" class="text-sm text-muted text-center py-4 bg-secondary-50 rounded-lg">
                            {{ __('admin.no_features_added') }}
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="space-y-4 pt-6 border-t border-border">
                    <h3 class="text-lg font-semibold text-text">{{ __('admin.settings') }}</h3>

                    <x-input
                        type="number"
                        name="sort_order"
                        label="{{ __('admin.sort_order') }}"
                        hint="{{ __('admin.sort_order_hint') }}"
                        value="0"
                        min="0"
                        required
                    />

                    <x-checkbox
                        name="is_active"
                        label="{{ __('admin.active_label') }}"
                        hint="{{ __('admin.plan_active_hint') }}"
                        checked
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.subscription-plans.index') }}" variant="outline-secondary">
                        {{ __('admin.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin.create_plan') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
