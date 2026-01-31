<x-app-layout>
    <x-slot name="title">Dashboard - Ultimate POS</x-slot>

    <x-slot name="header">
        <h2 class="text-2xl font-bold text-text">Dashboard</h2>
        <p class="text-muted mt-1">Welcome back, {{ auth()->user()->name }}!</p>
    </x-slot>

    @section('page-title', 'Dashboard')

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @if(auth()->user()->isSuperAdmin())
            <!-- Tenants -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Total Tenants</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $stats['tenants'] ?? 0 }}</p>
                        <p class="text-sm text-success mt-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                            </svg>
                            {{ $stats['active_tenants'] ?? 0 }} active
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Outlets -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Total Outlets</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $stats['outlets'] ?? 0 }}</p>
                    </div>
                    <div class="w-14 h-14 bg-accent-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Users -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Total Users</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $stats['users'] ?? 0 }}</p>
                    </div>
                    <div class="w-14 h-14 bg-success-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">System Status</p>
                        <p class="text-xl font-bold text-success mt-2">Operational</p>
                        <p class="text-sm text-muted mt-2">All services running</p>
                    </div>
                    <div class="w-14 h-14 bg-success-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        @else
            <!-- Outlets -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Your Outlets</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $stats['outlets'] ?? 0 }}</p>
                    </div>
                    <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Staff -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Staff Members</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $stats['users'] ?? 0 }}</p>
                    </div>
                    <div class="w-14 h-14 bg-accent-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Today's Orders -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Today's Orders</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $stats['today_orders'] ?? 0 }}</p>
                    </div>
                    <div class="w-14 h-14 bg-warning-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Today's Revenue -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Today's Revenue</p>
                        <p class="text-3xl font-bold text-text mt-2">Rp {{ number_format($stats['today_revenue'] ?? 0) }}</p>
                    </div>
                    <div class="w-14 h-14 bg-success-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions Card -->
        <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-text mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="#" class="flex items-center gap-3 p-3 rounded-lg bg-primary text-white hover:bg-primary-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Open POS
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-lg border border-border text-text hover:bg-secondary-50 transition-colors">
                    <svg class="w-5 h-5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Product
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-lg border border-border text-text hover:bg-secondary-50 transition-colors">
                    <svg class="w-5 h-5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    View Reports
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="lg:col-span-2 bg-surface rounded-xl border border-border p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-text mb-4">Recent Activity</h3>
            <div class="space-y-4">
                <div class="flex items-center gap-4 p-3 rounded-lg bg-secondary-50">
                    <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-text">System initialized successfully</p>
                        <p class="text-xs text-muted">{{ now()->format('M d, Y H:i') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 p-3 rounded-lg bg-secondary-50">
                    <div class="w-10 h-10 bg-info-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-text">Welcome to Ultimate POS!</p>
                        <p class="text-xs text-muted">Start by adding your first product</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
