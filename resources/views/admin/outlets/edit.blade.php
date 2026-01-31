<x-app-layout>
    <x-slot name="title">Edit Outlet - Ultimate POS</x-slot>

    @section('page-title', 'Edit Outlet')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.outlets.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Outlet</h2>
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
                        label="Outlet Name"
                        placeholder="e.g., Main Branch"
                        :value="$outlet->name"
                        required
                    />

                    <x-input
                        name="code"
                        label="Outlet Code"
                        placeholder="e.g., MAIN"
                        :value="$outlet->code"
                        hint="Unique identifier for this outlet"
                        required
                    />
                </div>

                <x-textarea
                    name="address"
                    label="Address"
                    placeholder="Enter full address"
                    :value="$outlet->address"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="tel"
                        name="phone"
                        label="Phone Number"
                        placeholder="e.g., +62 812 3456 7890"
                        :value="$outlet->phone"
                    />

                    <x-input
                        type="email"
                        name="email"
                        label="Email"
                        placeholder="e.g., outlet@business.com"
                        :value="$outlet->email"
                    />
                </div>

                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive outlets won't be accessible"
                    :checked="$outlet->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.outlets.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Update Outlet
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
