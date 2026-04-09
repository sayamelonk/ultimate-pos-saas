<x-app-layout>
    <x-slot name="title">{{ __('admin.outlets') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.outlets'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.outlet_management') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.manage_outlets') }}</p>
            </div>
            <x-button href="{{ route('admin.outlets.create') }}" icon="plus">
                {{ __('admin.add_outlet') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.outlets.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('admin.search_outlets') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-40">
                <option value="">{{ __('admin.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('app.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('app.inactive') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('app.filter') }}</x-button>
            @if(request()->hasAny(['search', 'status']))
                <x-button href="{{ route('admin.outlets.index') }}" variant="ghost">{{ __('app.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($outlets->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('admin.outlet') }}</x-th>
                    <x-th>{{ __('admin.code') }}</x-th>
                    <x-th>{{ __('admin.contact') }}</x-th>
                    @if(auth()->user()->isSuperAdmin())
                        <x-th>{{ __('admin.tenant') }}</x-th>
                    @endif
                    <x-th align="center">{{ __('app.status') }}</x-th>
                    <x-th align="right">{{ __('app.actions') }}</x-th>
                </x-slot>

                @foreach($outlets as $outlet)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="building" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $outlet->name }}</p>
                                    @if($outlet->address)
                                        <p class="text-xs text-muted truncate max-w-[200px]">{{ $outlet->address }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $outlet->code }}</code>
                        </x-td>
                        <x-td>
                            <div class="text-sm">
                                @if($outlet->phone)
                                    <p class="text-text">{{ $outlet->phone }}</p>
                                @endif
                                @if($outlet->email)
                                    <p class="text-muted text-xs">{{ $outlet->email }}</p>
                                @endif
                                @if(!$outlet->phone && !$outlet->email)
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </x-td>
                        @if(auth()->user()->isSuperAdmin())
                            <x-td>
                                <span class="text-sm text-muted">{{ $outlet->tenant?->name ?? '-' }}</span>
                            </x-td>
                        @endif
                        <x-td align="center">
                            @if($outlet->is_active)
                                <x-badge type="success" dot>{{ __('app.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('app.inactive') }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.outlets.show', $outlet) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('app.view') }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.outlets.edit', $outlet) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('app.edit') }}">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                </a>
                                <div x-data class="inline">
                                    <form x-ref="deleteForm" action="{{ route('admin.outlets.destroy', $outlet) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors"
                                                title="{{ __('app.delete') }}"
                                                onclick="console.log('onclick works'); window.dispatchEvent(new CustomEvent('confirm', { detail: { title: '{{ __('admin.delete_outlet') }}', message: '{{ __('admin.confirm_delete_outlet_message', ['name' => $outlet->name]) }}', confirmText: '{{ __('app.yes_delete') }}', cancelText: '{{ __('app.cancel') }}', variant: 'danger', onConfirm: () => this.closest('form').submit() } }))">
                                            <x-icon name="trash" class="w-4 h-4 pointer-events-none" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$outlets" />
            </div>
        @else
            <x-empty-state
                title="{{ __('admin.no_outlets_found') }}"
                description="{{ __('admin.no_outlets_desc') }}"
                icon="building"
            >
                <x-button href="{{ route('admin.outlets.create') }}" icon="plus">
                    {{ __('admin.add_outlet') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
