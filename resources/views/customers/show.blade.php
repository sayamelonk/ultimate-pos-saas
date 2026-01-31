<x-app-layout>
    <x-slot name="title">{{ $customer->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Customer Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('customers.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $customer->name }}</h2>
                    <p class="text-muted mt-1">{{ $customer->code }}</p>
                </div>
            </div>
            <x-button href="{{ route('customers.edit', $customer) }}" variant="secondary" icon="pencil">
                Edit
            </x-button>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card
                    title="Total Points"
                    :value="number_format($customer->total_points, 0, ',', '.')"
                    icon="tag"
                />
                <x-stat-card
                    title="Total Spent"
                    :value="'Rp ' . number_format($customer->total_spent, 0, ',', '.')"
                    icon="shopping-cart"
                />
                <x-stat-card
                    title="Total Visits"
                    :value="$customer->total_visits"
                    icon="calendar"
                />
                <x-stat-card
                    title="Point Value"
                    :value="'Rp ' . number_format($customer->getPointsValue(), 0, ',', '.')"
                    icon="receipt"
                />
            </div>

            <!-- Point History -->
            <x-card title="Point History">
                @if($pointHistory->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>Date</x-th>
                            <x-th>Type</x-th>
                            <x-th align="right">Points</x-th>
                            <x-th align="right">Balance</x-th>
                            <x-th>Description</x-th>
                        </x-slot>

                        @foreach($pointHistory as $point)
                            <tr>
                                <x-td>{{ $point->created_at->format('d M Y H:i') }}</x-td>
                                <x-td>
                                    @php
                                        $typeColors = [
                                            'earned' => 'success',
                                            'redeemed' => 'warning',
                                            'expired' => 'danger',
                                            'adjustment' => 'info',
                                        ];
                                    @endphp
                                    <x-badge type="{{ $typeColors[$point->type] ?? 'secondary' }}">
                                        {{ ucfirst($point->type) }}
                                    </x-badge>
                                </x-td>
                                <x-td align="right">
                                    <span class="{{ $point->points >= 0 ? 'text-success' : 'text-danger' }} font-medium">
                                        {{ $point->points >= 0 ? '+' : '' }}{{ number_format($point->points, 0, ',', '.') }}
                                    </span>
                                </x-td>
                                <x-td align="right">{{ number_format($point->balance_after, 0, ',', '.') }}</x-td>
                                <x-td>{{ $point->description ?? '-' }}</x-td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <p class="text-muted text-center py-4">No point history yet.</p>
                @endif
            </x-card>

            <!-- Recent Transactions -->
            <x-card title="Recent Transactions">
                @if($customer->transactions->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>Transaction</x-th>
                            <x-th>Date</x-th>
                            <x-th align="right">Total</x-th>
                            <x-th align="center">Status</x-th>
                        </x-slot>

                        @foreach($customer->transactions->take(10) as $transaction)
                            <tr>
                                <x-td>
                                    <a href="{{ route('transactions.show', $transaction) }}" class="text-primary hover:underline">
                                        {{ $transaction->transaction_number }}
                                    </a>
                                </x-td>
                                <x-td>{{ $transaction->created_at->format('d M Y H:i') }}</x-td>
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
                    <p class="text-muted text-center py-4">No transactions yet.</p>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <x-card title="Customer Information">
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-muted">Phone</dt>
                        <dd class="font-medium">{{ $customer->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Email</dt>
                        <dd class="font-medium">{{ $customer->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Address</dt>
                        <dd class="font-medium">{{ $customer->address ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Birth Date</dt>
                        <dd class="font-medium">{{ $customer->birth_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Gender</dt>
                        <dd class="font-medium">{{ $customer->gender ? ucfirst($customer->gender) : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Joined</dt>
                        <dd class="font-medium">{{ $customer->joined_at->format('d M Y') }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Membership">
                @php
                    $levelColors = [
                        'regular' => 'secondary',
                        'silver' => 'secondary',
                        'gold' => 'warning',
                        'platinum' => 'info',
                    ];
                @endphp
                <div class="text-center py-4">
                    <x-badge type="{{ $levelColors[$customer->membership_level] ?? 'secondary' }}" class="text-lg px-4 py-2">
                        {{ ucfirst($customer->membership_level) }}
                    </x-badge>
                    @if($customer->membership_expires_at)
                        <p class="text-sm text-muted mt-2">
                            Expires: {{ $customer->membership_expires_at->format('d M Y') }}
                        </p>
                    @endif
                </div>
            </x-card>

            <x-card title="Adjust Points">
                <form method="POST" action="{{ route('customers.points.adjust', $customer) }}">
                    @csrf
                    <x-input
                        type="number"
                        name="points"
                        label="Points"
                        placeholder="Enter points (+/-)"
                        required
                    />
                    <div class="mt-3">
                        <x-input
                            name="description"
                            label="Reason"
                            placeholder="Reason for adjustment"
                        />
                    </div>
                    <x-button type="submit" class="w-full mt-4">
                        Adjust Points
                    </x-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
