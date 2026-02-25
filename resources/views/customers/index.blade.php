<x-app-layout>
    <x-slot name="title">Customers - Ultimate POS</x-slot>

    @section('page-title', 'Customers')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">Customers</h2>
                <p class="text-muted mt-1">Manage your customer database and memberships</p>
            </div>
            <x-button href="{{ route('customers.create') }}" icon="plus">
                Add Customer
            </x-button>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('customers.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        placeholder="Search by name, code, phone, email..."
                        :value="request('search')"
                    />
                </div>
                <x-select name="membership_level" class="w-40">
                    <option value="">All Levels</option>
                    @foreach($membershipLevels as $value => $label)
                        <option value="{{ $value }}" @selected(request('membership_level') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    Filter
                </x-button>
                @if(request()->hasAny(['search', 'membership_level', 'status']))
                    <x-button href="{{ route('customers.index') }}" variant="ghost">
                        Clear
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($customers->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>Customer</x-th>
                    <x-th>Code</x-th>
                    <x-th>Contact</x-th>
                    <x-th align="center">Membership</x-th>
                    <x-th align="right">Points</x-th>
                    <x-th align="right">Total Spent</x-th>
                    <x-th align="center">Status</x-th>
                    <x-th align="right">Actions</x-th>
                </x-slot>

                @foreach($customers as $customer)
                    <tr>
                        <x-td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                    <span class="text-primary font-semibold">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ $customer->name }}</p>
                                    <p class="text-xs text-muted">Joined {{ $customer->joined_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <code class="px-2 py-1 bg-secondary-100 rounded text-xs">{{ $customer->code }}</code>
                        </x-td>
                        <x-td>
                            <div class="text-sm">
                                @if($customer->phone)
                                    <p class="text-text">{{ $customer->phone }}</p>
                                @endif
                                @if($customer->email)
                                    <p class="text-muted text-xs">{{ $customer->email }}</p>
                                @endif
                            </div>
                        </x-td>
                        <x-td align="center">
                            @php
                                $levelColors = [
                                    'regular' => 'secondary',
                                    'silver' => 'secondary',
                                    'gold' => 'warning',
                                    'platinum' => 'info',
                                ];
                            @endphp
                            <x-badge type="{{ $levelColors[$customer->membership_level] ?? 'secondary' }}">
                                {{ ucfirst($customer->membership_level) }}
                            </x-badge>
                        </x-td>
                        <x-td align="right">
                            <span class="font-medium">{{ number_format($customer->total_points, 0, ',', '.') }}</span>
                        </x-td>
                        <x-td align="right">
                            Rp {{ number_format($customer->total_spent, 0, ',', '.') }}
                        </x-td>
                        <x-td align="center">
                            @if($customer->is_active)
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

                                    <x-dropdown-item href="{{ route('customers.show', $customer) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        View Details
                                    </x-dropdown-item>
                                    <x-dropdown-item href="{{ route('customers.edit', $customer) }}">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                        Edit
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        type="button"
                                        danger
                                        @click="$dispatch('confirm', {
                                            title: 'Delete Customer',
                                            message: 'Are you sure you want to delete {{ $customer->name }}? This action cannot be undone.',
                                            confirmText: 'Delete',
                                            variant: 'danger',
                                            onConfirm: () => $refs.deleteForm{{ $loop->index }}.submit()
                                        })"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                        Delete
                                    </x-dropdown-item>
                                </x-dropdown>
                                <form x-ref="deleteForm{{ $loop->index }}" action="{{ route('customers.destroy', $customer) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$customers" />
            </div>
        @else
            <x-empty-state
                title="No customers found"
                description="Get started by adding your first customer."
                icon="users"
            >
                <x-button href="{{ route('customers.create') }}" icon="plus">
                    Add Customer
                </x-button>
            </x-empty-state>
        @endif
    </x-card>
</x-app-layout>
