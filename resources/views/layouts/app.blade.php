<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Ultimate POS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="min-h-screen bg-background" x-data="{ sidebarOpen: true, mobileMenuOpen: false }">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0" :class="{ 'lg:ml-64': sidebarOpen, 'lg:ml-20': !sidebarOpen }">
            <!-- Header -->
            @include('partials.header')

            <!-- Page Content -->
            <main class="flex-1 p-6">
                {{-- Frozen Mode Alert (global) --}}
                @auth
                    @if(!auth()->user()->isSuperAdmin() && auth()->user()->tenant?->isFrozen())
                        <div class="mb-6 bg-danger-600 text-white rounded-xl p-4 shadow-lg">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold">Akun dalam mode Frozen</p>
                                    <p class="text-sm text-white/80">Anda hanya bisa melihat data. Pilih paket untuk melanjutkan.</p>
                                </div>
                                <a href="{{ route('subscription.plans') }}" class="px-4 py-2 bg-white text-danger-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors">
                                    Pilih Paket
                                </a>
                            </div>
                        </div>
                    @endif
                @endauth

                <!-- Page Header -->
                @if(isset($header))
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @endif

                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-4 p-4 bg-success-50 border border-success-200 text-success-700 rounded-lg"
                         x-data="{ show: true }"
                         x-show="show"
                         x-transition
                         x-init="setTimeout(() => show = false, 5000)">
                        <div class="flex items-center justify-between">
                            <span>{{ session('success') }}</span>
                            <button @click="show = false" class="text-success-500 hover:text-success-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 p-4 bg-danger-50 border border-danger-200 text-danger-700 rounded-lg"
                         x-data="{ show: true }"
                         x-show="show"
                         x-transition>
                        <div class="flex items-center justify-between">
                            <span>{{ session('error') }}</span>
                            <button @click="show = false" class="text-danger-500 hover:text-danger-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Main Slot -->
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="py-4 px-6 border-t border-border bg-surface">
                <div class="text-center text-sm text-muted">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Ultimate POS') }}. All rights reserved.
                </div>
            </footer>
        </div>
    </div>

    {{-- Global Confirmation Dialog --}}
    <x-delete-confirm />

    @stack('scripts')
</body>
</html>
