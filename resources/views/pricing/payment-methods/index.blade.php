<x-app-layout>
    <x-slot name="title">Payment Methods - Ultimate POS</x-slot>

    @section('page-title', 'Payment Methods')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Payment Methods</h2>
                <p class="text-muted mt-1">Manage accepted payment methods</p>
            </div>
            <x-button href="{{ route('pricing.payment-methods.create') }}" icon="plus">
                Add Payment Method
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('pricing.payment-methods.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search payment methods..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="type" class="w-40">
                    <option value="">All Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </x-select>
                <x-button type="submit" variant="secondary">Filter</x-button>
            </form>
        </div>

        @if($paymentMethods->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Payment Method</x-th>
                    <x-th>Code</x-th>
                    <x-th>Type</x-th>
                    <x-th align="right">Charge %</x-th>
                    <x-th align="right">Fixed Fee</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($paymentMethods as $method)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-accent-100 rounded-lg flex items-center justify-center">
                                    <x-icon name="{{ $method->icon ?? 'credit-card' }}" class="w-5 h-5 text-accent" />
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $method->name }}</p>
                                    @if($method->provider)
                                        <p class="text-xs text-muted">{{ $method->provider }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $method->code }}</code>
                        </x-td>
                        <x-td>
                            <x-badge type="secondary">{{ $types[$method->type] ?? $method->type }}</x-badge>
                        </x-td>
                        <x-td align="right">{{ number_format($method->charge_percentage, 2) }}%</x-td>
                        <x-td align="right">Rp {{ number_format($method->charge_fixed, 0, ',', '.') }}</x-td>
                        <x-td align="center">
                            @if($method->is_active)
                                <x-badge type="success" dot>Active</x-badge>
                            @else
                                <x-badge type="danger" dot>Inactive</x-badge>
                            @endif
                        </x-td>
                        <x-td align="right">
                            <div x-data>
                                <x-dropdown align="right">
                                    <x-slot name="trigger">
                                        <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                            <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                        </button>
                                    </x-slot>

                                    <x-dropdown-item href="{{ route('pricing.payment-methods.edit', $method) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Payment Method',
                                            message: 'Are you sure you want to delete {{ $method->name }}? This action cannot be undone.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('pricing.payment-methods.destroy', $method) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$paymentMethods" />
            </div>
        @else
            <x-empty-state
                title="No payment methods found"
                description="Add payment methods to accept payments."
                icon="credit-card"
            >
                <x-button href="{{ route('pricing.payment-methods.create') }}" icon="plus">
                    Add Payment Method
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
