@props([
    'title' => 'No data found',
    'description' => null,
    'icon' => 'folder',
])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-secondary-100 mb-4">
        <x-icon :name="$icon" class="w-8 h-8 text-secondary-400" />
    </div>

    <h3 class="text-lg font-medium text-text">{{ $title }}</h3>

    @if($description)
        <p class="mt-2 text-sm text-muted max-w-sm mx-auto">{{ $description }}</p>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-6">
            {{ $slot }}
        </div>
    @endif
</div>
