<x-app-layout>
    <x-slot name="title">Stock Valuation Report - Ultimate POS</x-slot>

    @section('page-title', __('inventory.stock_valuation_report'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.stock_valuation_report') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.analyze_stock_valuation') }}</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.reports.stock-movement') }}" variant="outline-secondary" icon="arrows-right-left">
                    {{ __('inventory.stock_movement_report') }}
                </x-button>
                <x-button href="{{ route('inventory.reports.cogs') }}" variant="outline-secondary" icon="calculator">
                    {{ __('inventory.cogs_report') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('inventory.reports.stock-valuation') }}" class="flex flex-wrap gap-4">
                <x-select name="outlet_id" class="w-48">
                    <option value="">{{ __('inventory.all_outlets') }}</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="category_id" class="w-48">
                    <option value="">{{ __('inventory.all_categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('inventory.generate_report') }}
                </x-button>
                @if(request()->hasAny(['outlet_id', 'category_id']))
                    <x-button href="{{ route('inventory.reports.stock-valuation') }}" variant="ghost">
                        {{ __('inventory.filter') }}
                    </x-button>
                @endif
            </form>
        </x-card>

        <!-- Summary Stats -->
        <div class="grid grid-cols-3 gap-4">
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-accent">Rp {{ number_format($totalValue, 0, ',', '.') }}</p>
                    <p class="text-sm text-muted mt-2">{{ __('inventory.total_stock_value') }}</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-text">{{ number_format($totalItems) }}</p>
                    <p class="text-sm text-muted mt-2">{{ __('inventory.total') }} {{ __('inventory.stock') }} Records</p>
                </div>
            </x-card>
            <x-card>
                <div class="text-center">
                    <p class="text-4xl font-bold text-text">{{ $byCategory->count() }}</p>
                    <p class="text-sm text-muted mt-2">{{ __('inventory.categories') }}</p>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- By Category -->
            <x-card title="{{ __('inventory.value') }} {{ __('inventory.category') }}">
                @if($byCategory->count() > 0)
                    <div class="space-y-4">
                        @foreach($byCategory->sortByDesc('value') as $category => $data)
                            @php
                                $percentage = $totalValue > 0 ? ($data['value'] / $totalValue) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-medium">{{ $category }}</span>
                                        <span class="text-sm text-muted">({{ $data['count'] }} {{ __('inventory.items') }})</span>
                                    </div>
                                    <span class="font-medium">Rp {{ number_format($data['value'], 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full bg-secondary-200 rounded-full h-2">
                                    <div class="bg-accent-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="{{ __('inventory.no_data_for_period') }}"
                        description="{{ __('inventory.no_stock_records') }}"
                        icon="cube"
                    />
                @endif
            </x-card>

            <!-- By Outlet -->
            <x-card title="{{ __('inventory.value') }} {{ __('inventory.outlet') }}">
                @if($byOutlet->count() > 0)
                    <div class="space-y-4">
                        @foreach($byOutlet->sortByDesc('value') as $outlet => $data)
                            @php
                                $percentage = $totalValue > 0 ? ($data['value'] / $totalValue) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-medium">{{ $outlet }}</span>
                                        <span class="text-sm text-muted">({{ $data['count'] }} {{ __('inventory.items') }})</span>
                                    </div>
                                    <span class="font-medium">Rp {{ number_format($data['value'], 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full bg-secondary-200 rounded-full h-2">
                                    <div class="bg-success-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state
                        title="{{ __('inventory.no_data_for_period') }}"
                        description="{{ __('inventory.no_stock_records') }}"
                        icon="building-storefront"
                    />
                @endif
            </x-card>
        </div>

        <!-- Detail Table -->
        <x-card title="{{ __('inventory.stock_valuation') }} {{ __('inventory.item_details') }}">
            @if($valuationData->count() > 0)
                <x-table>
                    <x-slot name="head">
                        <x-th>{{ __('inventory.item') }}</x-th>
                        <x-th>{{ __('inventory.sku') }}</x-th>
                        <x-th>{{ __('inventory.category') }}</x-th>
                        <x-th>{{ __('inventory.outlet') }}</x-th>
                        <x-th align="right">{{ __('inventory.quantity') }}</x-th>
                        <x-th align="right">{{ __('inventory.avg_cost') }}</x-th>
                        <x-th align="right">{{ __('inventory.total_value') }}</x-th>
                        <x-th align="right">% {{ __('inventory.total') }}</x-th>
                    </x-slot>

                    @foreach($valuationData->sortByDesc('value')->take(50) as $data)
                        <tr>
                            <x-td>
                                <p class="font-medium">{{ $data['item']->name }}</p>
                            </x-td>
                            <x-td>
                                <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $data['item']->sku }}</code>
                            </x-td>
                            <x-td>{{ $data['item']->category->name ?? '-' }}</x-td>
                            <x-td>{{ $data['outlet']->name }}</x-td>
                            <x-td align="right">
                                {{ number_format($data['quantity'], 2) }}
                                {{ $data['item']->unit->abbreviation ?? '' }}
                            </x-td>
                            <x-td align="right">Rp {{ number_format($data['avg_cost'], 0, ',', '.') }}</x-td>
                            <x-td align="right" class="font-medium">Rp {{ number_format($data['value'], 0, ',', '.') }}</x-td>
                            <x-td align="right">
                                {{ $totalValue > 0 ? number_format(($data['value'] / $totalValue) * 100, 1) : 0 }}%
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>

                @if($valuationData->count() > 50)
                    <p class="mt-4 text-sm text-muted text-center">Showing top 50 {{ __('inventory.items') }} by {{ __('inventory.value') }}. {{ __('inventory.export') }} {{ __('inventory.reports') }} for complete data.</p>
                @endif
            @else
                <x-empty-state
                    title="{{ __('inventory.no_stock_records') }}"
                    description="{{ __('inventory.no_stock_description') }}"
                    icon="cube"
                />
            @endif
        </x-card>
    </div>
</x-app-layout>
