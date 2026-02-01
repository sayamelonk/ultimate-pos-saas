@props([
    'name' => '',
    'label' => null,
    'value' => '1',
    'checked' => false,
    'hint' => null,
    'error' => null,
    'disabled' => false,
])

@php
    $hasError = $error || ($errors->has($name) ?? false);
    $isChecked = old($name, $checked);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'relative flex items-start']) }}>
    <div class="flex items-center h-5">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox"
               name="{{ $name }}"
               id="{{ $name }}"
               value="{{ $value }}"
               @if($isChecked) checked @endif
               @if($disabled) disabled @endif
               class="w-4 h-4 rounded border-border text-primary focus:ring-primary/20 focus:ring-2 focus:ring-offset-0 transition-colors
                      {{ $disabled ? 'bg-secondary-100 cursor-not-allowed' : 'bg-surface cursor-pointer' }}
                      {{ $hasError ? 'border-danger-300' : 'border-border' }}">
    </div>

    @if($label || $hint)
        <div class="ml-3">
            @if($label)
                <label for="{{ $name }}" class="text-sm font-medium text-text {{ $disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                    {{ $label }}
                </label>
            @endif

            @if($hint)
                <p class="text-sm text-muted">{{ $hint }}</p>
            @endif
        </div>
    @endif

    @if($hasError)
        <p class="mt-1 text-sm text-danger">
            {{ $error ?? $errors->first($name) }}
        </p>
    @endif
</div>
