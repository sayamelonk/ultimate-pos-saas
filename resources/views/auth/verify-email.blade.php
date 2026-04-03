<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verifikasi Email - Ultimate POS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div class="min-h-screen flex">
        <!-- Left Panel - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary via-primary-600 to-primary-700 relative overflow-hidden">
            <!-- Decorative Circles -->
            <div class="absolute -top-20 -left-20 w-64 h-64 bg-white/5 rounded-full"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-white/5 rounded-full"></div>
            <div class="absolute top-1/3 right-1/4 w-32 h-32 bg-white/5 rounded-full"></div>
            <div class="absolute bottom-1/4 left-1/3 w-24 h-24 bg-white/5 rounded-full"></div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-center px-12 xl:px-20 text-white">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-12">
                    <div class="w-12 h-12 bg-white/10 backdrop-blur rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold">Ultimate POS</span>
                </div>

                <h1 class="text-4xl xl:text-5xl font-bold leading-tight mb-6">
                    Hampir<br>Selesai!
                </h1>

                <p class="text-lg text-primary-100 mb-12 max-w-md">
                    Satu langkah lagi untuk mengaktifkan akun Anda. Cek email untuk link verifikasi.
                </p>

                <!-- Info -->
                <div class="bg-white/10 backdrop-blur rounded-xl p-6 max-w-md">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Cek Email Anda</h3>
                            <p class="text-sm text-primary-100">Kami telah mengirim link verifikasi ke email Anda. Link berlaku selama 24 jam.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Verification Notice -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-text">Ultimate POS</span>
                </div>

                <!-- Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-border p-8">
                    <!-- Icon -->
                    <div class="w-16 h-16 bg-warning/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>

                    <h2 class="text-2xl font-bold text-text text-center mb-2">
                        Verifikasi Email Anda
                    </h2>

                    <p class="text-text-light text-center mb-6">
                        Terima kasih telah mendaftar! Sebelum memulai, silakan verifikasi alamat email Anda dengan mengklik link yang baru saja kami kirimkan.
                    </p>

                    @if (session('status') == 'verification-link-sent')
                        <div class="bg-success/10 border border-success/20 rounded-xl p-4 mb-6">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-success shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-success-700">Link verifikasi baru telah dikirim ke email Anda.</p>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-4">
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="w-full py-3 px-4 bg-primary hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                Kirim Ulang Email Verifikasi
                            </button>
                        </form>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full py-3 px-4 text-text-light hover:text-text font-medium transition-colors">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Help Text -->
                <p class="text-center text-sm text-text-light mt-6">
                    Tidak menerima email? Cek folder spam atau
                    <a href="mailto:support@ultimatepos.com" class="text-primary hover:text-primary-600">hubungi support</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
