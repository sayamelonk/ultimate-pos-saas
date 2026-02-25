<x-app-layout>
    <x-slot name="title">Edit {{ $item->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Edit Item')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.items.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Inventory Item</h2>
                <p class="text-muted mt-1">{{ $item->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <form action="{{ route('inventory.items.update', $item) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <x-card title="Basic Information">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-input
                            name="sku"
                            label="SKU"
                            placeholder="e.g., RAW-BEEF-001"
                            :value="$item->sku"
                            required
                        />
                        <x-input
                            name="barcode"
                            label="Barcode"
                            placeholder="e.g., 8991234567890"
                            :value="$item->barcode"
                        />
                    </div>

                    <x-input
                        name="name"
                        label="Item Name"
                        placeholder="e.g., Beef Sirloin"
                        :value="$item->name"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="category_id" label="Category" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </x-select>

                        <x-select name="unit_id" label="Unit of Measure" required>
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id', $item->unit_id) == $unit->id)>
                                    {{ $unit->name }} ({{ $unit->abbreviation }})
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="type" label="Type" required>
                            <option value="">Select Type</option>
                            <option value="raw_material" @selected(old('type', $item->type) == 'raw_material')>Raw Material</option>
                            <option value="finished_good" @selected(old('type', $item->type) == 'finished_good')>Finished Good</option>
                            <option value="consumable" @selected(old('type', $item->type) == 'consumable')>Consumable</option>
                            <option value="packaging" @selected(old('type', $item->type) == 'packaging')>Packaging</option>
                        </x-select>

                        <div x-data="{
                            rawValue: '{{ old('cost_price', (int) $item->cost_price) }}',
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
                                Cost Price (Rp) <span class="text-danger-500">*</span>
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
                        label="Description"
                        placeholder="Item description..."
                        :value="$item->description"
                        rows="2"
                    />
                </div>
            </x-card>

            <x-card title="Stock Settings">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <x-input
                            type="number"
                            step="1"
                            name="reorder_level"
                            label="Reorder Level"
                            placeholder="e.g., 10"
                            :value="old('reorder_level', $item->reorder_point ? (int) $item->reorder_point : '')"
                            hint="Alert when stock falls below"
                        />
                        <x-input
                            type="number"
                            step="1"
                            name="reorder_quantity"
                            label="Reorder Quantity"
                            placeholder="e.g., 50"
                            :value="old('reorder_quantity', $item->reorder_qty ? (int) $item->reorder_qty : '')"
                            hint="Suggested order quantity"
                        />
                        <x-input
                            type="number"
                            step="1"
                            name="max_stock_level"
                            label="Max Stock Level"
                            placeholder="e.g., 200"
                            :value="old('max_stock_level', $item->max_stock ? (int) $item->max_stock : '')"
                            hint="Maximum stock to keep"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-input
                            type="number"
                            name="shelf_life_days"
                            label="Shelf Life (Days)"
                            placeholder="e.g., 7"
                            :value="old('shelf_life_days', $item->shelf_life_days)"
                        />
                        <x-input
                            name="storage_location"
                            label="Storage Location"
                            placeholder="e.g., Cold Storage A"
                            :value="old('storage_location', $item->storage_location)"
                        />
                    </div>

                    <div class="flex gap-6">
                        <x-checkbox
                            name="is_perishable"
                            label="Perishable"
                            hint="This item can expire"
                            :checked="old('is_perishable', $item->is_perishable)"
                        />
                        <x-checkbox
                            name="track_batches"
                            label="Track Batches"
                            hint="Enable batch/lot tracking"
                            :checked="old('track_batches', $item->track_batches)"
                        />
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive items won't appear in selections"
                    :checked="old('is_active', $item->is_active)"
                />

                <div class="flex items-center justify-end gap-3 pt-4 mt-4 border-t border-border">
                    <x-button href="{{ route('inventory.items.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Update Item
                    </x-button>
                </div>
            </x-card>
        </form>
    </div>
</x-app-layout>
