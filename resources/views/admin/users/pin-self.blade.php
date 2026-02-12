<x-app-layout>
    <x-slot name="title">My PIN - Ultimate POS</x-slot>

    @section('page-title', 'My PIN')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.dashboard') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">My Authorization PIN</h2>
                <p class="text-muted mt-1">Set or update your authorization PIN</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-md">
        <x-card>
            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-border">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <x-icon name="key" class="w-8 h-8 text-primary" />
                </div>
                <div>
                    <h3 class="font-semibold text-lg">Authorization PIN</h3>
                    @if($hasPin)
                        <x-badge type="success" class="mt-1">PIN Active</x-badge>
                    @else
                        <x-badge type="secondary" class="mt-1">No PIN Set</x-badge>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.my-pin.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    @if($hasPin)
                        <div>
                            <label class="block text-sm font-medium text-text mb-1">
                                Current PIN <span class="text-danger">*</span>
                            </label>
                            <x-input
                                type="password"
                                name="current_pin"
                                maxlength="{{ $settings->pin_length }}"
                                placeholder="Enter current PIN"
                                pattern="[0-9]*"
                                inputmode="numeric"
                                required
                            />
                            @error('current_pin')
                                <p class="text-danger text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            {{ $hasPin ? 'New PIN' : 'PIN' }} <span class="text-danger">*</span>
                        </label>
                        <x-input
                            type="password"
                            name="pin"
                            maxlength="{{ $settings->pin_length }}"
                            placeholder="Enter {{ $settings->pin_length }}-digit PIN"
                            pattern="[0-9]*"
                            inputmode="numeric"
                            required
                            autocomplete="new-password"
                        />
                        <p class="text-xs text-muted mt-1">Must be exactly {{ $settings->pin_length }} digits</p>
                        @error('pin')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            Confirm PIN <span class="text-danger">*</span>
                        </label>
                        <x-input
                            type="password"
                            name="pin_confirmation"
                            maxlength="{{ $settings->pin_length }}"
                            placeholder="Confirm {{ $settings->pin_length }}-digit PIN"
                            pattern="[0-9]*"
                            inputmode="numeric"
                            required
                            autocomplete="new-password"
                        />
                    </div>
                </div>

                <div class="mt-6 p-4 bg-info/10 border border-info/20 rounded-lg">
                    <div class="flex gap-3">
                        <x-icon name="information-circle" class="w-5 h-5 text-info shrink-0 mt-0.5" />
                        <div class="text-sm">
                            <p class="font-medium text-info-700">About Authorization PIN</p>
                            <p class="text-muted mt-1">Your PIN is used to authorize sensitive actions at the POS such as void transactions, refunds, and price overrides. Keep it secure and don't share it with anyone.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-border">
                    <x-button href="{{ route('admin.dashboard') }}" variant="secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit" icon="check">
                        {{ $hasPin ? 'Update PIN' : 'Set PIN' }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
