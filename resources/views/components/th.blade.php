@props([
    'sortable' => false,
    'sorted' => null,
    'align' => 'left',
])

@php
    $alignments = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ];
@endphp

<th {{ $attributes->merge(['class' => 'px-6 py-3 text-xs font-semibold text-secondary-600 uppercase tracking-wider ' . ($alignments[$align] ?? 'text-left')]) }}>
    @if($sortable)
        <button class="group inline-flex items-center gap-1">
            {{ $slot }}
            <span class="flex flex-col">
                <svg class="w-3 h-3 {{ $sorted === 'asc' ? 'text-primary' : 'text-secondary-300 group-hover:text-secondary-400' }}" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 12l5-5 5 5H5z"/>
                </svg>
                <svg class="w-3 h-3 -mt-1 {{ $sorted === 'desc' ? 'text-primary' : 'text-secondary-300 group-hover:text-secondary-400' }}" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M15 8l-5 5-5-5h10z"/>
                </svg>
            </span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
