<x-app-layout>
    <x-slot name="title">Transactions - Ultimate POS</x-slot>

    @section('page-title', 'Transactions')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Transaction History</h2>
                <p class="text-muted mt-1">View and manage all transactions</p>
            </div>
        </div>
    </x-slot>

    <!-- Filters -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('transactions.index') }}">
            <div class="flex flex-wrap items-end gap-3">
                <!-- Search -->
                <div class="flex-1 min-w-[200px] max-w-xs">
                    <label class="block text-xs font-medium text-muted mb-1">Search</label>
                    <x-input
                        name="search"
                        placeholder="Transaction number..."
                        :value="request('search')"
                    />
                </div>

                <!-- Outlet -->
                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">Outlet</label>
                    <x-select name="outlet_id">
                        <option value="">All Outlets</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <!-- Type -->
                <div class="w-32">
                    <label class="block text-xs font-medium text-muted mb-1">Type</label>
                    <x-select name="type">
                        <option value="">All</option>
                        <option value="sale" @selected(request('type') === 'sale')>Sale</option>
                        <option value="refund" @selected(request('type') === 'refund')>Refund</option>
                        <option value="void" @selected(request('type') === 'void')>Void</option>
                    </x-select>
                </div>

                <!-- Status -->
                <div class="w-36">
                    <label class="block text-xs font-medium text-muted mb-1">Status</label>
                    <x-select name="status">
                        <option value="">All</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="voided" @selected(request('status') === 'voided')>Voided</option>
                    </x-select>
                </div>

                <!-- Date Range -->
                <div class="flex items-end gap-2">
                    <div class="w-36">
                        <label class="block text-xs font-medium text-muted mb-1">From</label>
                        <input
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="w-full px-3 py-2 text-sm border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                        />
                    </div>
                    <div class="w-36">
                        <label class="block text-xs font-medium text-muted mb-1">To</label>
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
                        Filter
                    </x-button>
                    @if(request()->hasAny(['search', 'outlet_id', 'type', 'status', 'date_from', 'date_to']))
                        <x-button href="{{ route('transactions.index') }}" variant="ghost">
                            Clear
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
                        <x-th>Transaction</x-th>
                        <x-th>Customer</x-th>
                        <x-th>Date</x-th>
                        <x-th>Payment</x-th>
                        <x-th align="center">Type</x-th>
                        <x-th align="right">Total</x-th>
                        <x-th align="center">Status</x-th>
                        <x-th align="center">Actions</x-th>
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
                                        <p class="text-muted">Walk-in</p>
                                    @endif
                                    <p class="text-xs text-muted">by {{ $transaction->user->name }}</p>
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
                                @endphp
                                <x-badge type="{{ $typeColors[$transaction->type] ?? 'secondary' }}">
                                    {{ ucfirst($transaction->type) }}
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
                                @endphp
                                <x-badge type="{{ $statusColors[$transaction->status] ?? 'secondary' }}" dot>
                                    {{ ucfirst($transaction->status) }}
                                </x-badge>
                            </x-td>
                            <x-td align="center">
                                <div class="flex items-center justify-center gap-1">
                                    <x-button href="{{ route('transactions.show', $transaction) }}" size="sm" variant="ghost" icon="eye" />
                                    @if($transaction->status === 'completed' && $transaction->type === 'sale')
                                        <x-button href="{{ route('transactions.refund', $transaction) }}" size="sm" variant="warning" icon="arrow-uturn-left">
                                            Refund
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
                title="No transactions found"
                description="No transactions match your filters."
                icon="receipt"
            />
        @endif
    </x-card>
</x-app-layout>
