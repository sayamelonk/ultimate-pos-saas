<x-app-layout>
    <x-slot name="title">{{ __('admin.all_invoices') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.all_invoices'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.all_invoices') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.manage_all_invoices') }}</p>
            </div>
        </div>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            :title="__('admin.total_paid')"
            :value="'Rp ' . number_format($stats['total_paid'] ?? 0, 0, ',', '.')"
            icon="check-circle"
            color="success"
        />
        <x-stat-card
            :title="__('admin.total_pending')"
            :value="'Rp ' . number_format($stats['total_pending'] ?? 0, 0, ',', '.')"
            icon="clock"
            color="warning"
        />
        <x-stat-card
            :title="__('admin.paid')"
            :value="$stats['count_paid'] ?? 0"
            icon="receipt"
            color="success"
        />
        <x-stat-card
            :title="__('admin.pending')"
            :value="$stats['count_pending'] ?? 0"
            icon="hourglass"
            color="warning"
        />
    </div>

    <x-card>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex flex-wrap items-center gap-3 mb-6">
            <x-input
                type="search"
                name="search"
                placeholder="{{ __('admin.search_invoices') }}"
                :value="request('search')"
                class="w-64"
            />
            <x-select name="status" class="w-40">
                <option value="">{{ __('admin.all_status') }}</option>
                <option value="paid" @selected(request('status') === 'paid')>{{ __('admin.paid') }}</option>
                <option value="pending" @selected(request('status') === 'pending')>{{ __('admin.pending') }}</option>
                <option value="failed" @selected(request('status') === 'failed')>{{ __('admin.failed') }}</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>{{ __('admin.cancelled') }}</option>
                <option value="refunded" @selected(request('status') === 'refunded')>{{ __('admin.refunded') }}</option>
            </x-select>
            <x-select name="tenant_id" class="w-48">
                <option value="">{{ __('admin.all_tenants') }}</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>
                        {{ $tenant->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input
                type="date"
                name="date_from"
                placeholder="{{ __('admin.from') }}"
                :value="request('date_from')"
                class="w-40"
            />
            <x-input
                type="date"
                name="date_to"
                placeholder="{{ __('admin.to') }}"
                :value="request('date_to')"
                class="w-40"
            />
            <x-button type="submit" variant="secondary">{{ __('admin.filter') }}</x-button>
            @if(request()->hasAny(['search', 'status', 'tenant_id', 'date_from', 'date_to']))
                <x-button href="{{ route('admin.invoices.index') }}" variant="ghost">{{ __('admin.clear') }}</x-button>
            @endif
        </form>

        <!-- Table -->
        @if($invoices->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('admin.invoice_number') }}</x-th>
                    <x-th>{{ __('admin.tenant') }}</x-th>
                    <x-th>{{ __('admin.plan') }}</x-th>
                    <x-th align="right">{{ __('admin.amount') }}</x-th>
                    <x-th align="center">{{ __('admin.status') }}</x-th>
                    <x-th align="center">{{ __('admin.created') }}</x-th>
                    <x-th align="center">{{ __('admin.paid_at') }}</x-th>
                    <x-th align="right">{{ __('admin.action') }}</x-th>
                </x-slot>

                @foreach($invoices as $invoice)
                    <tr>
                        <x-td>
                            <p class="font-mono text-sm font-medium text-text">{{ $invoice->invoice_number }}</p>
                        </x-td>
                        <x-td>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <span class="text-xs font-semibold text-primary">
                                        {{ strtoupper(substr($invoice->tenant->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $invoice->tenant->name }}</p>
                                    <p class="text-xs text-muted">{{ $invoice->tenant->email }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <span class="text-sm text-text">{{ ucfirst($invoice->plan_name) }}</span>
                        </x-td>
                        <x-td align="right">
                            <p class="font-semibold text-text">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
                        </x-td>
                        <x-td align="center">
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
                        </x-td>
                        <x-td align="center">
                            <span class="text-muted text-xs">{{ $invoice->created_at->format('M d, Y') }}</span>
                        </x-td>
                        <x-td align="center">
                            @if($invoice->paid_at)
                                <span class="text-muted text-xs">{{ $invoice->paid_at->format('M d, Y') }}</span>
                            @else
                                <span class="text-muted text-xs">-</span>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.invoices.show', $invoice) }}"
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
                <x-pagination :paginator="$invoices" />
            </div>
        @else
            <x-empty-state
                title="{{ __('admin.no_invoices_found') }}"
                description="{{ __('admin.no_invoices_desc') }}"
                icon="receipt"
            />
        @endif
    </x-card>
</x-app-layout>
