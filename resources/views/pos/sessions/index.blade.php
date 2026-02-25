<x-app-layout>
    <x-slot name="title">POS Sessions - Ultimate POS</x-slot>

    @section('page-title', 'POS Sessions')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">POS Sessions</h2>
                <p class="text-muted mt-1">Manage cashier shifts and sessions</p>
            </div>
            <x-button href="{{ route('pos.sessions.open') }}" icon="plus">
                Open Session
            </x-button>
        </div>
    </x-slot>

    <x-card>
        @if($sessions->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Session / Outlet</x-th>
                    <x-th>Cashier</x-th>
                    <x-th>Period</x-th>
                    <x-th align="right">Cash</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="center">Actions</x-th>
                </x-slot>

                @foreach($sessions as $session)
                    <tr>
                        <x-td>
                            <div>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $session->session_number }}</code>
                                <p class="text-sm text-muted mt-1">{{ $session->outlet->name }}</p>
                            </div>
                        </x-td>
                        <x-td>{{ $session->user->name }}</x-td>
                        <x-td>
                            <div class="text-sm">
                                <p>{{ $session->opened_at->format('d M Y H:i') }}</p>
                                @if($session->closed_at)
                                    <p class="text-muted">{{ $session->closed_at->format('d M Y H:i') }}</p>
                                @else
                                    <p class="text-muted">-</p>
                                @endif
                            </div>
                        </x-td>
                        <x-td align="right">
                            <div class="text-sm">
                                <p>Rp {{ number_format($session->opening_cash, 0, ',', '.') }}</p>
                                @if($session->closing_cash !== null)
                                    <p class="text-muted">Rp {{ number_format($session->closing_cash, 0, ',', '.') }}</p>
                                @else
                                    <p class="text-muted">-</p>
                                @endif
                            </div>
                        </x-td>
                        <x-td align="center">
                            @if($session->isOpen())
                                <x-badge type="success" dot>Open</x-badge>
                            @else
                                <x-badge type="secondary" dot>Closed</x-badge>
                            @endif
                        </x-td>
                        <x-td align="center">
                            <div class="flex items-center justify-center gap-1">
                                @if($session->isOpen())
                                    <x-button href="{{ route('pos.sessions.close', $session) }}" size="sm" variant="warning">
                                        Close
                                    </x-button>
                                @endif
                                <x-button href="{{ route('pos.sessions.report', $session) }}" size="sm" variant="ghost">
                                    Report
                                </x-button>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$sessions" />
            </div>
        @else
            <x-empty-state
                title="No sessions found"
                description="Open a session to start selling."
                icon="receipt"
            >
                <x-button href="{{ route('pos.sessions.open') }}" icon="plus">
                    Open Session
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
