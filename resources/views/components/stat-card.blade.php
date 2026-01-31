@props([
    'title' => '',
    'value' => '',
    'icon' => null,
    'trend' => null,
    'trendValue' => null,
    'color' => 'primary',
])

@php
    $colors = [
        'primary' => 'bg-primary-100 text-primary-600',
        'accent' => 'bg-accent-100 text-accent-600',
        'success' => 'bg-success-100 text-success-600',
        'warning' => 'bg-warning-100 text-warning-600',
        'danger' => 'bg-danger-100 text-danger-600',
        'info' => 'bg-info-100 text-info-600',
    ];

    $iconBg = $colors[$color] ?? $colors['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface rounded-xl border border-border p-6']) }}>
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-muted">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-text">{{ $value }}</p>

            @if($trend !== null && $trendValue !== null)
                <div class="mt-2 flex items-center gap-1">
                    @if($trend === 'up')
                        <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span class="text-sm font-medium text-success-600">{{ $trendValue }}</span>
                    @elseif($trend === 'down')
                        <svg class="w-4 h-4 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                        <span class="text-sm font-medium text-danger-600">{{ $trendValue }}</span>
                    @else
                        <span class="text-sm font-medium text-muted">{{ $trendValue }}</span>
                    @endif
                    <span class="text-xs text-muted">vs last period</span>
                </div>
            @endif
        </div>

        @if($icon)
            <div class="flex-shrink-0 p-3 rounded-xl {{ $iconBg }}">
                <x-icon :name="$icon" class="w-6 h-6" />
            </div>
        @endif
    </div>

    @if(isset($footer))
        <div class="mt-4 pt-4 border-t border-border">
            {{ $footer }}
        </div>
    @endif
</div>
