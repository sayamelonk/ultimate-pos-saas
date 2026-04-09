<x-app-layout>
    <x-slot name="title">{{ __('admin.edit_tenant') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.edit_tenant'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.tenants.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('admin.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.edit_tenant') }}</h2>
                <p class="text-muted mt-1">{{ $tenant->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('admin.tenants.update', $tenant) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <x-input
                    name="name"
                    label="{{ __('admin.tenant_name') }}"
                    placeholder="{{ __('admin.tenant_name_placeholder') }}"
                    :value="$tenant->name"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="email"
                        name="email"
                        label="{{ __('admin.email') }}"
                        placeholder="{{ __('admin.email_placeholder') }}"
                        :value="$tenant->email"
                    />

                    <x-input
                        name="phone"
                        label="{{ __('admin.phone') }}"
                        placeholder="{{ __('admin.phone_placeholder') }}"
                        :value="$tenant->phone"
                    />
                </div>

                <x-checkbox
                    name="is_active"
                    label="{{ __('admin.active_label') }}"
                    hint="{{ __('admin.inactive_hint') }}"
                    :checked="$tenant->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.tenants.index') }}" variant="outline-secondary">
                        {{ __('admin.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin.update_tenant') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
