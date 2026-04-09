<x-app-layout>
    <x-slot name="title">{{ __('admin.subscriptions') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.subscriptions'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.subscription_management') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.manage_all_subscriptions') }}</p>
            </div>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('admin.search_subscriptions') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-40" placeholder="{{ __('admin.all_status') }}">
                <option value="">{{ __('admin.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('admin.active') }}</option>
                <option value="trial" @selected(request('status') === 'trial')>{{ __('admin.trial') }}</option>
                <option value="expired" @selected(request('status') === 'expired')>{{ __('admin.expired') }}</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>{{ __('admin.cancelled') }}</option>
                <option value="frozen" @selected(request('status') === 'frozen')>{{ __('admin.frozen') }}</option>
            </x-select>
            <x-select name="plan" class="w-40" placeholder="{{ __('admin.all_plans') }}">
                <option value="">{{ __('admin.all_plans') }}</option>
                @foreach($plans as $planOption)
                    <option value="{{ $planOption->id }}" @selected(request('plan') == $planOption->id)>
                        {{ $planOption->name }}
                    </option>
                @endforeach
            </x-select>
            <x-select name="tenant" class="w-48" placeholder="{{ __('admin.select_tenant') }}">
                <option value="">{{ __('admin.all_status') }}</option>
                @foreach($tenants as $tenantOption)
                    <option value="{{ $tenantOption->id }}" @selected(request('tenant') == $tenantOption->id)>
                        {{ $tenantOption->name }}
                    </option>
                @endforeach
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('admin.filter') }}</x-button>
            @if(request()->hasAny(['search', 'status', 'plan', 'tenant']))
                <x-button href="{{ route('admin.subscriptions.index') }}" variant="ghost">{{ __('admin.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($subscriptions->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('admin.tenant') }}</x-th>
                    <x-th>{{ __('admin.plan') }}</x-th>
                    <x-th align="center">{{ __('admin.status') }}</x-th>
                    <x-th align="center">{{ __('admin.billing_cycle') }}</x-th>
                    <x-th align="center">{{ __('admin.start_date') }}</x-th>
                    <x-th align="center">{{ __('admin.end_date') }}</x-th>
                    <x-th align="right">{{ __('admin.amount') }}</x-th>
                    <x-th align="right">{{ __('admin.action') }}</x-th>
                </x-slot>

                @foreach($subscriptions as $subscription)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <span class="text-sm font-semibold text-primary">
                                        {{ strtoupper(substr($subscription->tenant->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $subscription->tenant->name }}</p>
                                    <p class="text-xs text-muted font-mono">{{ $subscription->tenant->code }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <span class="font-medium text-text">{{ $subscription->plan->name }}</span>
                        </x-td>
                        <x-td align="center">
                            @php
                                $statusColors = [
                                    'active' => 'success',
                                    'trial' => 'info',
                                    'expired' => 'danger',
                                    'cancelled' => 'secondary',
                                    'frozen' => 'warning'
                                ];
                                $badgeType = $statusColors[$subscription->status] ?? 'secondary';
                            @endphp
                            <x-badge type="{{ $badgeType }}" dot>{{ __('admin.' . $subscription->status) }}</x-badge>
                        </x-td>
                        <x-td align="center">
                            <span class="text-sm text-text">{{ ucfirst(__('admin.' . $subscription->billing_cycle)) }}</span>
                        </x-td>
                        <x-td align="center">
                            <span class="text-muted text-xs">{{ $subscription->starts_at?->format('M d, Y') ?? '-' }}</span>
                        </x-td>
                        <x-td align="center">
                            <span class="text-muted text-xs">{{ $subscription->ends_at?->format('M d, Y') ?? '-' }}</span>
                        </x-td>
                        <x-td align="right">
                            <span class="font-medium text-text">Rp {{ number_format($subscription->price ?? 0, 0, ',', '.') }}</span>
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.subscriptions.show', $subscription) }}"
                                   class="p-2 text-muted hover:text-text hover:bg-secondary-100 rounded-lg transition-colors"
                                   title="{{ __('admin.view_details') }}">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </a>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$subscriptions" />
            </div>
        @else
            <x-empty-state
                title="{{ __('admin.no_subscriptions_found') }}"
                description="{{ __('admin.no_subscriptions_desc') }}"
                icon="credit-card"
            />
        @endif
    </x-card>
</x-app-layout>
