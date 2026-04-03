<x-app-layout>
    <x-slot name="title">Dashboard - Ultimate POS</x-slot>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-text">Dashboard</h2>
                <p class="text-muted mt-1">Welcome back, {{ auth()->user()->name }}!</p>
            </div>

            @if(!auth()->user()->isSuperAdmin())
            {{-- Date Range Filter --}}
            <div x-data="{
                open: false,
                dateRange: '{{ $dateRange ?? 'today' }}',
                showCustom: {{ ($dateRange ?? 'today') === 'custom' ? 'true' : 'false' }},
                startDate: '{{ isset($startDate) ? $startDate->format('Y-m-d') : now()->format('Y-m-d') }}',
                endDate: '{{ isset($endDate) ? $endDate->format('Y-m-d') : now()->format('Y-m-d') }}',
                ranges: [
                    { value: 'today', label: 'Hari Ini' },
                    { value: 'yesterday', label: 'Kemarin' },
                    { value: 'this_week', label: 'Minggu Ini' },
                    { value: 'last_week', label: 'Minggu Lalu' },
                    { value: 'this_month', label: 'Bulan Ini' },
                    { value: 'last_month', label: 'Bulan Lalu' },
                    { value: 'this_year', label: 'Tahun Ini' },
                    { value: 'custom', label: 'Custom Range' }
                ],
                selectRange(range) {
                    this.dateRange = range;
                    if (range === 'custom') {
                        this.showCustom = true;
                    } else {
                        this.showCustom = false;
                        this.applyFilter();
                    }
                },
                applyFilter() {
                    const params = new URLSearchParams();
                    params.set('date_range', this.dateRange);
                    if (this.dateRange === 'custom') {
                        params.set('start_date', this.startDate);
                        params.set('end_date', this.endDate);
                    }
                    window.location.href = '{{ route('admin.dashboard') }}?' + params.toString();
                }
            }" class="relative">
                <button @click="open = !open" type="button" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface border border-border rounded-xl text-sm font-medium text-text hover:bg-secondary-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span x-text="ranges.find(r => r.value === dateRange)?.label || 'Pilih Periode'">{{ $dateRangeLabel ?? 'Hari Ini' }}</span>
                    <svg class="w-4 h-4 text-muted transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Dropdown Menu --}}
                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-64 bg-surface border border-border rounded-xl shadow-lg z-50" style="display: none;">
                    <div class="p-2">
                        <template x-for="range in ranges" :key="range.value">
                            <button @click="selectRange(range.value)" :class="{ 'bg-primary/10 text-primary': dateRange === range.value }" class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-secondary-50 transition-colors" x-text="range.label"></button>
                        </template>
                    </div>

                    {{-- Custom Date Range --}}
                    <div x-show="showCustom" class="border-t border-border p-3 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-muted mb-1">Dari Tanggal</label>
                            <input type="date" x-model="startDate" class="w-full px-3 py-2 text-sm border border-border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-surface text-text">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-muted mb-1">Sampai Tanggal</label>
                            <input type="date" x-model="endDate" class="w-full px-3 py-2 text-sm border border-border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-surface text-text">
                        </div>
                        <button @click="applyFilter()" class="w-full px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-600 transition-colors">
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </x-slot>

    @section('page-title', 'Dashboard')

    {{-- Trial/Frozen Banner --}}
    @if(!auth()->user()->isSuperAdmin() && isset($subscription))
        <x-trial-banner :subscription="$subscription" />
    @endif

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

            <!-- Orders -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Orders ({{ $dateRangeLabel ?? 'Hari Ini' }})</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $stats['orders'] ?? 0 }}</p>
                        @if(isset($stats['orders_change']))
                            <p class="text-sm mt-2 flex items-center gap-1 {{ $stats['orders_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                @if($stats['orders_change'] >= 0)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    </svg>
                                @endif
                                {{ abs($stats['orders_change']) }}% vs periode sebelumnya
                            </p>
                        @endif
                    </div>
                    <div class="w-14 h-14 bg-warning-100 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Revenue -->
            <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted font-medium">Revenue ({{ $dateRangeLabel ?? 'Hari Ini' }})</p>
                        <p class="text-3xl font-bold text-text mt-2">Rp {{ number_format($stats['revenue'] ?? 0) }}</p>
                        @if(isset($stats['revenue_change']))
                            <p class="text-sm mt-2 flex items-center gap-1 {{ $stats['revenue_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                @if($stats['revenue_change'] >= 0)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    </svg>
                                @endif
                                {{ abs($stats['revenue_change']) }}% vs periode sebelumnya
                            </p>
                        @endif
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
        {{-- Only show inventory alerts section if user has any inventory features --}}
        @if(($hasInventoryBasic ?? false) || ($hasInventoryAdvanced ?? false))
        <!-- Alerts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Expiring Items Alert - Only show if has inventory_advanced feature --}}
            @if($hasInventoryAdvanced ?? false)
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
            @endif

            {{-- Low Stock Alert - Only show if has inventory_basic feature --}}
            @if($hasInventoryBasic ?? false)
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
            @endif
        </div>
        @endif
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions Card -->
        <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-text mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('pos.index') }}" class="flex items-center gap-3 p-3 rounded-xl text-white transition-all hover:opacity-90" style="background: linear-gradient(135deg, #7C3AED 0%, #9333EA 100%); box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.4);">
                    <x-icon name="calculator" class="w-5 h-5" />
                    <span class="font-semibold">Open POS</span>
                </a>
                <a href="{{ route('menu.products.create') }}" class="flex items-center gap-3 p-3 rounded-lg border border-border text-text hover:bg-secondary-50 transition-colors">
                    <x-icon name="plus" class="w-5 h-5 text-secondary-500" />
                    Add Product
                </a>
                @if($hasInventoryBasic ?? false)
                <a href="{{ route('inventory.reports.stock-valuation') }}" class="flex items-center gap-3 p-3 rounded-lg border border-border text-text hover:bg-secondary-50 transition-colors">
                    <x-icon name="chart-bar" class="w-5 h-5 text-secondary-500" />
                    View Reports
                </a>
                @endif
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
