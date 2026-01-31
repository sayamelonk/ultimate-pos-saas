@props([
    'name' => 'confirm',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'type' => 'danger',
])

@php
    $buttonVariants = [
        'danger' => 'danger',
        'warning' => 'warning',
        'primary' => 'primary',
        'success' => 'success',
    ];

    $iconColors = [
        'danger' => 'text-danger-500 bg-danger-100',
        'warning' => 'text-warning-500 bg-warning-100',
        'primary' => 'text-primary-500 bg-primary-100',
        'success' => 'text-success-500 bg-success-100',
    ];

    $iconPaths = [
        'danger' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'primary' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
@endphp

<x-modal :name="$name" size="sm">
    <div class="text-center">
        {{-- Icon --}}
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full {{ $iconColors[$type] ?? $iconColors['danger'] }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths[$type] ?? $iconPaths['danger'] }}"/>
            </svg>
        </div>

        {{-- Title --}}
        <h3 class="mt-4 text-lg font-semibold text-text">{{ $title }}</h3>

        {{-- Message --}}
        <p class="mt-2 text-sm text-muted">{{ $message }}</p>

        {{-- Slot for additional content --}}
        @if($slot->isNotEmpty())
            <div class="mt-4">
                {{ $slot }}
            </div>
        @endif
    </div>

    <x-slot:footer>
        <div class="flex items-center justify-end gap-3">
            <x-button variant="outline-secondary" @click="$dispatch('close-modal', '{{ $name }}')">
                {{ $cancelText }}
            </x-button>
            <x-button variant="{{ $buttonVariants[$type] ?? 'danger' }}" {{ $attributes }}>
                {{ $confirmText }}
            </x-button>
        </div>
    </x-slot:footer>
</x-modal>
