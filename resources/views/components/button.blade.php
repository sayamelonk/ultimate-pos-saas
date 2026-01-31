@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'disabled' => false,
    'loading' => false,
    'icon' => null,
    'iconPosition' => 'left',
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-600 focus:ring-primary-500',
        'secondary' => 'bg-secondary-100 text-secondary-700 hover:bg-secondary-200 focus:ring-secondary-500',
        'accent' => 'bg-accent text-white hover:bg-accent-600 focus:ring-accent-500',
        'success' => 'bg-success text-white hover:bg-success-600 focus:ring-success-500',
        'warning' => 'bg-warning text-white hover:bg-warning-600 focus:ring-warning-500',
        'danger' => 'bg-danger text-white hover:bg-danger-600 focus:ring-danger-500',
        'outline' => 'border-2 border-primary text-primary hover:bg-primary hover:text-white focus:ring-primary-500',
        'outline-secondary' => 'border border-border text-secondary-700 hover:bg-secondary-50 focus:ring-secondary-500',
        'ghost' => 'text-secondary-600 hover:bg-secondary-100 focus:ring-secondary-500',
        'link' => 'text-primary hover:text-primary-600 hover:underline focus:ring-primary-500',
    ];

    $sizes = [
        'xs' => 'px-2.5 py-1.5 text-xs gap-1',
        'sm' => 'px-3 py-2 text-sm gap-1.5',
        'md' => 'px-4 py-2.5 text-sm gap-2',
        'lg' => 'px-5 py-3 text-base gap-2',
        'xl' => 'px-6 py-3.5 text-base gap-2.5',
    ];

    $iconSizes = [
        'xs' => 'w-3.5 h-3.5',
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-5 h-5',
        'xl' => 'w-6 h-6',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => $classes]) }}
       @if($disabled) aria-disabled="true" @endif>
        @if($loading)
            <svg class="animate-spin {{ $iconSizes[$size] ?? 'w-5 h-5' }}" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @elseif($icon && $iconPosition === 'left')
            <x-icon :name="$icon" :class="$iconSizes[$size] ?? 'w-5 h-5'" />
        @endif

        {{ $slot }}

        @if($icon && $iconPosition === 'right' && !$loading)
            <x-icon :name="$icon" :class="$iconSizes[$size] ?? 'w-5 h-5'" />
        @endif
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => $classes]) }}
            @if($disabled) disabled @endif
            @if($loading) disabled @endif>
        @if($loading)
            <svg class="animate-spin {{ $iconSizes[$size] ?? 'w-5 h-5' }}" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @elseif($icon && $iconPosition === 'left')
            <x-icon :name="$icon" :class="$iconSizes[$size] ?? 'w-5 h-5'" />
        @endif

        {{ $slot }}

        @if($icon && $iconPosition === 'right' && !$loading)
            <x-icon :name="$icon" :class="$iconSizes[$size] ?? 'w-5 h-5'" />
        @endif
    </button>
@endif
