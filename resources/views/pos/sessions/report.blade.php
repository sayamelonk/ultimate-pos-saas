<x-app-layout>
    <x-slot name="title">{{ __('pos.session_report') }} - Ultimate POS</x-slot>

    @section('page-title', __('pos.session_report'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('pos.sessions.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ __('pos.session_report') }}</h2>
                    <p class="text-muted mt-1">{{ $session->session_number }}</p>
                </div>
            </div>
            <x-button onclick="window.print()" variant="secondary" icon="printer">
                {{ __('pos.print') }}
            </x-button>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-stat-card
                title="{{ __('pos.transactions') }}"
                :value="$report['summary']['total_transactions']"
                icon="receipt"
            />
            <x-stat-card
                title="{{ __('pos.net_sales') }}"
                :value="'Rp ' . number_format($report['summary']['net_sales'], 0, ',', '.')"
                icon="shopping-cart"
            />
            <x-stat-card
                title="{{ __('pos.items_sold') }}"
                :value="number_format($report['summary']['total_items_sold'], 0, ',', '.')"
                icon="cube"
            />
            <x-stat-card
                title="{{ __('pos.avg_transaction') }}"
                :value="'Rp ' . number_format($report['summary']['average_transaction'], 0, ',', '.')"
                icon="chart-bar"
            />
        </div>

        <!-- Session Info & Cash Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Session Details -->
            <x-card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <x-icon name="information-circle" class="w-5 h-5 text-primary" />
                    </div>
                    <h3 class="font-semibold text-lg">{{ __('pos.session_details') }}</h3>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">{{ __('pos.outlet') }}</dt>
                        <dd class="font-medium text-sm mt-0.5">{{ $session->outlet->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">{{ __('pos.cashier') }}</dt>
                        <dd class="font-medium text-sm mt-0.5">{{ $session->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">{{ __('pos.opened_at') }}</dt>
                        <dd class="font-medium text-sm mt-0.5">{{ $session->opened_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide">{{ __('pos.status') }}</dt>
                        <dd class="mt-0.5">
                            @if($session->isOpen())
                                <x-badge type="success" dot>{{ __('pos.open') }}</x-badge>
                            @else
                                <x-badge type="secondary" dot>{{ __('pos.closed') }}</x-badge>
                            @endif
                        </dd>
                    </div>
                    @if($session->closed_at)
                        <div>
                            <dt class="text-xs text-muted uppercase tracking-wide">{{ __('pos.closed_at') }}</dt>
                            <dd class="font-medium text-sm mt-0.5">{{ $session->closed_at->format('d M Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted uppercase tracking-wide">{{ __('pos.closed_by') }}</dt>
                            <dd class="font-medium text-sm mt-0.5">{{ $session->closedByUser?->name ?? '-' }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Cash Summary -->
            <x-card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                        <x-icon name="cash" class="w-5 h-5 text-success" />
                    </div>
                    <h3 class="font-semibold text-lg">{{ __('pos.cash_summary') }}</h3>
                </div>
                <dl class="space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted">{{ __('pos.opening_cash') }}</span>
                        <span class="font-medium">Rp {{ number_format($report['cash']['opening_cash'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted">{{ __('pos.cash_sales') }}</span>
                        <span class="font-medium text-success">+ Rp {{ number_format($report['cash']['cash_sales'], 0, ',', '.') }}</span>
                    </div>
                    @if(isset($report['cash']['cash_in']) && $report['cash']['cash_in'] > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted">{{ __('pos.cash_in') }}</span>
                            <span class="font-medium text-success">+ Rp {{ number_format($report['cash']['cash_in'], 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if(isset($report['cash']['cash_out']) && $report['cash']['cash_out'] > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted">{{ __('pos.cash_out') }}</span>
                            <span class="font-medium text-danger">- Rp {{ number_format($report['cash']['cash_out'], 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center pt-2 border-t border-border">
                        <span class="font-medium">{{ __('pos.expected_cash') }}</span>
                        <span class="font-bold text-lg">Rp {{ number_format($report['cash']['expected_cash'], 0, ',', '.') }}</span>
                    </div>
                    @if($session->closing_cash !== null)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted">{{ __('pos.closing_cash') }}</span>
                            <span class="font-medium">Rp {{ number_format($report['cash']['closing_cash'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-border">
                            <span class="font-medium">{{ __('pos.difference') }}</span>
                            <span class="font-bold {{ $report['cash']['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $report['cash']['difference'] >= 0 ? '+' : '' }}Rp {{ number_format($report['cash']['difference'], 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Notes (if any) -->
            @if($session->opening_notes || $session->closing_notes)
                <x-card>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                            <x-icon name="document-text" class="w-5 h-5 text-warning" />
                        </div>
                        <h3 class="font-semibold text-lg">{{ __('pos.notes') }}</h3>
                    </div>
                    <div class="space-y-3">
                        @if($session->opening_notes)
                            <div>
                                <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ __('pos.opening_notes') }}</p>
                                <p class="text-sm bg-secondary-50 rounded-lg p-2.5">{{ $session->opening_notes }}</p>
                            </div>
                        @endif
                        @if($session->closing_notes)
                            <div>
                                <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ __('pos.closing_notes') }}</p>
                                <p class="text-sm bg-secondary-50 rounded-lg p-2.5">{{ $session->closing_notes }}</p>
                            </div>
                        @endif
                    </div>
                </x-card>
            @endif
        </div>

        <!-- Payment Summary -->
        <x-card>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                    <x-icon name="credit-card" class="w-5 h-5 text-info" />
                </div>
                <h3 class="font-semibold text-lg">{{ __('pos.payment_summary') }}</h3>
            </div>
            @if(count($report['payments']) > 0)
                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('pos.payment_method') }}</x-th>
                            <x-th align="center">{{ __('pos.count') }}</x-th>
                            <x-th align="right">{{ __('pos.amount') }}</x-th>
                            <x-th align="right">{{ __('pos.charges') }}</x-th>
                        </x-slot>

                        @foreach($report['payments'] as $payment)
                            <tr>
                                <x-td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-secondary-100 flex items-center justify-center">
                                            <x-icon name="credit-card" class="w-4 h-4 text-secondary-600" />
                                        </div>
                                        <span class="font-medium">{{ $payment['name'] }}</span>
                                    </div>
                                </x-td>
                                <x-td align="center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-semibold text-sm">
                                        {{ $payment['count'] }}
                                    </span>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-medium">Rp {{ number_format($payment['amount'], 0, ',', '.') }}</span>
                                </x-td>
                                <x-td align="right">
                                    @if($payment['charges'] > 0)
                                        <span class="text-danger">Rp {{ number_format($payment['charges'], 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </x-td>
                            </tr>
                        @endforeach

                        <!-- Total Row -->
                        <tr class="bg-secondary-50 font-semibold">
                            <x-td>{{ __('pos.total') }}</x-td>
                            <x-td align="center">{{ collect($report['payments'])->sum('count') }}</x-td>
                            <x-td align="right">Rp {{ number_format(collect($report['payments'])->sum('amount'), 0, ',', '.') }}</x-td>
                            <x-td align="right">Rp {{ number_format(collect($report['payments'])->sum('charges'), 0, ',', '.') }}</x-td>
                        </tr>
                    </x-table>
                </div>
            @else
                <p class="text-muted text-center py-6">{{ __('pos.no_payment_data') }}</p>
            @endif
        </x-card>

        <!-- Transactions List -->
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <x-icon name="receipt" class="w-5 h-5 text-primary" />
                    </div>
                    <h3 class="font-semibold text-lg">{{ __('pos.transactions') }}</h3>
                </div>
                <span class="text-sm text-muted">{{ __('pos.transaction_count', ['count' => $report['transactions']->count()]) }}</span>
            </div>

            @if($report['transactions']->count() > 0)
                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('pos.transaction') }}</x-th>
                            <x-th>{{ __('pos.time') }}</x-th>
                            <x-th>{{ __('pos.type') }}</x-th>
                            <x-th>{{ __('pos.payment') }}</x-th>
                            <x-th align="right">{{ __('pos.total') }}</x-th>
                            <x-th align="center">{{ __('pos.status') }}</x-th>
                        </x-slot>

                        @foreach($report['transactions'] as $transaction)
                            <tr class="hover:bg-secondary-50/50 transition-colors">
                                <x-td>
                                    <a href="{{ route('transactions.show', $transaction) }}" class="font-medium text-primary hover:underline">
                                        {{ $transaction->transaction_number }}
                                    </a>
                                </x-td>
                                <x-td>
                                    <span class="text-muted">{{ $transaction->created_at->format('H:i') }}</span>
                                </x-td>
                                <x-td>
                                    <x-badge type="{{ $transaction->type === 'sale' ? 'success' : 'warning' }}">
                                        {{ $transaction->type === 'sale' ? __('pos.sale') : ucfirst($transaction->type) }}
                                    </x-badge>
                                </x-td>
                                <x-td>
                                    <span class="text-sm">{{ $transaction->paymentMethod?->name ?? '-' }}</span>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-semibold">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
                                </x-td>
                                <x-td align="center">
                                    @php
                                        $statusColors = [
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'voided' => 'danger',
                                        ];
                                    @endphp
                                    <x-badge type="{{ $statusColors[$transaction->status] ?? 'secondary' }}">
                                        {{ __('pos.' . $transaction->status) }}
                                    </x-badge>
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>
            @else
                <div class="text-center py-8">
                    <x-icon name="receipt" class="w-12 h-12 text-muted/30 mx-auto mb-2" />
                    <p class="text-muted">{{ __('pos.no_transactions') }}</p>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
