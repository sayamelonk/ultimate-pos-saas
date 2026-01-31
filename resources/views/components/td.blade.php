@props([
    'align' => 'left',
])

@php
    $alignments = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ];
@endphp

<td {{ $attributes->merge(['class' => 'px-6 py-4 text-sm text-text whitespace-nowrap ' . ($alignments[$align] ?? 'text-left')]) }}>
    {{ $slot }}
</td>
