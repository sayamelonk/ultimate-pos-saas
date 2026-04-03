<x-app-layout>
    <x-slot name="title">{{ __('admin.authorization_logs_title') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.authorization_logs_title'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.authorization.settings') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ __('admin.authorization_logs_title') }}</h2>
                    <p class="text-muted mt-1">{{ __('admin.track_authorization') }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <!-- Filters -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.authorization.logs') }}">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('admin.action_type_label') }}</label>
                    <x-select name="action_type">
                        <option value="">{{ __('admin.all_actions') }}</option>
                        <option value="void" @selected(request('action_type') == 'void')>{{ __('admin.void_transaction_label') }}</option>
                        <option value="refund" @selected(request('action_type') == 'refund')>{{ __('admin.refund_label') }}</option>
                        <option value="discount" @selected(request('action_type') == 'discount')>{{ __('admin.manual_discount_label') }}</option>
                        <option value="price_override" @selected(request('action_type') == 'price_override')>{{ __('admin.price_override_label') }}</option>
                        <option value="no_sale" @selected(request('action_type') == 'no_sale')>{{ __('admin.no_sale_label') }}</option>
                        <option value="cancel_order" @selected(request('action_type') == 'cancel_order')>{{ __('admin.cancel_order_label') }}</option>
                    </x-select>
                </div>

                <div class="w-32">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('app.status') }}</label>
                    <x-select name="status">
                        <option value="">{{ __('admin.all_status') }}</option>
                        <option value="approved" @selected(request('status') == 'approved')>{{ __('admin.approved') }}</option>
                        <option value="denied" @selected(request('status') == 'denied')>{{ __('admin.denied') }}</option>
                    </x-select>
                </div>

                <div class="w-36">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('admin.from_date') }}</label>
                    <x-input type="date" name="date_from" :value="request('date_from')" />
                </div>

                <div class="w-36">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('admin.to_date') }}</label>
                    <x-input type="date" name="date_to" :value="request('date_to')" />
                </div>

                <x-button type="submit" variant="primary" icon="search">
                    {{ __('app.filter') }}
                </x-button>

                @if(request()->hasAny(['action_type', 'status', 'date_from', 'date_to']))
                    <x-button href="{{ route('admin.authorization.logs') }}" variant="ghost">
                        {{ __('app.clear') }}
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
                    <x-th>{{ __('admin.date_time') }}</x-th>
                    <x-th>{{ __('admin.action') }}</x-th>
                    <x-th>{{ __('admin.requested_by') }}</x-th>
                    <x-th>{{ __('admin.authorized_by_label') }}</x-th>
                    <x-th>{{ __('admin.reference') }}</x-th>
                    <x-th align="right">{{ __('admin.amount') }}</x-th>
                    <x-th align="center">{{ __('app.status') }}</x-th>
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
                title="{{ __('admin.no_authorization_logs_title') }}"
                description="{{ __('admin.authorization_logs_empty') }}"
                icon="shield-check"
            />
        @endif
    </x-card>
</x-app-layout>
