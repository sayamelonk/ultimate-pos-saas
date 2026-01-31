<x-app-layout>
    <x-slot name="title">Edit Supplier - Ultimate POS</x-slot>

    @section('page-title', 'Edit Supplier')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.suppliers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Supplier</h2>
                <p class="text-muted mt-1">{{ $supplier->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('inventory.suppliers.update', $supplier) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="code"
                        label="Supplier Code"
                        placeholder="e.g., SUP001"
                        :value="$supplier->code"
                        required
                    />

                    <x-input
                        name="name"
                        label="Supplier Name"
                        placeholder="e.g., Fresh Foods Co."
                        :value="$supplier->name"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="contact_person"
                        label="Contact Person"
                        placeholder="e.g., John Doe"
                        :value="$supplier->contact_person"
                    />

                    <x-input
                        type="email"
                        name="email"
                        label="Email"
                        placeholder="e.g., supplier@email.com"
                        :value="$supplier->email"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="tel"
                        name="phone"
                        label="Phone Number"
                        placeholder="e.g., +62 812 3456 7890"
                        :value="$supplier->phone"
                    />

                    <x-input
                        name="city"
                        label="City"
                        placeholder="e.g., Jakarta"
                        :value="$supplier->city"
                    />
                </div>

                <x-textarea
                    name="address"
                    label="Address"
                    placeholder="Enter full address"
                    :value="$supplier->address"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="tax_number"
                        label="Tax Number (NPWP)"
                        placeholder="e.g., 01.234.567.8-901.000"
                        :value="$supplier->tax_number"
                    />

                    <x-input
                        type="number"
                        name="payment_terms"
                        label="Payment Terms (Days)"
                        placeholder="e.g., 30"
                        :value="$supplier->payment_terms"
                        min="0"
                    />
                </div>

                <x-textarea
                    name="notes"
                    label="Notes"
                    placeholder="Additional notes about this supplier"
                    :value="$supplier->notes"
                    rows="2"
                />

                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive suppliers won't appear in selections"
                    :checked="$supplier->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.suppliers.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Update Supplier
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
