<x-qr-menu-layout :title="'Order ' . $order->order_number">
    <div x-data="orderStatusApp()" class="max-w-lg mx-auto min-h-screen p-4">
        {{-- Header --}}
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto bg-primary/10 rounded-full flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">{{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">{{ $order->outlet->name }} - {{ $order->table->display_name }}</p>
        </div>

        {{-- Status Badge --}}
        <div class="text-center mb-6">
            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold"
                  :class="statusClass">
                <span class="w-2 h-2 rounded-full" :class="statusDotClass"></span>
                <span x-text="statusLabel"></span>
            </span>
            <p class="text-xs text-gray-500 mt-2" x-show="status === 'waiting_payment'">
                Waiting for payment confirmation...
            </p>
            <p class="text-xs text-gray-500 mt-2" x-show="status === 'pay_at_counter'">
                Please pay at the counter. Your order is being prepared.
            </p>
            <p class="text-xs text-gray-500 mt-2" x-show="status === 'processing'">
                Your order is being prepared in the kitchen.
            </p>
            <p class="text-xs text-gray-500 mt-2" x-show="status === 'completed'">
                Your order is ready!
            </p>
        </div>

        {{-- Xendit Payment Link --}}
        @if($order->xendit_invoice_url && $order->status === 'waiting_payment')
            <div class="mb-6">
                <a href="{{ $order->xendit_invoice_url }}" target="_blank"
                   class="block w-full py-3 bg-primary text-white font-semibold rounded-xl text-center">
                    Complete Payment
                </a>
            </div>
        @endif

        {{-- Order Items --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
            <h3 class="font-semibold text-gray-900 mb-3">Order Items</h3>
            <div class="space-y-2">
                @foreach($order->items as $item)
                    <div class="flex items-start justify-between py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $item->item_name }}</p>
                            @if($item->modifiers)
                                <p class="text-xs text-gray-500">
                                    {{ collect($item->modifiers)->pluck('name')->join(', ') }}
                                </p>
                            @endif
                            @if($item->item_notes)
                                <p class="text-xs text-gray-400">Note: {{ $item->item_notes }}</p>
                            @endif
                            <p class="text-xs text-gray-500">x{{ $item->quantity }}</p>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Totals --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
            <div class="space-y-1">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($order->tax_amount > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Tax ({{ $order->tax_percentage }}%{{ $order->tax_mode === 'inclusive' ? ' incl.' : '' }})</span>
                        <span>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($order->service_charge_amount > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Service Charge ({{ $order->service_charge_percentage }}%)</span>
                        <span>Rp {{ number_format($order->service_charge_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-200">
                    <span>Total</span>
                    <span>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Customer Info --}}
        @if($order->customer_name || $order->customer_phone)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
                @if($order->customer_name)
                    <p class="text-sm text-gray-600"><span class="font-medium">Name:</span> {{ $order->customer_name }}</p>
                @endif
                @if($order->customer_phone)
                    <p class="text-sm text-gray-600"><span class="font-medium">Phone:</span> {{ $order->customer_phone }}</p>
                @endif
            </div>
        @endif

        {{-- Back to Menu --}}
        @if($order->table?->qr_token)
            <div class="text-center mt-6">
                <a href="{{ route('qr-menu.show', $order->table->qr_token) }}"
                   class="text-primary font-medium text-sm hover:underline">
                    Order Again
                </a>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function orderStatusApp() {
            return {
                status: '{{ $order->status }}',
                pollInterval: null,

                init() {
                    // Poll for status updates every 5 seconds
                    if (['pending', 'waiting_payment', 'pay_at_counter', 'paid', 'processing'].includes(this.status)) {
                        this.pollInterval = setInterval(() => this.pollStatus(), 5000);
                    }
                },

                destroy() {
                    if (this.pollInterval) clearInterval(this.pollInterval);
                },

                async pollStatus() {
                    try {
                        const response = await fetch('{{ route("qr-menu.order-status-json", $order->id) }}');
                        const data = await response.json();
                        if (data.success && data.data.status !== this.status) {
                            this.status = data.data.status;
                            // Stop polling when order reaches terminal state
                            if (['completed', 'cancelled', 'expired'].includes(this.status)) {
                                clearInterval(this.pollInterval);
                            }
                        }
                    } catch (e) {
                        // Silently fail
                    }
                },

                get statusLabel() {
                    const labels = {
                        pending: 'Pending',
                        waiting_payment: 'Waiting Payment',
                        paid: 'Paid',
                        pay_at_counter: 'Pay at Counter',
                        processing: 'Being Prepared',
                        completed: 'Completed',
                        cancelled: 'Cancelled',
                        expired: 'Expired'
                    };
                    return labels[this.status] || this.status;
                },

                get statusClass() {
                    const classes = {
                        pending: 'bg-yellow-100 text-yellow-800',
                        waiting_payment: 'bg-blue-100 text-blue-800',
                        paid: 'bg-green-100 text-green-800',
                        pay_at_counter: 'bg-orange-100 text-orange-800',
                        processing: 'bg-indigo-100 text-indigo-800',
                        completed: 'bg-green-100 text-green-800',
                        cancelled: 'bg-red-100 text-red-800',
                        expired: 'bg-gray-100 text-gray-800'
                    };
                    return classes[this.status] || 'bg-gray-100 text-gray-800';
                },

                get statusDotClass() {
                    const classes = {
                        pending: 'bg-yellow-500',
                        waiting_payment: 'bg-blue-500 animate-pulse',
                        paid: 'bg-green-500',
                        pay_at_counter: 'bg-orange-500 animate-pulse',
                        processing: 'bg-indigo-500 animate-pulse',
                        completed: 'bg-green-500',
                        cancelled: 'bg-red-500',
                        expired: 'bg-gray-500'
                    };
                    return classes[this.status] || 'bg-gray-500';
                }
            };
        }
    </script>
    @endpush
</x-qr-menu-layout>
