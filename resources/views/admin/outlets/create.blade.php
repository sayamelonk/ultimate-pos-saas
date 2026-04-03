<x-app-layout>
    <x-slot name="title">{{ __('admin.create_outlet') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.create_outlet'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.outlets.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.create_outlet') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.add_new_outlet') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('admin.outlets.store') }}" method="POST" class="space-y-6">
                @csrf

                @if(auth()->user()->isSuperAdmin())
                    <x-select name="tenant_id" label="{{ __('admin.tenant') }}" required>
                        <option value="">{{ __('admin.select_tenant') }}</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                {{ $tenant->name }} ({{ $tenant->code }})
                            </option>
                        @endforeach
                    </x-select>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="name"
                        label="{{ __('admin.outlet_name_label') }}"
                        placeholder="{{ __('admin.outlet_name_placeholder') }}"
                        required
                    />

                    <x-input
                        name="code"
                        label="{{ __('admin.outlet_code_label') }}"
                        placeholder="{{ __('admin.outlet_code_placeholder') }}"
                        hint="{{ __('admin.outlet_code_hint') }}"
                        required
                    />
                </div>

                <x-textarea
                    name="address"
                    label="{{ __('admin.address') }}"
                    placeholder="{{ __('admin.enter_address') }}"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="tel"
                        name="phone"
                        label="{{ __('admin.phone') }}"
                        placeholder="{{ __('admin.phone_placeholder') }}"
                    />

                    <x-input
                        type="email"
                        name="email"
                        label="{{ __('admin.email') }}"
                        placeholder="{{ __('admin.email_placeholder') }}"
                    />
                </div>

                <x-checkbox
                    name="is_active"
                    label="{{ __('app.active') }}"
                    hint="{{ __('admin.inactive_not_accessible') }}"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.outlets.index') }}" variant="outline-secondary">
                        {{ __('app.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin.create_outlet') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
