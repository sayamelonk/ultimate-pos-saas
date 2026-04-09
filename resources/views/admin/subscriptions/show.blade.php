<x-app-layout>
    <x-slot name="title">{{ __('admin.subscription_details') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.subscription_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.subscriptions.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('admin.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ __('admin.subscription_details') }}</h2>
                    <p class="text-muted text-sm mt-1">{{ $subscription->tenant->name }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
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
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Subscription Info -->
            <x-card title="{{ __('admin.subscription_information') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.plan') }}</dt>
                        <dd class="text-text font-medium text-lg">{{ $subscription->plan->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.billing_cycle') }}</dt>
                        <dd class="text-text">{{ ucfirst(__('admin.' . $subscription->billing_cycle)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.start_date') }}</dt>
                        <dd class="text-text">{{ $subscription->starts_at?->format('M d, Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.end_date') }}</dt>
                        <dd class="text-text">{{ $subscription->ends_at?->format('M d, Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.amount') }}</dt>
                        <dd class="text-text font-semibold text-lg">Rp {{ number_format($subscription->price ?? 0, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.status') }}</dt>
                        <dd>
                            <x-badge type="{{ $badgeType }}" dot>{{ __('admin.' . $subscription->status) }}</x-badge>
                        </dd>
                    </div>
                </div>
            </x-card>

            <!-- Tenant Info -->
            <x-card title="{{ __('admin.tenant') }}">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-primary-100 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-primary">
                            {{ strtoupper(substr($subscription->tenant->name, 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg text-text">{{ $subscription->tenant->name }}</h3>
                        <p class="text-sm text-muted font-mono">{{ $subscription->tenant->code }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-border">
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.email') }}</dt>
                        <dd class="text-text">{{ $subscription->tenant->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.phone') }}</dt>
                        <dd class="text-text">{{ $subscription->tenant->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.outlets') }}</dt>
                        <dd class="text-text">{{ $subscription->tenant->outlets_count }} {{ __('admin.outlets') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.users') }}</dt>
                        <dd class="text-text">{{ $subscription->tenant->users_count }} {{ __('admin.users') }}</dd>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.tenants.show', $subscription->tenant) }}" variant="ghost" size="sm" icon="eye">
                        {{ __('admin.view_details') }}
                    </x-button>
                </div>
            </x-card>

            <!-- Recent Invoices -->
            <x-card title="{{ __('admin.recent_invoices') }}">
                @if($subscription->invoices && $subscription->invoices->count() > 0)
                    <div class="space-y-3">
                        @foreach($subscription->invoices->take(5) as $invoice)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                        <x-icon name="document" class="w-5 h-5 text-primary" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-text">{{ $invoice->invoice_number }}</p>
                                        <p class="text-xs text-muted">{{ $invoice->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-text">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                                    @php
                                        $invoiceStatusColors = [
                                            'paid' => 'success',
                                            'unpaid' => 'danger',
                                            'pending' => 'warning'
                                        ];
                                        $invoiceBadgeType = $invoiceStatusColors[$invoice->status] ?? 'secondary';
                                    @endphp
                                    <x-badge type="{{ $invoiceBadgeType }}" size="sm">{{ __('admin.' . $invoice->status) }}</x-badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-sm text-center py-8">{{ __('admin.no_invoices') }}</p>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Current Period -->
            <x-card title="{{ __('admin.current_period') }}">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.start_date') }}</span>
                        <span class="text-sm font-medium text-text">{{ $subscription->starts_at?->format('M d, Y') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.end_date') }}</span>
                        <span class="text-sm font-medium text-text">{{ $subscription->ends_at?->format('M d, Y') ?? '-' }}</span>
                    </div>
                    @if($subscription->status === 'active' && $subscription->ends_at?->isFuture())
                        <div class="pt-4 border-t border-border">
                            <div class="flex items-center justify-between">
                                <span class="text-muted">{{ __('admin.next_billing') }}</span>
                                <span class="text-sm font-semibold text-primary">
                                    {{ $subscription->ends_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Change Status -->
            <x-card title="{{ __('admin.change_status') }}">
                <form action="{{ route('admin.subscriptions.update-status', $subscription) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-4">
                        <x-select name="status" placeholder="{{ __('admin.select_role') }}" required>
                            <option value="active" @selected($subscription->status === 'active')>{{ __('admin.active') }}</option>
                            <option value="trial" @selected($subscription->status === 'trial')>{{ __('admin.trial') }}</option>
                            <option value="expired" @selected($subscription->status === 'expired')>{{ __('admin.expired') }}</option>
                            <option value="cancelled" @selected($subscription->status === 'cancelled')>{{ __('admin.cancelled') }}</option>
                            <option value="frozen" @selected($subscription->status === 'frozen')>{{ __('admin.frozen') }}</option>
                        </x-select>
                        <x-button type="submit" class="w-full">
                            {{ __('admin.update_status') }}
                        </x-button>
                    </div>
                </form>
            </x-card>

            <!-- Timestamps -->
            <x-card title="{{ __('admin.activity') }}">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.created') }}</span>
                        <span class="text-text">{{ $subscription->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.last_updated') }}</span>
                        <span class="text-text">{{ $subscription->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
