<x-app-layout>
    <x-slot name="title">{{ __('admin.authorization_settings') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.authorization_settings'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.dashboard') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.authorization_settings') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.configure_pin_auth') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        <form action="{{ route('admin.authorization.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Actions Requiring Authorization -->
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                        <x-icon name="shield-check" class="w-5 h-5 text-danger" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">{{ __('admin.actions_requiring_auth') }}</h3>
                        <p class="text-sm text-muted">{{ __('admin.select_actions_requiring_auth') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_void"
                            value="1"
                            @checked(old('require_auth_void', $settings->require_auth_void))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('admin.void_transaction_label') }}</span>
                            <p class="text-sm text-muted">{{ __('admin.void_transaction_desc') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_refund"
                            value="1"
                            @checked(old('require_auth_refund', $settings->require_auth_refund))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('admin.refund_label') }}</span>
                            <p class="text-sm text-muted">{{ __('admin.refund_desc') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_cancel_order"
                            value="1"
                            @checked(old('require_auth_cancel_order', $settings->require_auth_cancel_order))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('admin.cancel_order_label') }}</span>
                            <p class="text-sm text-muted">{{ __('admin.cancel_order_desc') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_price_override"
                            value="1"
                            @checked(old('require_auth_price_override', $settings->require_auth_price_override))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('admin.price_override_label') }}</span>
                            <p class="text-sm text-muted">{{ __('admin.price_override_desc') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_no_sale"
                            value="1"
                            @checked(old('require_auth_no_sale', $settings->require_auth_no_sale))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('admin.no_sale_label') }}</span>
                            <p class="text-sm text-muted">{{ __('admin.no_sale_desc') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_reprint"
                            value="1"
                            @checked(old('require_auth_reprint', $settings->require_auth_reprint))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('admin.reprint_label') }}</span>
                            <p class="text-sm text-muted">{{ __('admin.reprint_desc') }}</p>
                        </div>
                    </label>
                </div>

                <!-- Discount Threshold -->
                <div class="mt-6 p-4 border border-border rounded-lg">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="require_auth_discount"
                            value="1"
                            @checked(old('require_auth_discount', $settings->require_auth_discount))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div class="flex-1">
                            <span class="font-medium text-text">{{ __('admin.manual_discount_label') }}</span>
                            <p class="text-sm text-muted mb-3">{{ __('admin.manual_discount_desc') }}</p>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted">{{ __('admin.threshold') }}:</span>
                                <input
                                    type="number"
                                    name="discount_threshold_percent"
                                    value="{{ old('discount_threshold_percent', $settings->discount_threshold_percent) }}"
                                    min="0"
                                    max="100"
                                    step="1"
                                    class="w-20 px-3 py-1 border border-border rounded text-center"
                                />
                                <span class="text-sm text-muted">%</span>
                            </div>
                        </div>
                    </label>
                </div>
            </x-card>

            <!-- PIN Settings -->
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <x-icon name="key" class="w-5 h-5 text-primary" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">{{ __('admin.pin_settings') }}</h3>
                        <p class="text-sm text-muted">{{ __('admin.configure_pin_security') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">{{ __('admin.pin_length') }}</label>
                        <x-select name="pin_length">
                            <option value="4" @selected(old('pin_length', $settings->pin_length) == 4)>{{ __('admin.digits', ['count' => 4]) }}</option>
                            <option value="6" @selected(old('pin_length', $settings->pin_length) == 6)>{{ __('admin.digits', ['count' => 6]) }}</option>
                        </x-select>
                        <p class="text-xs text-muted mt-1">{{ __('admin.number_of_digits') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">{{ __('admin.max_attempts') }}</label>
                        <x-input
                            type="number"
                            name="max_pin_attempts"
                            :value="old('max_pin_attempts', $settings->max_pin_attempts)"
                            min="1"
                            max="10"
                        />
                        <p class="text-xs text-muted mt-1">{{ __('admin.failed_attempts_before_lockout') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">{{ __('admin.lockout_duration') }}</label>
                        <div class="flex items-center gap-2">
                            <x-input
                                type="number"
                                name="lockout_minutes"
                                :value="old('lockout_minutes', $settings->lockout_minutes)"
                                min="1"
                                max="60"
                            />
                            <span class="text-sm text-muted">{{ __('admin.minutes') }}</span>
                        </div>
                        <p class="text-xs text-muted mt-1">{{ __('admin.duration_after_max_attempts') }}</p>
                    </div>
                </div>
            </x-card>

            <!-- Authorizers List -->
            <x-card class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                            <x-icon name="users" class="w-5 h-5 text-success" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">{{ __('admin.authorized_users') }}</h3>
                            <p class="text-sm text-muted">{{ __('admin.users_can_authorize') }}</p>
                        </div>
                    </div>
                </div>

                @if($authorizers->count() > 0)
                    <div class="divide-y divide-border">
                        @foreach($authorizers as $authorizer)
                            <div class="flex items-center justify-between py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary">{{ $authorizer->initials }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-text">{{ $authorizer->name }}</p>
                                        <p class="text-sm text-muted">{{ $authorizer->roles->pluck('name')->join(', ') }}</p>
                                    </div>
                                </div>
                                <x-badge type="success">{{ __('admin.pin_active') }}</x-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-icon name="user-plus" class="w-12 h-12 text-muted mx-auto mb-3" />
                        <p class="text-muted">{{ __('admin.no_users_with_pin') }}</p>
                        <p class="text-sm text-muted mt-1">{{ __('admin.users_need_pin') }}</p>
                    </div>
                @endif
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('admin.dashboard') }}" variant="secondary">
                    {{ __('app.cancel') }}
                </x-button>
                <x-button type="submit" icon="check">
                    {{ __('admin.save_settings') }}
                </x-button>
            </div>
        </form>
    </div>
</x-app-layout>
