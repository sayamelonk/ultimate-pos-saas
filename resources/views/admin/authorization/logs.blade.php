<x-app-layout>
    <x-slot name="title">Authorization Logs - Ultimate POS</x-slot>

    @section('page-title', 'Authorization Logs')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.authorization.settings') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">Authorization Logs</h2>
                    <p class="text-muted mt-1">Track all authorization requests and approvals</p>
                </div>
            </div>
        </div>
    </x-slot>

    <!-- Filters -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.authorization.logs') }}">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">Action Type</label>
                    <x-select name="action_type">
                        <option value="">All Actions</option>
                        <option value="void" @selected(request('action_type') == 'void')>Void</option>
                        <option value="refund" @selected(request('action_type') == 'refund')>Refund</option>
                        <option value="discount" @selected(request('action_type') == 'discount')>Discount</option>
                        <option value="price_override" @selected(request('action_type') == 'price_override')>Price Override</option>
                        <option value="no_sale" @selected(request('action_type') == 'no_sale')>No Sale</option>
                        <option value="cancel_order" @selected(request('action_type') == 'cancel_order')>Cancel Order</option>
                    </x-select>
                </div>

                <div class="w-32">
                    <label class="block text-xs font-medium text-muted mb-1">Status</label>
                    <x-select name="status">
                        <option value="">All Status</option>
                        <option value="approved" @selected(request('status') == 'approved')>Approved</option>
                        <option value="denied" @selected(request('status') == 'denied')>Denied</option>
                    </x-select>
                </div>

                <div class="w-36">
                    <label class="block text-xs font-medium text-muted mb-1">From Date</label>
                    <x-input type="date" name="date_from" :value="request('date_from')" />
                </div>

                <div class="w-36">
                    <label class="block text-xs font-medium text-muted mb-1">To Date</label>
                    <x-input type="date" name="date_to" :value="request('date_to')" />
                </div>

                <x-button type="submit" variant="primary" icon="search">
                    Filter
                </x-button>

                @if(request()->hasAny(['action_type', 'status', 'date_from', 'date_to']))
                    <x-button href="{{ route('admin.authorization.logs') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Logs Table -->
    <x-card>
        @if($logs->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Date/Time</x-th>
                    <x-th>Action</x-th>
                    <x-th>Requested By</x-th>
                    <x-th>Authorized By</x-th>
                    <x-th>Reference</x-th>
                    <x-th align="right">Amount</x-th>
                    <x-th align="center">Status</x-th>
                </x-slot>

                @foreach($logs as $log)
                    <tr>
                        <x-td>
                            <p class="font-medium">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-sm text-muted">{{ $log->created_at->format('H:i:s') }}</p>
                        </x-td>
                        <x-td>
                            <span class="font-medium">{{ \App\Models\AuthorizationLog::getActionLabel($log->action_type) }}</span>
                            @if($log->reason)
                                <p class="text-sm text-muted truncate max-w-[150px]" title="{{ $log->reason }}">{{ $log->reason }}</p>
                            @endif
                        </x-td>
                        <x-td>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-secondary-100 flex items-center justify-center">
                                    <span class="text-xs font-medium">{{ $log->requestedBy->initials ?? '?' }}</span>
                                </div>
                                <span>{{ $log->requestedBy->name ?? 'Unknown' }}</span>
                            </div>
                        </x-td>
                        <x-td>
                            @if($log->authorizedBy)
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center">
                                        <span class="text-xs font-medium text-success">{{ $log->authorizedBy->initials }}</span>
                                    </div>
                                    <span>{{ $log->authorizedBy->name }}</span>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td>
                            @if($log->reference_number)
                                <span class="font-mono text-sm">{{ $log->reference_number }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td align="right">
                            @if($log->amount)
                                <span class="font-medium">Rp {{ number_format($log->amount, 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td align="center">
                            <x-badge :type="\App\Models\AuthorizationLog::getStatusBadgeType($log->status)">
                                {{ ucfirst($log->status) }}
                            </x-badge>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @else
            <x-empty-state
                title="No authorization logs"
                description="Authorization activity will appear here once users start using PIN verification."
                icon="shield-check"
            />
        @endif
    </x-card>
</x-app-layout>
