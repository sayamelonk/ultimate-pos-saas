<x-app-layout>
    <x-slot name="title">{{ $outlet->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Outlet Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.outlets.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $outlet->name }}</h2>
                    <p class="text-muted mt-1">{{ $outlet->code }}</p>
                </div>
            </div>
            <x-button href="{{ route('admin.outlets.edit', $outlet) }}" variant="outline-secondary" icon="pencil">
                Edit
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <x-card title="Outlet Information">
            <dl class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm text-muted">Name</dt>
                    <dd class="mt-1 font-medium text-text">{{ $outlet->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Code</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $outlet->code }}</code>
                    </dd>
                </div>
                @if(auth()->user()->isSuperAdmin())
                    <div>
                        <dt class="text-sm text-muted">Tenant</dt>
                        <dd class="mt-1 text-text">{{ $outlet->tenant?->name ?? '-' }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-muted">Status</dt>
                    <dd class="mt-1">
                        @if($outlet->is_active)
                            <x-badge type="success" dot>Active</x-badge>
                        @else
                            <x-badge type="danger" dot>Inactive</x-badge>
                        @endif
                    </dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm text-muted">Address</dt>
                    <dd class="mt-1 text-text">{{ $outlet->address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Phone</dt>
                    <dd class="mt-1 text-text">{{ $outlet->phone ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Email</dt>
                    <dd class="mt-1 text-text">{{ $outlet->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Created</dt>
                    <dd class="mt-1 text-text">{{ $outlet->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Updated</dt>
                    <dd class="mt-1 text-text">{{ $outlet->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-layout>
