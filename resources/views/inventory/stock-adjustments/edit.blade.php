<x-app-layout>
    <x-slot name="title">{{ __('inventory.edit_adjustment') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.edit_adjustment'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stock-adjustments.show', $adjustment) }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.edit_adjustment') }}</h2>
                <p class="text-muted mt-1">{{ $adjustment->adjustment_number }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.stock-adjustments.update', $adjustment) }}" method="POST" x-data="adjustmentForm()" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card :title="__('inventory.adjustment_details')">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-text">{{ __('inventory.outlet') }}</label>
                                <p class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg">{{ $adjustment->outlet->name }}</p>
                            </div>

                            <x-input
                                type="date"
                                name="adjustment_date"
                                :label="__('inventory.adjustment_date')"
                                :value="old('adjustment_date', $adjustment->adjustment_date->format('Y-m-d'))"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-text">{{ __('inventory.adjustment_type') }}</label>
                                <p class="mt-1 px-3 py-2 bg-secondary-100 rounded-lg">
                                    @if($adjustment->type === 'addition')
                                        {{ __('inventory.addition_increase') }}
                                    @else
                                        {{ __('inventory.subtraction_decrease') }}
                                    @endif
                                </p>
                            </div>

                            <x-input
                                name="reference"
                                :label="__('inventory.reference')"
                                :placeholder="__('inventory.reference_placeholder')"
                                :value="old('reference', $adjustment->reference)"
                            />
                        </div>

                        <x-textarea
                            name="reason"
                            :label="__('inventory.reason')"
                            :placeholder="__('inventory.reason_placeholder')"
                            :value="old('reason', $adjustment->reason)"
                            rows="2"
                            required
                        />
                    </div>
                </x-card>

                <x-card :title="__('inventory.adjustment_items')">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
                            <input type="hidden" :name="'items[' + index + '][id]'" :value="item.id">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">{{ __('inventory.item') }}</label>
                                <select x-model="item.inventory_item_id" :name="'items[' + index + '][inventory_item_id]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" required>
                                    <option value="">{{ __('inventory.select_item') }}</option>
                                    @foreach($inventoryItems as $inventoryItem)
                                        <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }} ({{ $inventoryItem->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="text-sm font-medium text-text">{{ __('inventory.quantity') }}</label>
                                <input type="number" step="0.001" x-model="item.quantity" :name="'items[' + index + '][quantity]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0" min="0.001" required>
                            </div>
                            <div class="w-40">
                                <label class="text-sm font-medium text-text">{{ __('inventory.unit_cost') }} (Rp)</label>
                                <input type="number" step="0.01" x-model="item.unit_cost" :name="'items[' + index + '][unit_cost]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" placeholder="0">
                            </div>
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">{{ __('inventory.notes') }}</label>
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
                            <dt class="text-muted">{{ __('inventory.adjustment_number') }}</dt>
                            <dd class="font-medium">{{ $adjustment->adjustment_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.adjustment_type') }}</dt>
                            <dd>
                                @if($adjustment->type === 'addition')
                                    <x-badge type="success">{{ __('inventory.adjustment_add') }}</x-badge>
                                @else
                                    <x-badge type="danger">{{ __('inventory.adjustment_subtract') }}</x-badge>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.items') }}</dt>
                            <dd class="font-medium" x-text="items.length"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.total_quantity') }}</dt>
                            <dd class="font-medium" x-text="formatNumber(totalQuantity)"></dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="font-bold">{{ __('inventory.total_value') }}</dt>
                            <dd class="font-bold text-lg" x-text="'Rp ' + formatNumber(totalValue)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            {{ __('inventory.update') }} {{ __('inventory.stock_adjustment') }}
                        </x-button>
                        <x-button href="{{ route('inventory.stock-adjustments.show', $adjustment) }}" variant="outline-secondary" class="w-full">
                            {{ __('inventory.cancel') }}
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
                items: @json($adjustment->items->map(fn($item) => [
                    'id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'notes' => $item->notes,
                ])),
                get totalQuantity() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
                },
                get totalValue() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_cost) || 0), 0);
                },
                addItem() {
                    this.items.push({ id: null, inventory_item_id: '', quantity: 0, unit_cost: 0, notes: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
