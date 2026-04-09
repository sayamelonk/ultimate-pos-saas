<x-app-layout>
    <x-slot name="title">{{ __('inventory.waste_logs') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.waste_logs'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.waste_logs') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.manage_waste_logs') }}</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.waste-logs.report') }}" variant="outline-secondary" icon="chart-bar">
                    {{ __('inventory.waste_report') }}
                </x-button>
                <x-button href="{{ route('inventory.waste-logs.create') }}" icon="plus">
                    {{ __('inventory.create_waste') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.waste-logs.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="{{ __('inventory.search_waste') }}"
                        :value="request('search')"
                    />
                </div>
                <x-select name="outlet_id" class="w-48">
                    <option value="">{{ __('inventory.all_outlets') }}</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="reason" class="w-40">
                    <option value="">{{ __('inventory.all_reasons') }}</option>
                    <option value="expired" @selected(request('reason') === 'expired')>{{ __('inventory.waste_expired') }}</option>
                    <option value="damaged" @selected(request('reason') === 'damaged')>{{ __('inventory.waste_damaged') }}</option>
                    <option value="spoiled" @selected(request('reason') === 'spoiled')>{{ __('inventory.waste_spoiled') }}</option>
                    <option value="overproduction" @selected(request('reason') === 'overproduction')>{{ __('inventory.waste_overproduction') }}</option>
                    <option value="quality_issue" @selected(request('reason') === 'quality_issue')>{{ __('inventory.waste_quality_issue') }}</option>
                    <option value="other" @selected(request('reason') === 'other')>{{ __('inventory.waste_other') }}</option>
                </x-select>
                <x-input
                    type="date"
                    name="date_from"
                    :value="request('date_from')"
                    class="w-40"
                />
                <x-input
                    type="date"
                    name="date_to"
                    :value="request('date_to')"
                    class="w-40"
                />
                <x-button type="submit" variant="secondary">
                    {{ __('inventory.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'reason', 'date_from', 'date_to']))
                    <x-button href="{{ route('inventory.waste-logs.index') }}" variant="ghost">
                        {{ __('app.clear') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="p-4 bg-secondary-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-text">{{ $wasteLogs->total() }}</p>
                <p class="text-sm text-muted">{{ __('app.total_records') }}</p>
            </div>
            <div class="p-4 bg-danger-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-danger-600">Rp {{ number_format($totalValue ?? 0, 0, ',', '.') }}</p>
                <p class="text-sm text-muted">{{ __('app.total_value_lost') }}</p>
            </div>
            <div class="p-4 bg-warning-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-warning-600">{{ number_format($totalQuantity ?? 0, 2) }}</p>
                <p class="text-sm text-muted">{{ __('app.total_qty_wasted') }}</p>
            </div>
            <div class="p-4 bg-secondary-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-text">{{ $topReason ?? '-' }}</p>
                <p class="text-sm text-muted">{{ __('app.top_reason') }}</p>
            </div>
        </div>

        <!-- Table -->
        @if($wasteLogs->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.date') }}</x-th>
                    <x-th>{{ __('inventory.item') }}</x-th>
                    <x-th>{{ __('inventory.outlet') }}</x-th>
                    <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                    <x-th align="right">{{ __('inventory.value') }}</x-th>
                    <x-th>{{ __('inventory.reason') }}</x-th>
                    <x-th>{{ __('inventory.logged_by') }}</x-th>
                    <x-th align="right">{{ __('inventory.actions') }}</x-th>
                </x-slot>

                @foreach($wasteLogs as $log)
                    <tr>
                        <x-td>{{ $log->waste_date->format('M d, Y') }}</x-td>
                        <x-td>
                            <p class="font-medium">{{ $log->inventoryItem->name }}</p>
                            <p class="text-xs text-muted">{{ $log->inventoryItem->sku }}</p>
                        </x-td>
                        <x-td>{{ $log->outlet->name }}</x-td>
                        <x-td align="right">
                            <span class="text-danger-600 font-medium">-{{ number_format($log->quantity, 2) }}</span>
                            {{ $log->inventoryItem->unit->abbreviation ?? '' }}
                        </x-td>
                        <x-td align="right" class="font-medium text-danger-600">
                            Rp {{ number_format($log->value, 0, ',', '.') }}
                        </x-td>
                        <x-td>
                            @switch($log->reason)
                                @case('expired')
                                    <x-badge type="danger">{{ __('inventory.waste_expired') }}</x-badge>
                                    @break
                                @case('damaged')
                                    <x-badge type="warning">{{ __('inventory.waste_damaged') }}</x-badge>
                                    @break
                                @case('spoiled')
                                    <x-badge type="danger">{{ __('inventory.waste_spoiled') }}</x-badge>
                                    @break
                                @case('overproduction')
                                    <x-badge type="info">{{ __('inventory.waste_overproduction') }}</x-badge>
                                    @break
                                @case('quality_issue')
                                    <x-badge type="warning">{{ __('inventory.waste_quality_issue') }}</x-badge>
                                    @break
                                @default
                                    <x-badge type="secondary">{{ __('inventory.waste_other') }}</x-badge>
                            @endswitch
                        </x-td>
                        <x-td>{{ $log->loggedBy->name ?? '-' }}</x-td>
                        <x-td align="right">
                            <div x-data>
                                <x-dropdown align="right">
                                    <x-slot name="trigger">
                                        <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                            <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                        </button>
                                    </x-slot>

                                    <x-dropdown-item href="{{ route('inventory.waste-logs.show', $log) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('inventory.view_details') }}
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: '{{ __('inventory.delete_waste') }}',
                                            message: '{{ __('inventory.confirm_delete_waste') }}',
                                            confirmText: '{{ __('inventory.delete') }}',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        {{ __('inventory.delete') }}
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('inventory.waste-logs.destroy', $log) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$wasteLogs" />
            </div>
        @else
            <x-empty-state
                :title="__('inventory.no_waste_found')"
                :description="__('inventory.no_waste_description')"
                icon="trash"
            >
                <x-button href="{{ route('inventory.waste-logs.create') }}" icon="plus">
                    {{ __('inventory.create_waste') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
