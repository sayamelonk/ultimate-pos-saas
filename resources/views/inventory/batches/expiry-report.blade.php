<x-app-layout>
    <x-slot name="title">{{ __('inventory.expiry_report') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.expiry_report'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('inventory.batches.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ __('inventory.expiry_report') }}</h2>
                    <p class="text-muted mt-1">{{ __('inventory.track_expiring_items') }}</p>
                </div>
            </div>
            <x-button onclick="window.print()" variant="secondary" icon="printer">
                {{ __('inventory.print') }}
            </x-button>
        </div>
    </x-slot>

    <!-- Filters -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('inventory.batches.expiry-report') }}">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('inventory.days_until_expiry') }}</label>
                    <x-select name="days">
                        <option value="7" @selected($daysAhead == 7)>7 {{ __('inventory.days_until_expiry') }}</option>
                        <option value="14" @selected($daysAhead == 14)>14 {{ __('inventory.days_until_expiry') }}</option>
                        <option value="30" @selected($daysAhead == 30)>30 {{ __('inventory.days_until_expiry') }}</option>
                        <option value="60" @selected($daysAhead == 60)>60 {{ __('inventory.days_until_expiry') }}</option>
                        <option value="90" @selected($daysAhead == 90)>90 {{ __('inventory.days_until_expiry') }}</option>
                    </x-select>
                </div>

                <div class="w-40">
                    <label class="block text-xs font-medium text-muted mb-1">{{ __('inventory.outlet') }}</label>
                    <x-select name="outlet_id">
                        <option value="">{{ __('inventory.all_outlets') }}</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <x-button type="submit" variant="primary" icon="search">
                    {{ __('inventory.filter') }}
                </x-button>
            </div>
        </form>
    </x-card>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            :title="__('inventory.total_items')"
            :value="$batches->count()"
            icon="cube"
        />
        <x-stat-card
            :title="__('inventory.expired')"
            :value="$expired->count()"
            icon="x-circle"
            color="danger"
        />
        <x-stat-card
            :title="__('inventory.expiring_batches') . ' (' . $settings->expiry_critical_days . ' ' . __('inventory.days_until_expiry') . ')'"
            :value="$critical->count()"
            icon="exclamation-triangle"
            color="danger"
        />
        <x-stat-card
            :title="__('inventory.total_value')"
            :value="'Rp ' . number_format($totalValue, 0, ',', '.')"
            icon="currency-dollar"
            color="warning"
        />
    </div>

    @if($batches->count() > 0)
        <!-- Expired Items -->
        @if($expired->count() > 0)
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                        <x-icon name="x-circle" class="w-5 h-5 text-danger" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg text-danger">{{ __('inventory.expired') }}</h3>
                        <p class="text-sm text-muted">{{ $expired->count() }} {{ __('inventory.batches') }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('inventory.batch') }} / {{ __('inventory.item') }}</x-th>
                            <x-th>{{ __('inventory.outlet') }}</x-th>
                            <x-th align="center">{{ __('inventory.expiry_date') }}</x-th>
                            <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                            <x-th align="right">{{ __('inventory.value') }}</x-th>
                            <x-th align="center">{{ __('inventory.actions') }}</x-th>
                        </x-slot>

                        @foreach($expired as $batch)
                            <tr class="bg-danger-50/50">
                                <x-td>
                                    <div>
                                        <a href="{{ route('inventory.batches.show', $batch) }}" class="font-medium text-primary hover:underline">
                                            {{ $batch->batch_number }}
                                        </a>
                                        <p class="text-sm text-muted">{{ $batch->inventoryItem->name }}</p>
                                    </div>
                                </x-td>
                                <x-td>{{ $batch->outlet->name }}</x-td>
                                <x-td align="center">
                                    <span class="text-danger font-medium">{{ $batch->expiry_date->format('d M Y') }}</span>
                                    <p class="text-xs text-danger">{{ abs($batch->daysUntilExpiry()) }} {{ __('inventory.days_until_expiry') }}</p>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-medium">{{ number_format($batch->current_quantity, 2) }}</span>
                                    <span class="text-muted text-sm">{{ $batch->inventoryItem->unit->abbreviation }}</span>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-medium">Rp {{ number_format($batch->current_quantity * $batch->unit_cost, 0, ',', '.') }}</span>
                                </x-td>
                                <x-td align="center">
                                    <form action="{{ route('inventory.batches.dispose', $batch) }}" method="POST"
                                          onsubmit="return confirm('{{ __('inventory.confirm_delete_batch') }}')">
                                        @csrf
                                        @method('PATCH')
                                        <x-button type="submit" size="sm" variant="danger">
                                            {{ __('inventory.dispose_batch') }}
                                        </x-button>
                                    </form>
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>
            </x-card>
        @endif

        <!-- Critical Items -->
        @if($critical->count() > 0)
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                        <x-icon name="exclamation-triangle" class="w-5 h-5 text-danger" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg text-danger">{{ __('inventory.expiring_batches') }} {{ $settings->expiry_critical_days }} {{ __('inventory.days_until_expiry') }}</h3>
                        <p class="text-sm text-muted">{{ $critical->count() }} {{ __('inventory.batches') }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('inventory.batch') }} / {{ __('inventory.item') }}</x-th>
                            <x-th>{{ __('inventory.outlet') }}</x-th>
                            <x-th align="center">{{ __('inventory.expiry_date') }}</x-th>
                            <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                            <x-th align="right">{{ __('inventory.value') }}</x-th>
                            <x-th align="center">{{ __('inventory.actions') }}</x-th>
                        </x-slot>

                        @foreach($critical as $batch)
                            <tr class="bg-danger-50/30">
                                <x-td>
                                    <div>
                                        <a href="{{ route('inventory.batches.show', $batch) }}" class="font-medium text-primary hover:underline">
                                            {{ $batch->batch_number }}
                                        </a>
                                        <p class="text-sm text-muted">{{ $batch->inventoryItem->name }}</p>
                                    </div>
                                </x-td>
                                <x-td>{{ $batch->outlet->name }}</x-td>
                                <x-td align="center">
                                    <span class="font-medium">{{ $batch->expiry_date->format('d M Y') }}</span>
                                    <x-badge type="danger">{{ $batch->daysUntilExpiry() }} {{ __('inventory.days_until_expiry') }}</x-badge>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-medium">{{ number_format($batch->current_quantity, 2) }}</span>
                                    <span class="text-muted text-sm">{{ $batch->inventoryItem->unit->abbreviation }}</span>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-medium">Rp {{ number_format($batch->current_quantity * $batch->unit_cost, 0, ',', '.') }}</span>
                                </x-td>
                                <x-td align="center">
                                    <x-button href="{{ route('inventory.batches.show', $batch) }}" size="sm" variant="secondary">
                                        {{ __('inventory.view_details') }}
                                    </x-button>
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>
            </x-card>
        @endif

        <!-- Warning Items -->
        @if($warning->count() > 0)
            <x-card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                        <x-icon name="clock" class="w-5 h-5 text-warning" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg text-warning-700">{{ __('inventory.expiring_soon') }}</h3>
                        <p class="text-sm text-muted">{{ $warning->count() }} {{ __('inventory.batches') }} {{ $daysAhead }} {{ __('inventory.days_until_expiry') }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="head">
                            <x-th>{{ __('inventory.batch') }} / {{ __('inventory.item') }}</x-th>
                            <x-th>{{ __('inventory.outlet') }}</x-th>
                            <x-th align="center">{{ __('inventory.expiry_date') }}</x-th>
                            <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                            <x-th align="right">{{ __('inventory.value') }}</x-th>
                            <x-th align="center">{{ __('inventory.actions') }}</x-th>
                        </x-slot>

                        @foreach($warning as $batch)
                            <tr>
                                <x-td>
                                    <div>
                                        <a href="{{ route('inventory.batches.show', $batch) }}" class="font-medium text-primary hover:underline">
                                            {{ $batch->batch_number }}
                                        </a>
                                        <p class="text-sm text-muted">{{ $batch->inventoryItem->name }}</p>
                                    </div>
                                </x-td>
                                <x-td>{{ $batch->outlet->name }}</x-td>
                                <x-td align="center">
                                    <span class="font-medium">{{ $batch->expiry_date->format('d M Y') }}</span>
                                    <x-badge type="warning">{{ $batch->daysUntilExpiry() }} {{ __('inventory.days_until_expiry') }}</x-badge>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-medium">{{ number_format($batch->current_quantity, 2) }}</span>
                                    <span class="text-muted text-sm">{{ $batch->inventoryItem->unit->abbreviation }}</span>
                                </x-td>
                                <x-td align="right">
                                    <span class="font-medium">Rp {{ number_format($batch->current_quantity * $batch->unit_cost, 0, ',', '.') }}</span>
                                </x-td>
                                <x-td align="center">
                                    <x-button href="{{ route('inventory.batches.show', $batch) }}" size="sm" variant="secondary">
                                        {{ __('inventory.view_details') }}
                                    </x-button>
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>
            </x-card>
        @endif
    @else
        <x-card>
            <x-empty-state
                :title="__('inventory.no_batches_found')"
                :description="__('inventory.batches_description')"
                icon="check-circle"
            />
        </x-card>
    @endif
</x-app-layout>
