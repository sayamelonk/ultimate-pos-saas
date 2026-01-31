<x-app-layout>
    <x-slot name="title">Edit Tenant - Ultimate POS</x-slot>

    @section('page-title', 'Edit Tenant')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.tenants.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Tenant</h2>
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
                    label="Tenant Name"
                    placeholder="Enter tenant/business name"
                    :value="$tenant->name"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="email"
                        name="email"
                        label="Email"
                        placeholder="contact@business.com"
                        :value="$tenant->email"
                    />

                    <x-input
                        name="phone"
                        label="Phone"
                        placeholder="08123456789"
                        :value="$tenant->phone"
                    />
                </div>

                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive tenants cannot access the system"
                    :checked="$tenant->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.tenants.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Update Tenant
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
