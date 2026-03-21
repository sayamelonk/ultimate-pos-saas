<x-landing-layout>
    <!-- Hero Section -->
    <section class="hero-gradient min-h-screen flex items-center pt-16 relative overflow-hidden">
        <!-- Floating Elements -->
        <div class="absolute top-32 right-20 w-64 h-64 bg-accent/20 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-32 left-20 w-48 h-48 bg-primary-400/20 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="text-white">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur rounded-full text-sm font-medium mb-8">
                        <span class="w-2 h-2 bg-success rounded-full animate-pulse"></span>
                        <span>Multi-Tenant SaaS Platform</span>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                        Solusi POS
                        <span class="text-accent">Terlengkap</span>
                        untuk Bisnis F&B
                    </h1>

                    <p class="text-xl text-secondary-200 mb-8 leading-relaxed">
                        Kelola restoran, kafe, dan bisnis kuliner dengan sistem Point of Sale modern.
                        Multi-outlet, inventory real-time, KDS, mobile app, dan laporan lengkap dalam satu platform.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 mb-12">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-accent hover:bg-accent-600 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl">
                            <span>Coba Gratis 14 Hari</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="#demo" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl backdrop-blur transition-all border border-white/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Lihat Demo</span>
                        </a>
                    </div>

                    <!-- Trust Badges -->
                    <div class="flex flex-wrap items-center gap-6 text-secondary-300 text-sm">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Tanpa Kartu Kredit</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Setup 5 Menit</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Support 24/7</span>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Dashboard Preview -->
                <div class="relative lg:pl-8">
                    <div class="glass-effect rounded-2xl p-4 shadow-2xl">
                        <!-- Mock Dashboard -->
                        <div class="bg-white rounded-xl overflow-hidden shadow-inner">
                            <!-- Header Bar -->
                            <div class="bg-primary h-12 flex items-center px-4 gap-2">
                                <div class="flex gap-1.5">
                                    <div class="w-3 h-3 bg-danger-400 rounded-full"></div>
                                    <div class="w-3 h-3 bg-warning-400 rounded-full"></div>
                                    <div class="w-3 h-3 bg-success-400 rounded-full"></div>
                                </div>
                                <div class="flex-1 mx-4">
                                    <div class="bg-primary-400/50 h-6 rounded-md max-w-xs"></div>
                                </div>
                            </div>
                            <!-- Content -->
                            <div class="p-6 bg-background">
                                <!-- Stats Row -->
                                <div class="grid grid-cols-3 gap-4 mb-6">
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-border">
                                        <div class="text-sm text-muted mb-1">Penjualan Hari Ini</div>
                                        <div class="text-2xl font-bold text-primary">Rp 8.5 Jt</div>
                                        <div class="text-xs text-success">+12.5%</div>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-border">
                                        <div class="text-sm text-muted mb-1">Transaksi</div>
                                        <div class="text-2xl font-bold text-primary">127</div>
                                        <div class="text-xs text-success">+8.2%</div>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-border">
                                        <div class="text-sm text-muted mb-1">Rata-rata</div>
                                        <div class="text-2xl font-bold text-primary">Rp 67K</div>
                                        <div class="text-xs text-success">+3.1%</div>
                                    </div>
                                </div>
                                <!-- Chart Placeholder -->
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-border">
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="font-semibold text-text">Grafik Penjualan</span>
                                        <span class="text-sm text-muted">7 Hari Terakhir</span>
                                    </div>
                                    <div class="flex items-end gap-2 h-24">
                                        <div class="flex-1 bg-accent/20 rounded-t" style="height: 40%;"></div>
                                        <div class="flex-1 bg-accent/30 rounded-t" style="height: 60%;"></div>
                                        <div class="flex-1 bg-accent/40 rounded-t" style="height: 45%;"></div>
                                        <div class="flex-1 bg-accent/50 rounded-t" style="height: 80%;"></div>
                                        <div class="flex-1 bg-accent/60 rounded-t" style="height: 65%;"></div>
                                        <div class="flex-1 bg-accent/80 rounded-t" style="height: 90%;"></div>
                                        <div class="flex-1 bg-accent rounded-t" style="height: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Cards -->
                    <div class="absolute -left-4 top-1/2 glass-effect rounded-xl p-3 shadow-xl animate-float hidden lg:block" style="animation-delay: 1s;">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-success/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="text-white">
                                <div class="text-sm font-medium">Order #1234</div>
                                <div class="text-xs text-secondary-300">Selesai</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="#F8FAFC"/>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-accent/10 rounded-full text-accent font-medium text-sm mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Fitur Unggulan
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-text mb-4">
                    Semua yang Anda Butuhkan dalam <span class="gradient-text">Satu Platform</span>
                </h2>
                <p class="text-lg text-text-light">
                    Didesain khusus untuk bisnis F&B dengan fitur lengkap yang memudahkan operasional sehari-hari
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Multi-Outlet -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-border hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-text mb-3">Multi-Outlet Management</h3>
                    <p class="text-text-light">
                        Kelola banyak cabang dari satu dashboard. Setiap outlet memiliki pengaturan terpisah untuk pajak, diskon, dan produk.
                    </p>
                </div>

                <!-- Feature 2: Inventory -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-border hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-success/10 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-text mb-3">Inventory Real-Time</h3>
                    <p class="text-text-light">
                        Pantau stok bahan baku secara real-time dengan batch tracking, tanggal kadaluarsa, dan notifikasi stok rendah.
                    </p>
                </div>

                <!-- Feature 3: KDS -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-border hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-warning/10 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-text mb-3">Kitchen Display System</h3>
                    <p class="text-text-light">
                        Tampilan dapur digital dengan antrian pesanan real-time. Pisahkan berdasarkan station (Bar, Kitchen, Dessert).
                    </p>
                </div>

                <!-- Feature 4: Mobile App -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-border hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-accent/10 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-text mb-3">Mobile Waiter App</h3>
                    <p class="text-text-light">
                        Aplikasi mobile untuk pelayan dengan mode offline. Catat pesanan langsung di meja dan sync otomatis.
                    </p>
                </div>

                <!-- Feature 5: QR Order -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-border hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-info/10 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-text mb-3">QR Self-Order</h3>
                    <p class="text-text-light">
                        Pelanggan pesan sendiri via scan QR di meja. Kurangi waktu tunggu dan tingkatkan efisiensi pelayanan.
                    </p>
                </div>

                <!-- Feature 6: Reports -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-border hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-danger/10 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-text mb-3">Laporan & Analytics</h3>
                    <p class="text-text-light">
                        Laporan penjualan, inventory, dan keuangan lengkap. Export ke Excel/PDF untuk analisis lebih lanjut.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Modules Section -->
    <section id="modules" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 rounded-full text-primary font-medium text-sm mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Modul Lengkap
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-text mb-4">
                    12+ Modul <span class="gradient-text">Terintegrasi</span>
                </h2>
                <p class="text-lg text-text-light">
                    Semua modul yang Anda butuhkan untuk menjalankan bisnis F&B secara profesional
                </p>
            </div>

            <!-- Modules Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Module Cards -->
                @php
                $modules = [
                    ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'title' => 'User & Role', 'desc' => 'Kelola staff dengan role & permission detail'],
                    ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5', 'title' => 'Multi-Tenant', 'desc' => 'Satu sistem untuk banyak brand/franchise'],
                    ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'title' => 'Inventory', 'desc' => 'Stock, batch, expiry, PO, & transfer'],
                    ['icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'title' => 'Menu & Produk', 'desc' => 'Kategori, variant, modifier, combo'],
                    ['icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'title' => 'POS Core', 'desc' => 'Shift, held order, cash drawer'],
                    ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'title' => 'Pembayaran', 'desc' => 'Multi-payment, split bill, QRIS'],
                    ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'title' => 'Transaksi', 'desc' => 'History, void, refund dengan otorisasi'],
                    ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'title' => 'Pelanggan', 'desc' => 'Database customer & loyalty'],
                    ['icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', 'title' => 'Table Layout', 'desc' => 'Floor plan & status meja'],
                    ['icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'title' => 'KDS', 'desc' => 'Kitchen display multi-station'],
                    ['icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'title' => 'Waiter App', 'desc' => 'Mobile app dengan offline mode'],
                    ['icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'title' => 'Reports', 'desc' => 'Laporan lengkap & export'],
                ];
                @endphp

                @foreach($modules as $module)
                <div class="group p-6 bg-background rounded-xl border border-border hover:border-accent hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-primary/10 group-hover:bg-accent/10 rounded-lg flex items-center justify-center mb-4 transition-colors">
                        <svg class="w-6 h-6 text-primary group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $module['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-text mb-1">{{ $module['title'] }}</h3>
                    <p class="text-sm text-text-light">{{ $module['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 hero-gradient">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">500+</div>
                    <div class="text-secondary-300">Outlet Aktif</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">50K+</div>
                    <div class="text-secondary-300">Transaksi/Bulan</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">99.9%</div>
                    <div class="text-secondary-300">Uptime</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">24/7</div>
                    <div class="text-secondary-300">Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-24 bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-success/10 rounded-full text-success font-medium text-sm mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Harga Transparan
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-text mb-4">
                    Pilih Paket yang <span class="gradient-text">Sesuai Kebutuhan</span>
                </h2>
                <p class="text-lg text-text-light">
                    Mulai dari gratis untuk trial, upgrade kapan saja tanpa kontrak jangka panjang
                </p>
            </div>

            <!-- Pricing Cards -->
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Starter -->
                <div class="bg-white rounded-2xl p-8 border border-border shadow-lg">
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-bold text-text mb-2">Starter</h3>
                        <p class="text-text-light text-sm mb-4">Untuk bisnis baru & kecil</p>
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="text-4xl font-bold text-text">Rp 299K</span>
                            <span class="text-text-light">/bulan</span>
                        </div>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">1 Outlet</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">3 User</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">POS & Inventory Basic</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">Laporan Standar</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">Email Support</span>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full py-3 text-center font-semibold text-primary border-2 border-primary rounded-xl hover:bg-primary hover:text-white transition-colors">
                        Mulai Gratis
                    </a>
                </div>

                <!-- Professional - Popular -->
                <div class="bg-primary rounded-2xl p-8 shadow-xl relative transform scale-105">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 bg-accent text-white text-sm font-semibold rounded-full">
                        Paling Populer
                    </div>
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-bold text-white mb-2">Professional</h3>
                        <p class="text-secondary-200 text-sm mb-4">Untuk bisnis berkembang</p>
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="text-4xl font-bold text-white">Rp 599K</span>
                            <span class="text-secondary-200">/bulan</span>
                        </div>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-secondary-100">5 Outlet</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-secondary-100">15 User</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-secondary-100">Semua Modul</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-secondary-100">KDS & Mobile App</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-secondary-100">Priority Support</span>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full py-3 text-center font-semibold bg-accent text-white rounded-xl hover:bg-accent-600 transition-colors shadow-lg">
                        Mulai Gratis
                    </a>
                </div>

                <!-- Enterprise -->
                <div class="bg-white rounded-2xl p-8 border border-border shadow-lg">
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-bold text-text mb-2">Enterprise</h3>
                        <p class="text-text-light text-sm mb-4">Untuk bisnis besar & franchise</p>
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="text-4xl font-bold text-text">Custom</span>
                        </div>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">Unlimited Outlet</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">Unlimited User</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">Semua Fitur Pro</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">API & Custom Integration</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-text-light">Dedicated Account Manager</span>
                        </li>
                    </ul>
                    <a href="#contact" class="block w-full py-3 text-center font-semibold text-primary border-2 border-primary rounded-xl hover:bg-primary hover:text-white transition-colors">
                        Hubungi Sales
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-warning/10 rounded-full text-warning font-medium text-sm mb-4">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    Testimoni
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-text mb-4">
                    Dipercaya oleh <span class="gradient-text">Ratusan Bisnis</span>
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-background p-8 rounded-2xl border border-border">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-warning" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        @endfor
                    </div>
                    <p class="text-text-light mb-6">
                        "Sejak pakai Ultimate POS, operasional kafe kami jadi lebih efisien. Inventory tracking-nya sangat membantu mengontrol food cost."
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                            <span class="text-primary font-bold">AR</span>
                        </div>
                        <div>
                            <div class="font-semibold text-text">Andi Rahmat</div>
                            <div class="text-sm text-text-light">Owner, Kopi Nusantara</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-background p-8 rounded-2xl border border-border">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-warning" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        @endfor
                    </div>
                    <p class="text-text-light mb-6">
                        "Multi-outlet feature-nya luar biasa! Saya bisa monitor 8 cabang dari HP. KDS juga bikin dapur lebih terorganisir."
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-accent/10 rounded-full flex items-center justify-center">
                            <span class="text-accent font-bold">SW</span>
                        </div>
                        <div>
                            <div class="font-semibold text-text">Siti Wulandari</div>
                            <div class="text-sm text-text-light">Founder, Ayam Geprek Sultan</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-background p-8 rounded-2xl border border-border">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-warning" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        @endfor
                    </div>
                    <p class="text-text-light mb-6">
                        "QR Order-nya amazing! Pelanggan bisa order sendiri, staff bisa fokus ke service. Revenue naik 30% dalam 3 bulan."
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-success/10 rounded-full flex items-center justify-center">
                            <span class="text-success font-bold">BP</span>
                        </div>
                        <div>
                            <div class="font-semibold text-text">Budi Pratama</div>
                            <div class="text-sm text-text-light">Manager, Restoran Padang Jaya</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="contact" class="py-24 bg-background">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="hero-gradient rounded-3xl p-12 text-center relative overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-accent/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-primary-400/20 rounded-full blur-3xl"></div>

                <div class="relative z-10">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                        Siap Mengembangkan Bisnis Anda?
                    </h2>
                    <p class="text-xl text-secondary-200 mb-8 max-w-2xl mx-auto">
                        Mulai trial 14 hari gratis tanpa kartu kredit. Setup dalam 5 menit dan rasakan kemudahan mengelola bisnis F&B.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-accent hover:bg-accent-600 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl">
                            <span>Coba Gratis Sekarang</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="https://wa.me/6281234567890" target="_blank" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl backdrop-blur transition-all border border-white/20">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <span>Chat via WhatsApp</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-landing-layout>
