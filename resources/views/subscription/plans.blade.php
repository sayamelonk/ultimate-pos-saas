<x-app-layout>
    <x-slot name="title">Pilih Paket - Ultimate POS</x-slot>

    <x-slot name="header">
        <h2 class="text-2xl font-bold text-text">Pilih Paket Langganan</h2>
        <p class="text-muted mt-1">Pilih paket yang sesuai dengan kebutuhan bisnis Anda</p>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 p-4 bg-danger/10 border border-danger/20 rounded-lg text-danger">
            {{ session('error') }}
        </div>
    @endif

    <!-- Billing Toggle -->
    <div class="flex justify-center mb-8">
        <div class="inline-flex items-center gap-3 p-1 bg-secondary-100 rounded-lg">
            <button type="button"
                    id="btn-monthly"
                    class="px-4 py-2 rounded-md text-sm font-medium transition-colors billing-toggle active"
                    data-cycle="monthly">
                Bulanan
            </button>
            <button type="button"
                    id="btn-yearly"
                    class="px-4 py-2 rounded-md text-sm font-medium transition-colors billing-toggle"
                    data-cycle="yearly">
                Tahunan
                <span class="ml-1 text-xs bg-success text-white px-2 py-0.5 rounded-full">Hemat 20%</span>
            </button>
        </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        @foreach($plans as $plan)
            <div class="relative bg-surface rounded-xl border border-border p-6 shadow-sm {{ $loop->index === 1 ? 'ring-2 ring-primary' : '' }}">
                @if($loop->index === 1)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-primary text-white text-xs font-medium px-3 py-1 rounded-full">
                            Paling Populer
                        </span>
                    </div>
                @endif

                <div class="text-center mb-6">
                    <h3 class="text-xl font-bold text-text">{{ $plan->name }}</h3>
                    <p class="text-sm text-muted mt-2">{{ $plan->description }}</p>
                </div>

                <!-- Monthly Price -->
                <div class="text-center mb-6 price-display price-monthly">
                    <span class="text-4xl font-bold text-text">{{ $plan->getFormattedPriceMonthly() }}</span>
                    <span class="text-muted">/bulan</span>
                </div>

                <!-- Yearly Price -->
                <div class="text-center mb-6 price-display price-yearly hidden">
                    <span class="text-4xl font-bold text-text">{{ $plan->getFormattedPriceYearly() }}</span>
                    <span class="text-muted">/tahun</span>
                    <p class="text-sm text-success mt-1">
                        Hemat Rp {{ number_format(($plan->price_monthly * 12) - $plan->price_yearly, 0, ',', '.') }}
                    </p>
                </div>

                <!-- Features -->
                <ul class="space-y-3 mb-6">
                    <li class="flex items-center gap-2">
                        <x-icon name="check-circle" class="w-5 h-5 text-success" />
                        <span class="text-sm text-text">
                            {{ $plan->max_outlets === -1 ? 'Unlimited' : $plan->max_outlets }} Outlet
                        </span>
                    </li>
                    <li class="flex items-center gap-2">
                        <x-icon name="check-circle" class="w-5 h-5 text-success" />
                        <span class="text-sm text-text">
                            {{ $plan->max_users === -1 ? 'Unlimited' : $plan->max_users }} User
                        </span>
                    </li>
                    @if($plan->features)
                        @foreach($plan->features as $feature)
                            <li class="flex items-center gap-2">
                                <x-icon name="check-circle" class="w-5 h-5 text-success" />
                                <span class="text-sm text-text">{{ $feature }}</span>
                            </li>
                        @endforeach
                    @endif
                </ul>

                <!-- Subscribe Button -->
                @if($currentSubscription && $currentSubscription->plan->id === $plan->id)
                    <button type="button" disabled
                            class="w-full py-3 px-4 bg-secondary-200 text-secondary-500 rounded-lg font-medium cursor-not-allowed">
                        Paket Aktif
                    </button>
                @else
                    <form action="{{ route('subscription.subscribe', $plan) }}" method="POST">
                        @csrf
                        <input type="hidden" name="billing_cycle" class="billing-cycle-input" value="monthly">
                        <button type="submit"
                                class="w-full py-3 px-4 {{ $loop->index === 1 ? 'bg-primary text-white hover:bg-primary-600' : 'bg-secondary-100 text-text hover:bg-secondary-200' }} rounded-lg font-medium transition-colors">
                            Pilih {{ $plan->name }}
                        </button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Current Subscription Info -->
    @if($currentSubscription)
        <div class="mt-8 max-w-2xl mx-auto">
            <div class="bg-surface rounded-xl border border-border p-6">
                <h3 class="font-semibold text-text mb-4">Langganan Aktif</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-medium text-text">{{ $currentSubscription->plan->name }}</p>
                        <p class="text-sm text-muted">
                            Berakhir: {{ $currentSubscription->ends_at?->format('d M Y') ?? '-' }}
                            @if($currentSubscription->daysRemaining() <= 7 && $currentSubscription->daysRemaining() > 0)
                                <span class="text-warning">({{ $currentSubscription->daysRemaining() }} hari lagi)</span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('subscription.index') }}" class="text-primary hover:underline text-sm">
                        Kelola Langganan
                    </a>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtns = document.querySelectorAll('.billing-toggle');
            const monthlyPrices = document.querySelectorAll('.price-monthly');
            const yearlyPrices = document.querySelectorAll('.price-yearly');
            const billingInputs = document.querySelectorAll('.billing-cycle-input');

            toggleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const cycle = this.dataset.cycle;

                    toggleBtns.forEach(b => b.classList.remove('active', 'bg-white', 'shadow'));
                    this.classList.add('active', 'bg-white', 'shadow');

                    if (cycle === 'yearly') {
                        monthlyPrices.forEach(el => el.classList.add('hidden'));
                        yearlyPrices.forEach(el => el.classList.remove('hidden'));
                    } else {
                        monthlyPrices.forEach(el => el.classList.remove('hidden'));
                        yearlyPrices.forEach(el => el.classList.add('hidden'));
                    }

                    billingInputs.forEach(input => input.value = cycle);
                });
            });

            // Set initial state
            document.getElementById('btn-monthly').classList.add('bg-white', 'shadow');
        });
    </script>
    @endpush
</x-app-layout>
