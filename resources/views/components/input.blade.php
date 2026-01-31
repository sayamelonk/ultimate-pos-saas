@props([
    'type' => 'text',
    'name' => '',
    'label' => null,
    'placeholder' => '',
    'value' => '',
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'prefix' => null,
    'suffix' => null,
    'size' => 'md',
])

@php
    $hasError = $error || ($errors->has($name) ?? false);

    $sizes = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-4 py-3 text-base',
    ];

    $inputClasses = 'block w-full rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 ' .
        ($sizes[$size] ?? $sizes['md']) . ' ' .
        ($hasError
            ? 'border-danger-300 text-danger-900 placeholder-danger-300 focus:border-danger-500 focus:ring-danger-500/20'
            : 'border-border text-text placeholder-muted focus:border-accent focus:ring-accent/20') . ' ' .
        ($disabled ? 'bg-secondary-50 cursor-not-allowed' : 'bg-surface') . ' ' .
        ($prefix ? 'pl-10' : '') . ' ' .
        ($suffix ? 'pr-10' : '');
@endphp

<div @class(['w-full' => !$attributes->has('class'), $attributes->get('class') => $attributes->has('class')])>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-text mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($prefix)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-muted">{{ $prefix }}</span>
            </div>
        @endif

        <input type="{{ $type }}"
               name="{{ $name }}"
               id="{{ $name }}"
               value="{{ old($name, $value) }}"
               placeholder="{{ $placeholder }}"
               @if($required) required @endif
               @if($disabled) disabled @endif
               @if($readonly) readonly @endif
               {{ $attributes->except('class')->merge(['class' => $inputClasses]) }}>

        @if($suffix)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <span class="text-muted">{{ $suffix }}</span>
            </div>
        @endif
    </div>

    @if($hint && !$hasError)
        <p class="mt-1.5 text-sm text-muted">{{ $hint }}</p>
    @endif

    @if($hasError)
        <p class="mt-1.5 text-sm text-danger">
            {{ $error ?? $errors->first($name) }}
        </p>
    @endif
</div>
