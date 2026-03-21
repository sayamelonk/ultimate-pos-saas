<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Ultimate POS</title>
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
                    Selamat Datang<br>Kembali!
                </h1>

                <p class="text-lg text-primary-100 mb-12 max-w-md">
                    Masuk ke akun Anda untuk melanjutkan mengelola bisnis dengan Ultimate POS.
                </p>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-6 mb-12">
                    <div class="text-center">
                        <p class="text-3xl font-bold">500+</p>
                        <p class="text-sm text-primary-200">Bisnis Aktif</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold">10K+</p>
                        <p class="text-sm text-primary-200">Transaksi/Hari</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold">99.9%</p>
                        <p class="text-sm text-primary-200">Uptime</p>
                    </div>
                </div>

                <!-- Trust Badge -->
                <div class="flex items-center gap-4 pt-8 border-t border-white/10">
                    <div class="flex -space-x-2">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-sm font-medium border-2 border-primary-600">JD</div>
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-sm font-medium border-2 border-primary-600">AS</div>
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-sm font-medium border-2 border-primary-600">MK</div>
                        <div class="w-10 h-10 bg-white/30 rounded-full flex items-center justify-center text-xs font-medium border-2 border-primary-600">+497</div>
                    </div>
                    <p class="text-sm text-primary-100">Bergabung dengan ratusan pemilik bisnis lainnya</p>
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
                        <h2 class="text-2xl sm:text-3xl font-bold text-text">Masuk ke Akun</h2>
                        <p class="text-muted mt-2">Silakan masuk untuk melanjutkan</p>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-text mb-1.5">
                                Email
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
                                       autofocus
                                       autocomplete="email"
                                       class="w-full pl-11 pr-4 py-3 border border-border rounded-xl bg-surface text-text
                                              focus:ring-2 focus:ring-primary/20 focus:border-primary
                                              placeholder:text-muted transition-all @error('email') border-danger @enderror"
                                       placeholder="email@example.com">
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div x-data="{ show: false }">
                            <label for="password" class="block text-sm font-medium text-text mb-1.5">
                                Password
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
                                       autocomplete="current-password"
                                       class="w-full pl-11 pr-12 py-3 border border-border rounded-xl bg-surface text-text
                                              focus:ring-2 focus:ring-primary/20 focus:border-primary
                                              placeholder:text-muted transition-all @error('password') border-danger @enderror"
                                       placeholder="Masukkan password">
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
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox"
                                       name="remember"
                                       class="w-4 h-4 rounded border-border text-primary focus:ring-primary/20 cursor-pointer">
                                <span class="text-sm text-muted group-hover:text-text transition-colors">Ingat saya</span>
                            </label>
                            <a href="#" class="text-sm text-primary hover:text-primary-600 font-medium transition-colors">
                                Lupa password?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full py-3 px-4 bg-primary text-white font-medium rounded-xl
                                       hover:bg-primary-600 focus:ring-4 focus:ring-primary/20
                                       transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Masuk
                        </button>
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

                    <!-- Register Link -->
                    <div class="text-center mb-6">
                        <p class="text-muted">
                            Belum punya akun?
                            <a href="{{ route('register') }}" class="text-primary hover:text-primary-600 font-semibold transition-colors">
                                Daftar sekarang
                            </a>
                        </p>
                    </div>

                    <!-- Demo Credentials -->
                    <div class="p-4 bg-gradient-to-r from-primary/5 to-accent/5 rounded-xl border border-primary/10">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-semibold text-text">Demo Credentials</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2 bg-white/50 rounded-lg">
                                <p class="font-medium text-text">Super Admin</p>
                                <p class="text-muted truncate">superadmin@ultimatepos.com</p>
                            </div>
                            <div class="p-2 bg-white/50 rounded-lg">
                                <p class="font-medium text-text">Owner</p>
                                <p class="text-muted truncate">owner@demo.com</p>
                            </div>
                            <div class="p-2 bg-white/50 rounded-lg">
                                <p class="font-medium text-text">Manager</p>
                                <p class="text-muted truncate">manager@demo.com</p>
                            </div>
                            <div class="p-2 bg-white/50 rounded-lg">
                                <p class="font-medium text-text">Cashier</p>
                                <p class="text-muted truncate">cashier@demo.com</p>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-center text-muted">
                            <span class="font-medium">Password:</span> password
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
