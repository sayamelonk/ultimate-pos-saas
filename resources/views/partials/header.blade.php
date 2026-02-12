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
            @if(auth()->check())
                @if(auth()->user()->isSuperAdmin())
                    <!-- Tenant Selector for Super Admin -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg border {{ session('current_tenant_id') ? 'border-primary bg-primary-50' : 'border-border' }} hover:bg-secondary-50 transition-colors">
                            <svg class="w-5 h-5 {{ session('current_tenant_id') ? 'text-primary' : 'text-secondary-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="hidden sm:block text-sm font-medium {{ session('current_tenant_id') ? 'text-primary' : 'text-secondary-700' }}">
                                {{ session('current_tenant_name', 'Select Tenant') }}
                            </span>
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
                             class="absolute right-0 mt-2 w-64 bg-surface rounded-lg shadow-lg border border-border py-1 z-50 max-h-80 overflow-y-auto">
                            <div class="px-4 py-2 text-xs font-semibold text-muted uppercase tracking-wider border-b border-border">
                                Switch Tenant
                            </div>
                            @php
                                $tenants = \App\Models\Tenant::where('is_active', true)->orderBy('name')->get();
                            @endphp
                            @forelse($tenants as $tenant)
                                <form action="{{ route('admin.tenants.switch', $tenant) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 hover:bg-secondary-50 text-sm text-left {{ session('current_tenant_id') === $tenant->id ? 'bg-primary-50' : '' }}">
                                        <span class="w-2 h-2 rounded-full {{ session('current_tenant_id') === $tenant->id ? 'bg-primary' : 'bg-secondary-300' }}"></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-text truncate">{{ $tenant->name }}</p>
                                            <p class="text-xs text-muted">{{ $tenant->code }}</p>
                                        </div>
                                        @if(session('current_tenant_id') === $tenant->id)
                                            <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                            @empty
                                <p class="px-4 py-2 text-sm text-muted">No tenants available</p>
                            @endforelse
                            @if(session('current_tenant_id'))
                                <div class="border-t border-border mt-1 pt-1">
                                    <form action="{{ route('admin.tenants.clear') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 hover:bg-danger-50 text-sm text-danger-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Clear Selection
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- Outlet Selector for Regular Users -->
                    @php
                        $userOutlets = auth()->user()->outlets;
                        $currentOutlet = $userOutlets->where('pivot.is_default', true)->first() ?? $userOutlets->first();
                    @endphp
                    @if($userOutlets->count() > 0)
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg border border-border hover:bg-secondary-50 transition-colors">
                            <svg class="w-5 h-5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="hidden sm:block text-sm font-medium text-secondary-700">
                                {{ $currentOutlet?->name ?? 'No Outlet' }}
                            </span>
                            @if($userOutlets->count() > 1)
                            <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @endif
                        </button>

                        @if($userOutlets->count() > 1)
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
                            @foreach($userOutlets as $outlet)
                                <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-secondary-50 text-sm {{ $outlet->pivot->is_default ? '' : 'text-secondary-600' }}">
                                    <span class="w-2 h-2 rounded-full {{ $outlet->pivot->is_default ? 'bg-success' : 'bg-secondary-300' }}"></span>
                                    {{ $outlet->name }}
                                </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif
                @endif
            @endif

            <!-- Language Switcher -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="flex items-center justify-center w-10 h-10 rounded-lg hover:bg-secondary-100 transition-colors"
                        title="{{ __('app.switch_language') }}">
                    <span class="text-sm font-medium text-secondary-600 uppercase">{{ app()->getLocale() }}</span>
                </button>

                <!-- Language Dropdown -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     @click.away="open = false"
                     class="absolute right-0 mt-2 w-40 bg-surface rounded-lg shadow-lg border border-border py-1 z-50">
                    <div class="px-4 py-2 text-xs font-semibold text-muted uppercase tracking-wider border-b border-border">
                        {{ __('app.language') }}
                    </div>
                    @foreach(config('app.available_locales', []) as $locale => $label)
                        <a href="{{ route('locale.switch', $locale) }}"
                           class="flex items-center gap-3 px-4 py-2 hover:bg-secondary-50 text-sm {{ app()->getLocale() === $locale ? 'bg-primary-50 text-primary font-medium' : 'text-secondary-700' }}">
                            <span class="w-6 text-center uppercase font-mono">{{ $locale }}</span>
                            <span>{{ $label }}</span>
                            @if(app()->getLocale() === $locale)
                                <svg class="w-4 h-4 ml-auto text-primary" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

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
