<x-app-layout>
    <x-slot name="title">No Outlet - Ultimate POS</x-slot>

    @section('page-title', 'POS')

    <div class="flex flex-col items-center justify-center py-16">
        <div class="w-24 h-24 bg-secondary-100 rounded-full flex items-center justify-center mb-6">
            <x-icon name="building" class="w-12 h-12 text-secondary-400" />
        </div>
        <h2 class="text-2xl font-bold text-text mb-2">No Outlet Available</h2>
        <p class="text-muted text-center max-w-md mb-6">
            You don't have access to any outlet. Please contact your administrator to assign an outlet to your account.
        </p>
        <x-button href="{{ route('dashboard') }}" variant="secondary">
            Back to Dashboard
        </x-button>
    </div>
</x-app-layout>
