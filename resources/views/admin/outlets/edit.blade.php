<x-app-layout>
    <x-slot name="title">{{ __('admin.edit_outlet') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.edit_outlet'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.outlets.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.edit_outlet') }}</h2>
                <p class="text-muted mt-1">{{ $outlet->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('admin.outlets.update', $outlet) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="name"
                        label="{{ __('admin.outlet_name_label') }}"
                        placeholder="{{ __('admin.outlet_name_placeholder') }}"
                        :value="$outlet->name"
                        required
                    />

                    <x-input
                        name="code"
                        label="{{ __('admin.outlet_code_label') }}"
                        placeholder="{{ __('admin.outlet_code_placeholder') }}"
                        :value="$outlet->code"
                        hint="{{ __('admin.outlet_code_hint') }}"
                        required
                    />
                </div>

                <x-textarea
                    name="address"
                    label="{{ __('admin.address') }}"
                    placeholder="{{ __('admin.enter_address') }}"
                    :value="$outlet->address"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="tel"
                        name="phone"
                        label="{{ __('admin.phone') }}"
                        placeholder="{{ __('admin.phone_placeholder') }}"
                        :value="$outlet->phone"
                    />

                    <x-input
                        type="email"
                        name="email"
                        label="{{ __('admin.email') }}"
                        placeholder="{{ __('admin.email_placeholder') }}"
                        :value="$outlet->email"
                    />
                </div>

                <x-checkbox
                    name="is_active"
                    label="{{ __('app.active') }}"
                    hint="{{ __('admin.inactive_not_accessible') }}"
                    :checked="$outlet->is_active"
                />

                {{-- Tax & Service Charge Settings --}}
                <div class="border-t border-border pt-6">
                    <h3 class="text-lg font-semibold text-text mb-4">{{ __('admin.tax_settings') }}</h3>
                    <p class="text-sm text-muted mb-4">{{ __('admin.tax_settings_hint') }}</p>

                    <div class="space-y-4">
                        {{-- Tax Settings --}}
                        <div class="p-4 bg-surface-alt rounded-lg space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-text">{{ __('app.tax') }}</h4>
                                    <p class="text-sm text-muted">
                                        {{ __('admin.tenant_default') }}: {{ $outlet->tenant->tax_enabled ? $outlet->tenant->tax_percentage . '%' : __('admin.disabled') }}
                                        ({{ $outlet->tenant->tax_mode === 'inclusive' ? __('admin.tax_inclusive') : __('admin.tax_exclusive') }})
                                    </p>
                                </div>
                                <x-checkbox
                                    name="tax_enabled"
                                    :checked="$outlet->getRawOriginal('tax_enabled') ?? $outlet->tenant->tax_enabled"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <x-input
                                    type="number"
                                    name="tax_percentage"
                                    label="{{ __('admin.tax_percentage') }}"
                                    placeholder="{{ __('admin.leave_empty_inherit') }}"
                                    :value="$outlet->getRawOriginal('tax_percentage')"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    hint="{{ __('admin.override_tenant_tax_hint') }}"
                                />

                                <div>
                                    <label class="block text-sm font-medium text-text mb-1">{{ __('admin.tax_mode') }}</label>
                                    <select name="tax_mode" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-text focus:border-primary focus:ring-primary">
                                        <option value="" {{ $outlet->getRawOriginal('tax_mode') === null ? 'selected' : '' }}>
                                            {{ __('admin.inherit_from_tenant') }} ({{ $outlet->tenant->tax_mode === 'inclusive' ? __('admin.tax_inclusive') : __('admin.tax_exclusive') }})
                                        </option>
                                        <option value="exclusive" {{ $outlet->getRawOriginal('tax_mode') === 'exclusive' ? 'selected' : '' }}>
                                            {{ __('admin.tax_exclusive') }}
                                        </option>
                                        <option value="inclusive" {{ $outlet->getRawOriginal('tax_mode') === 'inclusive' ? 'selected' : '' }}>
                                            {{ __('admin.tax_inclusive') }}
                                        </option>
                                    </select>
                                    <p class="text-xs text-muted mt-1">{{ __('admin.tax_mode_hint') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Service Charge Settings --}}
                        <div class="p-4 bg-surface-alt rounded-lg space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-text">{{ __('admin.service_charge') }}</h4>
                                    <p class="text-sm text-muted">
                                        {{ __('admin.tenant_default') }}: {{ $outlet->tenant->service_charge_enabled ? $outlet->tenant->service_charge_percentage . '%' : __('admin.disabled') }}
                                    </p>
                                </div>
                                <x-checkbox
                                    name="service_charge_enabled"
                                    :checked="$outlet->getRawOriginal('service_charge_enabled') ?? $outlet->tenant->service_charge_enabled"
                                />
                            </div>

                            <x-input
                                type="number"
                                name="service_charge_percentage"
                                label="{{ __('admin.service_charge_percentage') }}"
                                placeholder="{{ __('admin.leave_empty_inherit') }}"
                                :value="$outlet->getRawOriginal('service_charge_percentage')"
                                min="0"
                                max="100"
                                step="0.01"
                                hint="{{ __('admin.override_tenant_service_hint') }}"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.outlets.index') }}" variant="outline-secondary">
                        {{ __('app.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin.update_outlet') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
