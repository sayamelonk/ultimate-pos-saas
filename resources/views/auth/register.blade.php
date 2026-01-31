<x-guest-layout>
    <x-slot name="title">Register - Ultimate POS</x-slot>

    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-text">Create Account</h1>
        <p class="text-muted mt-2">Start your free trial today</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <x-input type="text" name="name" label="Full Name" placeholder="Enter your full name" :value="old('name')"
            required autofocus />

        <!-- Email -->
        <x-input type="email" name="email" label="Email Address" placeholder="Enter your email" :value="old('email')"
            required />

        <!-- Business Name -->
        <x-input type="text" name="business_name" label="Business Name" placeholder="Enter your business name"
            :value="old('business_name')" required hint="This will be your tenant/company name" />

        <!-- Phone -->
        <x-input type="tel" name="phone" label="Phone Number" placeholder="Enter your phone number"
            :value="old('phone')" />

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-text mb-1.5">
                Password <span class="text-danger">*</span>
            </label>
            <div x-data="{ showPassword: false }" class="relative">
                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                    autocomplete="new-password"
                    class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                              focus:ring-2 focus:ring-accent/20 focus:border-accent
                              placeholder:text-muted transition-colors pr-12
                              @error('password') border-danger @enderror"
                    placeholder="Create a password">
                <button type="button" @click="showPassword = !showPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-text transition-colors">
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-text mb-1.5">
                Confirm Password <span class="text-danger">*</span>
            </label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                autocomplete="new-password"
                class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                          focus:ring-2 focus:ring-accent/20 focus:border-accent
                          placeholder:text-muted transition-colors"
                placeholder="Confirm your password">
        </div>

        <!-- Terms -->
        <div class="flex items-start gap-2">
            <input type="checkbox" name="terms" id="terms" required
                class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20">
            <label for="terms" class="text-sm text-muted">
                I agree to the
                <a href="#" class="text-accent hover:text-accent-600">Terms of Service</a>
                and
                <a href="#" class="text-accent hover:text-accent-600">Privacy Policy</a>
            </label>
        </div>

        <!-- Submit Button -->
        <x-button type="submit" variant="primary" class="w-full">
            Create Account
        </x-button>
    </form>

    <!-- Login Link -->
    <div class="mt-6 text-center text-sm text-muted">
        Already have an account?
        <a href="{{ route('login') }}" class="text-accent hover:text-accent-600 font-medium">
            Sign in
        </a>
    </div>
</x-guest-layout>
