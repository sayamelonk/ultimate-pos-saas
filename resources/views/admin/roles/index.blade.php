<x-app-layout>
    <x-slot name="title">{{ __('admin.roles') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.roles'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.role_management') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.manage_roles') }}</p>
            </div>
            <x-button href="{{ route('admin.roles.create') }}" icon="plus">
                {{ __('admin.add_role') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.roles.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('admin.search_roles') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-button type="submit" variant="secondary">{{ __('app.filter') }}</x-button>
            @if(request()->has('search'))
                <x-button href="{{ route('admin.roles.index') }}" variant="ghost">{{ __('app.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($roles->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('admin.role') }}</x-th>
                    <x-th>{{ __('app.description') }}</x-th>
                    <x-th align="center">{{ __('admin.users_count') }}</x-th>
                    <x-th align="center">{{ __('admin.permissions_count') }}</x-th>
                    <x-th align="center">{{ __('app.type') }}</x-th>
                    <x-th align="right">{{ __('app.actions') }}</x-th>
                </x-slot>

                @foreach($roles as $role)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="shield" class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $role->name }}</p>
                                    <p class="text-xs text-muted font-mono">{{ $role->slug }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <p class="text-sm text-muted max-w-xs truncate">
                                {{ $role->description ?? '-' }}
                            </p>
                        </x-td>
                        <x-td align="center">
                            <x-badge type="info">{{ $role->users_count }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            <x-badge type="secondary">{{ $role->permissions_count }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            @if($role->is_system)
                                <x-badge type="warning">{{ __('admin.system_role') }}</x-badge>
                            @else
                                <x-badge type="accent">{{ __('admin.custom_role') }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.roles.show', $role) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('app.view') }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.roles.permissions', $role) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('admin.manage_permissions') }}">
                                    <x-icon name="shield" class="w-4 h-4" />
                                </a>
                                @if(!$role->is_system)
                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                       title="{{ __('app.edit') }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                    </a>
                                    <form x-ref="deleteRole{{ $loop->index }}" action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors"
                                                title="{{ __('app.delete') }}"
                                                x-on:click="$dispatch('confirm', {
                                                    title: '{{ __('admin.delete_role') }}',
                                                    message: '{{ __('admin.confirm_delete_role', ['name' => $role->name]) }}',
                                                    confirmText: '{{ __('app.yes_delete') }}',
                                                    cancelText: '{{ __('app.cancel') }}',
                                                    variant: 'danger',
                                                    onConfirm: () => $refs.deleteRole{{ $loop->index }}.submit()
                                                })">
                                            <x-icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$roles" />
            </div>
        @else
            <x-empty-state
                title="{{ __('admin.no_roles_found') }}"
                description="{{ __('admin.no_roles_desc') }}"
                icon="shield"
            >
                <x-button href="{{ route('admin.roles.create') }}" icon="plus">
                    {{ __('admin.add_role') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
