<x-app-layout>
    <x-slot name="title">{{ __('pricing.edit_payment_method') }} - Ultimate POS</x-slot>

    @section('page-title', __('pricing.edit_payment_method'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('pricing.payment-methods.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('pricing.edit_payment_method') }}</h2>
                <p class="text-muted mt-1">{{ __('pricing.update_payment_method_details') }}</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('pricing.payment-methods.update', $paymentMethod) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-card :title="__('pricing.payment_method_details')">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input
                            :label="__('pricing.code')"
                            name="code"
                            :value="old('code', $paymentMethod->code)"
                            required
                        />
                        <x-input
                            :label="__('pricing.name')"
                            name="name"
                            :value="old('name', $paymentMethod->name)"
                            required
                        />
                        <x-select :label="__('pricing.type')" name="type" required>
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $paymentMethod->type) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </x-select>
                        <x-input
                            :label="__('pricing.provider')"
                            name="provider"
                            :value="old('provider', $paymentMethod->provider)"
                        />
                        <x-input
                            :label="__('pricing.charge_percentage') . ' (%)'"
                            name="charge_percentage"
                            type="number"
                            step="0.01"
                            :value="old('charge_percentage', $paymentMethod->charge_percentage)"
                        />
                        <x-input
                            :label="__('pricing.fixed_fee') . ' (Rp)'"
                            name="charge_fixed"
                            type="number"
                            :value="old('charge_fixed', $paymentMethod->charge_fixed)"
                        />
                        <x-input
                            :label="__('pricing.icon')"
                            name="icon"
                            :value="old('icon', $paymentMethod->icon)"
                        />
                        <x-input
                            :label="__('pricing.sort_order')"
                            name="sort_order"
                            type="number"
                            :value="old('sort_order', $paymentMethod->sort_order)"
                        />
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card :title="__('pricing.options')">
                    <div class="space-y-4">
                        <x-checkbox
                            name="requires_reference"
                            :label="__('pricing.requires_reference_number')"
                            :checked="old('requires_reference', $paymentMethod->requires_reference)"
                        />
                        <x-checkbox
                            name="opens_cash_drawer"
                            :label="__('pricing.opens_cash_drawer')"
                            :checked="old('opens_cash_drawer', $paymentMethod->opens_cash_drawer)"
                        />
                        <x-checkbox
                            name="is_active"
                            :label="__('pricing.active')"
                            :checked="old('is_active', $paymentMethod->is_active)"
                        />
                    </div>
                </x-card>

                <div class="flex gap-3">
                    <x-button type="submit" class="flex-1">{{ __('pricing.update') }}</x-button>
                    <x-button href="{{ route('pricing.payment-methods.index') }}" variant="secondary">{{ __('pricing.cancel') }}</x-button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
