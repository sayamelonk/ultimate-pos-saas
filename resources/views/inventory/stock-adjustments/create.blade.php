<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_adjustment') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_adjustment'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.create_adjustment') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_adjustment_description') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.stock-adjustments.store') }}" method="POST" x-data="adjustmentForm()" class="space-y-6">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card :title="__('inventory.adjustment_details')">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-select name="outlet_id" :label="__('admin.outlet')" required>
                                <option value="">{{ __('inventory.select_outlet') }}</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </x-select>

                            <x-input
                                type="date"
                                name="adjustment_date"
                                :label="__('inventory.adjustment_date')"
                                :value="old('adjustment_date', date('Y-m-d'))"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-select name="type" :label="__('inventory.adjustment_type')" x-model="type" required>
                                <option value="stock_take">{{ __('inventory.type_stock_take') }}</option>
                                <option value="correction">{{ __('inventory.type_correction') }}</option>
                                <option value="damage">{{ __('inventory.type_damage') }}</option>
                                <option value="loss">{{ __('inventory.type_loss') }}</option>
                                <option value="found">{{ __('inventory.type_found') }}</option>
                            </x-select>

                            <x-input
                                name="reference"
                                :label="__('inventory.reference')"
                                :placeholder="__('inventory.reference_placeholder')"
                                :value="old('reference')"
                            />
                        </div>

                        <x-textarea
                            name="reason"
                            :label="__('inventory.reason')"
                            :placeholder="__('inventory.reason_placeholder')"
                            :value="old('reason')"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card :title="__('inventory.adjustment_items')">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">{{ __('inventory.item') }}</label>
                                <select x-model="item.inventory_item_id" :name="'items[' + index + '][inventory_item_id]'" @change="updateSystemQty(index)" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">{{ __('inventory.select_item') }}</option>
                                    @foreach($inventoryItems as $inventoryItem)
                                        <option value="{{ $inventoryItem->id }}" data-stock="{{ $inventoryItem->stocks->sum('quantity') }}">{{ $inventoryItem->name }} ({{ $inventoryItem->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">{{ __('inventory.system_stock') }}</label>
                                <input type="number" step="0.001" x-model="item.system_quantity" :name="'items[' + index + '][system_quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg bg-secondary-100 text-muted" readonly>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">{{ __('inventory.actual_stock') }}</label>
                                <input type="number" step="0.001" x-model="item.actual_quantity" :name="'items[' + index + '][actual_quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0" required>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">{{ __('inventory.difference') }}</label>
                                <input type="text" :value="formatNumber(item.actual_quantity - item.system_quantity)" class="w-full mt-1 px-3 py-2 border border-border rounded-lg bg-secondary-100" :class="{'text-success-600': item.actual_quantity > item.system_quantity, 'text-danger-600': item.actual_quantity < item.system_quantity}" readonly>
                            </div>
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">{{ __('app.notes') }}</label>
                                <input type="text" x-model="item.notes" :name="'items[' + index + '][notes]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="{{ __('inventory.optional_notes') }}">
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removeItem(index)" class="p-2 text-danger-600 hover:bg-danger-50 rounded-lg" x-show="items.length > 1">
                                    <x-icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <x-button type="button" @click="addItem()" variant="outline-secondary" icon="plus" class="mt-4">
                        {{ __('inventory.add_item') }}
                    </x-button>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card :title="__('inventory.summary')">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('app.type') }}</dt>
                            <dd>
                                <span x-text="getTypeLabel(type)" class="px-2 py-1 text-xs font-medium rounded-full bg-secondary-100"></span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.items') }}</dt>
                            <dd class="font-medium" x-text="items.filter(i => i.inventory_item_id).length"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.total_variance') }}</dt>
                            <dd class="font-medium" x-text="formatNumber(totalVariance)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            {{ __('inventory.create_adjustment') }}
                        </x-button>
                        <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="outline-secondary" class="w-full">
                            {{ __('app.cancel') }}
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function adjustmentForm() {
            return {
                type: 'stock_take',
                items: [{ inventory_item_id: '', system_quantity: 0, actual_quantity: 0, notes: '' }],
                get totalVariance() {
                    return this.items.reduce((sum, item) => sum + Math.abs((parseFloat(item.actual_quantity) || 0) - (parseFloat(item.system_quantity) || 0)), 0);
                },
                addItem() {
                    this.items.push({ inventory_item_id: '', system_quantity: 0, actual_quantity: 0, notes: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                updateSystemQty(index) {
                    const select = document.querySelector(`select[name="items[${index}][inventory_item_id]"]`);
                    const option = select.options[select.selectedIndex];
                    this.items[index].system_quantity = parseFloat(option.dataset.stock) || 0;
                },
                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                },
                getTypeLabel(type) {
                    const labels = {
                        'stock_take': '{{ __('inventory.type_stock_take') }}',
                        'correction': '{{ __('inventory.type_correction') }}',
                        'damage': '{{ __('inventory.type_damage') }}',
                        'loss': '{{ __('inventory.type_loss') }}',
                        'found': '{{ __('inventory.type_found') }}'
                    };
                    return labels[type] || type;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
