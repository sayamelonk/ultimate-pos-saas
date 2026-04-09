<x-app-layout>
    <x-slot name="title">{{ __('admin.create_tenant') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.create_tenant'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.tenants.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('admin.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.create_tenant') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.add_new_tenant') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('admin.tenants.store') }}" method="POST" class="space-y-6">
                @csrf

                <x-input
                    name="name"
                    label="{{ __('admin.tenant_name') }}"
                    placeholder="{{ __('admin.tenant_name_placeholder') }}"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="email"
                        name="email"
                        label="{{ __('admin.email') }}"
                        placeholder="{{ __('admin.email_placeholder') }}"
                    />

                    <x-input
                        name="phone"
                        label="{{ __('admin.phone') }}"
                        placeholder="{{ __('admin.phone_placeholder') }}"
                    />
                </div>

                <x-checkbox
                    name="is_active"
                    label="{{ __('admin.active_label') }}"
                    hint="{{ __('admin.inactive_hint') }}"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.tenants.index') }}" variant="outline-secondary">
                        {{ __('admin.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin.create_tenant') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
