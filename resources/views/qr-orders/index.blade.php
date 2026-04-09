<x-app-layout :title="__('QR Orders')">
    <div x-data="qrOrdersDashboard()" class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-foreground">QR Orders</h1>
            <span class="text-sm text-muted">Auto-refresh: <span x-text="refreshCountdown + 's'"></span></span>
        </div>

        {{-- Table QR Management --}}
        <div>
            <h2 class="text-lg font-semibold text-foreground mb-3">Table QR Codes</h2>
            <div class="bg-surface rounded-xl border border-border overflow-x-auto">
                <table class="w-full min-w-[600px]">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Table</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase w-28">QR Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Generated At</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted uppercase w-64">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($tables as $table)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50" x-data="{ loading: false }">
                                <td class="px-4 py-3 text-sm font-medium text-foreground">{{ $table->display_name }}</td>
                                <td class="px-4 py-3">
                                    @if($table->hasQrCode())
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">No QR</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-muted">
                                    {{ $table->qr_generated_at ? \Carbon\Carbon::parse($table->qr_generated_at)->format('d M Y H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($table->hasQrCode())
                                            <a href="{{ route('qr-orders.download-qr', $table) }}"
                                               style="display:inline-block;padding:6px 12px;background:#6366f1;color:#fff;font-size:12px;font-weight:500;border-radius:6px;text-decoration:none;">
                                                Download
                                            </a>
                                            <a href="{{ route('qr-orders.print-qr', $table) }}" target="_blank"
                                               style="display:inline-block;padding:6px 12px;background:#fff;color:#333;font-size:12px;font-weight:500;border-radius:6px;border:1px solid #ddd;text-decoration:none;">
                                                Print
                                            </a>
                                            <button @click="if(confirm('Revoke QR code for this table?')) { revokeQr('{{ route('qr-orders.revoke-qr', $table) }}') }"
                                                    style="padding:6px 12px;background:#dc2626;color:#fff;font-size:12px;font-weight:500;border-radius:6px;border:none;cursor:pointer;">
                                                Revoke
                                            </button>
                                        @else
                                            <button @click="generateQr('{{ route('qr-orders.generate-qr', $table) }}')"
                                                    :disabled="loading"
                                                    style="padding:6px 12px;background:#16a34a;color:#fff;font-size:12px;font-weight:500;border-radius:6px;border:none;cursor:pointer;">
                                                <span x-show="!loading">Generate QR</span>
                                                <span x-show="loading">Generating...</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-muted">
                                    No tables found. Please create tables in your outlet settings first.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Active Orders --}}
        <div>
            <h2 class="text-lg font-semibold text-foreground mb-3">
                Active Orders
                <span class="text-sm font-normal text-muted">(<span x-text="activeOrders.length">{{ $activeOrders->count() }}</span>)</span>
            </h2>

            @if($activeOrders->count() === 0)
                <div class="bg-surface rounded-xl border border-border p-8 text-center">
                    <p class="text-muted">No active QR orders</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($activeOrders as $order)
                        <div class="bg-surface rounded-xl border border-border p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="font-semibold text-foreground">{{ $order->order_number }}</p>
                                    <p class="text-sm text-muted">{{ $order->table?->display_name ?? '-' }}</p>
                                </div>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'waiting_payment' => 'bg-blue-100 text-blue-800',
                                        'paid' => 'bg-green-100 text-green-800',
                                        'pay_at_counter' => 'bg-orange-100 text-orange-800',
                                        'processing' => 'bg-indigo-100 text-indigo-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </div>

                            <div class="text-sm text-muted mb-2">
                                @foreach($order->items->take(3) as $item)
                                    <p>{{ $item->quantity }}x {{ $item->item_name }}</p>
                                @endforeach
                                @if($order->items->count() > 3)
                                    <p class="text-xs">+{{ $order->items->count() - 3 }} more items</p>
                                @endif
                            </div>

                            @if($order->customer_name)
                                <p class="text-xs text-muted mb-2">Customer: {{ $order->customer_name }}</p>
                            @endif

                            <div class="flex items-center justify-between pt-3 border-t border-border">
                                <span class="font-bold text-foreground">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                                <div class="flex gap-2">
                                    @if(in_array($order->status, ['pending', 'pay_at_counter']))
                                        <button @click="approveOrder('{{ $order->id }}')"
                                                style="padding:6px 12px;background:#16a34a;color:#fff;font-size:12px;font-weight:500;border-radius:6px;border:none;cursor:pointer;">
                                            Terima & Proses
                                        </button>
                                    @endif
                                    @if($order->canCancel())
                                        <button @click="cancelOrder('{{ $order->id }}')"
                                                style="padding:6px 12px;background:#dc2626;color:#fff;font-size:12px;font-weight:500;border-radius:6px;border:none;cursor:pointer;">
                                            Cancel
                                        </button>
                                    @endif
                                    <a href="{{ route('qr-orders.show', $order) }}"
                                       class="px-3 py-1.5 bg-surface border border-border text-foreground text-xs font-medium rounded-lg hover:bg-gray-50">
                                        Detail
                                    </a>
                                </div>
                            </div>

                            <p class="text-xs text-muted mt-2">{{ $order->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Completed / Cancelled Orders --}}
        <div>
            <h2 class="text-lg font-semibold text-foreground mb-3">History</h2>
            <div class="bg-surface rounded-xl border border-border overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Order #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Table</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted uppercase">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($completedOrders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('qr-orders.show', $order) }}" class="text-sm font-medium text-primary hover:underline">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted">{{ $order->table?->display_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted">{{ $order->customer_name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $historyColors = [
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            'expired' => 'bg-gray-100 text-gray-800',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $historyColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-foreground text-right">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-muted">{{ $order->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-muted">No order history</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $completedOrders->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function qrOrdersDashboard() {
            return {
                activeOrders: @json($activeOrders),
                refreshCountdown: 15,
                interval: null,

                init() {
                    this.interval = setInterval(() => {
                        this.refreshCountdown--;
                        if (this.refreshCountdown <= 0) {
                            this.refreshCountdown = 15;
                            this.pollPending();
                        }
                    }, 1000);
                },

                destroy() {
                    if (this.interval) clearInterval(this.interval);
                },

                async approveOrder(orderId) {
                    if (!confirm('Terima pembayaran dan kirim ke kitchen?')) return;
                    try {
                        const res = await fetch(`/qr-orders/${orderId}/approve`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    } catch (e) {
                        alert('Failed to approve order');
                    }
                },

                async revokeQr(url) {
                    try {
                        await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        window.location.reload();
                    } catch (e) {
                        alert('Failed to revoke QR');
                    }
                },

                async generateQr(url) {
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Failed to generate QR');
                        }
                    } catch (e) {
                        alert('Failed to generate QR');
                    }
                },

                async pollPending() {
                    try {
                        const res = await fetch('{{ route("qr-orders.poll-pending") }}', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.activeOrders = data.data;
                            if (data.count > {{ $activeOrders->count() }}) {
                                // New order arrived - could play notification sound
                            }
                        }
                    } catch (e) {}
                },

                async completeOrder(orderId) {
                    if (!confirm('Complete this order and create transaction?')) return;
                    try {
                        const res = await fetch(`/qr-orders/${orderId}/complete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    } catch (e) {
                        alert('Failed to complete order');
                    }
                },

                async cancelOrder(orderId) {
                    if (!confirm('Cancel this order?')) return;
                    try {
                        const res = await fetch(`/qr-orders/${orderId}/cancel`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    } catch (e) {
                        alert('Failed to cancel order');
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
