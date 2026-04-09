<x-app-layout>
    <x-slot name="title">{{ $customer->name }} - Ultimate POS</x-slot>

    @section('page-title', __('customers.customer_details'))

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
                {{ __('customers.edit') }}
            </x-button>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card
                    :title="__('customers.total_points')"
                    :value="number_format($customer->total_points, 0, ',', '.')"
                    icon="tag"
                />
                <x-stat-card
                    :title="__('customers.total_spent')"
                    :value="'Rp ' . number_format($customer->total_spent, 0, ',', '.')"
                    icon="shopping-cart"
                />
                <x-stat-card
                    :title="__('customers.total_visits')"
                    :value="$customer->total_visits"
                    icon="calendar"
                />
                <x-stat-card
                    :title="__('customers.point_value')"
                    :value="'Rp ' . number_format($customer->getPointsValue(), 0, ',', '.')"
                    icon="receipt"
                />
            </div>

            <!-- Point History -->
            <x-card :title="__('customers.point_history')">
                @if($pointHistory->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('customers.date') }}</x-th>
                            <x-th>{{ __('customers.type') }}</x-th>
                            <x-th align="right">{{ __('customers.points') }}</x-th>
                            <x-th align="right">{{ __('customers.balance') }}</x-th>
                            <x-th>{{ __('customers.description') }}</x-th>
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
                                        $typeLabels = [
                                            'earned' => __('customers.earned'),
                                            'redeemed' => __('customers.redeemed'),
                                            'expired' => __('customers.expired'),
                                            'adjustment' => __('customers.adjustment'),
                                        ];
                                    @endphp
                                    <x-badge type="{{ $typeColors[$point->type] ?? 'secondary' }}">
                                        {{ $typeLabels[$point->type] ?? ucfirst($point->type) }}
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
                    <p class="text-muted text-center py-4">{{ __('customers.no_point_history') }}</p>
                @endif
            </x-card>

            <!-- Recent Transactions -->
            <x-card :title="__('customers.recent_transactions')">
                @if($customer->transactions->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('customers.transaction') }}</x-th>
                            <x-th>{{ __('customers.date') }}</x-th>
                            <x-th align="right">{{ __('customers.total') }}</x-th>
                            <x-th align="center">{{ __('customers.status') }}</x-th>
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
                                        $statusLabels = [
                                            'completed' => __('customers.completed'),
                                            'pending' => __('customers.pending'),
                                            'voided' => __('customers.voided'),
                                        ];
                                    @endphp
                                    <x-badge type="{{ $statusColors[$transaction->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$transaction->status] ?? ucfirst($transaction->status) }}
                                    </x-badge>
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <p class="text-muted text-center py-4">{{ __('customers.no_transactions_yet') }}</p>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <x-card :title="__('customers.customer_information')">
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-muted">{{ __('customers.phone') }}</dt>
                        <dd class="font-medium">{{ $customer->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('customers.email') }}</dt>
                        <dd class="font-medium">{{ $customer->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('customers.address') }}</dt>
                        <dd class="font-medium">{{ $customer->address ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('customers.birth_date') }}</dt>
                        <dd class="font-medium">{{ $customer->birth_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('customers.gender') }}</dt>
                        <dd class="font-medium">
                            @if($customer->gender)
                                {{ __('customers.' . $customer->gender) }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">{{ __('customers.member_since') }}</dt>
                        <dd class="font-medium">{{ $customer->joined_at->format('d M Y') }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card :title="__('customers.membership')">
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
                        {{ __('customers.' . $customer->membership_level) }}
                    </x-badge>
                    @if($customer->membership_expires_at)
                        <p class="text-sm text-muted mt-2">
                            {{ __('customers.expires', ['date' => $customer->membership_expires_at->format('d M Y')]) }}
                        </p>
                    @endif
                </div>
            </x-card>

            <x-card :title="__('customers.adjust_points')">
                <form method="POST" action="{{ route('customers.points.adjust', $customer) }}">
                    @csrf
                    <x-input
                        type="number"
                        name="points"
                        :label="__('customers.points')"
                        :placeholder="__('customers.enter_points')"
                        required
                    />
                    <div class="mt-3">
                        <x-input
                            name="description"
                            :label="__('customers.reason')"
                            :placeholder="__('customers.reason_placeholder')"
                        />
                    </div>
                    <x-button type="submit" class="w-full mt-4">
                        {{ __('customers.adjust_points') }}
                    </x-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
