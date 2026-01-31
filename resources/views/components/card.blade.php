@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
    'shadow' => true,
])

<div {{ $attributes->merge(['class' => 'bg-surface rounded-xl border border-border ' . ($shadow ? 'shadow-sm' : '')]) }}>
    {{-- Header --}}
    @if($title || isset($header))
        <div class="px-6 py-4 border-b border-border">
            @if(isset($header))
                {{ $header }}
            @else
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-text">{{ $title }}</h3>
                        @if($subtitle)
                            <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
                        @endif
                    </div>
                    @if(isset($actions))
                        <div class="flex items-center gap-2">
                            {{ $actions }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Content --}}
    <div class="{{ $padding ? 'p-6' : '' }}">
        {{ $slot }}
    </div>

    {{-- Footer --}}
    @if(isset($footer))
        <div class="px-6 py-4 border-t border-border bg-secondary-50 rounded-b-xl">
            {{ $footer }}
        </div>
    @endif
</div>
