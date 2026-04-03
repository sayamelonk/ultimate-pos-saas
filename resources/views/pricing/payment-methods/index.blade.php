<x-app-layout>
    <x-slot name="title">{{ __('pricing.payment_methods') }} - Ultimate POS</x-slot>

    @section('page-title', __('pricing.payment_methods'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('pricing.payment_methods') }}</h2>
                <p class="text-muted mt-1">{{ __('pricing.manage_accepted_payment_methods') }}</p>
            </div>
            <x-button href="{{ route('pricing.payment-methods.create') }}" icon="plus">
                {{ __('pricing.add_payment_method') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('pricing.payment-methods.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="{{ __('pricing.search_payment_methods') }}"
                        :value="request('search')"
                    />
                </div>
                <x-select name="type" class="w-40">
                    <option value="">{{ __('pricing.all_types') }}</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">{{ __('pricing.all_status') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('pricing.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('pricing.inactive') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">{{ __('pricing.filter') }}</x-button>
            </form>
        </div>

        @if($paymentMethods->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('pricing.payment_method') }}</x-th>
                    <x-th>{{ __('pricing.code') }}</x-th>
                    <x-th>{{ __('pricing.type') }}</x-th>
                    <x-th align="right">{{ __('pricing.charge_percentage') }}</x-th>
                    <x-th align="right">{{ __('pricing.fixed_fee') }}</x-th>
                    <x-th align="center">{{ __('pricing.status') }}</x-th>
                    <x-th align="right">{{ __('pricing.actions') }}</x-th>
                </x-slot>

                @foreach($paymentMethods as $method)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="{{ $method->icon ?? 'credit-card' }}" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $method->name }}</p>
                                    @if($method->provider)
                                        <p class="text-xs text-muted">{{ $method->provider }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $method->code }}</code>
                        </x-td>
                        <x-td>
                            <x-badge type="secondary">{{ $types[$method->type] ?? $method->type }}</x-badge>
                        </x-td>
                        <x-td align="right">{{ number_format($method->charge_percentage, 2) }}%</x-td>
                        <x-td align="right">Rp {{ number_format($method->charge_fixed, 0, ',', '.') }}</x-td>
                        <x-td align="center">
                            @if($method->is_active)
                                <x-badge type="success" dot>{{ __('pricing.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('pricing.inactive') }}</x-badge>
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

                                    <x-dropdown-item href="{{ route('pricing.payment-methods.edit', $method) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        {{ __('pricing.edit') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('pricing.delete_payment_method') }}',
                                            message: '{{ __('pricing.delete_payment_method_confirmation', ['name' => $method->name]) }}',
                                            confirmText: '{{ __('pricing.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('pricing.delete') }}
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('pricing.payment-methods.destroy', $method) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$paymentMethods" />
            </div>
        @else
            <x-empty-state
                title="{{ __('pricing.no_payment_methods_found') }}"
                description="{{ __('pricing.add_payment_methods_to_accept_payments') }}"
                icon="credit-card"
            >
                <x-button href="{{ route('pricing.payment-methods.create') }}" icon="plus">
                    {{ __('pricing.add_payment_method') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
