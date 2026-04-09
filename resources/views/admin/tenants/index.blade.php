<x-app-layout>
    <x-slot name="title">Tenants - Ultimate POS</x-slot>

    @section('page-title', __('admin.tenants'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.tenant_management') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.manage_all_tenants') }}</p>
            </div>
            <x-button href="{{ route('admin.tenants.create') }}" icon="plus">
                {{ __('admin.add_tenant') }}
            </x-button>
        </div>
    </x-slot>

    <!-- Current Tenant Context Banner -->
    @if(session('current_tenant_name'))
        <div class="mb-4 p-4 bg-primary-50 border border-primary-200 rounded-lg flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-icon name="building" class="w-5 h-5 text-primary" />
                <div>
                    <p class="text-sm text-primary-700">{{ __('admin.currently_managing') }}</p>
                    <p class="font-semibold text-primary-900">{{ session('current_tenant_name') }}</p>
                </div>
            </div>
            <form action="{{ route('admin.tenants.clear') }}" method="POST">
                @csrf
                <x-button type="submit" variant="outline-secondary" size="sm">
                    {{ __('admin.clear_selection') }}
                </x-button>
            </form>
        </div>
    @endif

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.tenants.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('admin.search_tenants') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-40" placeholder="{{ __('admin.all_status') }}">
                <option value="">{{ __('admin.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('admin.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('admin.inactive') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('admin.filter') }}</x-button>
            @if(request()->hasAny(['search', 'status']))
                <x-button href="{{ route('admin.tenants.index') }}" variant="ghost">{{ __('admin.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($tenants->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('admin.tenant') }}</x-th>
                    <x-th>{{ __('admin.contact') }}</x-th>
                    <x-th align="center">{{ __('admin.outlets') }}</x-th>
                    <x-th align="center">{{ __('admin.users') }}</x-th>
                    <x-th align="center">{{ __('admin.status') }}</x-th>
                    <x-th align="center">{{ __('admin.created') }}</x-th>
                    <x-th align="right">{{ __('admin.action') }}</x-th>
                </x-slot>

                @foreach($tenants as $tenant)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <span class="text-sm font-semibold text-primary">
                                        {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $tenant->name }}</p>
                                    <p class="text-xs text-muted font-mono">{{ $tenant->code }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <div class="text-sm">
                                @if($tenant->email)
                                    <p class="text-text">{{ $tenant->email }}</p>
                                @endif
                                @if($tenant->phone)
                                    <p class="text-muted text-xs">{{ $tenant->phone }}</p>
                                @endif
                                @if(!$tenant->email && !$tenant->phone)
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </x-td>
                        <x-td align="center">
                            <x-badge type="info">{{ $tenant->outlets_count }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            <x-badge type="secondary">{{ $tenant->users_count }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            @if($tenant->is_active)
                                <x-badge type="success" dot>{{ __('admin.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('admin.inactive') }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            <span class="text-muted text-xs">{{ $tenant->created_at->format('M d, Y') }}</span>
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <form action="{{ route('admin.tenants.switch', $tenant) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="p-2 text-primary hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors {{ session('current_tenant_id') === $tenant->id ? 'bg-primary-100' : '' }}"
                                            title="{{ __('admin.manage_tenant') }}">
                                        <x-icon name="cog" class="w-4 h-4" />
                                    </button>
                                </form>
                                <a href="{{ route('admin.tenants.show', $tenant) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('admin.view_details') }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.tenants.edit', $tenant) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('admin.edit') }}">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                </a>
                                <form x-ref="deleteTenant{{ $loop->index }}" action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors"
                                            title="{{ __('admin.delete') }}"
                                            x-on:click="$dispatch('confirm', {
                                                title: '{{ __('admin.delete_tenant') }}',
                                                message: '{{ __('admin.confirm_delete_tenant', ['name' => $tenant->name]) }}',
                                                confirmText: '{{ __('admin.yes_delete') }}',
                                                cancelText: '{{ __('admin.cancel') }}',
                                                variant: 'danger',
                                                onConfirm: () => $refs.deleteTenant{{ $loop->index }}.submit()
                                            })">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$tenants" />
            </div>
        @else
            <x-empty-state
                title="{{ __('admin.no_tenants_found') }}"
                description="{{ __('admin.no_tenants_desc') }}"
                icon="building"
            >
                <x-button href="{{ route('admin.tenants.create') }}" icon="plus">
                    {{ __('admin.add_tenant') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
