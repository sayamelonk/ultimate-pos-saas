<!-- Header -->
<header class="sticky top-0 z-30 bg-surface border-b border-border">
    <div class="flex items-center justify-between h-16 px-6">
        <!-- Left side -->
        <div class="flex items-center gap-4">
            <!-- Mobile Menu Button -->
            <button @click="mobileMenuOpen = true"
                    class="lg:hidden flex items-center justify-center w-10 h-10 rounded-lg hover:bg-secondary-100 transition-colors">
                <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Breadcrumb / Page Title -->
            <div>
                <h1 class="text-lg font-semibold text-text">
                    @yield('page-title', 'Dashboard')
                </h1>
                @hasSection('breadcrumb')
                    <nav class="text-sm text-muted">
                        @yield('breadcrumb')
                    </nav>
                @endif
            </div>
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-3">
            <!-- Outlet Selector -->
            @if(auth()->check())
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg border border-border hover:bg-secondary-50 transition-colors">
                    <svg class="w-5 h-5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="hidden sm:block text-sm font-medium text-secondary-700">Main Outlet</span>
                    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Dropdown -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     @click.away="open = false"
                     class="absolute right-0 mt-2 w-56 bg-surface rounded-lg shadow-lg border border-border py-1 z-50">
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-secondary-50 text-sm">
                        <span class="w-2 h-2 rounded-full bg-success"></span>
                        Main Outlet
                    </a>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-secondary-50 text-sm text-secondary-600">
                        <span class="w-2 h-2 rounded-full bg-secondary-300"></span>
                        Branch 1
                    </a>
                </div>
            </div>
            @endif

            <!-- Notifications -->
            <button class="relative flex items-center justify-center w-10 h-10 rounded-lg hover:bg-secondary-100 transition-colors">
                <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <!-- Notification Badge -->
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-danger rounded-full"></span>
            </button>

            <!-- User Menu -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary-100 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                        <span class="text-sm font-medium text-white">
                            {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 2)) : 'US' }}
                        </span>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-medium text-text">
                            {{ auth()->check() ? auth()->user()->name : 'Guest' }}
                        </p>
                        <p class="text-xs text-muted">Administrator</p>
                    </div>
                    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Dropdown -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     @click.away="open = false"
                     class="absolute right-0 mt-2 w-56 bg-surface rounded-lg shadow-lg border border-border py-1 z-50">
                    <div class="px-4 py-3 border-b border-border">
                        <p class="text-sm font-medium text-text">
                            {{ auth()->check() ? auth()->user()->name : 'Guest' }}
                        </p>
                        <p class="text-xs text-muted truncate">
                            {{ auth()->check() ? auth()->user()->email : '' }}
                        </p>
                    </div>

                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-secondary-50 text-sm text-secondary-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        My Profile
                    </a>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-secondary-50 text-sm text-secondary-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Settings
                    </a>

                    <div class="border-t border-border my-1"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-3 w-full px-4 py-2 hover:bg-danger-50 text-sm text-danger-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
