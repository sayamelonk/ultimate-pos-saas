@props([
    'href' => null,
    'type' => 'link',
    'danger' => false,
])

@php
    $classes = 'flex items-center gap-3 w-full px-4 py-2 text-sm text-left transition-colors ' .
        ($danger
            ? 'text-danger-600 hover:bg-danger-50'
            : 'text-secondary-700 hover:bg-secondary-50');
@endphp

@if($type === 'button')
    <button {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@elseif($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </div>
@endif
