<x-app-layout :title="'QR Order ' . $order->order_number">
    <div class="max-w-3xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('qr-orders.index') }}" class="text-sm text-muted hover:text-foreground mb-2 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to QR Orders
                </a>
                <h1 class="text-2xl font-bold text-foreground">{{ $order->order_number }}</h1>
            </div>
            @php
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'waiting_payment' => 'bg-blue-100 text-blue-800',
                    'paid' => 'bg-green-100 text-green-800',
                    'pay_at_counter' => 'bg-orange-100 text-orange-800',
                    'processing' => 'bg-indigo-100 text-indigo-800',
                    'completed' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                    'expired' => 'bg-gray-100 text-gray-800',
                ];
            @endphp
            <span class="px-3 py-1.5 text-sm font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
            </span>
        </div>

        {{-- Order Info --}}
        <div class="bg-surface rounded-xl border border-border p-6">
            <h2 class="text-lg font-semibold text-foreground mb-4">Order Details</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-muted">Table</p>
                    <p class="font-medium text-foreground">{{ $order->table?->display_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-muted">Outlet</p>
                    <p class="font-medium text-foreground">{{ $order->outlet?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-muted">Customer</p>
                    <p class="font-medium text-foreground">{{ $order->customer_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-muted">Phone</p>
                    <p class="font-medium text-foreground">{{ $order->customer_phone ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-muted">Payment Method</p>
                    <p class="font-medium text-foreground">{{ $order->payment_method ? ucfirst(str_replace('_', ' ', $order->payment_method)) : '-' }}</p>
                </div>
                <div>
                    <p class="text-muted">Created</p>
                    <p class="font-medium text-foreground">{{ $order->created_at->format('d M Y H:i') }}</p>
                </div>
                @if($order->transaction_id)
                    <div>
                        <p class="text-muted">Transaction</p>
                        <a href="{{ route('transactions.show', $order->transaction_id) }}" class="font-medium text-primary hover:underline">
                            {{ $order->transaction?->transaction_number ?? $order->transaction_id }}
                        </a>
                    </div>
                @endif
                @if($order->notes)
                    <div class="col-span-2">
                        <p class="text-muted">Notes</p>
                        <p class="font-medium text-foreground">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Items --}}
        <div class="bg-surface rounded-xl border border-border p-6">
            <h2 class="text-lg font-semibold text-foreground mb-4">Items</h2>
            <table class="w-full">
                <thead>
                    <tr class="border-b border-border">
                        <th class="text-left text-xs font-medium text-muted uppercase pb-2">Item</th>
                        <th class="text-center text-xs font-medium text-muted uppercase pb-2">Qty</th>
                        <th class="text-right text-xs font-medium text-muted uppercase pb-2">Price</th>
                        <th class="text-right text-xs font-medium text-muted uppercase pb-2">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-3">
                                <p class="text-sm font-medium text-foreground">{{ $item->item_name }}</p>
                                @if($item->modifiers)
                                    <p class="text-xs text-muted">{{ collect($item->modifiers)->pluck('name')->join(', ') }}</p>
                                @endif
                                @if($item->item_notes)
                                    <p class="text-xs text-muted italic">{{ $item->item_notes }}</p>
                                @endif
                            </td>
                            <td class="py-3 text-center text-sm text-foreground">{{ $item->quantity }}</td>
                            <td class="py-3 text-right text-sm text-muted">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-3 text-right text-sm font-medium text-foreground">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="border-t border-border pt-3 mt-3 space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="text-muted">Subtotal</span>
                    <span class="text-foreground">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($order->tax_amount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-muted">Tax ({{ $order->tax_percentage }}%{{ $order->tax_mode === 'inclusive' ? ' incl.' : '' }})</span>
                        <span class="text-foreground">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($order->service_charge_amount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-muted">Service Charge ({{ $order->service_charge_percentage }}%)</span>
                        <span class="text-foreground">Rp {{ number_format($order->service_charge_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-base font-bold pt-2 border-t border-border">
                    <span class="text-foreground">Total</span>
                    <span class="text-foreground">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
