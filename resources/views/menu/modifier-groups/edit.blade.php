<x-app-layout>
    <x-slot name="title">Edit {{ $modifierGroup->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Edit Modifier Group')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.modifier-groups.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Modifier Group</h2>
                <p class="text-muted mt-1">{{ $modifierGroup->name }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.modifier-groups.update', $modifierGroup) }}" method="POST" x-data="modifierGroupForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <!-- Basic Information -->
                <x-card title="Group Information">
                    <div class="space-y-4">
                        <x-form-group label="Group Name" name="name" required>
                            <x-input
                                name="name"
                                :value="old('name', $modifierGroup->name)"
                                placeholder="e.g., Toppings, Extra Shots, Sauces"
                                required
                            />
                        </x-form-group>

                        <x-form-group label="Description" name="description">
                            <x-textarea
                                name="description"
                                :value="old('description', $modifierGroup->description)"
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
                                        {{ old('selection_type', $modifierGroup->selection_type) === 'single' ? 'checked' : '' }}
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
                                        {{ old('selection_type', $modifierGroup->selection_type) === 'multiple' ? 'checked' : '' }}
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
                                    :value="old('min_selections', $modifierGroup->min_selections ?? 0)"
                                    min="0"
                                />
                                <p class="text-xs text-muted mt-1">0 = optional</p>
                            </x-form-group>

                            <x-form-group label="Maximum Selections" name="max_selections">
                                <x-input
                                    type="number"
                                    name="max_selections"
                                    :value="old('max_selections', $modifierGroup->max_selections)"
                                    min="1"
                                    placeholder="Leave empty for unlimited"
                                />
                            </x-form-group>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="Sort Order" name="sort_order">
                                <x-input
                                    type="number"
                                    name="sort_order"
                                    :value="old('sort_order', $modifierGroup->sort_order)"
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
                                        {{ old('is_active', $modifierGroup->is_active) ? 'checked' : '' }}
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
                    <p class="text-muted mb-4">Manage modifier options</p>

                    <div class="space-y-3">
                        <template x-for="(modifier, index) in modifiers" :key="modifier.id || index">
                            <div class="flex items-start gap-3 p-4 bg-secondary-50 rounded-lg">
                                <input type="hidden" :name="`modifiers[${index}][id]`" :value="modifier.id">
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
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-1 text-xs">
                                        <input
                                            type="checkbox"
                                            :name="`modifiers[${index}][is_active]`"
                                            :checked="modifier.is_active"
                                            value="1"
                                            class="rounded border-border text-accent focus:ring-accent"
                                        >
                                        Active
                                    </label>
                                    <button
                                        type="button"
                                        @click="removeModifier(index)"
                                        class="p-2 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                        x-show="modifiers.length > 1"
                                    >
                                        <x-icon name="trash" class="w-5 h-5" />
                                    </button>
                                </div>
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
                <!-- Products Using -->
                @if($modifierGroup->products->count() > 0)
                    <x-card title="Products Using This Group">
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($modifierGroup->products as $product)
                                <a href="{{ route('menu.products.show', $product) }}" class="flex items-center gap-2 p-2 bg-secondary-50 rounded hover:bg-secondary-100 transition-colors">
                                    <x-icon name="cube" class="w-4 h-4 text-muted" />
                                    <span class="text-sm truncate">{{ $product->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                <!-- Metadata -->
                <x-card title="Information">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted">Modifiers</dt>
                            <dd class="font-medium">{{ $modifierGroup->modifiers->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Products</dt>
                            <dd class="font-medium">{{ $modifierGroup->products->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Created</dt>
                            <dd>{{ $modifierGroup->created_at->format('d M Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Updated</dt>
                            <dd>{{ $modifierGroup->updated_at->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <x-button type="submit" icon="check" class="w-full">
                        Update Modifier Group
                    </x-button>
                    <x-button href="{{ route('menu.modifier-groups.index') }}" variant="ghost" class="w-full">
                        Cancel
                    </x-button>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    @php
        $modifiersData = old('modifiers', $modifierGroup->modifiers->map(function($m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'price' => $m->price,
                'inventory_item_id' => $m->inventory_item_id ?? '',
                'is_active' => $m->is_active
            ];
        })->toArray());
    @endphp
    <script>
        function modifierGroupForm() {
            return {
                selectionType: '{{ old('selection_type', $modifierGroup->selection_type) }}',
                modifiers: @json($modifiersData),

                addModifier() {
                    this.modifiers.push({
                        id: null,
                        name: '',
                        price: 0,
                        inventory_item_id: '',
                        is_active: true
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
