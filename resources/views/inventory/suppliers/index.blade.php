<x-app-layout>
    <x-slot name="title">{{ __('inventory.suppliers') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.suppliers'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.supplier_management') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.manage_suppliers') }}</p>
            </div>
            <x-button href="{{ route('inventory.suppliers.create') }}" icon="plus">
                {{ __('inventory.add_supplier') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.suppliers.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        :placeholder="__('inventory.search_suppliers')"
                        :value="request('search')"
                    />
                </div>
                <x-select name="status" :value="request('status')" class="w-40">
                    <option value="">{{ __('inventory.all_status') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('inventory.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('inventory.inactive') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('inventory.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'status']))
                    <x-button href="{{ route('inventory.suppliers.index') }}" variant="ghost">
                        {{ __('inventory.cancel') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($suppliers->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.supplier') }}</x-th>
                    <x-th>{{ __('inventory.supplier_code') }}</x-th>
                    <x-th>{{ __('inventory.contact_person') }}</x-th>
                    <x-th>{{ __('inventory.payment_terms') }}</x-th>
                    <x-th align="center">{{ __('inventory.status') }}</x-th>
                    <x-th align="right">{{ __('inventory.actions') }}</x-th>
                </x-slot>

                @foreach($suppliers as $supplier)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="truck" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $supplier->name }}</p>
                                    @if($supplier->city)
                                        <p class="text-xs text-muted">{{ $supplier->city }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $supplier->code }}</code>
                        </x-td>
                        <x-td>
                            <div class="text-sm">
                                @if($supplier->contact_person)
                                    <p class="text-text">{{ $supplier->contact_person }}</p>
                                @endif
                                @if($supplier->phone)
                                    <p class="text-muted text-xs">{{ $supplier->phone }}</p>
                                @endif
                                @if(!$supplier->contact_person && !$supplier->phone)
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </x-td>
                        <x-td>
                            @if($supplier->payment_terms)
                                {{ __('inventory.lead_time_days', ['days' => $supplier->payment_terms]) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($supplier->is_active)
                                <x-badge type="success" dot>{{ __('inventory.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('inventory.inactive') }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div x-data>
                                <x-dropdown align="right">
                                    <x-slot name="trigger">
                                        <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                            <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                        </button>
                                    </x-slot>

                                    <x-dropdown-item href="{{ route('inventory.suppliers.show', $supplier) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('inventory.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('inventory.suppliers.edit', $supplier) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('inventory.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('inventory.delete_supplier') }}',
                                            message: '{{ __('inventory.confirm_delete_supplier', ['name' => $supplier->name]) }}',
                                            confirmText: '{{ __('inventory.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('inventory.delete') }}
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.suppliers.destroy', $supplier) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$suppliers" />
            </div>
        @else
            <x-empty-state
                :title="__('inventory.no_suppliers_found')"
                :description="__('inventory.no_suppliers_description')"
                icon="truck"
            >
                <x-button href="{{ route('inventory.suppliers.create') }}" icon="plus">
                    {{ __('inventory.add_supplier') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
