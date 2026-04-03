<x-app-layout>
    <x-slot name="title">{{ __('inventory.batch_settings') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.batch_settings'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.batches.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.batch_settings') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.manage_batch_settings') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('inventory.batches.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Expiry Warning Settings -->
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                        <x-icon name="exclamation-triangle" class="w-5 h-5 text-warning" />
                    </div>
                    <h3 class="font-semibold text-lg">{{ __('inventory.expiry_alert_days') }}</h3>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            {{ __('inventory.expiry_alert_days') }} <span class="text-danger">*</span>
                        </label>
                        <div class="relative">
                            <x-input
                                type="number"
                                name="expiry_warning_days"
                                :value="old('expiry_warning_days', $settings->expiry_warning_days)"
                                min="1"
                                max="365"
                                required
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted text-sm">{{ __('inventory.days_until_expiry') }}</span>
                        </div>
                        <p class="text-xs text-muted mt-1">{{ __('inventory.enable_expiry_alerts') }}</p>
                        @error('expiry_warning_days')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            {{ __('inventory.expiry_alert_days') }} <span class="text-danger">*</span>
                        </label>
                        <div class="relative">
                            <x-input
                                type="number"
                                name="expiry_critical_days"
                                :value="old('expiry_critical_days', $settings->expiry_critical_days)"
                                min="1"
                                max="90"
                                required
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted text-sm">{{ __('inventory.days_until_expiry') }}</span>
                        </div>
                        <p class="text-xs text-muted mt-1">{{ __('inventory.enable_expiry_alerts') }}</p>
                        @error('expiry_critical_days')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <!-- Auto Actions -->
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <x-icon name="cog-6-tooth" class="w-5 h-5 text-primary" />
                    </div>
                    <h3 class="font-semibold text-lg">{{ __('inventory.settings') }}</h3>
                </div>

                <div class="space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="auto_mark_expired"
                            value="1"
                            @checked(old('auto_mark_expired', $settings->auto_mark_expired))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('inventory.expired') }}</span>
                            <p class="text-sm text-muted">{{ __('inventory.enable_expiry_alerts') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="block_expired_sales"
                            value="1"
                            @checked(old('block_expired_sales', $settings->block_expired_sales))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('inventory.expired_batches') }}</span>
                            <p class="text-sm text-muted">{{ __('inventory.enable_expiry_alerts') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="enable_fefo"
                            value="1"
                            @checked(old('enable_fefo', $settings->enable_fefo))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('inventory.fefo_enabled') }}</span>
                            <p class="text-sm text-muted">{{ __('inventory.fefo_enabled') }}</p>
                        </div>
                    </label>
                </div>
            </x-card>

            <!-- Notifications -->
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                        <x-icon name="bell" class="w-5 h-5 text-info" />
                    </div>
                    <h3 class="font-semibold text-lg">{{ __('admin.notifications') }}</h3>
                </div>

                <div class="space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="notify_expiry_warning"
                            value="1"
                            @checked(old('notify_expiry_warning', $settings->notify_expiry_warning))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('inventory.enable_expiry_alerts') }}</span>
                            <p class="text-sm text-muted">{{ __('inventory.enable_expiry_alerts') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="notify_expiry_critical"
                            value="1"
                            @checked(old('notify_expiry_critical', $settings->notify_expiry_critical))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('inventory.enable_expiry_alerts') }}</span>
                            <p class="text-sm text-muted">{{ __('inventory.enable_expiry_alerts') }}</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="daily_expiry_report"
                            value="1"
                            @checked(old('daily_expiry_report', $settings->daily_expiry_report))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('inventory.expiry_report') }}</span>
                            <p class="text-sm text-muted">{{ __('inventory.expiry_report') }}</p>
                        </div>
                    </label>
                </div>
            </x-card>

            <!-- Batch Number Settings -->
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-secondary-100 flex items-center justify-center">
                        <x-icon name="hashtag" class="w-5 h-5 text-secondary-600" />
                    </div>
                    <h3 class="font-semibold text-lg">{{ __('inventory.batch_number') }}</h3>
                </div>

                <div class="space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="auto_generate_batch"
                            value="1"
                            @checked(old('auto_generate_batch', $settings->auto_generate_batch))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">{{ __('inventory.batch_number_auto') }}</span>
                            <p class="text-sm text-muted">{{ __('inventory.batch_number_auto') }}</p>
                        </div>
                    </label>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            {{ __('inventory.batch_number') }} <span class="text-danger">*</span>
                        </label>
                        <x-input
                            name="batch_prefix"
                            :value="old('batch_prefix', $settings->batch_prefix)"
                            maxlength="10"
                            required
                            placeholder="BTH"
                        />
                        <p class="text-xs text-muted mt-1">
                            {{ $settings->batch_prefix }}-YYYYMMDD-001
                        </p>
                        @error('batch_prefix')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('inventory.batches.index') }}" variant="secondary">
                    {{ __('inventory.cancel') }}
                </x-button>
                <x-button type="submit" icon="check">
                    {{ __('inventory.save_settings') }}
                </x-button>
            </div>
        </form>
    </div>
</x-app-layout>
