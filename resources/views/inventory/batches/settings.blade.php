<x-app-layout>
    <x-slot name="title">Batch Settings - Ultimate POS</x-slot>

    @section('page-title', 'Batch Settings')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.batches.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Batch Settings</h2>
                <p class="text-muted mt-1">Configure batch tracking and expiry alerts</p>
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
                    <h3 class="font-semibold text-lg">Expiry Warning Thresholds</h3>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            Warning Days <span class="text-danger">*</span>
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
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted text-sm">days</span>
                        </div>
                        <p class="text-xs text-muted mt-1">Yellow warning before expiry</p>
                        @error('expiry_warning_days')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            Critical Days <span class="text-danger">*</span>
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
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted text-sm">days</span>
                        </div>
                        <p class="text-xs text-muted mt-1">Red critical alert before expiry</p>
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
                    <h3 class="font-semibold text-lg">Automatic Actions</h3>
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
                            <span class="font-medium text-text">Auto-mark as expired</span>
                            <p class="text-sm text-muted">Automatically mark batches as expired when they pass expiry date</p>
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
                            <span class="font-medium text-text">Block expired item sales</span>
                            <p class="text-sm text-muted">Prevent selling items from expired batches</p>
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
                            <span class="font-medium text-text">Enable FEFO (First Expired First Out)</span>
                            <p class="text-sm text-muted">Automatically deduct from batches expiring soonest first</p>
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
                    <h3 class="font-semibold text-lg">Notifications</h3>
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
                            <span class="font-medium text-text">Warning notifications</span>
                            <p class="text-sm text-muted">Show dashboard alerts for items in warning period</p>
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
                            <span class="font-medium text-text">Critical notifications</span>
                            <p class="text-sm text-muted">Show prominent alerts for items in critical period</p>
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
                            <span class="font-medium text-text">Daily expiry report email</span>
                            <p class="text-sm text-muted">Send daily email summary of expiring items</p>
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
                    <h3 class="font-semibold text-lg">Batch Number Generation</h3>
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
                            <span class="font-medium text-text">Auto-generate batch numbers</span>
                            <p class="text-sm text-muted">Automatically generate batch numbers when not provided</p>
                        </div>
                    </label>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            Batch Prefix <span class="text-danger">*</span>
                        </label>
                        <x-input
                            name="batch_prefix"
                            :value="old('batch_prefix', $settings->batch_prefix)"
                            maxlength="10"
                            required
                            placeholder="BTH"
                        />
                        <p class="text-xs text-muted mt-1">
                            Format: {{ $settings->batch_prefix }}-YYYYMMDD-001
                        </p>
                        @error('batch_prefix')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('inventory.batches.index') }}" variant="secondary">
                    Cancel
                </x-button>
                <x-button type="submit" icon="check">
                    Save Settings
                </x-button>
            </div>
        </form>
    </div>
</x-app-layout>
