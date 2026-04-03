<x-app-layout>
    <x-slot name="title">{{ __('inventory.edit_supplier') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.edit_supplier'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.suppliers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.edit_supplier') }}</h2>
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
                        :label="__('inventory.supplier_code')"
                        :placeholder="__('inventory.supplier_code_placeholder')"
                        :value="$supplier->code"
                        required
                    />

                    <x-input
                        name="name"
                        :label="__('inventory.supplier_name')"
                        :placeholder="__('inventory.supplier_name_placeholder')"
                        :value="$supplier->name"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="contact_person"
                        :label="__('inventory.contact_person')"
                        :placeholder="__('inventory.contact_person_placeholder')"
                        :value="$supplier->contact_person"
                    />

                    <x-input
                        type="email"
                        name="email"
                        :label="__('inventory.email')"
                        placeholder="e.g., supplier@email.com"
                        :value="$supplier->email"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="tel"
                        name="phone"
                        :label="__('inventory.phone')"
                        placeholder="e.g., +62 812 3456 7890"
                        :value="$supplier->phone"
                    />

                    <x-input
                        name="city"
                        :label="__('inventory.city')"
                        placeholder="e.g., Jakarta"
                        :value="$supplier->city"
                    />
                </div>

                <x-textarea
                    name="address"
                    :label="__('inventory.address')"
                    :placeholder="__('inventory.address')"
                    :value="$supplier->address"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="tax_number"
                        :label="__('inventory.tax_number')"
                        placeholder="e.g., 01.234.567.8-901.000"
                        :value="$supplier->tax_number"
                    />

                    <x-input
                        type="number"
                        name="payment_terms"
                        :label="__('inventory.payment_terms_days')"
                        placeholder="e.g., 30"
                        :value="$supplier->payment_terms"
                        min="0"
                    />
                </div>

                <x-textarea
                    name="notes"
                    :label="__('inventory.notes')"
                    :placeholder="__('inventory.notes')"
                    :value="$supplier->notes"
                    rows="2"
                />

                <x-checkbox
                    name="is_active"
                    :label="__('inventory.active')"
                    :hint="__('inventory.inactive_hint')"
                    :checked="$supplier->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.suppliers.index') }}" variant="outline-secondary">
                        {{ __('inventory.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('inventory.update_supplier') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
