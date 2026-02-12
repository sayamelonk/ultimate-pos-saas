<x-app-layout>
    <x-slot name="title">Add Modifier Group - Ultimate POS</x-slot>

    @section('page-title', 'Add Modifier Group')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.modifier-groups.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Add Modifier Group</h2>
                <p class="text-muted mt-1">Create a new modifier group with options</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.modifier-groups.store') }}" method="POST" x-data="modifierGroupForm()">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <!-- Basic Information -->
                <x-card title="Group Information">
                    <div class="space-y-4">
                        <x-form-group label="Group Name" name="name" required>
                            <x-input
                                name="name"
                                :value="old('name')"
                                placeholder="e.g., Toppings, Extra Shots, Sauces"
                                required
                            />
                        </x-form-group>

                        <x-form-group label="Description" name="description">
                            <x-textarea
                                name="description"
                                :value="old('description')"
                                placeholder="Optional description..."
                                rows="2"
                            />
                        </x-form-group>

                        <x-form-group label="Selection Type" name="selection_type" required>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="selection_type"
                                        value="single"
                                        x-model="selectionType"
                                        {{ old('selection_type', 'single') === 'single' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <x-icon name="check-circle" class="w-6 h-6 mb-2 mx-auto text-muted" />
                                        <p class="text-center font-medium">Single Select</p>
                                        <p class="text-xs text-center text-muted mt-1">Customer picks one option</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="radio"
                                        name="selection_type"
                                        value="multiple"
                                        x-model="selectionType"
                                        {{ old('selection_type') === 'multiple' ? 'checked' : '' }}
                                        class="peer sr-only"
                                    >
                                    <div class="p-4 border-2 border-border rounded-lg peer-checked:border-accent peer-checked:bg-accent/5 transition-all">
                                        <x-icon name="squares-plus" class="w-6 h-6 mb-2 mx-auto text-muted" />
                                        <p class="text-center font-medium">Multi Select</p>
                                        <p class="text-xs text-center text-muted mt-1">Customer picks multiple</p>
                                    </div>
                                </label>
                            </div>
                        </x-form-group>

                        <!-- Selection Rules (for multiple) -->
                        <div x-show="selectionType === 'multiple'" x-cloak class="grid grid-cols-2 gap-4">
                            <x-form-group label="Minimum Selections" name="min_selections">
                                <x-input
                                    type="number"
                                    name="min_selections"
                                    :value="old('min_selections', 0)"
                                    min="0"
                                    x-model="minSelections"
                                />
                                <p class="text-xs text-muted mt-1">0 = optional</p>
                            </x-form-group>

                            <x-form-group label="Maximum Selections" name="max_selections">
                                <x-input
                                    type="number"
                                    name="max_selections"
                                    :value="old('max_selections')"
                                    min="1"
                                    placeholder="Leave empty for unlimited"
                                    x-model="maxSelections"
                                />
                            </x-form-group>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="Sort Order" name="sort_order">
                                <x-input
                                    type="number"
                                    name="sort_order"
                                    :value="old('sort_order', 0)"
                                    min="0"
                                />
                            </x-form-group>

                            <x-form-group>
                                <label class="block text-sm font-medium text-text mb-2">&nbsp;</label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="hidden" name="is_active" value="0">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        {{ old('is_active', true) ? 'checked' : '' }}
                                        class="rounded border-border text-accent focus:ring-accent"
                                    >
                                    <span class="text-sm font-medium text-text">Active</span>
                                </label>
                            </x-form-group>
                        </div>
                    </div>
                </x-card>

                <!-- Modifiers -->
                <x-card title="Modifiers">
                    <p class="text-muted mb-4">Add modifier options (e.g., Extra Cheese, Whipped Cream, Caramel Sauce)</p>

                    <div class="space-y-3">
                        <template x-for="(modifier, index) in modifiers" :key="index">
                            <div class="flex items-start gap-3 p-4 bg-secondary-50 rounded-lg">
                                <div class="flex-1 grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs text-muted mb-1">Modifier Name *</label>
                                        <input
                                            type="text"
                                            :name="`modifiers[${index}][name]`"
                                            x-model="modifier.name"
                                            placeholder="e.g., Extra Cheese"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs text-muted mb-1">Price</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">Rp</span>
                                            <input
                                                type="number"
                                                :name="`modifiers[${index}][price]`"
                                                x-model="modifier.price"
                                                placeholder="0"
                                                min="0"
                                                step="100"
                                                class="w-full pl-10 pr-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-muted mb-1">Inventory Item</label>
                                        <select
                                            :name="`modifiers[${index}][inventory_item_id]`"
                                            x-model="modifier.inventory_item_id"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                        >
                                            <option value="">None</option>
                                            @foreach($inventoryItems as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeModifier(index)"
                                    class="p-2 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                    x-show="modifiers.length > 1"
                                >
                                    <x-icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </template>
                    </div>

                    <x-button type="button" variant="outline-secondary" size="sm" icon="plus" @click="addModifier()" class="mt-4">
                        Add Modifier
                    </x-button>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Preview -->
                <x-card title="Preview">
                    <div class="space-y-3">
                        <p class="text-sm text-muted">How it appears in POS:</p>

                        <div class="p-3 bg-secondary-50 rounded-lg">
                            <p class="font-medium mb-2" x-text="'{{ old('name') }}' || 'Group Name'"></p>

                            <!-- Single Select Preview -->
                            <div x-show="selectionType === 'single'" class="space-y-2">
                                <template x-for="(modifier, index) in modifiers" :key="index">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" :name="'preview'" :checked="index === 0" class="text-accent focus:ring-accent">
                                        <span x-text="modifier.name || 'Modifier ' + (index + 1)"></span>
                                        <span x-show="modifier.price > 0" class="text-xs text-muted">
                                            (+Rp <span x-text="Number(modifier.price || 0).toLocaleString('id-ID')"></span>)
                                        </span>
                                    </label>
                                </template>
                            </div>

                            <!-- Multiple Select Preview -->
                            <div x-show="selectionType === 'multiple'" class="space-y-2">
                                <template x-for="(modifier, index) in modifiers" :key="index">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" class="rounded text-accent focus:ring-accent">
                                        <span x-text="modifier.name || 'Modifier ' + (index + 1)"></span>
                                        <span x-show="modifier.price > 0" class="text-xs text-muted">
                                            (+Rp <span x-text="Number(modifier.price || 0).toLocaleString('id-ID')"></span>)
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div x-show="selectionType === 'multiple'" class="text-xs text-muted">
                            <span x-show="minSelections > 0">Min: <span x-text="minSelections"></span></span>
                            <span x-show="minSelections > 0 && maxSelections"> | </span>
                            <span x-show="maxSelections">Max: <span x-text="maxSelections"></span></span>
                        </div>
                    </div>
                </x-card>

                <!-- Examples -->
                <x-card title="Common Examples">
                    <div class="space-y-3 text-sm text-muted">
                        <div>
                            <p class="font-medium text-text">Toppings (Multi)</p>
                            <p>Cheese, Bacon, Onion, Mushroom</p>
                        </div>
                        <div>
                            <p class="font-medium text-text">Extra Shots (Multi)</p>
                            <p>Espresso +5k, Vanilla +3k, Caramel +3k</p>
                        </div>
                        <div>
                            <p class="font-medium text-text">Sauce (Single)</p>
                            <p>BBQ, Mayo, Ketchup, Mustard</p>
                        </div>
                        <div>
                            <p class="font-medium text-text">Milk Type (Single)</p>
                            <p>Regular, Oat +5k, Almond +5k, Soy +3k</p>
                        </div>
                    </div>
                </x-card>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <x-button type="submit" icon="check" class="w-full">
                        Create Modifier Group
                    </x-button>
                    <x-button href="{{ route('menu.modifier-groups.index') }}" variant="ghost" class="w-full">
                        Cancel
                    </x-button>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function modifierGroupForm() {
            return {
                selectionType: '{{ old('selection_type', 'single') }}',
                minSelections: {{ old('min_selections', 0) }},
                maxSelections: {{ old('max_selections') ? old('max_selections') : 'null' }},
                modifiers: @json(old('modifiers', [
                    ['name' => '', 'price' => 0, 'inventory_item_id' => '']
                ])),

                addModifier() {
                    this.modifiers.push({
                        name: '',
                        price: 0,
                        inventory_item_id: ''
                    });
                },

                removeModifier(index) {
                    this.modifiers.splice(index, 1);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
