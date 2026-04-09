<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup Wizard - Ultimate POS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background" x-data="{ currentStep: {{ $currentStep }} }">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-border py-4">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xl font-bold text-text">Ultimate POS</span>
                            <span class="text-sm text-muted ml-2">{{ __('onboarding.setup_wizard') }}</span>
                        </div>
                    </div>
                    <a href="{{ route('onboarding.skip') }}" class="text-sm text-muted hover:text-text">
                        {{ __('onboarding.skip_for_now') }}
                    </a>
                </div>
            </div>
        </header>

        <!-- Progress Steps -->
        <div class="bg-white border-b border-border py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    @foreach($steps as $stepNum => $step)
                        <div class="flex items-center {{ $stepNum < count($steps) ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold transition-colors
                                    {{ $step['completed'] ? 'bg-success text-white' : ($currentStep == $stepNum ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500') }}">
                                    @if($step['completed'])
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{ $stepNum }}
                                    @endif
                                </div>
                                <span class="mt-2 text-xs font-medium {{ $currentStep == $stepNum ? 'text-primary' : 'text-muted' }}">
                                    {{ $step['title'] }}
                                </span>
                            </div>
                            @if($stepNum < count($steps))
                                <div class="flex-1 mx-4 h-0.5 {{ $step['completed'] ? 'bg-success' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 p-4 bg-success/10 border border-success/20 text-success-700 rounded-xl">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Step 1: Business Settings -->
                <div x-show="currentStep === 1" x-cloak>
                    <div class="bg-white rounded-2xl shadow-sm border border-border p-8">
                        <h2 class="text-2xl font-bold text-text mb-2">{{ __('onboarding.step_1_title') }}</h2>
                        <p class="text-muted mb-6">{{ __('onboarding.step_1_description') }}</p>

                        <form action="{{ route('onboarding.business') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.business_name') }}</label>
                                    <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required
                                           class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.logo') }}</label>
                                    <input type="file" name="logo" accept="image/*"
                                           class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('logo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.phone_number') }}</label>
                                    <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}"
                                           class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.timezone') }}</label>
                                        <select name="timezone" required
                                                class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                            <option value="Asia/Jakarta" {{ old('timezone', $tenant->timezone) == 'Asia/Jakarta' ? 'selected' : '' }}>{{ __('onboarding.timezone_wib') }}</option>
                                            <option value="Asia/Makassar" {{ old('timezone', $tenant->timezone) == 'Asia/Makassar' ? 'selected' : '' }}>{{ __('onboarding.timezone_wita') }}</option>
                                            <option value="Asia/Jayapura" {{ old('timezone', $tenant->timezone) == 'Asia/Jayapura' ? 'selected' : '' }}>{{ __('onboarding.timezone_wit') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.currency') }}</label>
                                        <select name="currency" required
                                                class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                            <option value="IDR" {{ old('currency', $tenant->currency) == 'IDR' ? 'selected' : '' }}>{{ __('onboarding.currency_idr') }}</option>
                                            <option value="USD" {{ old('currency', $tenant->currency) == 'USD' ? 'selected' : '' }}>{{ __('onboarding.currency_usd') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.tax') }}</label>
                                        <input type="number" name="tax_percentage" value="{{ old('tax_percentage', $tenant->tax_percentage ?? 11) }}" step="0.01" min="0" max="100"
                                               class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.service_charge') }}</label>
                                        <input type="number" name="service_charge_percentage" value="{{ old('service_charge_percentage', $tenant->service_charge_percentage ?? 0) }}" step="0.01" min="0" max="100"
                                               class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                                    {{ __('onboarding.save_continue') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Step 2: Add First Product -->
                <div x-show="currentStep === 2" x-cloak>
                    <div class="bg-white rounded-2xl shadow-sm border border-border p-8">
                        <h2 class="text-2xl font-bold text-text mb-2">{{ __('onboarding.step_2_title') }}</h2>
                        <p class="text-muted mb-6">{{ __('onboarding.step_2_description') }}</p>

                        <form action="{{ route('onboarding.product') }}" method="POST">
                            @csrf
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.product_name') }}</label>
                                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="{{ __('onboarding.product_name_placeholder') }}"
                                           class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.category') }}</label>
                                    <input type="text" name="category_name" value="{{ old('category_name') }}" required placeholder="{{ __('onboarding.category_placeholder') }}"
                                           class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('category_name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.price') }} ({{ $tenant->currency ?? 'IDR' }})</label>
                                    <input type="number" name="price" value="{{ old('price') }}" required min="0" step="100" placeholder="{{ __('onboarding.price_placeholder') }}"
                                           class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('price') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.sku') }}</label>
                                    <input type="text" name="sku" value="{{ old('sku') }}" placeholder="{{ __('onboarding.sku_placeholder') }}"
                                           class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-text mb-1.5">{{ __('onboarding.description') }}</label>
                                    <textarea name="description" rows="3" placeholder="{{ __('onboarding.description_placeholder') }}"
                                              class="w-full px-4 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-between">
                                <button type="button" @click="currentStep = 1" class="px-6 py-2.5 text-text hover:bg-gray-100 font-medium rounded-xl transition-colors">
                                    {{ __('onboarding.back') }}
                                </button>
                                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                                    {{ __('onboarding.save_continue') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Step 3: Payment Methods -->
                <div x-show="currentStep === 3" x-cloak>
                    <div class="bg-white rounded-2xl shadow-sm border border-border p-8">
                        <h2 class="text-2xl font-bold text-text mb-2">{{ __('onboarding.step_3_title') }}</h2>
                        <p class="text-muted mb-6">{{ __('onboarding.step_3_description') }}</p>

                        <form action="{{ route('onboarding.payment-methods') }}" method="POST" x-data="{ methods: ['cash'] }">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                @php
                                    $paymentMethods = [
                                        'cash' => ['label' => __('onboarding.payment_cash'), 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                                        'bank_transfer' => ['label' => __('onboarding.payment_bank_transfer'), 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                                        'qris' => ['label' => __('onboarding.payment_qris'), 'icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
                                        'credit_card' => ['label' => __('onboarding.payment_credit_card'), 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                                        'debit_card' => ['label' => __('onboarding.payment_debit_card'), 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                                        'e_wallet' => ['label' => __('onboarding.payment_e_wallet'), 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                                    ];
                                @endphp

                                @foreach($paymentMethods as $code => $method)
                                    <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-colors"
                                           :class="methods.includes('{{ $code }}') ? 'border-primary bg-primary/5' : 'border-border hover:border-gray-300'">
                                        <input type="checkbox" name="methods[]" value="{{ $code }}"
                                               x-model="methods"
                                               class="sr-only">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3"
                                             :class="methods.includes('{{ $code }}') ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-500'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $method['icon'] }}"/>
                                            </svg>
                                        </div>
                                        <span class="font-medium" :class="methods.includes('{{ $code }}') ? 'text-primary' : 'text-text'">{{ $method['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="mt-8 flex justify-between">
                                <button type="button" @click="currentStep = 2" class="px-6 py-2.5 text-text hover:bg-gray-100 font-medium rounded-xl transition-colors">
                                    {{ __('onboarding.back') }}
                                </button>
                                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                                    {{ __('onboarding.save_continue') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Step 4: Invite Staff -->
                <div x-show="currentStep === 4" x-cloak>
                    <div class="bg-white rounded-2xl shadow-sm border border-border p-8">
                        <h2 class="text-2xl font-bold text-text mb-2">{{ __('onboarding.step_4_title') }}</h2>
                        <p class="text-muted mb-6">{{ __('onboarding.step_4_description') }}</p>

                        <form action="{{ route('onboarding.staff') }}" method="POST" x-data="{ staffCount: 0 }">
                            @csrf
                            <div class="space-y-4" x-show="staffCount > 0">
                                <template x-for="i in staffCount" :key="i">
                                    <div class="p-4 bg-gray-50 rounded-xl space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-text" x-text="'{{ __('onboarding.staff_number') }}' + i"></span>
                                            <button type="button" @click="staffCount--" class="text-danger hover:text-danger-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-3 gap-3">
                                            <input type="text" :name="'staff[' + (i-1) + '][name]'" placeholder="{{ __('onboarding.name_placeholder') }}" required
                                                   class="px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                            <input type="email" :name="'staff[' + (i-1) + '][email]'" placeholder="{{ __('onboarding.email_placeholder') }}" required
                                                   class="px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                            <select :name="'staff[' + (i-1) + '][role]'" required
                                                    class="px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                                <option value="cashier">{{ __('onboarding.role_cashier') }}</option>
                                                <option value="waiter">{{ __('onboarding.role_waiter') }}</option>
                                                <option value="manager">{{ __('onboarding.role_manager') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4" x-show="staffCount < 3">
                                <button type="button" @click="staffCount++"
                                        class="flex items-center gap-2 px-4 py-2.5 text-primary hover:bg-primary/5 font-medium rounded-xl transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    {{ __('onboarding.add_staff_member') }}
                                </button>
                            </div>

                            <div class="mt-8 flex justify-between">
                                <button type="button" @click="currentStep = 3" class="px-6 py-2.5 text-text hover:bg-gray-100 font-medium rounded-xl transition-colors">
                                    {{ __('onboarding.back') }}
                                </button>
                                <div class="flex gap-3">
                                    <a href="{{ route('onboarding.complete') }}" class="px-6 py-2.5 text-muted hover:text-text font-medium rounded-xl transition-colors">
                                        {{ __('onboarding.skip_finish') }}
                                    </a>
                                    <button type="submit" x-show="staffCount > 0" class="px-6 py-2.5 bg-primary hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                                        {{ __('onboarding.send_invites_finish') }}
                                    </button>
                                    <a href="{{ route('onboarding.complete') }}" x-show="staffCount === 0" class="px-6 py-2.5 bg-primary hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                                        {{ __('onboarding.complete_setup') }}
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Trial Info -->
                <div class="mt-6 p-4 bg-accent/10 border border-accent/20 rounded-xl">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-accent/20 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-text">{{ __('onboarding.trial_active_title') }}</p>
                            <p class="text-sm text-muted">{{ __('onboarding.trial_active_description') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
