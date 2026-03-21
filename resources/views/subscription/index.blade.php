<x-app-layout>
    <x-slot name="title">Langganan Saya - Ultimate POS</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Langganan Saya</h2>
                <p class="text-muted mt-1">Kelola langganan dan lihat riwayat pembayaran</p>
            </div>
            <a href="{{ route('subscription.plans') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                <x-icon name="arrow-path" class="w-5 h-5" />
                Upgrade Paket
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 p-4 bg-success/10 border border-success/20 rounded-lg text-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-danger/10 border border-danger/20 rounded-lg text-danger">
            {{ session('error') }}
        </div>
    @endif

    <!-- Current Subscription -->
    <div class="bg-surface rounded-xl border border-border p-6 shadow-sm mb-8">
        <h3 class="text-lg font-semibold text-text mb-4">Status Langganan</h3>

        @if($currentSubscription)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm text-muted">Paket</p>
                    <p class="text-lg font-semibold text-text">{{ $currentSubscription->plan->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted">Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $currentSubscription->status === 'active' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }}">
                        {{ ucfirst($currentSubscription->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-muted">Periode</p>
                    <p class="text-lg font-semibold text-text">{{ ucfirst($currentSubscription->billing_cycle) }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted">Berakhir</p>
                    <p class="text-lg font-semibold text-text">
                        {{ $currentSubscription->ends_at?->format('d M Y') ?? '-' }}
                    </p>
                    @if($currentSubscription->daysRemaining() <= 14 && $currentSubscription->daysRemaining() > 0)
                        <p class="text-sm text-warning">{{ $currentSubscription->daysRemaining() }} hari lagi</p>
                    @endif
                </div>
            </div>

            <!-- Features -->
            <div class="mt-6 pt-6 border-t border-border">
                <p class="text-sm text-muted mb-3">Fitur yang termasuk:</p>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <x-icon name="check-circle" class="w-5 h-5 text-success" />
                        <span class="text-sm text-text">
                            {{ $currentSubscription->plan->max_outlets === -1 ? 'Unlimited' : $currentSubscription->plan->max_outlets }} Outlet
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-icon name="check-circle" class="w-5 h-5 text-success" />
                        <span class="text-sm text-text">
                            {{ $currentSubscription->plan->max_users === -1 ? 'Unlimited' : $currentSubscription->plan->max_users }} User
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 pt-6 border-t border-border flex flex-wrap gap-3">
                <form action="{{ route('subscription.renew') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                        <x-icon name="arrow-path" class="w-5 h-5" />
                        Perpanjang Langganan
                    </button>
                </form>

                <button type="button"
                        onclick="document.getElementById('cancel-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-danger text-danger rounded-lg hover:bg-danger/5 transition-colors">
                    <x-icon name="x-circle" class="w-5 h-5" />
                    Batalkan Langganan
                </button>
            </div>
        @else
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-warning/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-icon name="exclamation-triangle" class="w-8 h-8 text-warning" />
                </div>
                <p class="text-lg font-medium text-text mb-2">Belum ada langganan aktif</p>
                <p class="text-muted mb-4">Pilih paket untuk mulai menggunakan semua fitur</p>
                <a href="{{ route('subscription.plans') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                    Lihat Paket
                </a>
            </div>
        @endif
    </div>

    <!-- Invoice History -->
    <div class="bg-surface rounded-xl border border-border shadow-sm">
        <div class="p-6 border-b border-border">
            <h3 class="text-lg font-semibold text-text">Riwayat Pembayaran</h3>
        </div>

        @if($invoices && $invoices->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase">No. Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Paket</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-text">
                                    {{ $invoice->invoice_number }}
                                </td>
                                <td class="px-6 py-4 text-sm text-text">
                                    {{ $invoice->plan->name }}
                                    <span class="text-muted">({{ ucfirst($invoice->billing_cycle) }})</span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-text">
                                    {{ $invoice->getFormattedAmount() }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($invoice->status === 'paid') bg-success/10 text-success
                                        @elseif($invoice->status === 'pending') bg-warning/10 text-warning
                                        @elseif($invoice->status === 'expired') bg-secondary-100 text-muted
                                        @else bg-danger/10 text-danger
                                        @endif">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-muted">
                                    {{ $invoice->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($invoice->status === 'pending' && $invoice->xendit_invoice_url)
                                        <a href="{{ $invoice->xendit_invoice_url }}" target="_blank"
                                           class="text-primary hover:underline text-sm">
                                            Bayar
                                        </a>
                                    @elseif($invoice->status === 'paid')
                                        <a href="{{ route('subscription.invoice', $invoice) }}"
                                           class="text-primary hover:underline text-sm">
                                            Lihat
                                        </a>
                                    @else
                                        <span class="text-muted text-sm">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-border">
                {{ $invoices->links() }}
            </div>
        @else
            <div class="p-8 text-center">
                <p class="text-muted">Belum ada riwayat pembayaran</p>
            </div>
        @endif
    </div>

    <!-- Cancel Modal -->
    <div id="cancel-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-surface rounded-xl p-6 max-w-md w-full mx-4 shadow-xl">
            <h3 class="text-lg font-semibold text-text mb-4">Batalkan Langganan?</h3>
            <p class="text-muted mb-4">
                Langganan akan tetap aktif hingga akhir periode saat ini.
                Setelah itu, akses ke fitur premium akan dibatasi.
            </p>
            <form action="{{ route('subscription.cancel') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="reason" class="block text-sm font-medium text-text mb-2">
                        Alasan pembatalan (opsional)
                    </label>
                    <textarea name="reason" id="reason" rows="3"
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                              placeholder="Beritahu kami alasan Anda..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button"
                            onclick="document.getElementById('cancel-modal').classList.add('hidden')"
                            class="flex-1 px-4 py-2 border border-border text-text rounded-lg hover:bg-secondary-50 transition-colors">
                        Kembali
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger-600 transition-colors">
                        Ya, Batalkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
