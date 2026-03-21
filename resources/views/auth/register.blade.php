<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar - Ultimate POS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div class="min-h-screen flex">
        <!-- Left Panel - Branding & Features -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary via-primary-600 to-primary-700 relative overflow-hidden">
            <!-- Decorative Circles -->
            <div class="absolute -top-20 -left-20 w-64 h-64 bg-white/5 rounded-full"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-white/5 rounded-full"></div>
            <div class="absolute top-1/2 left-1/4 w-48 h-48 bg-white/5 rounded-full"></div>

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
                    Kelola Bisnis Anda<br>dengan Lebih Mudah
                </h1>

                <p class="text-lg text-primary-100 mb-12 max-w-md">
                    Sistem Point of Sale modern yang membantu Anda mengelola penjualan, inventori, dan laporan dalam satu platform.
                </p>

                <!-- Features -->
                <div class="space-y-5">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/10 backdrop-blur rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Multi-Outlet Support</p>
                            <p class="text-sm text-primary-200">Kelola banyak cabang dalam satu dashboard</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/10 backdrop-blur rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Inventori Real-time</p>
                            <p class="text-sm text-primary-200">Pantau stok dan batch dengan akurat</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/10 backdrop-blur rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Laporan Lengkap</p>
                            <p class="text-sm text-primary-200">Analisis penjualan dan food cost</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial -->
                <div class="mt-12 pt-8 border-t border-white/10">
                    <p class="text-primary-100 italic mb-4">
                        "Ultimate POS membantu kami meningkatkan efisiensi operasional hingga 40%. Sangat recommended!"
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-semibold">
                            AB
                        </div>
                        <div>
                            <p class="font-medium">Ahmad Budiman</p>
                            <p class="text-sm text-primary-200">Owner, Warung Sederhana</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="w-full lg:w-1/2 flex flex-col">
            <!-- Mobile Logo -->
            <div class="lg:hidden flex items-center justify-center gap-3 py-6 border-b border-border">
                <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-primary">Ultimate POS</span>
            </div>

            <!-- Form Container -->
            <div class="flex-1 flex items-center justify-center px-6 py-8 sm:px-12 lg:px-16 xl:px-24">
                <div class="w-full max-w-md">
                    <!-- Header -->
                    <div class="mb-8">
                        <h2 class="text-2xl sm:text-3xl font-bold text-text">Buat Akun Baru</h2>
                        <p class="text-muted mt-2">Mulai kelola bisnis Anda hari ini</p>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ step: 1 }">
                        @csrf

                        <!-- Step Indicator -->
                        <div class="flex items-center gap-2 mb-6">
                            <div class="flex items-center gap-2 flex-1">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors"
                                     :class="step >= 1 ? 'bg-primary text-white' : 'bg-secondary-100 text-muted'">
                                    1
                                </div>
                                <span class="text-sm font-medium" :class="step >= 1 ? 'text-text' : 'text-muted'">Info Bisnis</span>
                            </div>
                            <div class="flex-1 h-0.5 bg-secondary-200">
                                <div class="h-full bg-primary transition-all duration-300" :style="step >= 2 ? 'width: 100%' : 'width: 0%'"></div>
                            </div>
                            <div class="flex items-center gap-2 flex-1 justify-end">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors"
                                     :class="step >= 2 ? 'bg-primary text-white' : 'bg-secondary-100 text-muted'">
                                    2
                                </div>
                                <span class="text-sm font-medium" :class="step >= 2 ? 'text-text' : 'text-muted'">Keamanan</span>
                            </div>
                        </div>

                        <!-- Step 1: Business Info -->
                        <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                            <!-- Business Name -->
                            <div class="mb-4">
                                <label for="business_name" class="block text-sm font-medium text-text mb-1.5">
                                    Nama Bisnis <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </span>
                                    <input type="text"
                                           id="business_name"
                                           name="business_name"
                                           value="{{ old('business_name') }}"
                                           required
                                           class="w-full pl-11 pr-4 py-3 border border-border rounded-xl bg-surface text-text
                                                  focus:ring-2 focus:ring-primary/20 focus:border-primary
                                                  placeholder:text-muted transition-all @error('business_name') border-danger @enderror"
                                           placeholder="cth: Warung Makan Bahagia">
                                </div>
                                @error('business_name')
                                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Full Name -->
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-text mb-1.5">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </span>
                                    <input type="text"
                                           id="name"
                                           name="name"
                                           value="{{ old('name') }}"
                                           required
                                           class="w-full pl-11 pr-4 py-3 border border-border rounded-xl bg-surface text-text
                                                  focus:ring-2 focus:ring-primary/20 focus:border-primary
                                                  placeholder:text-muted transition-all @error('name') border-danger @enderror"
                                           placeholder="Nama lengkap Anda">
                                </div>
                                @error('name')
                                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-text mb-1.5">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <input type="email"
                                           id="email"
                                           name="email"
                                           value="{{ old('email') }}"
                                           required
                                           class="w-full pl-11 pr-4 py-3 border border-border rounded-xl bg-surface text-text
                                                  focus:ring-2 focus:ring-primary/20 focus:border-primary
                                                  placeholder:text-muted transition-all @error('email') border-danger @enderror"
                                           placeholder="email@example.com">
                                </div>
                                @error('email')
                                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="mb-6">
                                <label for="phone" class="block text-sm font-medium text-text mb-1.5">
                                    No. Telepon
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </span>
                                    <input type="tel"
                                           id="phone"
                                           name="phone"
                                           value="{{ old('phone') }}"
                                           class="w-full pl-11 pr-4 py-3 border border-border rounded-xl bg-surface text-text
                                                  focus:ring-2 focus:ring-primary/20 focus:border-primary
                                                  placeholder:text-muted transition-all"
                                           placeholder="08xxxxxxxxxx">
                                </div>
                            </div>

                            <!-- Next Button -->
                            <button type="button"
                                    @click="step = 2"
                                    class="w-full py-3 px-4 bg-primary text-white font-medium rounded-xl
                                           hover:bg-primary-600 focus:ring-4 focus:ring-primary/20
                                           transition-all flex items-center justify-center gap-2">
                                Lanjutkan
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Step 2: Security -->
                        <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                            <!-- Password -->
                            <div class="mb-4" x-data="{ show: false }">
                                <label for="password" class="block text-sm font-medium text-text mb-1.5">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </span>
                                    <input :type="show ? 'text' : 'password'"
                                           id="password"
                                           name="password"
                                           required
                                           autocomplete="new-password"
                                           class="w-full pl-11 pr-12 py-3 border border-border rounded-xl bg-surface text-text
                                                  focus:ring-2 focus:ring-primary/20 focus:border-primary
                                                  placeholder:text-muted transition-all @error('password') border-danger @enderror"
                                           placeholder="Minimal 8 karakter">
                                    <button type="button"
                                            @click="show = !show"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-text transition-colors">
                                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                                @enderror
                                <p class="mt-1.5 text-xs text-muted">Gunakan kombinasi huruf, angka, dan simbol</p>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-6" x-data="{ show: false }">
                                <label for="password_confirmation" class="block text-sm font-medium text-text mb-1.5">
                                    Konfirmasi Password <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </span>
                                    <input :type="show ? 'text' : 'password'"
                                           id="password_confirmation"
                                           name="password_confirmation"
                                           required
                                           autocomplete="new-password"
                                           class="w-full pl-11 pr-12 py-3 border border-border rounded-xl bg-surface text-text
                                                  focus:ring-2 focus:ring-primary/20 focus:border-primary
                                                  placeholder:text-muted transition-all"
                                           placeholder="Ketik ulang password">
                                    <button type="button"
                                            @click="show = !show"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-text transition-colors">
                                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="mb-6">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox"
                                           name="terms"
                                           id="terms"
                                           required
                                           class="mt-0.5 w-5 h-5 rounded border-border text-primary focus:ring-primary/20 cursor-pointer">
                                    <span class="text-sm text-muted group-hover:text-text transition-colors">
                                        Saya setuju dengan
                                        <a href="#" class="text-primary hover:underline">Syarat & Ketentuan</a>
                                        dan
                                        <a href="#" class="text-primary hover:underline">Kebijakan Privasi</a>
                                    </span>
                                </label>
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-3">
                                <button type="button"
                                        @click="step = 1"
                                        class="flex-1 py-3 px-4 border border-border text-text font-medium rounded-xl
                                               hover:bg-secondary-50 focus:ring-4 focus:ring-secondary-100
                                               transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                                    </svg>
                                    Kembali
                                </button>
                                <button type="submit"
                                        class="flex-[2] py-3 px-4 bg-primary text-white font-medium rounded-xl
                                               hover:bg-primary-600 focus:ring-4 focus:ring-primary/20
                                               transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Buat Akun
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Divider -->
                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-background text-muted">atau</span>
                        </div>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center">
                        <p class="text-muted">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="text-primary hover:text-primary-600 font-semibold transition-colors">
                                Masuk di sini
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="py-4 px-6 text-center text-sm text-muted border-t border-border">
                &copy; {{ date('Y') }} Ultimate POS. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
