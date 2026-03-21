<x-app-layout>
    <x-slot name="title">Pembayaran Berhasil - Ultimate POS</x-slot>

    <div class="min-h-[60vh] flex items-center justify-center">
        <div class="text-center max-w-md mx-auto p-8">
            <div class="w-20 h-20 bg-success/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <x-icon name="check-circle" class="w-12 h-12 text-success" />
            </div>

            <h1 class="text-2xl font-bold text-text mb-4">Pembayaran Berhasil!</h1>

            <p class="text-muted mb-8">
                Terima kasih! Langganan Anda telah aktif.
                Sekarang Anda dapat menggunakan semua fitur sesuai paket yang dipilih.
            </p>

            <div class="flex flex-col gap-3">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                    <x-icon name="home" class="w-5 h-5" />
                    Ke Dashboard
                </a>

                <a href="{{ route('subscription.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 border border-border text-text rounded-lg hover:bg-secondary-50 transition-colors">
                    <x-icon name="document-text" class="w-5 h-5" />
                    Lihat Detail Langganan
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
