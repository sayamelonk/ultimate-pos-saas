<x-app-layout>
    <x-slot name="title">Create Inventory Item - Ultimate POS</x-slot>

    @section('page-title', 'Create Item')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.items.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Create Inventory Item</h2>
                <p class="text-muted mt-1">Add a new item to your inventory</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <form action="{{ route('inventory.items.store') }}" method="POST" class="space-y-6">
            @csrf

            <x-card title="Basic Information">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-input
                            name="sku"
                            label="SKU"
                            placeholder="e.g., RAW-BEEF-001"
                            required
                        />
                        <x-input
                            name="barcode"
                            label="Barcode"
                            placeholder="e.g., 8991234567890"
                        />
                    </div>

                    <x-input
                        name="name"
                        label="Item Name"
                        placeholder="e.g., Beef Sirloin"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="category_id" label="Category" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </x-select>

                        <x-select name="unit_id" label="Unit of Measure" required>
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                    {{ $unit->name }} ({{ $unit->abbreviation }})
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="type" label="Type" required>
                            <option value="">Select Type</option>
                            <option value="raw_material" @selected(old('type') === 'raw_material')>Raw Material</option>
                            <option value="finished_good" @selected(old('type') === 'finished_good')>Finished Good</option>
                            <option value="consumable" @selected(old('type') === 'consumable')>Consumable</option>
                            <option value="packaging" @selected(old('type') === 'packaging')>Packaging</option>
                        </x-select>

                        <x-input
                            type="number"
                            step="0.01"
                            name="cost_price"
                            label="Cost Price (Rp)"
                            placeholder="e.g., 150000"
                            required
                        />
                    </div>

                    <x-textarea
                        name="description"
                        label="Description"
                        placeholder="Item description..."
                        rows="2"
                    />
                </div>
            </x-card>

            <x-card title="Stock Settings">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <x-input
                            type="number"
                            step="0.01"
                            name="reorder_level"
                            label="Reorder Level"
                            placeholder="e.g., 10"
                            hint="Alert when stock falls below"
                        />
                        <x-input
                            type="number"
                            step="0.01"
                            name="reorder_quantity"
                            label="Reorder Quantity"
                            placeholder="e.g., 50"
                            hint="Suggested order quantity"
                        />
                        <x-input
                            type="number"
                            step="0.01"
                            name="max_stock_level"
                            label="Max Stock Level"
                            placeholder="e.g., 200"
                            hint="Maximum stock to keep"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-input
                            type="number"
                            name="shelf_life_days"
                            label="Shelf Life (Days)"
                            placeholder="e.g., 7"
                        />
                        <x-input
                            name="storage_location"
                            label="Storage Location"
                            placeholder="e.g., Cold Storage A"
                        />
                    </div>

                    <div class="flex gap-6">
                        <x-checkbox
                            name="is_perishable"
                            label="Perishable"
                            hint="This item can expire"
                        />
                        <x-checkbox
                            name="track_batches"
                            label="Track Batches"
                            hint="Enable batch/lot tracking"
                        />
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive items won't appear in selections"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 mt-4 border-t border-border">
                    <x-button href="{{ route('inventory.items.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Create Item
                    </x-button>
                </div>
            </x-card>
        </form>
    </div>
</x-app-layout>
