@props([
    'striped' => false,
    'hoverable' => true,
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-border overflow-x-auto']) }}>
    <table class="min-w-full divide-y divide-border whitespace-nowrap">
        @if(isset($head))
            <thead class="bg-secondary-50">
                <tr>
                    {{ $head }}
                </tr>
            </thead>
        @endif

        <tbody class="bg-surface divide-y divide-border {{ $striped ? '[&>tr:nth-child(even)]:bg-secondary-50' : '' }} {{ $hoverable ? '[&>tr]:hover:bg-secondary-50' : '' }}">
            {{ $slot }}
        </tbody>

        @if(isset($foot))
            <tfoot class="bg-secondary-50">
                {{ $foot }}
            </tfoot>
        @endif
    </table>
</div>
