<x-app-layout>
    <x-slot name="title">Edit {{ $variantGroup->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Edit Variant Group')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('menu.variant-groups.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Edit Variant Group</h2>
                <p class="text-muted mt-1">{{ $variantGroup->name }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('menu.variant-groups.update', $variantGroup) }}" method="POST" x-data="variantGroupForm()">
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
                                :value="old('name', $variantGroup->name)"
                                placeholder="e.g., Size, Ice Level, Sugar Level"
                                required
                            />
                        </x-form-group>

                        <x-form-group label="Description" name="description">
                            <x-textarea
                                name="description"
                                :value="old('description', $variantGroup->description)"
                                placeholder="Optional description..."
                                rows="2"
                            />
                        </x-form-group>

                        <div class="grid grid-cols-2 gap-4">
                            <x-form-group label="Display Type" name="display_type">
                                <x-select name="display_type" x-model="displayType">
                                    <option value="button" @selected(old('display_type', $variantGroup->display_type) === 'button')>Button</option>
                                    <option value="dropdown" @selected(old('display_type', $variantGroup->display_type) === 'dropdown')>Dropdown</option>
                                    <option value="color" @selected(old('display_type', $variantGroup->display_type) === 'color')>Color Swatch</option>
                                    <option value="image" @selected(old('display_type', $variantGroup->display_type) === 'image')>Image</option>
                                </x-select>
                            </x-form-group>

                            <x-form-group label="Sort Order" name="sort_order">
                                <x-input
                                    type="number"
                                    name="sort_order"
                                    :value="old('sort_order', $variantGroup->sort_order)"
                                    min="0"
                                />
                            </x-form-group>
                        </div>

                        <x-form-group>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', $variantGroup->is_active) ? 'checked' : '' }}
                                    class="rounded border-border text-accent focus:ring-accent"
                                >
                                <span class="text-sm font-medium text-text">Active</span>
                            </label>
                        </x-form-group>
                    </div>
                </x-card>

                <!-- Options -->
                <x-card title="Variant Options">
                    <p class="text-muted mb-4">Manage options for this variant group</p>

                    <div class="space-y-3" x-ref="optionsContainer">
                        <template x-for="(option, index) in options" :key="option.id || index">
                            <div class="flex items-start gap-3 p-4 bg-secondary-50 rounded-lg">
                                <input type="hidden" :name="`options[${index}][id]`" :value="option.id">
                                <div class="flex-1 grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs text-muted mb-1">Option Name *</label>
                                        <input
                                            type="text"
                                            :name="`options[${index}][name]`"
                                            x-model="option.name"
                                            placeholder="e.g., Small"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs text-muted mb-1">Price Adjustment</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">Rp</span>
                                            <input
                                                type="number"
                                                :name="`options[${index}][price_adjustment]`"
                                                x-model="option.price_adjustment"
                                                placeholder="0"
                                                step="100"
                                                class="w-full pl-10 pr-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                            >
                                        </div>
                                    </div>
                                    <div x-show="displayType === 'color'">
                                        <label class="block text-xs text-muted mb-1">Color</label>
                                        <input
                                            type="color"
                                            :name="`options[${index}][color_code]`"
                                            x-model="option.color_code"
                                            class="w-full h-10 rounded border border-border cursor-pointer"
                                        >
                                    </div>
                                    <div x-show="displayType !== 'color'">
                                        <label class="block text-xs text-muted mb-1">Sort Order</label>
                                        <input
                                            type="number"
                                            :name="`options[${index}][sort_order]`"
                                            x-model="option.sort_order"
                                            placeholder="0"
                                            min="0"
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent"
                                        >
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-1 text-xs">
                                        <input
                                            type="checkbox"
                                            :name="`options[${index}][is_active]`"
                                            :checked="option.is_active"
                                            value="1"
                                            class="rounded border-border text-accent focus:ring-accent"
                                        >
                                        Active
                                    </label>
                                    <button
                                        type="button"
                                        @click="removeOption(index)"
                                        class="p-2 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                        x-show="options.length > 1"
                                    >
                                        <x-icon name="trash" class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <x-button type="button" variant="outline-secondary" size="sm" icon="plus" @click="addOption()" class="mt-4">
                        Add Option
                    </x-button>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Preview -->
                <x-card title="Preview">
                    <div class="space-y-3">
                        <p class="text-sm text-muted">How options will appear:</p>

                        <!-- Button Preview -->
                        <div x-show="displayType === 'button'" class="flex flex-wrap gap-2">
                            <template x-for="(option, index) in options.filter(o => o.is_active !== false)" :key="index">
                                <button type="button" class="px-4 py-2 border-2 border-border rounded-lg hover:border-accent transition-colors" :class="index === 0 ? 'border-accent bg-accent/5' : ''">
                                    <span x-text="option.name || 'Option ' + (index + 1)"></span>
                                </button>
                            </template>
                        </div>

                        <!-- Dropdown Preview -->
                        <div x-show="displayType === 'dropdown'">
                            <select class="w-full px-3 py-2 border border-border rounded-lg">
                                <template x-for="(option, index) in options.filter(o => o.is_active !== false)" :key="index">
                                    <option x-text="option.name || 'Option ' + (index + 1)"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Color Preview -->
                        <div x-show="displayType === 'color'" class="flex flex-wrap gap-2">
                            <template x-for="(option, index) in options.filter(o => o.is_active !== false)" :key="index">
                                <button type="button" class="w-10 h-10 rounded-full border-2 border-border hover:border-accent transition-colors" :class="index === 0 ? 'ring-2 ring-accent ring-offset-2' : ''" :style="`background-color: ${option.color_code || '#e5e7eb'}`"></button>
                            </template>
                        </div>

                        <!-- Image Preview -->
                        <div x-show="displayType === 'image'" class="flex flex-wrap gap-2">
                            <template x-for="(option, index) in options.filter(o => o.is_active !== false)" :key="index">
                                <div class="w-16 h-16 border-2 border-border rounded-lg flex items-center justify-center bg-secondary-100" :class="index === 0 ? 'border-accent' : ''">
                                    <x-icon name="photo" class="w-6 h-6 text-muted" />
                                </div>
                            </template>
                        </div>
                    </div>
                </x-card>

                <!-- Products Using -->
                @if($variantGroup->products->count() > 0)
                    <x-card title="Products Using This Group">
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($variantGroup->products as $product)
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
                            <dt class="text-muted">Options</dt>
                            <dd class="font-medium">{{ $variantGroup->options->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Products</dt>
                            <dd class="font-medium">{{ $variantGroup->products->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Created</dt>
                            <dd>{{ $variantGroup->created_at->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <x-button type="submit" icon="check" class="w-full">
                        Update Variant Group
                    </x-button>
                    <x-button href="{{ route('menu.variant-groups.index') }}" variant="ghost" class="w-full">
                        Cancel
                    </x-button>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function variantGroupForm() {
            return {
                displayType: '{{ old('display_type', $variantGroup->display_type) }}',
                options: @json(old('options', $variantGroup->options->map(fn($o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'price_adjustment' => $o->price_adjustment,
                    'color_code' => $o->color_code ?? '#3b82f6',
                    'sort_order' => $o->sort_order,
                    'is_active' => $o->is_active
                ])->toArray())),

                addOption() {
                    this.options.push({
                        id: null,
                        name: '',
                        price_adjustment: 0,
                        color_code: '#3b82f6',
                        sort_order: this.options.length,
                        is_active: true
                    });
                },

                removeOption(index) {
                    this.options.splice(index, 1);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
