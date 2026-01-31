@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $types = [
        'info' => [
            'bg' => 'bg-info-50',
            'border' => 'border-info-200',
            'text' => 'text-info-800',
            'icon' => 'text-info-500',
            'iconPath' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'success' => [
            'bg' => 'bg-success-50',
            'border' => 'border-success-200',
            'text' => 'text-success-800',
            'icon' => 'text-success-500',
            'iconPath' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'warning' => [
            'bg' => 'bg-warning-50',
            'border' => 'border-warning-200',
            'text' => 'text-warning-800',
            'icon' => 'text-warning-500',
            'iconPath' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ],
        'danger' => [
            'bg' => 'bg-danger-50',
            'border' => 'border-danger-200',
            'text' => 'text-danger-800',
            'icon' => 'text-danger-500',
            'iconPath' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    ];

    $config = $types[$type] ?? $types['info'];
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     {{ $attributes->merge(['class' => 'rounded-lg border p-4 ' . $config['bg'] . ' ' . $config['border']]) }}>
    <div class="flex">
        {{-- Icon --}}
        <div class="flex-shrink-0">
            <svg class="w-5 h-5 {{ $config['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['iconPath'] }}"/>
            </svg>
        </div>

        {{-- Content --}}
        <div class="ml-3 flex-1">
            @if($title)
                <h3 class="text-sm font-medium {{ $config['text'] }}">{{ $title }}</h3>
                <div class="mt-2 text-sm {{ $config['text'] }} opacity-90">
                    {{ $slot }}
                </div>
            @else
                <p class="text-sm {{ $config['text'] }}">{{ $slot }}</p>
            @endif
        </div>

        {{-- Dismiss Button --}}
        @if($dismissible)
            <div class="ml-auto pl-3">
                <button @click="show = false"
                        class="-mx-1.5 -my-1.5 p-1.5 rounded-lg {{ $config['text'] }} hover:{{ $config['bg'] }} focus:ring-2 focus:ring-offset-2 focus:ring-offset-{{ $type }}-50 focus:ring-{{ $type }}-500 transition-colors">
                    <span class="sr-only">Dismiss</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>
</div>
