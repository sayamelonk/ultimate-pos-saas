<x-app-layout>
    <x-slot name="title">Invoice {{ $invoice->invoice_number }} - Ultimate POS</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Invoice</h2>
                <p class="text-muted mt-1">{{ $invoice->invoice_number }}</p>
            </div>
            <a href="{{ route('subscription.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-border text-text rounded-lg hover:bg-secondary-50 transition-colors">
                <x-icon name="arrow-left" class="w-5 h-5" />
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-surface rounded-xl border border-border shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-border bg-secondary-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-text">Ultimate POS</h1>
                        <p class="text-sm text-muted">Point of Sale Solution</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($invoice->status === 'paid') bg-success/10 text-success
                            @elseif($invoice->status === 'pending') bg-warning/10 text-warning
                            @else bg-secondary-100 text-muted
                            @endif">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="p-6 border-b border-border">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-muted mb-1">Ditagihkan kepada:</p>
                        <p class="font-medium text-text">{{ $invoice->tenant->name }}</p>
                        <p class="text-sm text-muted">{{ $invoice->tenant->email }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-muted mb-1">No. Invoice:</p>
                        <p class="font-medium text-text">{{ $invoice->invoice_number }}</p>
                        <p class="text-sm text-muted mt-2">Tanggal:</p>
                        <p class="font-medium text-text">{{ $invoice->created_at->format('d M Y') }}</p>
                        @if($invoice->paid_at)
                            <p class="text-sm text-muted mt-2">Dibayar:</p>
                            <p class="font-medium text-text">{{ $invoice->paid_at->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="p-6 border-b border-border">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="pb-3 text-left text-sm font-medium text-muted">Deskripsi</th>
                            <th class="pb-3 text-right text-sm font-medium text-muted">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="py-4">
                                <p class="font-medium text-text">Langganan {{ $invoice->plan->name }}</p>
                                <p class="text-sm text-muted">Periode: {{ ucfirst($invoice->billing_cycle) }}</p>
                            </td>
                            <td class="py-4 text-right font-medium text-text">
                                Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t border-border">
                        @if($invoice->tax_amount > 0)
                            <tr>
                                <td class="pt-4 text-right text-sm text-muted">Pajak:</td>
                                <td class="pt-4 text-right text-text">
                                    Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="pt-2 text-right font-medium text-text">Total:</td>
                            <td class="pt-2 text-right text-xl font-bold text-primary">
                                {{ $invoice->getFormattedAmount() }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment Info -->
            @if($invoice->isPaid())
                <div class="p-6 bg-success/5">
                    <div class="flex items-center gap-3">
                        <x-icon name="check-circle" class="w-6 h-6 text-success" />
                        <div>
                            <p class="font-medium text-success">Pembayaran Diterima</p>
                            @if($invoice->payment_method || $invoice->payment_channel)
                                <p class="text-sm text-muted">
                                    via {{ $invoice->payment_channel ?? $invoice->payment_method }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($invoice->isPending() && $invoice->xendit_invoice_url)
                <div class="p-6">
                    <a href="{{ $invoice->xendit_invoice_url }}" target="_blank"
                       class="block w-full text-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                        Bayar Sekarang
                    </a>
                    @if($invoice->expired_at)
                        <p class="text-center text-sm text-muted mt-2">
                            Berlaku hingga: {{ $invoice->expired_at->format('d M Y H:i') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Print Button -->
        @if($invoice->isPaid())
            <div class="mt-6 text-center">
                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-border text-text rounded-lg hover:bg-secondary-50 transition-colors">
                    <x-icon name="printer" class="w-5 h-5" />
                    Cetak Invoice
                </button>
            </div>
        @endif
    </div>
</x-app-layout>
