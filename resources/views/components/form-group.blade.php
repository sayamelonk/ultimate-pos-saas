@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
])

@php
    $hasError = $name && $errors->has($name);
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-text mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($hint && !$hasError)
        <p class="mt-1.5 text-sm text-muted">{{ $hint }}</p>
    @endif

    @if($hasError)
        <p class="mt-1.5 text-sm text-danger">
            {{ $errors->first($name) }}
        </p>
    @endif
</div>
