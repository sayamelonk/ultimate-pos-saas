<x-app-layout>
    <x-slot name="title">{{ __('pos.no_outlet_access') }} - Ultimate POS</x-slot>

    @section('page-title', __('pos.pos'))

    <div class="flex flex-col items-center justify-center py-16">
        <div class="w-24 h-24 bg-secondary-100 rounded-full flex items-center justify-center mb-6">
            <x-icon name="building" class="w-12 h-12 text-secondary-400" />
        </div>
        <h2 class="text-2xl font-bold text-text mb-2">{{ __('pos.no_outlet_access') }}</h2>
        <p class="text-muted text-center max-w-md mb-6">
            {{ __('pos.no_outlet_desc') }}
        </p>
        <x-button href="{{ route('dashboard') }}" variant="secondary">
            {{ __('pos.back_to_dashboard') }}
        </x-button>
    </div>
</x-app-layout>
