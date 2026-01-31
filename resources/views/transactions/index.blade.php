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
        <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <x-input
                    name="search"
                    placeholder="Search transaction number..."
                    :value="request('search')"
                />
            </div>
            <div>
                <x-select name="outlet_id">
                    <option value="">All Outlets</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') === $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-select name="type">
                    <option value="">All Types</option>
                    <option value="sale" @selected(request('type') === 'sale')>Sale</option>
                    <option value="refund" @selected(request('type') === 'refund')>Refund</option>
                    <option value="void" @selected(request('type') === 'void')>Void</option>
                </x-select>
            </div>
            <div>
                <x-select name="status">
                    <option value="">All Status</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="voided" @selected(request('status') === 'voided')>Voided</option>
                </x-select>
            </div>
            <div>
                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="px-4 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                />
            </div>
            <div>
                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="px-4 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                />
            </div>
            <x-button type="submit" variant="secondary" icon="search">
                Filter
            </x-button>
            @if(request()->hasAny(['search', 'outlet_id', 'type', 'status', 'date_from', 'date_to']))
                <x-button href="{{ route('transactions.index') }}" variant="ghost">
                    Clear
                </x-button>
            @endif
        </form>
    </x-card>

    <x-card>
        @if($transactions->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Transaction</x-th>
                    <x-th>Outlet</x-th>
                    <x-th>Customer</x-th>
                    <x-th>Cashier</x-th>
                    <x-th>Date/Time</x-th>
                    <x-th align="center">Type</x-th>
                    <x-th align="right">Total</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($transactions as $transaction)
                    <tr>
                        <x-td>
                            <a href="{{ route('transactions.show', $transaction) }}" class="text-primary hover:underline">
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $transaction->transaction_number }}</code>
                            </a>
                        </x-td>
                        <x-td>{{ $transaction->outlet->name }}</x-td>
                        <x-td>
                            @if($transaction->customer)
                                <span>{{ $transaction->customer->name }}</span>
                            @else
                                <span class="text-muted">Walk-in</span>
                            @endif
                        </x-td>
                        <x-td>{{ $transaction->user->name }}</x-td>
                        <x-td>{{ $transaction->created_at->format('d M Y H:i') }}</x-td>
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
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-2">
                                <x-button href="{{ route('transactions.show', $transaction) }}" size="sm" variant="secondary" icon="eye">
                                    View
                                </x-button>
                                @if($transaction->status === 'completed' && $transaction->type === 'sale')
                                    <x-button href="{{ route('transactions.refund', $transaction) }}" size="sm" variant="warning">
                                        Refund
                                    </x-button>
                                @endif
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

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
