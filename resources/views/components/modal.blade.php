@props([
    'name' => 'modal',
    'title' => null,
    'size' => 'md',
    'closeable' => true,
])

@php
    $sizes = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        'full' => 'max-w-full mx-4',
    ];

    $maxWidth = $sizes[$size] ?? $sizes['md'];
@endphp

<div x-data="{ show: false, name: '{{ $name }}' }"
     x-show="show"
     x-on:open-modal.window="if ($event.detail === name) show = true"
     x-on:close-modal.window="if ($event.detail === name) show = false"
     x-on:keydown.escape.window="if ({{ $closeable ? 'true' : 'false' }}) show = false"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-secondary-900/50 backdrop-blur-sm"
         @if($closeable) @click="show = false" @endif>
    </div>

    {{-- Modal Container --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full {{ $maxWidth }} bg-surface rounded-xl shadow-xl border border-border"
             @click.stop>

            {{-- Header --}}
            @if($title || $closeable)
                <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                    @if($title)
                        <h3 class="text-lg font-semibold text-text">{{ $title }}</h3>
                    @else
                        <div></div>
                    @endif

                    @if($closeable)
                        <button @click="show = false"
                                class="p-2 -m-2 text-muted hover:text-text rounded-lg hover:bg-secondary-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Content --}}
            <div class="px-6 py-4">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @if(isset($footer))
                <div class="px-6 py-4 border-t border-border bg-secondary-50 rounded-b-xl">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
