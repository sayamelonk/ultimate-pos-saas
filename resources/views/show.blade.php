<x-app-layout>
    <x-slot name="title">Transaction {{ $transaction->transaction_number }} - Ultimate POS</x-slot>

    @section('page-title', 'Transaction Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('transactions.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">Transaction Details</h2>
                    <p class="text-muted mt-1">{{ $transaction->transaction_number }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button href="{{ route('pos.receipt', $transaction) }}" target="_blank" variant="secondary" icon="printer">
                    Print Receipt
                </x-button>
                @if($transaction->canRefund())
                    <x-button href="{{ route('transactions.refund', $transaction) }}" variant="warning">
                        Refund
                    </x-button>
                @endif
                @if($transaction->canVoid())
                    <button
                        type="button"
                        onclick="document.getElementById('voidModal').showModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-danger rounded-lg hover:bg-danger-600 transition-colors"
                    >
                        <x-icon name="x-circle" class="w-4 h-4" />
                        Void
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Transaction Items -->
            <x-card title="Items">
                <x-table>
                    <x-slot name="head">
                        <x-th>Item</x-th>
                        <x-th align="center">Qty</x-th>
                        <x-th align="right">Unit Price</x-th>
                        <x-th align="right">Discount</x-th>
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
                            <x-td align="right">
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </x-td>
                            <x-td align="right">
                                @if($item->discount_amount > 0)
                                    <span class="text-danger">-Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </x-td>
                            <x-td align="right" class="font-medium">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>

            <!-- Payment Information -->
            <x-card title="Payments">
                <x-table>
                    <x-slot name="head">
                        <x-th>Method</x-th>
                        <x-th>Reference</x-th>
                        <x-th align="right">Amount</x-th>
                        <x-th align="right">Charges</x-th>
                    </x-slot>

                    @foreach($transaction->payments as $payment)
                        <tr>
                            <x-td>
                                <span class="font-medium">{{ $payment->paymentMethod->name }}</span>
                            </x-td>
                            <x-td>
                                @if($payment->reference_number)
                                    <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $payment->reference_number }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </x-td>
                            <x-td align="right" class="font-medium">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </x-td>
                            <x-td align="right">
                                @if($payment->charge_amount > 0)
                                    <span class="text-muted">Rp {{ number_format($payment->charge_amount, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </x-td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>

            <!-- Applied Discounts -->
            @if($transaction->discounts->count() > 0)
                <x-card title="Applied Discounts">
                    <x-table>
                        <x-slot name="head">
                            <x-th>Discount</x-th>
                            <x-th>Type</x-th>
                            <x-th>Item</x-th>
                            <x-th align="right">Amount</x-th>
                        </x-slot>

                        @foreach($transaction->discounts as $discount)
                            <tr>
                                <x-td class="font-medium">{{ $discount->discount_name }}</x-td>
                                <x-td>
                                    <x-badge type="secondary">
                                        {{ $discount->type === 'percentage' ? $discount->value . '%' : 'Fixed' }}
                                    </x-badge>
                                </x-td>
                                <x-td>
                                    @if($discount->transactionItem)
                                        {{ $discount->transactionItem->item_name }}
                                    @else
                                        <span class="text-muted">Order Level</span>
                                    @endif
                                </x-td>
                                <x-td align="right" class="font-medium text-danger">
                                    -Rp {{ number_format($discount->amount, 0, ',', '.') }}
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Transaction Summary -->
            <x-card title="Summary">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted">Status</span>
                        @php
                            $statusColors = [
                                'completed' => 'success',
                                'pending' => 'warning',
                                'voided' => 'danger',
                            ];
                        @endphp
                        <x-badge type="{{ $statusColors[$transaction->status] ?? 'secondary' }}" dot>
                            {{ ucfirst($transaction->status) }}
                        </x-badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Type</span>
                        @php
                            $typeColors = [
                                'sale' => 'success',
                                'refund' => 'warning',
                                'void' => 'danger',
                            ];
                        @endphp
                        <x-badge type="{{ $typeColors[$transaction->type] ?? 'secondary' }}">
                            {{ ucfirst($transaction->type) }}
                        </x-badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Date</span>
                        <span class="font-medium">{{ $transaction->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Time</span>
                        <span class="font-medium">{{ $transaction->created_at->format('H:i:s') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Outlet</span>
                        <span class="font-medium">{{ $transaction->outlet->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Cashier</span>
                        <span class="font-medium">{{ $transaction->user->name }}</span>
                    </div>
                    @if($transaction->posSession)
                        <div class="flex justify-between">
                            <span class="text-muted">Session</span>
                            <a href="{{ route('pos.sessions.report', $transaction->posSession) }}" class="text-primary hover:underline">
                                {{ $transaction->posSession->session_number }}
                            </a>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Customer Info -->
            @if($transaction->customer)
                <x-card title="Customer">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold text-lg">
                            {{ strtoupper(substr($transaction->customer->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-medium">{{ $transaction->customer->name }}</p>
                            <p class="text-sm text-muted">{{ ucfirst($transaction->customer->membership_level) }}</p>
                        </div>
                    </div>
                    <dl class="space-y-2 text-sm">
                        @if($transaction->customer->phone)
                            <div class="flex justify-between">
                                <span class="text-muted">Phone</span>
                                <span>{{ $transaction->customer->phone }}</span>
                            </div>
                        @endif
                        @if($transaction->customer->email)
                            <div class="flex justify-between">
                                <span class="text-muted">Email</span>
                                <span>{{ $transaction->customer->email }}</span>
                            </div>
                        @endif
                        @if($transaction->points_earned > 0)
                            <div class="flex justify-between">
                                <span class="text-muted">Points Earned</span>
                                <span class="text-success font-medium">+{{ number_format($transaction->points_earned) }}</span>
                            </div>
                        @endif
                        @if($transaction->points_redeemed > 0)
                            <div class="flex justify-between">
                                <span class="text-muted">Points Redeemed</span>
                                <span class="text-danger font-medium">-{{ number_format($transaction->points_redeemed) }}</span>
                            </div>
                        @endif
                    </dl>
                </x-card>
            @endif

            <!-- Totals -->
            <x-card title="Totals">
                <dl class="space-y-3">
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
                            <span class="text-muted">Tax ({{ $transaction->tax_percentage }}%)</span>
                            <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($transaction->service_charge_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-muted">Service ({{ $transaction->service_charge_percentage }}%)</span>
                            <span>Rp {{ number_format($transaction->service_charge_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($transaction->rounding != 0)
                        <div class="flex justify-between">
                            <span class="text-muted">Rounding</span>
                            <span>Rp {{ number_format($transaction->rounding, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-border">
                        <span>Grand Total</span>
                        <span class="text-primary">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Payment</span>
                        <span>Rp {{ number_format($transaction->payment_amount, 0, ',', '.') }}</span>
                    </div>
                    @if($transaction->change_amount > 0)
                        <div class="flex justify-between text-success">
                            <span>Change</span>
                            <span class="font-medium">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Notes -->
            @if($transaction->notes)
                <x-card title="Notes">
                    <p class="text-muted">{{ $transaction->notes }}</p>
                </x-card>
            @endif

            <!-- Original Transaction (for refunds) -->
            @if($transaction->originalTransaction)
                <x-card title="Original Transaction">
                    <a href="{{ route('transactions.show', $transaction->originalTransaction) }}" class="text-primary hover:underline">
                        {{ $transaction->originalTransaction->transaction_number }}
                    </a>
                </x-card>
            @endif

            <!-- Refund Transactions -->
            @if($transaction->refundTransactions->count() > 0)
                <x-card title="Refund History">
                    <div class="space-y-2">
                        @foreach($transaction->refundTransactions as $refund)
                            <a href="{{ route('transactions.show', $refund) }}" class="block p-3 border border-border rounded-lg hover:border-primary/50 transition-colors">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-text">{{ $refund->transaction_number }}</p>
                                        <p class="text-xs text-muted">{{ $refund->created_at->format('d M Y H:i') }}</p>
                                    </div>
                                    <span class="text-danger font-medium">-Rp {{ number_format($refund->grand_total, 0, ',', '.') }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>
    </div>

    <!-- Void Modal with PIN Authorization -->
    @if($transaction->canVoid())
    <div x-data="voidTransaction()" x-cloak>
        <!-- Step 1: Reason Modal -->
        <dialog id="voidModal" class="rounded-xl p-0 backdrop:bg-black/50 max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-text">Void Transaction</h3>
                    <button type="button" onclick="document.getElementById('voidModal').close()" class="text-muted hover:text-text">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 bg-danger-50 border border-danger-200 rounded-lg mb-4">
                    <div class="flex items-center gap-2 text-danger-700">
                        <x-icon name="exclamation-triangle" class="w-5 h-5" />
                        <span class="font-medium">This action cannot be undone</span>
                    </div>
                    <p class="text-sm text-danger-600 mt-1">
                        Voiding this transaction will mark it as void and return stock to inventory.
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-text mb-2">Reason for Void</label>
                    <textarea
                        x-model="reason"
                        rows="3"
                        required
                        placeholder="Please provide a reason for voiding this transaction..."
                        class="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                    ></textarea>
                </div>

                <div class="flex gap-3">
                    <button
                        type="button"
                        onclick="document.getElementById('voidModal').close()"
                        class="flex-1 px-4 py-2.5 bg-secondary-100 text-secondary-700 font-medium rounded-lg hover:bg-secondary-200"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="proceedToAuthorization()"
                        :disabled="!reason.trim()"
                        class="flex-1 px-4 py-2.5 bg-danger text-white font-medium rounded-lg hover:bg-danger-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Continue
                    </button>
                </div>
            </div>
        </dialog>

        <!-- Hidden form for final submission -->
        <form id="voidForm" method="POST" action="{{ route('transactions.void', $transaction) }}" class="hidden">
            @csrf
            <input type="hidden" name="reason" id="voidReasonInput">
            <input type="hidden" name="authorization_log_id" id="voidAuthLogInput">
        </form>
    </div>

    <!-- PIN Authorization Modal -->
    <x-pin-modal id="void-pin-modal" title="Authorization Required" />

    <script>
    function voidTransaction() {
        return {
            reason: '',
            authorizationLogId: null,
            requiresAuth: @json($requiresVoidAuth ?? true),

            submitVoidForm() {
                // Set values to hidden form before submit
                document.getElementById('voidReasonInput').value = this.reason;
                document.getElementById('voidAuthLogInput').value = this.authorizationLogId || '';
                document.getElementById('voidForm').submit();
            },

            async proceedToAuthorization() {
                if (!this.reason.trim()) return;

                document.getElementById('voidModal').close();

                if (!this.requiresAuth) {
                    // No authorization required, submit directly
                    this.submitVoidForm();
                    return;
                }

                // Open PIN modal for authorization
                const self = this;
                window.dispatchEvent(new CustomEvent('open-pin-modal', {
                    detail: {
                        id: 'void-pin-modal',
                        title: 'Void Authorization',
                        subtitle: 'Enter supervisor PIN to void this transaction',
                        action: 'void',
                        outletId: '{{ $transaction->outlet_id }}',
                        referenceType: 'transaction',
                        referenceId: '{{ $transaction->id }}',
                        referenceNumber: '{{ $transaction->transaction_number }}',
                        amount: {{ $transaction->grand_total }},
                        reason: this.reason,
                        onSuccess: (data) => {
                            self.authorizationLogId = data.authorization_log_id;
                            setTimeout(() => {
                                self.submitVoidForm();
                            }, 100);
                        },
                        onCancel: () => {
                            // Reopen reason modal if cancelled
                            document.getElementById('voidModal').showModal();
                        }
                    }
                }));
            }
        }
    }
    </script>
    @endif
</x-app-layout>
