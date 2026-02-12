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

    @if(!auth()->user()->isSuperAdmin())
        <!-- Alerts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Expiring Items Alert -->
            <div class="bg-surface rounded-xl border border-border shadow-sm">
                <div class="flex items-center justify-between p-4 border-b border-border">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-warning/10 rounded-lg flex items-center justify-center">
                            <x-icon name="exclamation-triangle" class="w-5 h-5 text-warning" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-text">Expiring Soon</h3>
                            <p class="text-xs text-muted">Items nearing expiry date</p>
                        </div>
                    </div>
                    <a href="{{ route('inventory.batches.expiry-report') }}" class="text-sm text-primary hover:underline">View All</a>
                </div>
                <div class="p-4">
                    @if($expiringBatches->count() > 0)
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($expiringBatches as $batch)
                                @php
                                    $daysLeft = $batch->daysUntilExpiry();
                                    $isExpired = $daysLeft !== null && $daysLeft < 0;
                                    $isCritical = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7;
                                @endphp
                                <a href="{{ route('inventory.batches.show', $batch) }}" class="flex items-center justify-between p-3 rounded-lg {{ $isExpired ? 'bg-danger/10' : ($isCritical ? 'bg-danger/5' : 'bg-warning/5') }} hover:bg-opacity-75 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full {{ $isExpired ? 'bg-danger/20' : ($isCritical ? 'bg-danger/10' : 'bg-warning/10') }} flex items-center justify-center">
                                            <x-icon name="cube" class="w-4 h-4 {{ $isExpired ? 'text-danger' : ($isCritical ? 'text-danger' : 'text-warning') }}" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-text">{{ $batch->inventoryItem->name }}</p>
                                            <p class="text-xs text-muted">{{ $batch->batch_number }} &middot; {{ $batch->outlet->name }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $isExpired ? 'bg-danger text-white' : ($isCritical ? 'bg-danger/20 text-danger' : 'bg-warning/20 text-warning-700') }}">
                                            @if($isExpired)
                                                Expired
                                            @else
                                                {{ $daysLeft }} days
                                            @endif
                                        </span>
                                        <p class="text-xs text-muted mt-1">{{ number_format($batch->current_quantity, 2) }} {{ $batch->inventoryItem->unit->abbreviation ?? '' }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="w-12 h-12 bg-success/10 rounded-full flex items-center justify-center mb-3">
                                <x-icon name="check-circle" class="w-6 h-6 text-success" />
                            </div>
                            <p class="text-sm text-muted">No items expiring soon</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-surface rounded-xl border border-border shadow-sm">
                <div class="flex items-center justify-between p-4 border-b border-border">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-danger/10 rounded-lg flex items-center justify-center">
                            <x-icon name="arrow-trending-down" class="w-5 h-5 text-danger" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-text">Low Stock</h3>
                            <p class="text-xs text-muted">Items below reorder point</p>
                        </div>
                    </div>
                    <a href="{{ route('inventory.stocks.low') }}" class="text-sm text-primary hover:underline">View All</a>
                </div>
                <div class="p-4">
                    @if($lowStockItems->count() > 0)
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($lowStockItems as $stock)
                                <a href="{{ route('inventory.stocks.show', $stock) }}" class="flex items-center justify-between p-3 rounded-lg bg-danger/5 hover:bg-danger/10 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-danger/10 flex items-center justify-center">
                                            <x-icon name="cube" class="w-4 h-4 text-danger" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-text">{{ $stock->inventoryItem->name }}</p>
                                            <p class="text-xs text-muted">{{ $stock->outlet->name }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-danger">{{ number_format($stock->quantity, 2) }}</p>
                                        <p class="text-xs text-muted">of {{ number_format($stock->inventoryItem->reorder_point, 2) }} {{ $stock->inventoryItem->unit->abbreviation ?? '' }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="w-12 h-12 bg-success/10 rounded-full flex items-center justify-center mb-3">
                                <x-icon name="check-circle" class="w-6 h-6 text-success" />
                            </div>
                            <p class="text-sm text-muted">All items are well stocked</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions Card -->
        <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-text mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('pos.index') }}" class="flex items-center gap-3 p-3 rounded-lg bg-primary text-white hover:bg-primary-600 transition-colors">
                    <x-icon name="calculator" class="w-5 h-5" />
                    Open POS
                </a>
                <a href="{{ route('menu.products.create') }}" class="flex items-center gap-3 p-3 rounded-lg border border-border text-text hover:bg-secondary-50 transition-colors">
                    <x-icon name="plus" class="w-5 h-5 text-secondary-500" />
                    Add Product
                </a>
                <a href="{{ route('inventory.reports.stock-valuation') }}" class="flex items-center gap-3 p-3 rounded-lg border border-border text-text hover:bg-secondary-50 transition-colors">
                    <x-icon name="chart-bar" class="w-5 h-5 text-secondary-500" />
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
                        <x-icon name="check" class="w-5 h-5 text-success" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-text">System initialized successfully</p>
                        <p class="text-xs text-muted">{{ now()->format('M d, Y H:i') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 p-3 rounded-lg bg-secondary-50">
                    <div class="w-10 h-10 bg-info-100 rounded-full flex items-center justify-center">
                        <x-icon name="information-circle" class="w-5 h-5 text-info" />
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
