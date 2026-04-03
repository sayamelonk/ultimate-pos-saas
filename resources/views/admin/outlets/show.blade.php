<x-app-layout>
    <x-slot name="title">{{ $outlet->name }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.outlet_details'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('admin.outlets.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    {{ __('app.back') }}
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $outlet->name }}</h2>
                    <p class="text-muted mt-1">{{ $outlet->code }}</p>
                </div>
            </div>
            <x-button href="{{ route('admin.outlets.edit', $outlet) }}" variant="outline-secondary" icon="pencil">
                {{ __('app.edit') }}
            </x-button>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <x-card title="{{ __('admin.outlet_information') }}">
            <dl class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm text-muted">{{ __('admin.name') }}</dt>
                    <dd class="mt-1 font-medium text-text">{{ $outlet->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('admin.code') }}</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-secondary-100 rounded text-sm">{{ $outlet->code }}</code>
                    </dd>
                </div>
                @if(auth()->user()->isSuperAdmin())
                    <div>
                        <dt class="text-sm text-muted">{{ __('admin.tenant') }}</dt>
                        <dd class="mt-1 text-text">{{ $outlet->tenant?->name ?? '-' }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-muted">{{ __('app.status') }}</dt>
                    <dd class="mt-1">
                        @if($outlet->is_active)
                            <x-badge type="success" dot>{{ __('app.active') }}</x-badge>
                        @else
                            <x-badge type="danger" dot>{{ __('app.inactive') }}</x-badge>
                        @endif
                    </dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm text-muted">{{ __('admin.address') }}</dt>
                    <dd class="mt-1 text-text">{{ $outlet->address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('admin.phone') }}</dt>
                    <dd class="mt-1 text-text">{{ $outlet->phone ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('admin.email') }}</dt>
                    <dd class="mt-1 text-text">{{ $outlet->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('admin.created') }}</dt>
                    <dd class="mt-1 text-text">{{ $outlet->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">{{ __('admin.updated') }}</dt>
                    <dd class="mt-1 text-text">{{ $outlet->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-layout>
