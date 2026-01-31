<x-app-layout>
    <x-slot name="title">{{ $discount->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Discount Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('pricing.discounts.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-left" class="w-4 h-4" />
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $discount->name }}</h2>
                    <p class="text-muted mt-1">{{ $discount->code }}</p>
                </div>
            </div>
            <x-button href="{{ route('pricing.discounts.edit', $discount) }}" variant="secondary" icon="pencil">
                Edit
            </x-button>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Discount Details">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">Type</dt>
                        <dd class="font-medium">{{ $types[$discount->type] ?? $discount->type }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Scope</dt>
                        <dd class="font-medium">{{ $scopes[$discount->scope] ?? $discount->scope }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Value</dt>
                        <dd class="font-medium">
                            @if($discount->type === 'percentage')
                                {{ number_format($discount->value, 0) }}%
                            @else
                                Rp {{ number_format($discount->value, 0, ',', '.') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Max Discount</dt>
                        <dd class="font-medium">
                            @if($discount->max_discount)
                                Rp {{ number_format($discount->max_discount, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Min Purchase</dt>
                        <dd class="font-medium">
                            @if($discount->min_purchase)
                                Rp {{ number_format($discount->min_purchase, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Min Quantity</dt>
                        <dd class="font-medium">{{ $discount->min_qty ?? '-' }}</dd>
                    </div>
                </dl>

                @if($discount->description)
                    <div class="mt-4 pt-4 border-t">
                        <dt class="text-sm text-muted">Description</dt>
                        <dd class="mt-1">{{ $discount->description }}</dd>
                    </div>
                @endif
            </x-card>

            <x-card title="Validity">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">Valid From</dt>
                        <dd class="font-medium">{{ $discount->valid_from->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Valid Until</dt>
                        <dd class="font-medium">{{ $discount->valid_until?->format('d M Y') ?? 'No expiry' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Usage</dt>
                        <dd class="font-medium">
                            {{ $discount->usage_count }}
                            @if($discount->usage_limit)
                                / {{ $discount->usage_limit }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($discount->isValid())
                                <x-badge type="success" dot>Active</x-badge>
                            @elseif(!$discount->is_active)
                                <x-badge type="danger" dot>Disabled</x-badge>
                            @else
                                <x-badge type="warning" dot>Expired</x-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Member Settings">
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-muted">Member Only</dt>
                        <dd class="font-medium">{{ $discount->member_only ? 'Yes' : 'No' }}</dd>
                    </div>
                    @if($discount->membership_levels && count($discount->membership_levels) > 0)
                        <div>
                            <dt class="text-sm text-muted">Applicable Levels</dt>
                            <dd class="flex flex-wrap gap-1 mt-1">
                                @foreach($discount->membership_levels as $level)
                                    <x-badge type="secondary">{{ ucfirst($level) }}</x-badge>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-card title="Options">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted">Auto Apply</span>
                        <span class="font-medium">{{ $discount->is_auto_apply ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Active</span>
                        <span class="font-medium">{{ $discount->is_active ? 'Yes' : 'No' }}</span>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>
