<x-app-layout>
    <x-slot name="title">{{ __('transactions.refund_transaction') }} - Ultimate POS</x-slot>

    @section('page-title', __('transactions.refund_transaction'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('transactions.show', $transaction) }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('transactions.refund_transaction') }}</h2>
                <p class="text-muted mt-1">{{ $transaction->transaction_number }}</p>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Original Transaction -->
        <x-card :title="__('transactions.original_transaction_title')">
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('transactions.item') }}</x-th>
                    <x-th align="center">{{ __('transactions.qty') }}</x-th>
                    <x-th align="right">{{ __('transactions.subtotal') }}</x-th>
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
                    <span class="text-muted">{{ __('transactions.subtotal') }}</span>
                    <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($transaction->discount_amount > 0)
                    <div class="flex justify-between text-danger">
                        <span>{{ __('transactions.discount') }}</span>
                        <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($transaction->tax_amount > 0)
                    <div class="flex justify-between">
                        <span class="text-muted">{{ __('transactions.tax') }}</span>
                        <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-lg font-bold pt-2 border-t border-border">
                    <span>{{ __('transactions.total') }}</span>
                    <span>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </x-card>

        <!-- Refund Form -->
        <x-card :title="__('transactions.refund_details')">
            <form method="POST" action="{{ route('transactions.refund.store', $transaction) }}" x-data="refundForm()" id="refundForm" @submit.prevent="submitForm()">
                @csrf
                <input type="hidden" name="authorization_log_id" x-model="authorizationLogId">

                <div class="space-y-4">
                    <div class="p-4 bg-warning-50 border border-warning-200 rounded-lg">
                        <div class="flex items-center gap-2 text-warning-700">
                            <x-icon name="exclamation" class="w-5 h-5" />
                            <span class="font-medium">{{ __('transactions.refund_warning') }}</span>
                        </div>
                        <p class="text-sm text-warning-600 mt-1">
                            {{ __('transactions.refund_description') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-2">{{ __('transactions.refund_type') }}</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="refund_type" value="full" x-model="refundType" class="text-primary focus:ring-primary" checked>
                                <span>{{ __('transactions.full_refund') }} (Rp {{ number_format($transaction->grand_total, 0, ',', '.') }})</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="refund_type" value="partial" x-model="refundType" class="text-primary focus:ring-primary">
                                <span>{{ __('transactions.partial_refund') }}</span>
                            </label>
                        </div>
                    </div>

                    <template x-if="refundType === 'partial'">
                        <div class="space-y-4">
                            <p class="text-sm text-muted">{{ __('transactions.select_items_to_refund') }}</p>
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
                                            <p class="text-xs text-muted">{{ __('transactions.max') }}: {{ number_format($item->quantity, 2) }}</p>
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

                    <div>
                        <label class="block text-sm font-medium text-text mb-2">{{ __('transactions.refund_payment_method') }}</label>
                        <select name="payment_method_id" x-model="paymentMethodId" required class="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">{{ __('transactions.select_payment_method') }}</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-textarea
                        :label="__('transactions.reason_for_refund')"
                        name="reason"
                        x-model="reason"
                        rows="3"
                        :placeholder="__('transactions.refund_reason_placeholder')"
                        required
                    >{{ old('reason') }}</x-textarea>

                    <!-- Refund Summary -->
                    <div class="p-4 bg-secondary-50 rounded-lg space-y-2">
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('transactions.refund_amount') }}</span>
                            <span class="font-bold text-lg text-danger" x-text="'Rp ' + formatNumber(refundAmount)"></span>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <x-button href="{{ route('transactions.show', $transaction) }}" variant="secondary" class="flex-1">
                            {{ __('transactions.cancel') }}
                        </x-button>
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-danger text-white font-medium rounded-lg hover:bg-danger-600">
                            {{ __('transactions.process_refund') }}
                        </button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>

    <!-- PIN Authorization Modal -->
    <x-pin-modal id="refund-pin-modal" :title="__('transactions.authorization_required')" />

    @push('scripts')
    <script>
        function refundForm() {
            return {
                refundType: 'full',
                selectedItems: {},
                itemPrices: @json($transaction->items->pluck('subtotal', 'id')),
                totalAmount: {{ $transaction->grand_total }},
                authorizationLogId: null,
                paymentMethodId: '',
                reason: '',
                requiresAuth: @json($requiresRefundAuth ?? true),

                get refundAmount() {
                    if (this.refundType === 'full') {
                        return this.totalAmount;
                    }

                    let total = 0;
                    for (const [id, selected] of Object.entries(this.selectedItems)) {
                        if (selected && this.itemPrices[id]) {
                            total += this.itemPrices[id];
                        }
                    }
                    return total;
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num);
                },

                submitForm() {
                    // Validate form
                    if (!this.paymentMethodId) {
                        alert('{{ __('transactions.select_payment_method') }}');
                        return;
                    }
                    if (!this.reason.trim()) {
                        alert('{{ __('transactions.refund_reason_placeholder') }}');
                        return;
                    }

                    if (!this.requiresAuth) {
                        // No authorization required, submit directly
                        document.getElementById('refundForm').submit();
                        return;
                    }

                    // Open PIN modal for authorization
                    window.dispatchEvent(new CustomEvent('open-pin-modal', {
                        detail: {
                            id: 'refund-pin-modal',
                            title: '{{ __('transactions.refund_authorization') }}',
                            subtitle: '{{ __('transactions.refund_pin_subtitle') }}',
                            action: 'refund',
                            outletId: '{{ $transaction->outlet_id }}',
                            referenceType: 'transaction',
                            referenceId: '{{ $transaction->id }}',
                            referenceNumber: '{{ $transaction->transaction_number }}',
                            amount: this.refundAmount,
                            reason: this.reason,
                            onSuccess: (data) => {
                                this.authorizationLogId = data.authorization_log_id;
                                this.$nextTick(() => {
                                    document.getElementById('refundForm').submit();
                                });
                            },
                            onCancel: () => {
                                // Do nothing, user can retry
                            }
                        }
                    }));
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
