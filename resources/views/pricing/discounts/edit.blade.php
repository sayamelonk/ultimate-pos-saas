<x-app-layout>
    <x-slot name="title">{{ __('pricing.edit_discount') }} - Ultimate POS</x-slot>

    @section('page-title', __('pricing.edit_discount'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('pricing.discounts.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('pricing.edit_discount') }}</h2>
                <p class="text-muted mt-1">{{ __('pricing.update_discount_description') }}</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('pricing.discounts.update', $discount) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-card title="{{ __('pricing.basic_information') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="{{ __('pricing.code') }}" name="code" :value="old('code', $discount->code)" required />
                        <x-input label="{{ __('pricing.name') }}" name="name" :value="old('name', $discount->name)" required />
                    </div>
                    <div class="mt-4">
                        <x-textarea label="{{ __('pricing.description') }}" name="description" rows="2">{{ old('description', $discount->description) }}</x-textarea>
                    </div>
                </x-card>

                <x-card title="{{ __('pricing.discount_settings') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select label="{{ __('pricing.type') }}" name="type" required>
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $discount->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-select label="{{ __('pricing.scope') }}" name="scope" required>
                            @foreach($scopes as $value => $label)
                                <option value="{{ $value }}" @selected(old('scope', $discount->scope) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-input label="{{ __('pricing.value') }}" name="value" type="number" step="0.01" :value="old('value', $discount->value)" required />
                        <x-input label="{{ __('pricing.max_discount') }} (Rp)" name="max_discount" type="number" :value="old('max_discount', $discount->max_discount)" />
                        <x-input label="{{ __('pricing.min_purchase') }} (Rp)" name="min_purchase" type="number" :value="old('min_purchase', $discount->min_purchase)" />
                        <x-input label="{{ __('pricing.min_qty') }}" name="min_qty" type="number" :value="old('min_qty', $discount->min_qty)" />
                    </div>
                </x-card>

                <x-card title="{{ __('pricing.validity_period') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="{{ __('pricing.valid_from') }}" name="valid_from" type="date" :value="old('valid_from', $discount->valid_from->format('Y-m-d'))" required />
                        <x-input label="{{ __('pricing.valid_until') }}" name="valid_until" type="date" :value="old('valid_until', $discount->valid_until?->format('Y-m-d'))" />
                        <x-input label="{{ __('pricing.usage_limit') }}" name="usage_limit" type="number" :value="old('usage_limit', $discount->usage_limit)" />
                        <div>
                            <label class="block text-sm font-medium text-text mb-1">{{ __('pricing.current_usage') }}</label>
                            <p class="text-lg font-semibold">{{ $discount->usage_count }}</p>
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="{{ __('pricing.member_settings') }}">
                    <x-checkbox name="member_only" label="{{ __('pricing.member_only') }}" :checked="old('member_only', $discount->member_only)" />
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-text mb-2">{{ __('pricing.applicable_levels') }}</label>
                        <div class="space-y-2">
                            @foreach(['silver' => __('pricing.silver'), 'gold' => __('pricing.gold'), 'platinum' => __('pricing.platinum')] as $value => $label)
                                <x-checkbox
                                    name="membership_levels[]"
                                    value="{{ $value }}"
                                    label="{{ $label }}"
                                    :checked="in_array($value, old('membership_levels', $discount->membership_levels ?? []))"
                                />
                            @endforeach
                        </div>
                    </div>
                </x-card>

                <x-card title="{{ __('pricing.options') }}">
                    <div class="space-y-4">
                        <x-checkbox name="is_auto_apply" label="{{ __('pricing.auto_apply') }}" :checked="old('is_auto_apply', $discount->is_auto_apply)" />
                        <x-checkbox name="is_active" label="{{ __('pricing.active') }}" :checked="old('is_active', $discount->is_active)" />
                    </div>
                </x-card>

                <div class="flex gap-3">
                    <x-button type="submit" class="flex-1">{{ __('pricing.update') }}</x-button>
                    <x-button href="{{ route('pricing.discounts.index') }}" variant="secondary">{{ __('pricing.cancel') }}</x-button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
