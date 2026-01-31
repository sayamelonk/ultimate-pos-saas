@props([
    'align' => 'right',
    'width' => '48',
    'contentClasses' => 'py-1 bg-surface',
])

@php
    $alignmentClasses = match ($align) {
        'left' => 'left-0 origin-top-left',
        'top' => 'bottom-full mb-2 origin-bottom',
        default => 'right-0 origin-top-right',
    };

    $widthClasses = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        'full' => 'w-full',
        default => 'w-48',
    };
@endphp

<div class="relative inline-block"
     x-data="{
        open: false,
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.positionDropdown());
            }
        },
        positionDropdown() {
            const trigger = this.$refs.trigger;
            const dropdown = this.$refs.dropdown;
            if (!trigger || !dropdown) return;

            const rect = trigger.getBoundingClientRect();
            const dropdownRect = dropdown.getBoundingClientRect();

            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.bottom + 4) + 'px';
            @if($align === 'left')
                dropdown.style.left = rect.left + 'px';
            @else
                dropdown.style.left = (rect.right - dropdownRect.width) + 'px';
            @endif
        }
     }"
     @click.outside="open = false"
     @close.stop="open = false"
     @scroll.window="if(open) positionDropdown()"
     @resize.window="if(open) positionDropdown()">
    {{-- Trigger --}}
    <div x-ref="trigger" @click="toggle()">
        {{ $trigger }}
    </div>

    {{-- Dropdown Content --}}
    <div x-ref="dropdown"
         x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-[9999] {{ $widthClasses }} rounded-lg shadow-lg border border-border"
         style="display: none;"
         @click.stop>
        <div class="rounded-lg {{ $contentClasses }}">
            {{ $slot }}
        </div>
    </div>
</div>
