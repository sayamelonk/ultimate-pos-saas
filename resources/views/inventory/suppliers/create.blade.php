<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_supplier') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_supplier'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.suppliers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.create_supplier') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_supplier_desc') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('inventory.suppliers.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="code"
                        :label="__('inventory.supplier_code')"
                        :placeholder="__('inventory.supplier_code_placeholder')"
                        required
                    />

                    <x-input
                        name="name"
                        :label="__('inventory.supplier_name')"
                        :placeholder="__('inventory.supplier_name_placeholder')"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="contact_person"
                        :label="__('inventory.contact_person')"
                        :placeholder="__('inventory.contact_person_placeholder')"
                    />

                    <x-input
                        type="email"
                        name="email"
                        :label="__('inventory.email')"
                        placeholder="e.g., supplier@email.com"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="tel"
                        name="phone"
                        :label="__('inventory.phone')"
                        placeholder="e.g., +62 812 3456 7890"
                    />

                    <x-input
                        name="city"
                        :label="__('inventory.city')"
                        placeholder="e.g., Jakarta"
                    />
                </div>

                <x-textarea
                    name="address"
                    :label="__('inventory.address')"
                    :placeholder="__('inventory.address')"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        name="tax_number"
                        :label="__('inventory.tax_number')"
                        placeholder="e.g., 01.234.567.8-901.000"
                    />

                    <x-input
                        type="number"
                        name="payment_terms"
                        :label="__('inventory.payment_terms_days')"
                        placeholder="e.g., 30"
                        min="0"
                    />
                </div>

                <x-textarea
                    name="notes"
                    :label="__('inventory.notes')"
                    :placeholder="__('inventory.notes')"
                    rows="2"
                />

                <x-checkbox
                    name="is_active"
                    :label="__('inventory.active')"
                    :hint="__('inventory.inactive_hint')"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.suppliers.index') }}" variant="outline-secondary">
                        {{ __('inventory.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('inventory.create_supplier') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
