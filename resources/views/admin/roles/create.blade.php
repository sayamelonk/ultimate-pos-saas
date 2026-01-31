<x-app-layout>
    <x-slot name="title">Create Role - Ultimate POS</x-slot>

    @section('page-title', 'Create Role')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.roles.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Create Role</h2>
                <p class="text-muted mt-1">Add a new custom role</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('admin.roles.store') }}" method="POST" class="space-y-6">
                @csrf

                <x-input
                    name="name"
                    label="Role Name"
                    placeholder="e.g., Head Cashier"
                    required
                />

                <x-textarea
                    name="description"
                    label="Description"
                    placeholder="Brief description of this role"
                    rows="3"
                />

                <x-alert type="info">
                    After creating the role, you'll be redirected to assign permissions.
                </x-alert>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.roles.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Create & Assign Permissions
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
