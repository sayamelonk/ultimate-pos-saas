<x-app-layout>
    <x-slot name="title">{{ $combo->name }} - Ultimate POS</x-slot>

    @section('page-title', 'Combo Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-button href="{{ route('menu.combos.index') }}" variant="ghost" icon="arrow-left" size="sm">
                    Back
                </x-button>
                <div>
                    <h2 class="text-2xl font-bold text-text">{{ $combo->name }}</h2>
                    <p class="text-muted mt-1">Combo Details</p>
                </div>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('menu.combos.edit', $combo) }}" icon="pencil">
                    Edit
                </x-button>
            </div>
        </div>
    </x-slot>

    @php
        $comboSettings = $combo->combo;
        $comboItems = $comboSettings?->items ?? collect([]);
        $pricingType = $comboSettings?->pricing_type ?? 'fixed';
        $discountValue = $comboSettings?->discount_value ?? 0;
        $allowSubstitutions = $comboSettings?->allow_substitutions ?? false;
    @endphp

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            <!-- Combo Information -->
            <x-card title="Combo Information">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-muted">Name</dt>
                        <dd class="mt-1 font-medium">{{ $combo->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="mt-1">
                            @if($combo->is_active)
                                <x-badge type="success">Active</x-badge>
                            @else
                                <x-badge type="danger">Inactive</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Allow Substitutions</dt>
                        <dd class="mt-1">
                            @if($allowSubstitutions)
                                <x-badge type="info">Yes</x-badge>
                            @else
                                <x-badge type="secondary">No</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Sort Order</dt>
                        <dd class="mt-1 font-medium">{{ $combo->sort_order }}</dd>
                    </div>
                    @if($combo->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-muted">Description</dt>
                            <dd class="mt-1">{{ $combo->description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Pricing -->
            <x-card title="Pricing">
                <div class="grid grid-cols-2 gap-6">
                    <div class="p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-muted mb-1">Pricing Type</p>
                        <p class="font-bold text-lg">
                            @if($pricingType === 'fixed')
                                Fixed Price
                            @elseif($pricingType === 'sum')
                                Sum of Items
                            @elseif($pricingType === 'discount_percent')
                                {{ $discountValue }}% Discount
                            @else
                                Rp {{ number_format($discountValue, 0, ',', '.') }} Off
                            @endif
                        </p>
                    </div>
                    <div class="p-4 bg-accent/10 rounded-lg">
                        <p class="text-sm text-muted mb-1">
                            @if($pricingType === 'fixed')
                                Combo Price
                            @else
                                Estimated Price
                            @endif
                        </p>
                        <p class="font-bold text-2xl text-accent">
                            @php
                                $estimatedTotal = $comboItems->sum(function($item) {
                                    return $item->product ? $item->product->base_price * $item->quantity : 0;
                                });
                                if ($pricingType === 'fixed') {
                                    $displayPrice = $combo->base_price;
                                } elseif ($pricingType === 'discount_percent') {
                                    $displayPrice = $estimatedTotal * (1 - $discountValue / 100);
                                } elseif ($pricingType === 'discount_amount') {
                                    $displayPrice = max(0, $estimatedTotal - $discountValue);
                                } else {
                                    $displayPrice = $estimatedTotal;
                                }
                            @endphp
                            Rp {{ number_format($displayPrice, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-card>

            <!-- Combo Items -->
            <x-card title="Combo Items">
                @if($comboItems->count() > 0)
                    <x-table>
                        <x-slot name="head">
                            <x-th>Item</x-th>
                            <x-th>Type</x-th>
                            <x-th align="center">Quantity</x-th>
                            <x-th align="right">Unit Price</x-th>
                            <x-th align="right">Subtotal</x-th>
                        </x-slot>

                        @foreach($comboItems->sortBy('sort_order') as $item)
                            <tr>
                                <x-td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-secondary-100 rounded-lg flex items-center justify-center">
                                            <x-icon name="{{ $item->product_id ? 'cube' : 'squares-2x2' }}" class="w-5 h-5 text-muted" />
                                        </div>
                                        <div>
                                            <p class="font-medium">
                                                @if($item->product)
                                                    {{ $item->product->name }}
                                                @elseif($item->category)
                                                    Any from {{ $item->category->name }}
                                                @else
                                                    Unknown Item
                                                @endif
                                            </p>
                                            @if($item->product)
                                                <p class="text-xs text-muted">{{ $item->product->sku }}</p>
                                            @endif
                                            @if($item->group_name)
                                                <p class="text-xs text-accent">{{ $item->group_name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </x-td>
                                <x-td>
                                    @if($item->product_id)
                                        <x-badge type="secondary">Specific Product</x-badge>
                                    @else
                                        <x-badge type="info">Category Choice</x-badge>
                                    @endif
                                </x-td>
                                <x-td align="center">
                                    <span class="font-medium">{{ $item->quantity }}x</span>
                                </x-td>
                                <x-td align="right">
                                    @if($item->product)
                                        Rp {{ number_format($item->product->base_price, 0, ',', '.') }}
                                    @else
                                        <span class="text-muted">Varies</span>
                                    @endif
                                </x-td>
                                <x-td align="right">
                                    @if($item->product)
                                        <span class="font-medium">Rp {{ number_format($item->product->base_price * $item->quantity, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </x-td>
                            </tr>
                        @endforeach
                    </x-table>

                    @php
                        $itemsTotal = $comboItems->sum(function($item) {
                            return $item->product ? $item->product->base_price * $item->quantity : 0;
                        });
                    @endphp
                    <div class="mt-4 pt-4 border-t border-border">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">Items Total</span>
                            <span class="font-medium">Rp {{ number_format($itemsTotal, 0, ',', '.') }}</span>
                        </div>
                        @if($pricingType === 'discount_percent')
                            <div class="flex justify-between text-sm mt-2">
                                <span class="text-muted">Discount ({{ $discountValue }}%)</span>
                                <span class="text-success font-medium">-Rp {{ number_format($itemsTotal * $discountValue / 100, 0, ',', '.') }}</span>
                            </div>
                        @elseif($pricingType === 'discount_amount')
                            <div class="flex justify-between text-sm mt-2">
                                <span class="text-muted">Discount</span>
                                <span class="text-success font-medium">-Rp {{ number_format($discountValue, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between mt-2 pt-2 border-t border-border">
                            <span class="font-medium">Combo Price</span>
                            <span class="font-bold text-lg text-accent">
                                @if($pricingType === 'fixed')
                                    Rp {{ number_format($combo->base_price, 0, ',', '.') }}
                                @elseif($pricingType === 'sum')
                                    Rp {{ number_format($itemsTotal, 0, ',', '.') }}
                                @elseif($pricingType === 'discount_percent')
                                    Rp {{ number_format($itemsTotal * (1 - $discountValue / 100), 0, ',', '.') }}
                                @else
                                    Rp {{ number_format(max(0, $itemsTotal - $discountValue), 0, ',', '.') }}
                                @endif
                            </span>
                        </div>
                    </div>
                @else
                    <x-empty-state
                        title="No items"
                        description="Add items to this combo."
                        icon="rectangle-stack"
                        size="sm"
                    />
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Visual Preview -->
            <x-card title="Preview">
                <div class="space-y-4">
                    <div class="aspect-video bg-gradient-to-br from-accent/20 to-warning/20 rounded-lg flex items-center justify-center relative overflow-hidden">
                        <x-icon name="rectangle-stack" class="w-16 h-16 text-accent" />
                        @if(!$combo->is_active)
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <span class="bg-danger text-white px-3 py-1 rounded text-sm font-medium">Inactive</span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <p class="font-bold text-lg">{{ $combo->name }}</p>
                        @if($combo->description)
                            <p class="text-sm text-muted mt-1">{{ $combo->description }}</p>
                        @endif
                    </div>

                    <div class="border-t border-border pt-4">
                        <p class="text-sm text-muted mb-2">Includes:</p>
                        <ul class="space-y-2">
                            @foreach($comboItems as $item)
                                <li class="flex items-center gap-2 text-sm">
                                    <x-icon name="check-circle" class="w-4 h-4 text-success" />
                                    <span>
                                        {{ $item->quantity }}x
                                        @if($item->product)
                                            {{ $item->product->name }}
                                        @elseif($item->category)
                                            Any {{ $item->category->name }}
                                        @else
                                            Item
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="border-t border-border pt-4 text-center">
                        @if($pricingType === 'fixed')
                            <p class="text-sm text-muted">Combo Price</p>
                            <p class="text-3xl font-bold text-accent">Rp {{ number_format($combo->base_price, 0, ',', '.') }}</p>
                        @elseif($pricingType === 'discount_percent')
                            <p class="text-sm text-muted">Save</p>
                            <p class="text-3xl font-bold text-success">{{ $discountValue }}% OFF</p>
                        @elseif($pricingType === 'discount_amount')
                            <p class="text-sm text-muted">Save</p>
                            <p class="text-3xl font-bold text-success">Rp {{ number_format($discountValue, 0, ',', '.') }}</p>
                        @else
                            <p class="text-sm text-muted">Price based on selections</p>
                        @endif
                    </div>
                </div>
            </x-card>

            <!-- Statistics -->
            <x-card title="Statistics">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Total Items</dt>
                        <dd class="font-bold text-lg">{{ $comboItems->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Total Quantity</dt>
                        <dd class="font-bold text-lg">{{ $comboItems->sum('quantity') }}</dd>
                    </div>
                    @if($pricingType !== 'sum')
                        @php
                            $savings = 0;
                            if ($pricingType === 'fixed') {
                                $savings = $itemsTotal - $combo->base_price;
                            } elseif ($pricingType === 'discount_percent') {
                                $savings = $itemsTotal * $discountValue / 100;
                            } elseif ($pricingType === 'discount_amount') {
                                $savings = $discountValue;
                            }
                        @endphp
                        <div class="flex items-center justify-between">
                            <dt class="text-muted">Customer Savings</dt>
                            <dd class="font-bold text-lg text-success">Rp {{ number_format(max(0, $savings), 0, ',', '.') }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <!-- Metadata -->
            <x-card title="Information">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Created</dt>
                        <dd>{{ $combo->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Updated</dt>
                        <dd>{{ $combo->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>
