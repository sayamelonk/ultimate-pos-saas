@props([
    'type' => 'default',
    'size' => 'md',
    'dot' => false,
    'removable' => false,
])

@php
    $types = [
        'default' => 'bg-secondary-100 text-secondary-700',
        'primary' => 'bg-primary-100 text-primary-700',
        'secondary' => 'bg-secondary-100 text-secondary-700',
        'accent' => 'bg-accent-100 text-accent-700',
        'success' => 'bg-success-100 text-success-700',
        'warning' => 'bg-warning-100 text-warning-700',
        'danger' => 'bg-danger-100 text-danger-700',
        'info' => 'bg-info-100 text-info-700',
    ];

    $dotColors = [
        'default' => 'bg-secondary-400',
        'primary' => 'bg-primary-500',
        'secondary' => 'bg-secondary-400',
        'accent' => 'bg-accent-500',
        'success' => 'bg-success-500',
        'warning' => 'bg-warning-500',
        'danger' => 'bg-danger-500',
        'info' => 'bg-info-500',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1.5 text-sm',
    ];

    $classes = ($types[$type] ?? $types['default']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 font-medium rounded-full ' . $classes]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$type] ?? $dotColors['default'] }}"></span>
    @endif

    {{ $slot }}

    @if($removable)
        <button type="button"
                class="flex-shrink-0 ml-0.5 -mr-1 p-0.5 rounded-full hover:bg-black/10 focus:outline-none focus:ring-2 focus:ring-offset-1 transition-colors"
                @click="$el.parentElement.remove()">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</span>
