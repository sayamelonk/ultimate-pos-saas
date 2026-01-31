<x-app-layout>
    <x-slot name="title">Manage Permissions - {{ $role->name }}</x-slot>

    @section('page-title', 'Manage Permissions')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.roles.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Manage Permissions</h2>
                <p class="text-muted mt-1">{{ $role->name }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('admin.roles.permissions.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @foreach($permissions as $module => $modulePermissions)
                <x-card>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-text capitalize">{{ str_replace('_', ' ', $module) }}</h3>
                            <p class="text-sm text-muted">{{ $modulePermissions->count() }} permissions</p>
                        </div>
                        <div x-data="{ allChecked: false }">
                            <button type="button"
                                    @click="allChecked = !allChecked; document.querySelectorAll('[data-module=\'{{ $module }}\']').forEach(el => el.checked = allChecked)"
                                    class="text-sm text-accent hover:text-accent-600 font-medium">
                                Toggle All
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($modulePermissions as $permission)
                            <label class="flex items-start gap-3 p-3 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permission->id }}"
                                       data-module="{{ $module }}"
                                       class="mt-0.5 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                                       @checked(in_array($permission->id, $rolePermissions))>
                                <div>
                                    <span class="text-sm font-medium text-text">{{ $permission->name }}</span>
                                    @if($permission->description)
                                        <p class="text-xs text-muted mt-0.5">{{ $permission->description }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="sticky bottom-0 bg-background border-t border-border mt-6 -mx-6 px-6 py-4">
            <div class="flex items-center justify-between max-w-7xl mx-auto">
                <p class="text-sm text-muted">
                    <span x-data="{ count: {{ count($rolePermissions) }} }"
                          x-text="document.querySelectorAll('input[name=\'permissions[]\']:checked').length + ' permissions selected'">
                    </span>
                </p>
                <div class="flex items-center gap-3">
                    <x-button href="{{ route('admin.roles.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Save Permissions
                    </x-button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
