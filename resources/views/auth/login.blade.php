<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.login') }} - Ultimate POS</title>
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
                    {{ __('auth.welcome_back') }}
                </h1>

                <p class="text-lg text-primary-100 mb-12 max-w-md">
                    {{ __('auth.welcome_back_subtitle') }}
                </p>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-6 mb-12">
                    <div class="text-center">
                        <p class="text-3xl font-bold">500+</p>
                        <p class="text-sm text-primary-200">{{ __('auth.active_businesses') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold">10K+</p>
                        <p class="text-sm text-primary-200">{{ __('auth.transactions_per_day') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold">99.9%</p>
                        <p class="text-sm text-primary-200">{{ __('auth.uptime') }}</p>
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
                    <p class="text-sm text-primary-100">{{ __('auth.join_other_owners') }}</p>
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
                        <h2 class="text-2xl sm:text-3xl font-bold text-text">{{ __('auth.login_title') }}</h2>
                        <p class="text-muted mt-2">{{ __('auth.login_subtitle') }}</p>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-text mb-1.5">
                                {{ __('auth.email') }}
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
                                       placeholder="{{ __('auth.email_placeholder') }}">
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div x-data="{ show: false }">
                            <label for="password" class="block text-sm font-medium text-text mb-1.5">
                                {{ __('auth.password_label') }}
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
                                       placeholder="{{ __('auth.password_placeholder') }}">
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
                                <span class="text-sm text-muted group-hover:text-text transition-colors">{{ __('auth.remember_me') }}</span>
                            </label>
                            <a href="#" class="text-sm text-primary hover:text-primary-600 font-medium transition-colors">
                                {{ __('auth.forgot_password') }}
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
                            {{ __('auth.login_button') }}
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-background text-muted">{{ __('auth.or') }}</span>
                        </div>
                    </div>

                    <!-- Register Link -->
                    <div class="text-center mb-6">
                        <p class="text-muted">
                            {{ __('auth.no_account') }}
                            <a href="{{ route('register') }}" class="text-primary hover:text-primary-600 font-semibold transition-colors">
                                {{ __('auth.register_now') }}
                            </a>
                        </p>
                    </div>

                    <!-- Demo Credentials by Role -->
                    <div class="p-4 bg-gradient-to-r from-primary/5 to-accent/5 rounded-xl border border-primary/10 mb-4">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-semibold text-text">{{ __('auth.demo_by_role') }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button type="button" onclick="fillCredentials('superadmin@ultimatepos.com')" class="p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors text-left">
                                <p class="font-medium text-text">{{ __('auth.super_admin') }}</p>
                                <p class="text-muted truncate">superadmin@ultimatepos.com</p>
                            </button>
                            <button type="button" onclick="fillCredentials('owner@demo.com')" class="p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors text-left">
                                <p class="font-medium text-text">{{ __('auth.owner') }}</p>
                                <p class="text-muted truncate">owner@demo.com</p>
                            </button>
                            <button type="button" onclick="fillCredentials('manager@demo.com')" class="p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors text-left">
                                <p class="font-medium text-text">{{ __('auth.manager') }}</p>
                                <p class="text-muted truncate">manager@demo.com</p>
                            </button>
                            <button type="button" onclick="fillCredentials('cashier@demo.com')" class="p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors text-left">
                                <p class="font-medium text-text">{{ __('auth.cashier') }}</p>
                                <p class="text-muted truncate">cashier@demo.com</p>
                            </button>
                        </div>
                    </div>

                    <!-- Demo Credentials by Plan (Feature Gating Test) -->
                    <div class="p-4 bg-gradient-to-r from-success/5 to-warning/5 rounded-xl border border-success/20">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                            <p class="text-sm font-semibold text-text">{{ __('auth.test_by_plan') }}</p>
                            <span class="text-xs text-muted">({{ __('auth.feature_gating') }})</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button type="button" onclick="fillCredentials('starter@demo.com')" class="p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors text-left border-l-2 border-gray-400">
                                <p class="font-medium text-text">{{ __('auth.starter') }}</p>
                                <p class="text-muted truncate">starter@demo.com</p>
                                <p class="text-[10px] text-gray-500 mt-1">{{ __('auth.no_inventory') }}</p>
                            </button>
                            <button type="button" onclick="fillCredentials('growth@demo.com')" class="p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors text-left border-l-2 border-blue-400">
                                <p class="font-medium text-text">{{ __('auth.growth') }}</p>
                                <p class="text-muted truncate">growth@demo.com</p>
                                <p class="text-[10px] text-blue-500 mt-1">{{ __('auth.basic_inventory') }}</p>
                            </button>
                            <button type="button" onclick="fillCredentials('professional@demo.com')" class="p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors text-left border-l-2 border-purple-400">
                                <p class="font-medium text-text">{{ __('auth.professional') }}</p>
                                <p class="text-muted truncate">professional@demo.com</p>
                                <p class="text-[10px] text-purple-500 mt-1">{{ __('auth.all_features') }}</p>
                            </button>
                            <button type="button" onclick="fillCredentials('enterprise@demo.com')" class="p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors text-left border-l-2 border-amber-400">
                                <p class="font-medium text-text">{{ __('auth.enterprise') }}</p>
                                <p class="text-muted truncate">enterprise@demo.com</p>
                                <p class="text-[10px] text-amber-500 mt-1">{{ __('auth.unlimited_api') }}</p>
                            </button>
                        </div>
                        <p class="mt-3 text-xs text-center text-muted">
                            <span class="font-medium">Password:</span> password
                            <span class="mx-2">|</span>
                            <span class="text-success">{{ __('auth.click_to_autofill') }}</span>
                        </p>
                    </div>

                    <script>
                        function fillCredentials(email) {
                            document.getElementById('email').value = email;
                            document.getElementById('password').value = 'password';
                            document.getElementById('email').focus();
                        }
                    </script>
                </div>
            </div>

            <!-- Footer -->
            <div class="py-4 px-6 text-center text-sm text-muted border-t border-border">
                &copy; {{ date('Y') }} Ultimate POS. {{ __('auth.all_rights_reserved') }}
            </div>
        </div>
    </div>
</body>
</html>
