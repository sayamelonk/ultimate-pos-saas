<x-app-layout>
    <x-slot name="title">Add Payment Method - Ultimate POS</x-slot>

    @section('page-title', 'Add Payment Method')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('pricing.payment-methods.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Add Payment Method</h2>
                <p class="text-muted mt-1">Create a new payment method</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('pricing.payment-methods.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-card title="Payment Method Details">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input
                            label="Code"
                            name="code"
                            :value="old('code')"
                            placeholder="e.g., CASH, GOPAY"
                            required
                        />
                        <x-input
                            label="Name"
                            name="name"
                            :value="old('name')"
                            required
                        />
                        <x-select label="Type" name="type" required>
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </x-select>
                        <x-input
                            label="Provider"
                            name="provider"
                            :value="old('provider')"
                            placeholder="e.g., Gojek, Bank BCA"
                        />
                        <x-input
                            label="Charge Percentage (%)"
                            name="charge_percentage"
                            type="number"
                            step="0.01"
                            :value="old('charge_percentage', 0)"
                        />
                        <x-input
                            label="Fixed Fee (Rp)"
                            name="charge_fixed"
                            type="number"
                            :value="old('charge_fixed', 0)"
                        />
                        <x-input
                            label="Icon"
                            name="icon"
                            :value="old('icon', 'credit-card')"
                            placeholder="e.g., cash, credit-card, qrcode"
                        />
                        <x-input
                            label="Sort Order"
                            name="sort_order"
                            type="number"
                            :value="old('sort_order', 0)"
                        />
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Options">
                    <div class="space-y-4">
                        <x-checkbox
                            name="requires_reference"
                            label="Requires Reference Number"
                            :checked="old('requires_reference')"
                        />
                        <x-checkbox
                            name="opens_cash_drawer"
                            label="Opens Cash Drawer"
                            :checked="old('opens_cash_drawer')"
                        />
                        <x-checkbox
                            name="is_active"
                            label="Active"
                            :checked="old('is_active', true)"
                        />
                    </div>
                </x-card>

                <div class="flex gap-3">
                    <x-button type="submit" class="flex-1">Save</x-button>
                    <x-button href="{{ route('pricing.payment-methods.index') }}" variant="secondary">Cancel</x-button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
