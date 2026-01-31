<x-app-layout>
    <x-slot name="title">Prices - Ultimate POS</x-slot>

    @section('page-title', 'Price Management')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Price Management</h2>
                <p class="text-muted mt-1">Set selling prices per outlet</p>
            </div>
            <x-button href="{{ route('pricing.prices.bulk-edit', ['outlet_id' => $selectedOutletId]) }}" variant="secondary" icon="pencil">
                Bulk Edit
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('pricing.prices.index') }}" class="flex flex-wrap gap-4">
                <x-select name="outlet_id" class="w-48" onchange="this.form.submit()">
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected($selectedOutletId === $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search items..."
                        :value="request('search')"
                    />
                </div>
                <x-button type="submit" variant="secondary">Filter</x-button>
            </form>
        </div>

        @if($items->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th>Category</x-th>
                    <x-th align="right">Cost Price</x-th>
                    <x-th align="right">Selling Price</x-th>
                    <x-th align="right">Member Price</x-th>
                    <x-th align="center">Margin</x-th>
                </x-slot>

                @foreach($items as $item)
                    @php
                        $sellingPrice = $prices[$item->id] ?? null;
                        $memberPrice = $memberPrices[$item->id] ?? null;
                        $margin = $sellingPrice && $item->cost_price > 0
                            ? (($sellingPrice - $item->cost_price) / $item->cost_price) * 100
                            : null;
                    @endphp
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="cube" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $item->name }}</p>
                                    <p class="text-xs text-muted">{{ $item->unit?->name ?? 'pcs' }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->sku }}</code>
                        </x-td>
                        <x-td>{{ $item->category?->name ?? '-' }}</x-td>
                        <x-td align="right">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</x-td>
                        <x-td align="right">
                            @if($sellingPrice)
                                <span class="font-medium">Rp {{ number_format($sellingPrice, 0, ',', '.') }}</span>
                            @else
                                <span class="text-warning">Not set</span>
                            @endif
                        </x-td>
                        <x-td align="right">
                            @if($memberPrice)
                                Rp {{ number_format($memberPrice, 0, ',', '.') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                        <x-td align="center">
                            @if($margin !== null)
                                <x-badge type="{{ $margin >= 20 ? 'success' : ($margin >= 10 ? 'warning' : 'danger') }}">
                                    {{ number_format($margin, 1) }}%
                                </x-badge>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$items" />
            </div>
        @else
            <x-empty-state
                title="No items found"
                description="No inventory items available for pricing."
                icon="cube"
            />
        @endif
    </x-card>
</x-app-layout>
