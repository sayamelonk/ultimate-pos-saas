<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_item') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_item'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.items.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.create_item') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.add_new_item') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <form action="{{ route('inventory.items.store') }}" method="POST" class="space-y-6">
            @csrf

            <x-card title="{{ __('inventory.basic_information') }}">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-input
                            name="sku"
                            label="{{ __('inventory.sku') }}"
                            placeholder="{{ __('inventory.sku_placeholder') }}"
                            required
                        />
                        <x-input
                            name="barcode"
                            label="{{ __('inventory.barcode') }}"
                            placeholder="{{ __('inventory.barcode_placeholder') }}"
                        />
                    </div>

                    <x-input
                        name="name"
                        label="{{ __('inventory.item_name') }}"
                        placeholder="{{ __('inventory.item_name_placeholder') }}"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="category_id" label="{{ __('inventory.category') }}" required>
                            <option value="">{{ __('inventory.select_item') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </x-select>

                        <x-select name="unit_id" label="{{ __('inventory.unit') }}" required>
                            <option value="">{{ __('inventory.select_item') }}</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                    {{ $unit->name }} ({{ $unit->abbreviation }})
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="type" label="{{ __('inventory.item_type') }}" required>
                            <option value="">{{ __('inventory.select_item') }}</option>
                            <option value="raw_material" @selected(old('type') === 'raw_material')>{{ __('inventory.raw_material') }}</option>
                            <option value="finished_good" @selected(old('type') === 'finished_good')>{{ __('inventory.finished_good') }}</option>
                            <option value="consumable" @selected(old('type') === 'consumable')>{{ __('inventory.consumable') }}</option>
                            <option value="packaging" @selected(old('type') === 'packaging')>{{ __('inventory.packaging') }}</option>
                        </x-select>

                        <div x-data="{
                            rawValue: '{{ old('cost_price', '') }}',
                            displayValue: '',
                            init() {
                                if (this.rawValue) {
                                    this.displayValue = this.formatDisplay(this.rawValue);
                                }
                            },
                            formatDisplay(val) {
                                const num = String(val).replace(/\D/g, '');
                                return num ? new Intl.NumberFormat('id-ID').format(num) : '';
                            },
                            updateValue(e) {
                                const num = e.target.value.replace(/\D/g, '');
                                this.rawValue = num;
                                this.displayValue = this.formatDisplay(num);
                            }
                        }">
                            <label class="block text-sm font-medium text-text mb-1">
                                {{ __('inventory.cost_price') }} (Rp) <span class="text-danger-500">*</span>
                            </label>
                            <input
                                type="text"
                                x-model="displayValue"
                                @input="updateValue($event)"
                                class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent @error('cost_price') border-danger-500 @enderror"
                                placeholder="e.g., 150.000"
                                required
                            >
                            <input type="hidden" name="cost_price" x-model="rawValue">
                            @error('cost_price')
                                <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <x-textarea
                        name="description"
                        label="{{ __('inventory.description') }}"
                        placeholder="{{ __('inventory.description_placeholder') }}"
                        rows="2"
                    />
                </div>
            </x-card>

            <x-card title="{{ __('inventory.stock_settings') }}">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <x-input
                            type="number"
                            step="1"
                            name="reorder_level"
                            label="{{ __('inventory.reorder_level') }}"
                            placeholder="e.g., 10"
                        />
                        <x-input
                            type="number"
                            step="1"
                            name="reorder_quantity"
                            label="{{ __('inventory.reorder_quantity') }}"
                            placeholder="e.g., 50"
                        />
                        <x-input
                            type="number"
                            step="1"
                            name="max_stock_level"
                            label="{{ __('inventory.max_stock_level') }}"
                            placeholder="e.g., 200"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-input
                            type="number"
                            name="shelf_life_days"
                            label="{{ __('inventory.shelf_life_days') }}"
                            placeholder="e.g., 7"
                        />
                        <x-input
                            name="storage_location"
                            label="{{ __('inventory.storage_location') }}"
                            placeholder="e.g., Cold Storage A"
                        />
                    </div>

                    <div class="flex gap-6">
                        <x-checkbox
                            name="is_perishable"
                            label="{{ __('inventory.is_perishable') }}"
                        />
                        <x-checkbox
                            name="track_batches"
                            label="{{ __('inventory.track_batches') }}"
                        />
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-checkbox
                    name="is_active"
                    label="{{ __('inventory.active') }}"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 mt-4 border-t border-border">
                    <x-button href="{{ route('inventory.items.index') }}" variant="outline-secondary">
                        {{ __('inventory.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('inventory.create_item') }}
                    </x-button>
                </div>
            </x-card>
        </form>
    </div>
</x-app-layout>
