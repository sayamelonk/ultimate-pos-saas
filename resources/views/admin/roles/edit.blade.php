<x-app-layout>
    <x-slot name="title">Edit Role - Ultimate POS</x-slot>

    @section('page-title', 'Edit Role')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.roles.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Role</h2>
                <p class="text-muted mt-1">{{ $role->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <x-input
                    name="name"
                    label="Role Name"
                    placeholder="e.g., Head Cashier"
                    :value="$role->name"
                    required
                />

                <x-textarea
                    name="description"
                    label="Description"
                    placeholder="Brief description of this role"
                    :value="$role->description"
                    rows="3"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.roles.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Update Role
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
