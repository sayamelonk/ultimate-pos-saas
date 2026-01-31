@props([
    'name' => '',
    'label' => null,
    'value' => '',
    'checked' => false,
    'hint' => null,
    'disabled' => false,
])

@php
    $isChecked = old($name) == $value || $checked;
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'relative flex items-start']) }}>
    <div class="flex items-center h-5">
        <input type="radio"
               name="{{ $name }}"
               id="{{ $name }}-{{ $value }}"
               value="{{ $value }}"
               @if($isChecked) checked @endif
               @if($disabled) disabled @endif
               class="w-4 h-4 border-border text-primary focus:ring-primary/20 focus:ring-2 focus:ring-offset-0 transition-colors
                      {{ $disabled ? 'bg-secondary-100 cursor-not-allowed' : 'bg-surface cursor-pointer' }}">
    </div>

    @if($label || $hint)
        <div class="ml-3">
            @if($label)
                <label for="{{ $name }}-{{ $value }}" class="text-sm font-medium text-text {{ $disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                    {{ $label }}
                </label>
            @endif

            @if($hint)
                <p class="text-sm text-muted">{{ $hint }}</p>
            @endif
        </div>
    @endif
</div>
