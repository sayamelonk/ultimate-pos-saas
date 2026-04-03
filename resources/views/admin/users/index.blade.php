<x-app-layout>
    <x-slot name="title">{{ __('admin.users') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.users'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.user_management') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.manage_users') }}</p>
            </div>
            <x-button href="{{ route('admin.users.create') }}" icon="plus">
                {{ __('admin.add_user') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('admin.search_users') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-40">
                <option value="">{{ __('admin.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('app.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('app.inactive') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('app.filter') }}</x-button>
            @if(request()->hasAny(['search', 'status', 'role']))
                <x-button href="{{ route('admin.users.index') }}" variant="ghost">{{ __('app.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($users->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('admin.user') }}</x-th>
                    <x-th>{{ __('admin.roles') }}</x-th>
                    <x-th>{{ __('admin.outlets') }}</x-th>
                    @if(auth()->user()->isSuperAdmin())
                        <x-th>{{ __('admin.tenant') }}</x-th>
                    @endif
                    <x-th align="center">{{ __('app.status') }}</x-th>
                    <x-th align="center">{{ __('admin.last_login') }}</x-th>
                    <x-th align="right">{{ __('app.actions') }}</x-th>
                </x-slot>

                @foreach($users as $user)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-white">{{ $user->initials }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $user->name }}</p>
                                    <p class="text-xs text-muted">{{ $user->email }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles->take(2) as $role)
                                    <x-badge type="primary" size="sm">{{ $role->name }}</x-badge>
                                @endforeach
                                @if($user->roles->count() > 2)
                                    <x-badge type="secondary" size="sm">+{{ $user->roles->count() - 2 }}</x-badge>
                                @endif
                            </div>
                        </x-td>
                        <x-td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->outlets->take(2) as $outlet)
                                    <x-badge type="accent" size="sm">{{ $outlet->code }}</x-badge>
                                @endforeach
                                @if($user->outlets->count() > 2)
                                    <x-badge type="secondary" size="sm">+{{ $user->outlets->count() - 2 }}</x-badge>
                                @endif
                                @if($user->outlets->isEmpty())
                                    <span class="text-muted text-sm">-</span>
                                @endif
                            </div>
                        </x-td>
                        @if(auth()->user()->isSuperAdmin())
                            <x-td>
                                <span class="text-sm text-muted">{{ $user->tenant?->name ?? __('admin.system') }}</span>
                            </x-td>
                        @endif
                        <x-td align="center">
                            @if($user->is_active)
                                <x-badge type="success" dot>{{ __('app.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('app.inactive') }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($user->last_login_at)
                                <span class="text-xs text-muted">{{ $user->last_login_at->diffForHumans() }}</span>
                            @else
                                <span class="text-xs text-muted">{{ __('admin.never') }}</span>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.users.show', $user) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('app.view') }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('app.edit') }}">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                </a>
                                @if($user->id !== auth()->id())
                                    <form x-ref="deleteUser{{ $loop->index }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors"
                                                title="{{ __('app.delete') }}"
                                                x-on:click="$dispatch('confirm', {
                                                    title: '{{ __('admin.delete_user') }}',
                                                    message: '{{ __('admin.confirm_delete_user', ['name' => $user->name]) }}',
                                                    confirmText: '{{ __('app.yes_delete') }}',
                                                    cancelText: '{{ __('app.cancel') }}',
                                                    variant: 'danger',
                                                    onConfirm: () => $refs.deleteUser{{ $loop->index }}.submit()
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
                <x-pagination :paginator="$users" />
            </div>
        @else
            <x-empty-state
                title="{{ __('admin.no_users_found') }}"
                description="{{ __('admin.no_users_desc') }}"
                icon="users"
            >
                <x-button href="{{ route('admin.users.create') }}" icon="plus">
                    {{ __('admin.add_user') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
