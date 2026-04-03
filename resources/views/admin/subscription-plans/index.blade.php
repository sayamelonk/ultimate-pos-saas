<x-app-layout>
    <x-slot name="title">{{ __('admin.subscription_plans') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.subscription_plans'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.subscription_plans') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.manage_subscription_plans') }}</p>
            </div>
            <x-button href="{{ route('admin.subscription-plans.create') }}" icon="plus">
                {{ __('admin.add_plan') }}
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.subscription-plans.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('admin.search_plans') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-40" placeholder="{{ __('admin.all_status') }}">
                <option value="">{{ __('admin.all_status') }}</option>
                <option value="1" @selected(request('status') === '1')>{{ __('admin.active') }}</option>
                <option value="0" @selected(request('status') === '0')>{{ __('admin.inactive') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('admin.filter') }}</x-button>
            @if(request()->hasAny(['search', 'status']))
                <x-button href="{{ route('admin.subscription-plans.index') }}" variant="ghost">{{ __('admin.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($plans->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('admin.name') }}</x-th>
                    <x-th>{{ __('admin.slug') }}</x-th>
                    <x-th align="right">{{ __('admin.price_monthly') }}</x-th>
                    <x-th align="right">{{ __('admin.price_yearly') }}</x-th>
                    <x-th align="center">{{ __('admin.max_outlets') }}</x-th>
                    <x-th align="center">{{ __('admin.max_users') }}</x-th>
                    <x-th align="center">{{ __('admin.max_products') }}</x-th>
                    <x-th align="center">{{ __('admin.status') }}</x-th>
                    <x-th align="right">{{ __('admin.action') }}</x-th>
                </x-slot>

                @foreach($plans as $plan)
                    <tr>
                        <x-td>
                            <div>
                                <p class="font-medium text-text">{{ $plan->name }}</p>
                                @if($plan->description)
                                    <p class="text-xs text-muted line-clamp-1">{{ $plan->description }}</p>
                                @endif
                            </div>
                        </x-td>
                        <x-td>
                            <span class="font-mono text-sm text-muted">{{ $plan->slug }}</span>
                        </x-td>
                        <x-td align="right">
                            <span class="font-semibold text-text">Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}</span>
                        </x-td>
                        <x-td align="right">
                            <span class="font-semibold text-text">Rp {{ number_format($plan->price_yearly, 0, ',', '.') }}</span>
                        </x-td>
                        <x-td align="center">
                            @if($plan->max_outlets === -1)
                                <x-badge type="info">{{ __('admin.unlimited') }}</x-badge>
                            @else
                                <x-badge type="secondary">{{ $plan->max_outlets }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($plan->max_users === -1)
                                <x-badge type="info">{{ __('admin.unlimited') }}</x-badge>
                            @else
                                <x-badge type="secondary">{{ $plan->max_users }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($plan->max_products === -1)
                                <x-badge type="info">{{ __('admin.unlimited') }}</x-badge>
                            @else
                                <x-badge type="secondary">{{ $plan->max_products }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($plan->is_active)
                                <x-badge type="success" dot>{{ __('admin.active') }}</x-badge>
                            @else
                                <x-badge type="danger" dot>{{ __('admin.inactive') }}</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.subscription-plans.show', $plan) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('admin.view_details') }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('admin.subscription-plans.edit', $plan) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('admin.edit') }}">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                </a>
                                <form x-ref="deletePlan{{ $loop->index }}" action="{{ route('admin.subscription-plans.destroy', $plan) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="p-2 text-danger-500 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors"
                                            title="{{ __('admin.delete') }}"
                                            x-on:click="$dispatch('confirm', {
                                                title: '{{ __('admin.delete_plan') }}',
                                                message: '{{ __('admin.confirm_delete_plan', ['name' => $plan->name]) }}',
                                                confirmText: '{{ __('admin.yes_delete') }}',
                                                cancelText: '{{ __('admin.cancel') }}',
                                                variant: 'danger',
                                                onConfirm: () => $refs.deletePlan{{ $loop->index }}.submit()
                                            })">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <x-empty-state
                title="{{ __('admin.no_plans_found') }}"
                description="{{ __('admin.no_plans_desc') }}"
                icon="credit-card"
            >
                <x-button href="{{ route('admin.subscription-plans.create') }}" icon="plus">
                    {{ __('admin.add_plan') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
