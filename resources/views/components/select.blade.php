@props([
    'name' => '',
    'label' => null,
    'placeholder' => 'Select an option',
    'value' => '',
    'options' => [],
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'size' => 'md',
])

@php
    $hasError = $error || ($errors->has($name) ?? false);

    $sizes = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-4 py-3 text-base',
    ];

    $selectClasses = 'block w-full rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 appearance-none bg-no-repeat ' .
        ($sizes[$size] ?? $sizes['md']) . ' ' .
        ($hasError
            ? 'border-danger-300 text-danger-900 focus:border-danger-500 focus:ring-danger-500/20'
            : 'border-border text-text focus:border-accent focus:ring-accent/20') . ' ' .
        ($disabled ? 'bg-secondary-50 cursor-not-allowed' : 'bg-surface');
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
        <select name="{{ $name }}"
                id="{{ $name }}"
                @if($required) required @endif
                @if($disabled) disabled @endif
                {{ $attributes->except('class')->merge(['class' => $selectClasses]) }}
                style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 24 24%27 stroke=%27%2394A3B8%27%3E%3Cpath stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%272%27 d=%27M19 9l-7 7-7-7%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-size: 1.25rem;">
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                @if($placeholder)
                    <option value="">{{ $placeholder }}</option>
                @endif
                @foreach($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @if(old($name, $value) == $optionValue) selected @endif>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            @endif
        </select>
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
