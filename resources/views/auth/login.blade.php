<x-guest-layout>
    <x-slot name="title">Login - Ultimate POS</x-slot>

    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-text">Welcome Back</h1>
        <p class="text-muted mt-2">Sign in to your account</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-text mb-2">
                Email Address
            </label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="email"
                   class="w-full px-4 py-3 border border-border rounded-lg bg-surface text-text
                          focus:ring-2 focus:ring-accent focus:border-accent
                          placeholder:text-muted transition-colors
                          @error('email') border-danger @enderror"
                   placeholder="Enter your email">
            @error('email')
                <p class="mt-2 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-text mb-2">
                Password
            </label>
            <div x-data="{ showPassword: false }" class="relative">
                <input :type="showPassword ? 'text' : 'password'"
                       id="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       class="w-full px-4 py-3 border border-border rounded-lg bg-surface text-text
                              focus:ring-2 focus:ring-accent focus:border-accent
                              placeholder:text-muted transition-colors pr-12
                              @error('password') border-danger @enderror"
                       placeholder="Enter your password">
                <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-text transition-colors">
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-2 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox"
                       name="remember"
                       class="w-4 h-4 rounded border-border text-accent focus:ring-accent">
                <span class="text-sm text-text-light">Remember me</span>
            </label>
            <a href="#" class="text-sm text-accent hover:text-accent-600 transition-colors">
                Forgot password?
            </a>
        </div>

        <!-- Submit Button -->
        <button type="submit"
                class="w-full py-3 px-4 bg-primary text-white font-medium rounded-lg
                       hover:bg-primary-600 focus:ring-4 focus:ring-primary-200
                       transition-all duration-200 shadow-sm hover:shadow">
            Sign In
        </button>
    </form>

    <!-- Register Link -->
    <div class="mt-6 text-center text-sm text-muted">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-accent hover:text-accent-600 font-medium">
            Create one
        </a>
    </div>

    <!-- Demo Credentials -->
    <div class="mt-6 p-4 bg-secondary-50 rounded-lg border border-secondary-200">
        <p class="text-sm font-medium text-secondary-700 mb-3">Demo Credentials:</p>
        <div class="space-y-2 text-xs text-secondary-600">
            <p><span class="font-medium">Super Admin:</span> superadmin@ultimatepos.com</p>
            <p><span class="font-medium">Owner:</span> owner@demo.com</p>
            <p><span class="font-medium">Manager:</span> manager@demo.com</p>
            <p><span class="font-medium">Cashier:</span> cashier@demo.com</p>
            <p class="text-secondary-500 italic">Password: password</p>
        </div>
    </div>
</x-guest-layout>
