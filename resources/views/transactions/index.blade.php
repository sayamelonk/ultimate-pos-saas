<x-app-layout>
    <x-slot name="title">{{ __('transactions.transactions') }} - Ultimate POS</x-slot>

    @section('page-title', __('transactions.transactions'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('transactions.transaction_history') }}</h2>
                <p class="text-muted mt-1">{{ __('transactions.manage_transactions') }}</p>
            </div>
        </div>
    </x-slot>

    <!-- Filters -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('transactions.index') }}">
            <div class="flex flex-wrap items-end gap-3">
                <!-- Search -->
                <div class="flex-1 min-w-[200px] max-w-xs">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('transactions.search') }}</label>
                    <x-input
                        name="search"
                        :placeholder="__('transactions.search_placeholder')"
                        :value="request('search')"
                    />
                </div>

                <!-- Outlet -->
                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('transactions.outlet') }}</label>
                    <x-select name="outlet_id">
                        <option value="">{{ __('transactions.all_outlets') }}</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <!-- Type -->
                <div class="w-32">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('transactions.type') }}</label>
                    <x-select name="type">
                        <option value="">{{ __('transactions.all_types') }}</option>
                        <option value="sale" @selected(request('type') === 'sale')>{{ __('transactions.sale') }}</option>
                        <option value="refund" @selected(request('type') === 'refund')>{{ __('transactions.refund') }}</option>
                        <option value="void" @selected(request('type') === 'void')>{{ __('transactions.void') }}</option>
                    </x-select>
                </div>

                <!-- Status -->
                <div class="w-36">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('transactions.status') }}</label>
                    <x-select name="status">
                        <option value="">{{ __('transactions.all_status') }}</option>
                        <option value="completed" @selected(request('status') === 'completed')>{{ __('transactions.completed') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('transactions.pending') }}</option>
                        <option value="voided" @selected(request('status') === 'voided')>{{ __('transactions.voided') }}</option>
                    </x-select>
                </div>

                <!-- Date Range -->
                <div class="flex items-end gap-2">
                    <div class="w-36">
                        <label class="block text-xs font-medium text-muted mb-1">{{ __('transactions.from') }}</label>
                        <input
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="w-full px-3 py-2 text-sm border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                        />
                    </div>
                    <div class="w-36">
                        <label class="block text-xs font-medium text-muted mb-1">{{ __('transactions.to') }}</label>
                        <input
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="w-full px-3 py-2 text-sm border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                        />
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" icon="search">
                        {{ __('transactions.filter') }}
                    </x-button>
                    @if(request()->hasAny(['search', 'outlet_id', 'type', 'status', 'date_from', 'date_to']))
                        <x-button href="{{ route('transactions.index') }}" variant="ghost">
                            {{ __('transactions.clear') }}
                        </x-button>
                    @endif
                </div>
            </div>
        </form>
    </x-card>

    <x-card>
        @if($transactions->count() > 0)
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('transactions.transaction') }}</x-th>
                        <x-th>{{ __('transactions.customer') }}</x-th>
                        <x-th>{{ __('transactions.date') }}</x-th>
                        <x-th>{{ __('transactions.payment') }}</x-th>
                        <x-th align="center">{{ __('transactions.type') }}</x-th>
                        <x-th align="right">{{ __('transactions.total') }}</x-th>
                        <x-th align="center">{{ __('transactions.status') }}</x-th>
                        <x-th align="center">{{ __('transactions.actions') }}</x-th>
                    </x-slot>

                    @foreach($transactions as $transaction)
                        <tr class="hover:bg-secondary-50/50 transition-colors">
                            <x-td>
                                <div>
                                    <a href="{{ route('transactions.show', $transaction) }}" class="font-medium text-primary hover:underline">
                                        {{ $transaction->transaction_number }}
                                    </a>
                                    <p class="text-xs text-muted mt-0.5">{{ $transaction->outlet->name }}</p>
                                </div>
                            </x-td>
                            <x-td>
                                <div class="text-sm">
                                    @if($transaction->customer)
                                        <p class="font-medium">{{ $transaction->customer->name }}</p>
                                    @else
                                        <p class="text-muted">{{ __('transactions.walk_in') }}</p>
                                    @endif
                                    <p class="text-xs text-muted">{{ __('transactions.by') }} {{ $transaction->user->name }}</p>
                                </div>
                            </x-td>
                            <x-td>
                                <div class="text-sm">
                                    <p>{{ $transaction->created_at->format('d M Y') }}</p>
                                    <p class="text-xs text-muted">{{ $transaction->created_at->format('H:i') }}</p>
                                </div>
                            </x-td>
                            <x-td>
                                <span class="text-sm">{{ $transaction->paymentMethod?->name ?? '-' }}</span>
                            </x-td>
                            <x-td align="center">
                                @php
                                    $typeColors = [
                                        'sale' => 'success',
                                        'refund' => 'warning',
                                        'void' => 'danger',
                                    ];
                                    $typeLabels = [
                                        'sale' => __('transactions.sale'),
                                        'refund' => __('transactions.refund'),
                                        'void' => __('transactions.void'),
                                    ];
                                @endphp
                                <x-badge type="{{ $typeColors[$transaction->type] ?? 'secondary' }}">
                                    {{ $typeLabels[$transaction->type] ?? ucfirst($transaction->type) }}
                                </x-badge>
                            </x-td>
                            <x-td align="right">
                                <span class="font-semibold {{ $transaction->type === 'refund' ? 'text-danger' : '' }}">
                                    {{ $transaction->type === 'refund' ? '-' : '' }}Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}
                                </span>
                            </x-td>
                            <x-td align="center">
                                @php
                                    $statusColors = [
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'voided' => 'danger',
                                    ];
                                    $statusLabels = [
                                        'completed' => __('transactions.completed'),
                                        'pending' => __('transactions.pending'),
                                        'voided' => __('transactions.voided'),
                                    ];
                                @endphp
                                <x-badge type="{{ $statusColors[$transaction->status] ?? 'secondary' }}" dot>
                                    {{ $statusLabels[$transaction->status] ?? ucfirst($transaction->status) }}
                                </x-badge>
                            </x-td>
                            <x-td align="center">
                                <div class="flex items-center justify-center gap-1">
                                    <x-button href="{{ route('transactions.show', $transaction) }}" size="sm" variant="ghost" icon="eye" />
                                    @if($transaction->status === 'completed' && $transaction->type === 'sale')
                                        <x-button href="{{ route('transactions.refund', $transaction) }}" size="sm" variant="warning" icon="arrow-uturn-left">
                                            {{ __('transactions.refund') }}
                                        </x-button>
                                    @endif
                                </div>
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </div>

            <div class="mt-6">
                <x-pagination :paginator="$transactions" />
            </div>
        @else
            <x-empty-state
                :title="__('transactions.no_transactions')"
                :description="__('transactions.no_transactions_desc')"
                icon="receipt"
            />
        @endif
    </x-card>
</x-app-layout>
