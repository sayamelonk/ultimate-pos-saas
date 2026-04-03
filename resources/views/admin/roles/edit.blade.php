<x-app-layout>
    <x-slot name="title">{{ __('admin.edit_role') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.edit_role'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.roles.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.edit_role') }}</h2>
                <p class="text-muted mt-1">{{ $role->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <x-input
                    name="name"
                    label="{{ __('admin.role_name_label') }}"
                    placeholder="{{ __('admin.role_name_placeholder') }}"
                    :value="$role->name"
                    required
                />

                <x-textarea
                    name="description"
                    label="{{ __('app.description') }}"
                    placeholder="{{ __('admin.role_description_placeholder') }}"
                    :value="$role->description"
                    rows="3"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.roles.index') }}" variant="outline-secondary">
                        {{ __('app.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin.update_role') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
