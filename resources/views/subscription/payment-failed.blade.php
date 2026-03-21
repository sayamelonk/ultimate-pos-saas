<x-app-layout>
    <x-slot name="title">Pembayaran Gagal - Ultimate POS</x-slot>

    <div class="min-h-[60vh] flex items-center justify-center">
        <div class="text-center max-w-md mx-auto p-8">
            <div class="w-20 h-20 bg-danger/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <x-icon name="x-circle" class="w-12 h-12 text-danger" />
            </div>

            <h1 class="text-2xl font-bold text-text mb-4">Pembayaran Gagal</h1>

            <p class="text-muted mb-8">
                Maaf, pembayaran Anda tidak dapat diproses.
                Silakan coba lagi atau gunakan metode pembayaran lain.
            </p>

            <div class="flex flex-col gap-3">
                <a href="{{ route('subscription.plans') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                    <x-icon name="arrow-path" class="w-5 h-5" />
                    Coba Lagi
                </a>

                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 border border-border text-text rounded-lg hover:bg-secondary-50 transition-colors">
                    <x-icon name="home" class="w-5 h-5" />
                    Ke Dashboard
                </a>
            </div>

            <p class="text-sm text-muted mt-8">
                Butuh bantuan? Hubungi support kami di
                <a href="mailto:support@ultimatepos.id" class="text-primary hover:underline">support@ultimatepos.id</a>
            </p>
        </div>
    </div>
</x-app-layout>
