<x-app-layout>
    <x-slot name="title">{{ __('inventory.create_transfer') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.create_transfer'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('inventory.stock-transfers.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('inventory.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.add_transfer_title') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.create_new_transfer') }}</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('inventory.stock-transfers.store') }}" method="POST" x-data="transferForm()" class="space-y-6">
        @csrf

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-6">
                <x-card :title="__('inventory.transfer_details')">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-select name="from_outlet_id" :label="__('inventory.from_outlet')" x-model="fromOutlet" required>
                                <option value="">{{ __('inventory.source_outlet') }}</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(old('from_outlet_id') == $outlet->id)>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </x-select>

                            <x-select name="to_outlet_id" :label="__('inventory.to_outlet')" x-model="toOutlet" required>
                                <option value="">{{ __('inventory.destination_outlet') }}</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(old('to_outlet_id') == $outlet->id) x-bind:disabled="fromOutlet == '{{ $outlet->id }}'">
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-input
                                type="date"
                                name="transfer_date"
                                :label="__('inventory.transfer_date')"
                                :value="old('transfer_date', date('Y-m-d'))"
                                required
                            />

                            <x-input
                                type="date"
                                name="expected_date"
                                :label="__('inventory.expected_date')"
                                :value="old('expected_date')"
                            />
                        </div>

                        <x-textarea
                            name="notes"
                            :label="__('inventory.notes')"
                            :placeholder="__('inventory.optional_notes')"
                            :value="old('notes')"
                            rows="2"
                        />
                    </div>
                </x-card>

                <x-card :title="__('inventory.transfer_items_title')">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 mb-4 p-4 bg-secondary-50 rounded-lg">
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
                            <div class="flex-1">
                                <label class="text-sm font-medium text-text">{{ __('inventory.notes') }}</label>
                                <input type="text" x-model="item.notes" :name="'items[' + index + '][notes]'" class="w-full mt-1 px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent focus:border-accent" :placeholder="__('inventory.optional_notes')">
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removeItem(index)" class="p-2 text-danger-600 hover:bg-danger-50 rounded-lg" x-show="items.length > 1">
                                    <x-icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <x-button type="button" @click="addItem()" variant="outline-secondary" icon="plus" class="mt-4">
                        {{ __('inventory.add_transfer_item') }}
                    </x-button>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card :title="__('inventory.summary')">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.from_outlet') }}</dt>
                            <dd class="font-medium" x-text="fromOutlet ? outlets[fromOutlet] : '-'"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.to_outlet') }}</dt>
                            <dd class="font-medium" x-text="toOutlet ? outlets[toOutlet] : '-'"></dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-border">
                            <dt class="font-bold">{{ __('inventory.total_items') }}</dt>
                            <dd class="font-bold text-lg" x-text="items.length"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ __('inventory.total_quantity') }}</dt>
                            <dd class="font-medium" x-text="formatNumber(totalQuantity)"></dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" class="w-full">
                            {{ __('inventory.create_transfer') }}
                        </x-button>
                        <x-button href="{{ route('inventory.stock-transfers.index') }}" variant="outline-secondary" class="w-full">
                            {{ __('inventory.cancel') }}
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function transferForm() {
            return {
                fromOutlet: '{{ old('from_outlet_id', '') }}',
                toOutlet: '{{ old('to_outlet_id', '') }}',
                outlets: @json($outlets->pluck('name', 'id')),
                items: [{ inventory_item_id: '', quantity: 0, notes: '' }],
                get totalQuantity() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
                },
                addItem() {
                    this.items.push({ inventory_item_id: '', quantity: 0, notes: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
