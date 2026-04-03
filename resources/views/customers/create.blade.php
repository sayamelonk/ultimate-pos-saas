<x-app-layout>
    <x-slot name="title">{{ __('customers.add_customer_title') }} - Ultimate POS</x-slot>

    @section('page-title', __('customers.add_customer_title'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('customers.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('customers.add_customer_title') }}</h2>
                <p class="text-muted mt-1">{{ __('customers.create_customer_desc') }}</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('customers.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2">
                <x-card :title="__('customers.customer_information')">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input
                            :label="__('customers.customer_code')"
                            name="code"
                            :value="old('code', $suggestedCode)"
                            required
                        />
                        <x-input
                            :label="__('customers.full_name')"
                            name="name"
                            :value="old('name')"
                            required
                        />
                        <x-input
                            :label="__('customers.email')"
                            type="email"
                            name="email"
                            :value="old('email')"
                        />
                        <x-input
                            :label="__('customers.phone')"
                            name="phone"
                            :value="old('phone')"
                        />
                        <x-input
                            :label="__('customers.birth_date')"
                            type="date"
                            name="birth_date"
                            :value="old('birth_date')"
                        />
                        <x-select :label="__('customers.gender')" name="gender">
                            <option value="">{{ __('customers.select_gender') }}</option>
                            <option value="male" @selected(old('gender') === 'male')>{{ __('customers.male') }}</option>
                            <option value="female" @selected(old('gender') === 'female')>{{ __('customers.female') }}</option>
                            <option value="other" @selected(old('gender') === 'other')>{{ __('customers.other') }}</option>
                        </x-select>
                    </div>

                    <div class="mt-4">
                        <x-textarea
                            :label="__('customers.address')"
                            name="address"
                            rows="3"
                        >{{ old('address') }}</x-textarea>
                    </div>

                    <div class="mt-4">
                        <x-textarea
                            :label="__('customers.notes')"
                            name="notes"
                            rows="2"
                        >{{ old('notes') }}</x-textarea>
                    </div>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <x-card :title="__('customers.membership')">
                    <x-select :label="__('customers.membership_level')" name="membership_level">
                        @foreach($membershipLevels as $value => $label)
                            <option value="{{ $value }}" @selected(old('membership_level', 'regular') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>

                    <div class="mt-4">
                        <x-input
                            :label="__('customers.membership_expires')"
                            type="date"
                            name="membership_expires_at"
                            :value="old('membership_expires_at')"
                        />
                    </div>
                </x-card>

                <x-card :title="__('customers.status')">
                    <x-checkbox
                        name="is_active"
                        :label="__('customers.active')"
                        :checked="old('is_active', true)"
                    />
                </x-card>

                <div class="flex gap-3">
                    <x-button type="submit" class="flex-1">
                        {{ __('customers.save_customer') }}
                    </x-button>
                    <x-button href="{{ route('customers.index') }}" variant="secondary">
                        {{ __('customers.cancel') }}
                    </x-button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
