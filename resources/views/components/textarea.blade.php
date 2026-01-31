@props([
    'name' => '',
    'label' => null,
    'placeholder' => '',
    'value' => '',
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'rows' => 4,
])

@php
    $hasError = $error || ($errors->has($name) ?? false);

    $textareaClasses = 'block w-full px-4 py-2.5 text-sm rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 resize-y ' .
        ($hasError
            ? 'border-danger-300 text-danger-900 placeholder-danger-300 focus:border-danger-500 focus:ring-danger-500/20'
            : 'border-border text-text placeholder-muted focus:border-accent focus:ring-accent/20') . ' ' .
        ($disabled ? 'bg-secondary-50 cursor-not-allowed' : 'bg-surface');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-text mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <textarea name="{{ $name }}"
              id="{{ $name }}"
              rows="{{ $rows }}"
              placeholder="{{ $placeholder }}"
              @if($required) required @endif
              @if($disabled) disabled @endif
              @if($readonly) readonly @endif
              {{ $attributes->except('class')->merge(['class' => $textareaClasses]) }}>{{ old($name, $value) }}</textarea>

    @if($hint && !$hasError)
        <p class="mt-1.5 text-sm text-muted">{{ $hint }}</p>
    @endif

    @if($hasError)
        <p class="mt-1.5 text-sm text-danger">
            {{ $error ?? $errors->first($name) }}
        </p>
    @endif
</div>
