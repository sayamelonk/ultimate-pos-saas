<x-app-layout>
    <x-slot name="title">Bulk Edit Prices - Ultimate POS</x-slot>

    @section('page-title', 'Bulk Edit Prices')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('pricing.prices.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Bulk Edit Prices</h2>
                <p class="text-muted mt-1">Update multiple item prices at once</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('pricing.prices.bulk-update') }}">
        @csrf

        <x-card>
            <div class="mb-6 flex items-end gap-4">
                <div>
                    <x-select label="Outlet" name="outlet_id" required>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($selectedOutletId === $outlet->id)>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <x-button type="submit">Save All Prices</x-button>
            </div>

            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th>SKU</x-th>
                    <x-th align="right">Cost Price</x-th>
                    <x-th align="right">Selling Price</x-th>
                    <x-th align="right">Member Price</x-th>
                </x-slot>

                @foreach($items as $item)
                    <tr>
                        <x-td>
                            <div>
                                <p class="font-medium text-text">{{ $item->name }}</p>
                                <p class="text-xs text-muted">{{ $item->category?->name }}</p>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $item->sku }}</code>
                        </x-td>
                        <x-td align="right">
                            <span class="text-muted">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</span>
                        </x-td>
                        <x-td align="right">
                            <input
                                type="number"
                                name="prices[{{ $item->id }}][selling_price]"
                                value="{{ $prices[$item->id] ?? '' }}"
                                class="w-32 px-3 py-1.5 text-right border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="0"
                            />
                        </x-td>
                        <x-td align="right">
                            <input
                                type="number"
                                name="prices[{{ $item->id }}][member_price]"
                                value="{{ $memberPrices[$item->id] ?? '' }}"
                                class="w-32 px-3 py-1.5 text-right border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Optional"
                            />
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6 flex justify-end">
                <x-button type="submit">Save All Prices</x-button>
            </div>
        </x-card>
    </form>
</x-app-layout>
