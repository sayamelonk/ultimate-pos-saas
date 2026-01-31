<x-app-layout>
    <x-slot name="title">Refund Transaction - Ultimate POS</x-slot>

    @section('page-title', 'Refund Transaction')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('transactions.show', $transaction) }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Refund Transaction</h2>
                <p class="text-muted mt-1">{{ $transaction->transaction_number }}</p>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Original Transaction -->
        <x-card title="Original Transaction">
            <x-table>
                <x-slot name="head">
                    <x-th>Item</x-th>
                    <x-th align="center">Qty</x-th>
                    <x-th align="right">Subtotal</x-th>
                </x-slot>

                @foreach($transaction->items as $item)
                    <tr>
                        <x-td>
                            <div>
                                <p class="font-medium text-text">{{ $item->item_name }}</p>
                                <p class="text-xs text-muted">{{ $item->item_sku }}</p>
                            </div>
                        </x-td>
                        <x-td align="center">
                            {{ number_format($item->quantity, 2) }} {{ $item->unit_name }}
                        </x-td>
                        <x-td align="right" class="font-medium">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4 pt-4 border-t border-border space-y-2">
                <div class="flex justify-between">
                    <span class="text-muted">Subtotal</span>
                    <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($transaction->discount_amount > 0)
                    <div class="flex justify-between text-danger">
                        <span>Discount</span>
                        <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($transaction->tax_amount > 0)
                    <div class="flex justify-between">
                        <span class="text-muted">Tax</span>
                        <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-lg font-bold pt-2 border-t border-border">
                    <span>Total</span>
                    <span>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </x-card>

        <!-- Refund Form -->
        <x-card title="Refund Details">
            <form method="POST" action="{{ route('transactions.refund.store', $transaction) }}" x-data="refundForm()">
                @csrf

                <div class="space-y-4">
                    <div class="p-4 bg-warning-50 border border-warning-200 rounded-lg">
                        <div class="flex items-center gap-2 text-warning-700">
                            <x-icon name="exclamation" class="w-5 h-5" />
                            <span class="font-medium">This action cannot be undone</span>
                        </div>
                        <p class="text-sm text-warning-600 mt-1">
                            Creating a refund will return the stock and reverse the transaction.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-2">Refund Type</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="refund_type" value="full" x-model="refundType" class="text-primary focus:ring-primary" checked>
                                <span>Full Refund (Rp {{ number_format($transaction->grand_total, 0, ',', '.') }})</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="refund_type" value="partial" x-model="refundType" class="text-primary focus:ring-primary">
                                <span>Partial Refund</span>
                            </label>
                        </div>
                    </div>

                    <template x-if="refundType === 'partial'">
                        <div class="space-y-4">
                            <p class="text-sm text-muted">Select items to refund:</p>
                            @foreach($transaction->items as $index => $item)
                                <div class="flex items-center justify-between p-3 border border-border rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            name="items[{{ $item->id }}][selected]"
                                            value="1"
                                            x-model="selectedItems['{{ $item->id }}']"
                                            class="text-primary focus:ring-primary rounded"
                                        >
                                        <div>
                                            <p class="font-medium">{{ $item->item_name }}</p>
                                            <p class="text-xs text-muted">Max: {{ number_format($item->quantity, 2) }}</p>
                                        </div>
                                    </div>
                                    <input
                                        type="number"
                                        name="items[{{ $item->id }}][quantity]"
                                        :disabled="!selectedItems['{{ $item->id }}']"
                                        min="0.01"
                                        max="{{ $item->quantity }}"
                                        step="0.01"
                                        value="{{ $item->quantity }}"
                                        class="w-24 px-3 py-1.5 text-right border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:bg-secondary-50 disabled:cursor-not-allowed"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </template>

                    <x-textarea
                        label="Reason for Refund"
                        name="reason"
                        rows="3"
                        placeholder="Please provide a reason for this refund..."
                        required
                    >{{ old('reason') }}</x-textarea>

                    <div class="pt-4 flex gap-3">
                        <x-button href="{{ route('transactions.show', $transaction) }}" variant="secondary" class="flex-1">
                            Cancel
                        </x-button>
                        <x-button type="submit" variant="danger" class="flex-1">
                            Process Refund
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>

    @push('scripts')
    <script>
        function refundForm() {
            return {
                refundType: 'full',
                selectedItems: {}
            };
        }
    </script>
    @endpush
</x-app-layout>
