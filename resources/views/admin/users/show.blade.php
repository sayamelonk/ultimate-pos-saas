<x-app-layout>
    <x-slot name="title">{{ $user->name }} - Ultimate POS</x-slot>

    @section('page-title', 'User Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.users.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-primary rounded-full flex items-center justify-center">
                        <span class="text-xl font-bold text-white">{{ $user->initials }}</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-text">{{ $user->name }}</h2>
                        <p class="text-muted mt-1">{{ $user->email }}</p>
                    </div>
                </div>
            </div>
            <x-button href="{{ route('admin.users.edit', $user) }}" variant="outline-secondary" icon="pencil">
                Edit
            </x-button>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <x-card title="User Information">
                <dl class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm text-muted">Full Name</dt>
                        <dd class="mt-1 font-medium text-text">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Email</dt>
                        <dd class="mt-1 text-text">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Phone</dt>
                        <dd class="mt-1 text-text">{{ $user->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($user->is_active)
                                <x-badge type="success" dot>Active</x-badge>
                            @else
                                <x-badge type="danger" dot>Inactive</x-badge>
                            @endif
                        </dd>
                    </div>
                    @if(auth()->user()->isSuperAdmin())
                        <div>
                            <dt class="text-sm text-muted">Tenant</dt>
                            <dd class="mt-1 text-text">{{ $user->tenant?->name ?? 'System' }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm text-muted">Last Login</dt>
                        <dd class="mt-1 text-text">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('M d, Y H:i') }}
                                <span class="text-muted text-sm">({{ $user->last_login_at->diffForHumans() }})</span>
                            @else
                                Never
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Created</dt>
                        <dd class="mt-1 text-text">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Updated</dt>
                        <dd class="mt-1 text-text">{{ $user->updated_at->format('M d, Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Outlets -->
            <x-card title="Assigned Outlets">
                @if($user->outlets->count() > 0)
                    <div class="space-y-3">
                        @foreach($user->outlets as $outlet)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                        <x-icon name="building" class="w-5 h-5 text-accent" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-text">{{ $outlet->name }}</p>
                                        <p class="text-xs text-muted">{{ $outlet->code }}</p>
                                    </div>
                                </div>
                                @if($outlet->pivot->is_default)
                                    <x-badge type="primary" size="sm">Default</x-badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-sm">No outlets assigned.</p>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Roles -->
            <x-card title="Assigned Roles">
                @if($user->roles->count() > 0)
                    <div class="space-y-2">
                        @foreach($user->roles as $role)
                            <div class="p-3 bg-primary-50 rounded-lg">
                                <p class="font-medium text-primary">{{ $role->name }}</p>
                                @if($role->description)
                                    <p class="text-xs text-primary-600 mt-1">{{ $role->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-sm">No roles assigned.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
