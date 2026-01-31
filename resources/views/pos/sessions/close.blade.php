<x-app-layout>
    <x-slot name="title">Close Session - Ultimate POS</x-slot>

    @section('page-title', 'Close Session')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('pos.sessions.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Close Session</h2>
                <p class="text-muted mt-1">{{ $session->session_number }}</p>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Summary -->
        <div class="space-y-6">
            <x-card title="Session Summary">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted">Outlet</span>
                        <span class="font-medium">{{ $session->outlet->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Cashier</span>
                        <span class="font-medium">{{ $session->user->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Opened At</span>
                        <span class="font-medium">{{ $session->opened_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Duration</span>
                        <span class="font-medium">{{ $session->opened_at->diffForHumans(null, true) }}</span>
                    </div>
                </dl>
            </x-card>

            <x-card title="Sales Summary">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted">Total Transactions</span>
                        <span class="font-medium">{{ $report['summary']['total_transactions'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Total Refunds</span>
                        <span class="font-medium">{{ $report['summary']['total_refunds'] }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Net Sales</span>
                        <span>Rp {{ number_format($report['summary']['net_sales'], 0, ',', '.') }}</span>
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
                    <div class="flex justify-between text-lg font-semibold border-t pt-2">
                        <span>Expected Cash</span>
                        <span>Rp {{ number_format($report['cash']['expected_cash'], 0, ',', '.') }}</span>
                    </div>
                </dl>
            </x-card>
        </div>

        <!-- Close Form -->
        <div>
            <x-card title="Close Session">
                <form method="POST" action="{{ route('pos.sessions.close.store', $session) }}">
                    @csrf

                    <div class="space-y-4">
                        <div class="p-4 bg-accent-50 rounded-lg border border-accent-200">
                            <p class="text-sm text-muted">Expected Cash</p>
                            <p class="text-2xl font-bold text-accent">Rp {{ number_format($report['cash']['expected_cash'], 0, ',', '.') }}</p>
                        </div>

                        <x-input
                            label="Actual Closing Cash (Rp)"
                            name="closing_cash"
                            type="number"
                            :value="old('closing_cash', $report['cash']['expected_cash'])"
                            required
                            prefix="Rp"
                        />

                        <x-textarea
                            label="Closing Notes"
                            name="closing_notes"
                            rows="3"
                            placeholder="Any notes about this session..."
                        >{{ old('closing_notes') }}</x-textarea>

                        <div class="pt-4">
                            <x-button type="submit" class="w-full" variant="warning">
                                Close Session
                            </x-button>
                        </div>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
