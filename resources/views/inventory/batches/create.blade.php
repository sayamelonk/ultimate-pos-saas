<x-app-layout>
    <x-slot name="title">{{ __('inventory.add_batch') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.add_batch'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.batches.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.add_batch') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_batch_description') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('inventory.batches.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <!-- Outlet -->
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            {{ __('app.outlet') }} <span class="text-danger">*</span>
                        </label>
                        <x-select name="outlet_id" required>
                            <option value="">{{ __('inventory.select_outlet') }}</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $selectedOutlet) == $outlet->id)>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </x-select>
                        @error('outlet_id')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Item -->
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">
                            {{ __('inventory.inventory_item') }} <span class="text-danger">*</span>
                        </label>
                        <x-select name="inventory_item_id" required>
                            <option value="">{{ __('inventory.select_item') }}</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(old('inventory_item_id', $selectedItem) == $item->id)>
                                    {{ $item->name }} ({{ $item->sku }})
                                </option>
                            @endforeach
                        </x-select>
                        @error('inventory_item_id')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-muted mt-1">{{ __('inventory.batch_items_note') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Batch Number -->
                        <div>
                            <label class="block text-sm font-medium text-text mb-1">{{ __('inventory.batch_number') }}</label>
                            <x-input
                                name="batch_number"
                                :value="old('batch_number')"
                                placeholder="{{ $settings->auto_generate_batch ? 'Auto-generated' : __('inventory.batch_number') }}"
                            />
                            @error('batch_number')
                                <p class="text-danger text-sm mt-1">{{ $message }}</p>
                            @enderror
                            @if($settings->auto_generate_batch)
                                <p class="text-xs text-muted mt-1">{{ __('inventory.batch_number_auto') }}</p>
                            @endif
                        </div>

                        <!-- Supplier Batch -->
                        <div>
                            <label class="block text-sm font-medium text-text mb-1">{{ __('inventory.supplier_batch_number') }}</label>
                            <x-input
                                name="supplier_batch_number"
                                :value="old('supplier_batch_number')"
                                :placeholder="__('inventory.supplier_batch_placeholder')"
                            />
                            @error('supplier_batch_number')
                                <p class="text-danger text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Production Date -->
                        <div>
                            <label class="block text-sm font-medium text-text mb-1">{{ __('inventory.production_date') }}</label>
                            <input
                                type="date"
                                name="production_date"
                                value="{{ old('production_date') }}"
                                class="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                            @error('production_date')
                                <p class="text-danger text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Expiry Date -->
                        <div>
                            <label class="block text-sm font-medium text-text mb-1">{{ __('inventory.expiry_date') }}</label>
                            <input
                                type="date"
                                name="expiry_date"
                                value="{{ old('expiry_date') }}"
                                class="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                            @error('expiry_date')
                                <p class="text-danger text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Quantity -->
                        <div>
                            <label class="block text-sm font-medium text-text mb-1">
                                {{ __('inventory.initial_quantity') }} <span class="text-danger">*</span>
                            </label>
                            <x-input
                                type="number"
                                name="initial_quantity"
                                :value="old('initial_quantity')"
                                step="0.0001"
                                min="0.0001"
                                required
                                placeholder="0.00"
                            />
                            @error('initial_quantity')
                                <p class="text-danger text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Unit Cost -->
                        <div x-data="{
                            rawValue: {{ old('unit_cost', 0) }},
                            displayValue: '',
                            init() {
                                this.displayValue = this.formatNumber(this.rawValue);
                            },
                            formatNumber(num) {
                                return new Intl.NumberFormat('id-ID').format(num || 0);
                            },
                            parseNumber(str) {
                                return parseFloat(str.replace(/\./g, '').replace(/,/g, '.')) || 0;
                            },
                            onInput(e) {
                                let value = e.target.value.replace(/[^\d]/g, '');
                                this.rawValue = parseInt(value) || 0;
                                this.displayValue = this.formatNumber(this.rawValue);
                            }
                        }">
                            <label class="block text-sm font-medium text-text mb-1">{{ __('inventory.unit_cost') }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">Rp</span>
                                <input type="hidden" name="unit_cost" :value="rawValue">
                                <input
                                    type="text"
                                    x-model="displayValue"
                                    @input="onInput($event)"
                                    class="w-full pl-10 pr-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="0"
                                />
                            </div>
                            @error('unit_cost')
                                <p class="text-danger text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">{{ __('app.notes') }}</label>
                        <textarea
                            name="notes"
                            rows="3"
                            placeholder="{{ __('inventory.optional_notes') }}"
                            class="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-danger text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-border">
                    <x-button href="{{ route('inventory.batches.index') }}" variant="secondary">
                        {{ __('app.cancel') }}
                    </x-button>
                    <x-button type="submit" icon="check">
                        {{ __('inventory.create_batch') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
