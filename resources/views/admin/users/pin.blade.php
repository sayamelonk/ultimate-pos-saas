<x-app-layout>
    <x-slot name="title">Set PIN - {{ $user->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Set User PIN')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.users.show', $user) }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Set PIN for {{ $user->name }}</h2>
                <p class="text-muted mt-1">Set authorization PIN for this user</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-md">
        <x-card>
            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-border">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-xl font-bold text-primary">{{ $user->initials }}</span>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">{{ $user->name }}</h3>
                    <p class="text-sm text-muted">{{ $user->email }}</p>
                    @if($hasPin)
                        <x-badge type="success" class="mt-1">PIN Active</x-badge>
                    @else
                        <x-badge type="secondary" class="mt-1">No PIN Set</x-badge>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.users.pin.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4">
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

                <div class="mt-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
                    <div class="flex gap-3">
                        <x-icon name="exclamation-triangle" class="w-5 h-5 text-warning shrink-0 mt-0.5" />
                        <div class="text-sm">
                            <p class="font-medium text-warning-700">Important</p>
                            <p class="text-muted mt-1">This PIN will be used to authorize sensitive actions like void, refund, and price override. Make sure to remember it or store it securely.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-6 border-t border-border">
                    @if($hasPin)
                        <form action="{{ route('admin.users.pin.destroy', $user) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="danger" onclick="return confirm('Deactivate this user\'s PIN?')">
                                Deactivate PIN
                            </x-button>
                        </form>
                    @else
                        <div></div>
                    @endif

                    <div class="flex items-center gap-3">
                        <x-button href="{{ route('admin.users.show', $user) }}" variant="secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit" icon="check">
                            {{ $hasPin ? 'Update PIN' : 'Set PIN' }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
