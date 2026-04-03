<x-app-layout>
    <x-slot name="title">{{ $invoice->invoice_number }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.invoice_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.invoices.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('admin.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ __('admin.invoice_details') }}</h2>
                    <p class="text-muted text-sm font-mono">{{ $invoice->invoice_number }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($invoice->status === 'paid')
                    <x-badge type="success" dot>{{ __('admin.paid') }}</x-badge>
                @elseif($invoice->status === 'pending')
                    <x-badge type="warning" dot>{{ __('admin.pending') }}</x-badge>
                @elseif($invoice->status === 'failed')
                    <x-badge type="danger" dot>{{ __('admin.failed') }}</x-badge>
                @elseif($invoice->status === 'cancelled')
                    <x-badge type="secondary" dot>{{ __('admin.cancelled') }}</x-badge>
                @elseif($invoice->status === 'refunded')
                    <x-badge type="info" dot>{{ __('admin.refunded') }}</x-badge>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Info -->
            <x-card title="{{ __('admin.invoice_information') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.invoice_number') }}</dt>
                        <dd class="font-mono text-sm text-text bg-secondary-100 px-2 py-1 rounded inline-block">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.amount') }}</dt>
                        <dd class="text-xl font-bold text-text">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.status') }}</dt>
                        <dd>
                            @if($invoice->status === 'paid')
                                <x-badge type="success" dot>{{ __('admin.paid') }}</x-badge>
                            @elseif($invoice->status === 'pending')
                                <x-badge type="warning" dot>{{ __('admin.pending') }}</x-badge>
                            @elseif($invoice->status === 'failed')
                                <x-badge type="danger" dot>{{ __('admin.failed') }}</x-badge>
                            @elseif($invoice->status === 'cancelled')
                                <x-badge type="secondary" dot>{{ __('admin.cancelled') }}</x-badge>
                            @elseif($invoice->status === 'refunded')
                                <x-badge type="info" dot>{{ __('admin.refunded') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.plan') }}</dt>
                        <dd class="text-text font-medium">{{ ucfirst($invoice->plan_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.created') }}</dt>
                        <dd class="text-text">{{ $invoice->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.paid_at') }}</dt>
                        <dd class="text-text">{{ $invoice->paid_at ? $invoice->paid_at->format('M d, Y H:i') : '-' }}</dd>
                    </div>
                </div>
            </x-card>

            <!-- Tenant Info -->
            <x-card title="{{ __('admin.tenant_information') }}">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-primary-100 rounded-xl flex items-center justify-center shrink-0">
                        <span class="text-xl font-bold text-primary">
                            {{ strtoupper(substr($invoice->tenant->name, 0, 2)) }}
                        </span>
                    </div>
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.tenant_name') }}</dt>
                            <dd class="text-text font-medium">{{ $invoice->tenant->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.code') }}</dt>
                            <dd class="font-mono text-sm text-text">{{ $invoice->tenant->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.email') }}</dt>
                            <dd class="text-text">{{ $invoice->tenant->email ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.phone') }}</dt>
                            <dd class="text-text">{{ $invoice->tenant->phone ?? '-' }}</dd>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Subscription Info -->
            @if($invoice->subscription)
                <x-card title="{{ __('admin.subscription_information') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.subscription_plan') }}</dt>
                            <dd class="text-text font-medium">{{ ucfirst($invoice->subscription->plan) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.status') }}</dt>
                            <dd>
                                @if($invoice->subscription->status === 'active')
                                    <x-badge type="success">{{ __('admin.active') }}</x-badge>
                                @elseif($invoice->subscription->status === 'cancelled')
                                    <x-badge type="secondary">{{ __('admin.cancelled') }}</x-badge>
                                @else
                                    <x-badge type="danger">{{ ucfirst($invoice->subscription->status) }}</x-badge>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.started_at') }}</dt>
                            <dd class="text-text">{{ $invoice->subscription->started_at ? $invoice->subscription->started_at->format('M d, Y') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted mb-1">{{ __('admin.expires_at') }}</dt>
                            <dd class="text-text">{{ $invoice->subscription->expires_at ? $invoice->subscription->expires_at->format('M d, Y') : '-' }}</dd>
                        </div>
                    </div>
                </x-card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Change Status -->
            <x-card title="{{ __('admin.change_status') }}">
                <form action="{{ route('admin.invoices.update-status', $invoice) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <x-form-group>
                        <x-select name="status" required>
                            <option value="">{{ __('admin.select_status') }}</option>
                            <option value="paid" @selected($invoice->status === 'paid')>{{ __('admin.paid') }}</option>
                            <option value="pending" @selected($invoice->status === 'pending')>{{ __('admin.pending') }}</option>
                            <option value="failed" @selected($invoice->status === 'failed')>{{ __('admin.failed') }}</option>
                            <option value="cancelled" @selected($invoice->status === 'cancelled')>{{ __('admin.cancelled') }}</option>
                            <option value="refunded" @selected($invoice->status === 'refunded')>{{ __('admin.refunded') }}</option>
                        </x-select>
                    </x-form-group>

                    <x-button type="submit" class="w-full">
                        {{ __('admin.update_status') }}
                    </x-button>
                </form>
            </x-card>

            <!-- Plan Info -->
            @if($invoice->subscriptionPlan)
                <x-card title="{{ __('admin.plan_details') }}">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-muted">{{ __('admin.plan') }}</span>
                            <span class="font-medium text-text">{{ ucfirst($invoice->subscriptionPlan->name) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted">{{ __('admin.price') }}</span>
                            <span class="font-medium text-text">Rp {{ number_format($invoice->subscriptionPlan->price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted">{{ __('admin.max_outlets') }}</span>
                            <span class="font-medium text-text">{{ $invoice->subscriptionPlan->max_outlets }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted">{{ __('admin.max_users') }}</span>
                            <span class="font-medium text-text">{{ $invoice->subscriptionPlan->max_users }}</span>
                        </div>
                    </div>
                </x-card>
            @endif

            <!-- Timestamps -->
            <x-card title="{{ __('admin.activity') }}">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.created') }}</span>
                        <span class="text-text">{{ $invoice->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">{{ __('admin.last_updated') }}</span>
                        <span class="text-text">{{ $invoice->updated_at->diffForHumans() }}</span>
                    </div>
                    @if($invoice->paid_at)
                        <div class="flex items-center justify-between">
                            <span class="text-muted">{{ __('admin.paid_at') }}</span>
                            <span class="text-text">{{ $invoice->paid_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
