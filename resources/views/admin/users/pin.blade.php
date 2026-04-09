<x-app-layout>
    <x-slot name="title">{{ __('admin.set_pin') }} - {{ $user->name }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.set_user_pin'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.users.show', $user) }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.set_pin_for_user', ['name' => $user->name]) }}</h2>
                <p class="text-muted mt-1">{{ __('admin.set_authorization_pin_user') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-md">
        <x-card>
            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-border">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-xl font-bold text-primary">{{ $user->initials }}</span>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">{{ $user->name }}</h3>
                    <p class="text-sm text-muted">{{ $user->email }}</p>
                    @if($hasPin)
                        <x-badge type="success" class="mt-1">{{ __('admin.pin_active') }}</x-badge>
                    @else
                        <x-badge type="secondary" class="mt-1">{{ __('admin.no_pin_set') }}</x-badge>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.users.pin.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            {{ $hasPin ? __('admin.new_pin') : __('admin.pin') }} <span class="text-danger">*</span>
                        </label>
                        <x-input
                            type="password"
                            name="pin"
                            maxlength="{{ $settings->pin_length }}"
                            placeholder="{{ __('admin.enter_digit_pin', ['length' => $settings->pin_length]) }}"
                            pattern="[0-9]*"
                            inputmode="numeric"
                            required
                            autocomplete="new-password"
                        />
                        <p class="text-xs text-muted mt-1">{{ __('admin.must_be_exactly_digits', ['length' => $settings->pin_length]) }}</p>
                        @error('pin')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            {{ __('admin.confirm_pin') }} <span class="text-danger">*</span>
                        </label>
                        <x-input
                            type="password"
                            name="pin_confirmation"
                            maxlength="{{ $settings->pin_length }}"
                            placeholder="{{ __('admin.confirm_digit_pin', ['length' => $settings->pin_length]) }}"
                            pattern="[0-9]*"
                            inputmode="numeric"
                            required
                            autocomplete="new-password"
                        />
                    </div>
                </div>

                <div class="mt-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
                    <div class="flex gap-3">
                        <x-icon name="exclamation-triangle" class="w-5 h-5 text-warning shrink-0 mt-0.5" />
                        <div class="text-sm">
                            <p class="font-medium text-warning-700">{{ __('admin.important') }}</p>
                            <p class="text-muted mt-1">{{ __('admin.pin_usage_warning') }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-6 border-t border-border">
                    @if($hasPin)
                        <form action="{{ route('admin.users.pin.destroy', $user) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="danger" onclick="return confirm('{{ __('admin.confirm_deactivate_pin') }}')">
                                {{ __('admin.deactivate_pin') }}
                            </x-button>
                        </form>
                    @else
                        <div></div>
                    @endif

                    <div class="flex items-center gap-3">
                        <x-button href="{{ route('admin.users.show', $user) }}" variant="secondary">
                            {{ __('admin.cancel') }}
                        </x-button>
                        <x-button type="submit" icon="check">
                            {{ $hasPin ? __('admin.update_pin') : __('admin.set_pin') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
