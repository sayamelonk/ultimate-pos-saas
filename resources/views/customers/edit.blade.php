<x-app-layout>
    <x-slot name="title">Edit Customer - Ultimate POS</x-slot>

    @section('page-title', 'Edit Customer')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('customers.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Customer</h2>
                <p class="text-muted mt-1">Update customer information</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2">
                <x-card title="Customer Information">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input
                            label="Customer Code"
                            name="code"
                            :value="old('code', $customer->code)"
                            required
                        />
                        <x-input
                            label="Full Name"
                            name="name"
                            :value="old('name', $customer->name)"
                            required
                        />
                        <x-input
                            label="Email"
                            type="email"
                            name="email"
                            :value="old('email', $customer->email)"
                        />
                        <x-input
                            label="Phone"
                            name="phone"
                            :value="old('phone', $customer->phone)"
                        />
                        <x-input
                            label="Birth Date"
                            type="date"
                            name="birth_date"
                            :value="old('birth_date', $customer->birth_date?->format('Y-m-d'))"
                        />
                        <x-select label="Gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" @selected(old('gender', $customer->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $customer->gender) === 'female')>Female</option>
                            <option value="other" @selected(old('gender', $customer->gender) === 'other')>Other</option>
                        </x-select>
                    </div>

                    <div class="mt-4">
                        <x-textarea
                            label="Address"
                            name="address"
                            rows="3"
                        >{{ old('address', $customer->address) }}</x-textarea>
                    </div>

                    <div class="mt-4">
                        <x-textarea
                            label="Notes"
                            name="notes"
                            rows="2"
                        >{{ old('notes', $customer->notes) }}</x-textarea>
                    </div>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <x-card title="Membership">
                    <x-select label="Membership Level" name="membership_level">
                        @foreach($membershipLevels as $value => $label)
                            <option value="{{ $value }}" @selected(old('membership_level', $customer->membership_level) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>

                    <div class="mt-4">
                        <x-input
                            label="Membership Expires"
                            type="date"
                            name="membership_expires_at"
                            :value="old('membership_expires_at', $customer->membership_expires_at?->format('Y-m-d'))"
                        />
                    </div>
                </x-card>

                <x-card title="Statistics">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-muted">Total Points</span>
                            <span class="font-semibold">{{ number_format($customer->total_points, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Total Spent</span>
                            <span class="font-semibold">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Total Visits</span>
                            <span class="font-semibold">{{ $customer->total_visits }}</span>
                        </div>
                    </div>
                </x-card>

                <x-card title="Status">
                    <x-checkbox
                        name="is_active"
                        label="Active"
                        :checked="old('is_active', $customer->is_active)"
                    />
                </x-card>

                <div class="flex gap-3">
                    <x-button type="submit" class="flex-1">
                        Update Customer
                    </x-button>
                    <x-button href="{{ route('customers.index') }}" variant="secondary">
                        Cancel
                    </x-button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
