<x-app-layout>
    <x-slot name="title">Session Report - Ultimate POS</x-slot>

    @section('page-title', 'Session Report')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('pos.sessions.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">Session Report</h2>
                    <p class="text-muted mt-1">{{ $session->session_number }}</p>
                </div>
            </div>
            <x-button onclick="window.print()" variant="secondary" icon="printer">
                Print
            </x-button>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card
                    title="Transactions"
                    :value="$report['summary']['total_transactions']"
                    icon="receipt"
                />
                <x-stat-card
                    title="Net Sales"
                    :value="'Rp ' . number_format($report['summary']['net_sales'], 0, ',', '.')"
                    icon="shopping-cart"
                />
                <x-stat-card
                    title="Items Sold"
                    :value="number_format($report['summary']['total_items_sold'], 0, ',', '.')"
                    icon="cube"
                />
                <x-stat-card
                    title="Avg. Transaction"
                    :value="'Rp ' . number_format($report['summary']['average_transaction'], 0, ',', '.')"
                    icon="chart-bar"
                />
            </div>

            <!-- Payment Summary -->
            <x-card title="Payment Summary">
                <x-table>
                    <x-slot name="head">
                        <x-th>Payment Method</x-th>
                        <x-th align="center">Count</x-th>
                        <x-th align="right">Amount</x-th>
                        <x-th align="right">Charges</x-th>
                    </x-slot>

                    @foreach($report['payments'] as $payment)
                        <tr>
                            <x-td>{{ $payment['name'] }}</x-td>
                            <x-td align="center">{{ $payment['count'] }}</x-td>
                            <x-td align="right">Rp {{ number_format($payment['amount'], 0, ',', '.') }}</x-td>
                            <x-td align="right">Rp {{ number_format($payment['charges'], 0, ',', '.') }}</x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>

            <!-- Transactions List -->
            <x-card title="Transactions">
                @if($report['transactions']->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>Transaction</x-th>
                            <x-th>Time</x-th>
                            <x-th>Type</x-th>
                            <x-th align="right">Total</x-th>
                            <x-th align="center">Status</x-th>
                        </x-slot>

                        @foreach($report['transactions'] as $transaction)
                            <tr>
                                <x-td>
                                    <a href="{{ route('transactions.show', $transaction) }}" class="text-primary hover:underline">
                                        {{ $transaction->transaction_number }}
                                    </a>
                                </x-td>
                                <x-td>{{ $transaction->created_at->format('H:i') }}</x-td>
                                <x-td>
                                    <x-badge type="{{ $transaction->type === 'sale' ? 'success' : 'warning' }}">
                                        {{ ucfirst($transaction->type) }}
                                    </x-badge>
                                </x-td>
                                <x-td align="right">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</x-td>
                                <x-td align="center">
                                    @php
                                        $statusColors = [
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'voided' => 'danger',
                                        ];
                                    @endphp
                                    <x-badge type="{{ $statusColors[$transaction->status] ?? 'secondary' }}">
                                        {{ ucfirst($transaction->status) }}
                                    </x-badge>
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <p class="text-muted text-center py-4">No transactions in this session.</p>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <x-card title="Session Details">
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-muted">Outlet</dt>
                        <dd class="font-medium">{{ $session->outlet->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Cashier</dt>
                        <dd class="font-medium">{{ $session->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Opened At</dt>
                        <dd class="font-medium">{{ $session->opened_at->format('d M Y H:i') }}</dd>
                    </div>
                    @if($session->closed_at)
                        <div>
                            <dt class="text-sm text-muted">Closed At</dt>
                            <dd class="font-medium">{{ $session->closed_at->format('d M Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted">Closed By</dt>
                            <dd class="font-medium">{{ $session->closedByUser?->name ?? '-' }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($session->isOpen())
                                <x-badge type="success" dot>Open</x-badge>
                            @else
                                <x-badge type="secondary" dot>Closed</x-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Cash Summary">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted">Opening Cash</span>
                        <span class="font-medium">Rp {{ number_format($report['cash']['opening_cash'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Cash Sales</span>
                        <span class="font-medium text-success">+ Rp {{ number_format($report['cash']['cash_sales'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <span class="text-muted">Expected Cash</span>
                        <span class="font-semibold">Rp {{ number_format($report['cash']['expected_cash'], 0, ',', '.') }}</span>
                    </div>
                    @if($session->closing_cash !== null)
                        <div class="flex justify-between">
                            <span class="text-muted">Closing Cash</span>
                            <span class="font-medium">Rp {{ number_format($report['cash']['closing_cash'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t">
                            <span class="font-medium">Difference</span>
                            <span class="font-semibold {{ $report['cash']['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $report['cash']['difference'] >= 0 ? '+' : '' }}Rp {{ number_format($report['cash']['difference'], 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </dl>
            </x-card>

            @if($session->opening_notes || $session->closing_notes)
                <x-card title="Notes">
                    @if($session->opening_notes)
                        <div class="mb-3">
                            <p class="text-sm text-muted">Opening Notes</p>
                            <p>{{ $session->opening_notes }}</p>
                        </div>
                    @endif
                    @if($session->closing_notes)
                        <div>
                            <p class="text-sm text-muted">Closing Notes</p>
                            <p>{{ $session->closing_notes }}</p>
                        </div>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
