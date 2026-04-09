<x-qr-menu-layout :title="$outlet->name . ' - Menu'">
    <div x-data="qrMenuApp()" class="max-w-lg mx-auto min-h-screen flex flex-col pb-24">
        {{-- Sticky Header --}}
        <div class="sticky top-0 z-30 bg-white shadow-sm border-b border-gray-200">
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-gray-900">{{ $outlet->name }}</h1>
                    <p class="text-sm text-gray-500">{{ $table->display_name }}</p>
                </div>
                <button @click="showCart = true" class="relative p-2 bg-primary text-white rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                    <span x-show="cartCount > 0"
                          x-text="cartCount"
                          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"></span>
                </button>
            </div>

            {{-- Category Tabs --}}
            <div class="overflow-x-auto scrollbar-hide">
                <div class="flex gap-2 px-4 pb-3">
                    <button @click="activeCategory = null"
                            :class="activeCategory === null ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700'"
                            class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors">
                        All
                    </button>
                    @foreach($categories as $category)
                        <button @click="activeCategory = '{{ $category->id }}'"
                                :class="activeCategory === '{{ $category->id }}' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700'"
                                class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors whitespace-nowrap">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Product Grid --}}
        <div class="flex-1 px-4 py-4 space-y-3">
            @foreach($products as $product)
                <div x-show="!activeCategory || activeCategory === '{{ $product->category_id }}'"
                     class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex">
                    @if($product->image_url)
                        <div class="w-24 h-24 shrink-0">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        </div>
                    @endif
                    <div class="flex-1 p-3 flex flex-col justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 text-sm">{{ $product->name }}</h3>
                            @if($product->description)
                                <p class="text-xs text-gray-500 line-clamp-2 mt-0.5">{{ $product->description }}</p>
                            @endif
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-sm font-bold text-primary">
                                Rp {{ number_format($product->productOutlets->first()?->custom_price ?? $product->base_price, 0, ',', '.') }}
                            </span>
                            @if($product->variants->count() > 0 || $product->modifierGroups->count() > 0)
                                <button @click="openProductDetail({{ $product->toJson() }})"
                                        class="px-3 py-1 bg-primary text-white text-xs font-medium rounded-lg">
                                    Add
                                </button>
                            @else
                                <button @click="quickAddToCart({{ $product->toJson() }})"
                                        class="px-3 py-1 bg-primary text-white text-xs font-medium rounded-lg">
                                    Add
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Product Detail Modal (Bottom Sheet) --}}
        <div x-show="showProductDetail" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-black/50" @click="showProductDetail = false">
        </div>
        <div x-show="showProductDetail"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-2xl max-h-[80vh] overflow-y-auto max-w-lg mx-auto">
            <template x-if="selectedProduct">
                <div class="p-4 pb-8">
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                    <h2 class="text-lg font-bold text-gray-900" x-text="selectedProduct.name"></h2>
                    <p class="text-sm text-gray-500 mt-1" x-text="selectedProduct.description"></p>

                    {{-- Variants --}}
                    <template x-if="selectedProduct.variants && selectedProduct.variants.length > 0">
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Choose Variant</h3>
                            <div class="space-y-2">
                                <template x-for="variant in selectedProduct.variants" :key="variant.id">
                                    <label class="flex items-center justify-between p-3 rounded-lg border cursor-pointer"
                                           :class="selectedVariant?.id === variant.id ? 'border-primary bg-primary/5' : 'border-gray-200'">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" name="variant" :value="variant.id"
                                                   @change="selectedVariant = variant"
                                                   :checked="selectedVariant?.id === variant.id"
                                                   class="text-primary">
                                            <span class="text-sm font-medium" x-text="variant.name"></span>
                                        </div>
                                        <span class="text-sm font-bold text-primary" x-text="'Rp ' + formatPrice(variant.price)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Modifier Groups --}}
                    <template x-if="selectedProduct.modifier_groups && selectedProduct.modifier_groups.length > 0">
                        <div>
                            <template x-for="group in selectedProduct.modifier_groups" :key="group.id">
                                <div class="mt-4">
                                    <h3 class="text-sm font-semibold text-gray-700 mb-2" x-text="group.name"></h3>
                                    <div class="space-y-2">
                                        <template x-for="modifier in group.modifiers" :key="modifier.id">
                                            <label class="flex items-center justify-between p-3 rounded-lg border cursor-pointer"
                                                   :class="selectedModifiers.includes(modifier.id) ? 'border-primary bg-primary/5' : 'border-gray-200'">
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" :value="modifier.id"
                                                           @change="toggleModifier(modifier)"
                                                           :checked="selectedModifiers.includes(modifier.id)"
                                                           class="rounded text-primary">
                                                    <span class="text-sm font-medium" x-text="modifier.display_name || modifier.name"></span>
                                                </div>
                                                <span class="text-sm text-gray-600" x-text="modifier.price > 0 ? '+Rp ' + formatPrice(modifier.price) : 'Free'"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Item Notes --}}
                    <div class="mt-4">
                        <label class="text-sm font-semibold text-gray-700">Notes</label>
                        <input type="text" x-model="itemNotes" placeholder="Special requests..."
                               class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-primary focus:border-primary">
                    </div>

                    {{-- Quantity --}}
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700">Quantity</span>
                        <div class="flex items-center gap-3">
                            <button @click="itemQuantity = Math.max(1, itemQuantity - 1)"
                                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            </button>
                            <span class="text-lg font-bold w-8 text-center" x-text="itemQuantity"></span>
                            <button @click="itemQuantity++"
                                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Add to Cart Button --}}
                    <button @click="addToCartFromDetail()"
                            class="mt-4 w-full py-3 bg-primary text-white font-semibold rounded-xl text-center">
                        Add to Cart - <span x-text="'Rp ' + formatPrice(getDetailTotal())"></span>
                    </button>
                </div>
            </template>
        </div>

        {{-- Cart Bottom Sheet --}}
        <div x-show="showCart" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-black/50" @click="showCart = false">
        </div>
        <div x-show="showCart"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto max-w-lg mx-auto">
            <div class="p-4 pb-8">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h2 class="text-lg font-bold text-gray-900 mb-4">Your Order</h2>

                {{-- Cart Items --}}
                <template x-if="cart.length === 0">
                    <p class="text-center text-gray-500 py-8">Your cart is empty</p>
                </template>
                <div class="space-y-3">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-medium text-sm text-gray-900" x-text="item.name"></p>
                                        <p class="text-xs text-gray-500" x-show="item.variantName" x-text="item.variantName"></p>
                                        <p class="text-xs text-gray-500" x-show="item.modifierNames" x-text="item.modifierNames"></p>
                                        <p class="text-xs text-gray-400" x-show="item.notes" x-text="'Note: ' + item.notes"></p>
                                    </div>
                                    <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-600 ml-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center gap-2">
                                        <button @click="updateQuantity(index, item.quantity - 1)"
                                                class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center text-xs">-</button>
                                        <span class="text-sm font-medium" x-text="item.quantity"></span>
                                        <button @click="updateQuantity(index, item.quantity + 1)"
                                                class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center text-xs">+</button>
                                    </div>
                                    <span class="text-sm font-bold text-primary" x-text="'Rp ' + formatPrice(item.subtotal)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Customer Info --}}
                <div x-show="cart.length > 0" class="mt-4 space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Your Name</label>
                        <input type="text" x-model="customerName" placeholder="Enter your name"
                               class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Phone (optional)</label>
                        <input type="tel" x-model="customerPhone" placeholder="08xxxxxxxxxx"
                               class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Order Notes (optional)</label>
                        <input type="text" x-model="orderNotes" placeholder="Any special instructions"
                               class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-primary focus:border-primary">
                    </div>
                </div>

                {{-- Totals --}}
                <div x-show="cart.length > 0" class="mt-4 pt-4 border-t border-gray-200 space-y-1">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span x-text="'Rp ' + formatPrice(cartSubtotal)"></span>
                    </div>
                    @if($tax_percentage > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Tax ({{ $tax_percentage }}%{{ $tax_mode === 'inclusive' ? ' incl.' : '' }})</span>
                        <span x-text="'Rp ' + formatPrice(cartTax)"></span>
                    </div>
                    @endif
                    @if($service_charge_percentage > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Service Charge ({{ $service_charge_percentage }}%)</span>
                        <span x-text="'Rp ' + formatPrice(cartServiceCharge)"></span>
                    </div>
                    @endif
                    <div class="flex justify-between text-base font-bold text-gray-900 pt-2">
                        <span>Total</span>
                        <span x-text="'Rp ' + formatPrice(cartTotal)"></span>
                    </div>
                </div>

                {{-- Checkout Buttons --}}
                <div x-show="cart.length > 0" class="mt-4 space-y-2">
                    <button @click="checkout('pay_at_counter')"
                            :disabled="isSubmitting"
                            class="w-full py-3 bg-primary text-white font-semibold rounded-xl text-center disabled:opacity-50">
                        <span x-show="!isSubmitting">Bayar di Kasir</span>
                        <span x-show="isSubmitting">Processing...</span>
                    </button>
                    @if(config('xendit.secret_key'))
                    <button @click="checkout('qris')"
                            :disabled="isSubmitting"
                            class="w-full py-3 bg-gray-800 text-white font-semibold rounded-xl text-center disabled:opacity-50">
                        <span x-show="!isSubmitting">Bayar QRIS</span>
                        <span x-show="isSubmitting">Processing...</span>
                    </button>
                    @endif
                </div>

                {{-- Error Message --}}
                <div x-show="errorMessage" class="mt-3 p-3 bg-red-50 text-red-700 rounded-lg text-sm" x-text="errorMessage"></div>
            </div>
        </div>

        {{-- Floating Cart Button (when cart has items) --}}
        <div x-show="cartCount > 0 && !showCart && !showProductDetail"
             class="fixed bottom-4 left-4 right-4 max-w-lg mx-auto z-20">
            <button @click="showCart = true"
                    class="w-full py-3 bg-primary text-white font-semibold rounded-xl shadow-lg flex items-center justify-between px-6">
                <span class="flex items-center gap-2">
                    <span class="bg-white/20 px-2 py-0.5 rounded text-sm" x-text="cartCount + ' item(s)'"></span>
                </span>
                <span x-text="'Rp ' + formatPrice(cartSubtotal)"></span>
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
        function qrMenuApp() {
            return {
                cart: JSON.parse(localStorage.getItem('qr_cart_{{ $table->id }}') || '[]'),
                activeCategory: null,
                showCart: false,
                showProductDetail: false,
                selectedProduct: null,
                selectedVariant: null,
                selectedModifiers: [],
                itemQuantity: 1,
                itemNotes: '',
                customerName: localStorage.getItem('qr_customer_name') || '',
                customerPhone: localStorage.getItem('qr_customer_phone') || '',
                orderNotes: '',
                isSubmitting: false,
                errorMessage: '',
                taxMode: '{{ $tax_mode }}',
                taxPercentage: {{ $tax_percentage }},
                serviceChargePercentage: {{ $service_charge_percentage }},

                get cartCount() {
                    return this.cart.reduce((sum, item) => sum + item.quantity, 0);
                },

                get cartSubtotal() {
                    return this.cart.reduce((sum, item) => sum + item.subtotal, 0);
                },

                get cartTax() {
                    const base = this.cartSubtotal;
                    if (this.taxMode === 'inclusive') {
                        return this.taxPercentage > 0 ? base * (this.taxPercentage / (100 + this.taxPercentage)) : 0;
                    }
                    return (base * this.taxPercentage) / 100;
                },

                get cartServiceCharge() {
                    return (this.cartSubtotal * this.serviceChargePercentage) / 100;
                },

                get cartTotal() {
                    if (this.taxMode === 'inclusive') {
                        return Math.round(this.cartSubtotal + this.cartServiceCharge);
                    }
                    return Math.round(this.cartSubtotal + this.cartTax + this.cartServiceCharge);
                },

                formatPrice(amount) {
                    return new Intl.NumberFormat('id-ID').format(Math.round(amount));
                },

                saveCart() {
                    localStorage.setItem('qr_cart_{{ $table->id }}', JSON.stringify(this.cart));
                    localStorage.setItem('qr_customer_name', this.customerName);
                    localStorage.setItem('qr_customer_phone', this.customerPhone);
                },

                openProductDetail(product) {
                    this.selectedProduct = product;
                    this.selectedVariant = product.variants?.length > 0 ? product.variants[0] : null;
                    this.selectedModifiers = [];
                    this.itemQuantity = 1;
                    this.itemNotes = '';
                    this.showProductDetail = true;
                },

                toggleModifier(modifier) {
                    const idx = this.selectedModifiers.indexOf(modifier.id);
                    if (idx >= 0) {
                        this.selectedModifiers.splice(idx, 1);
                    } else {
                        this.selectedModifiers.push(modifier.id);
                    }
                },

                getDetailTotal() {
                    if (!this.selectedProduct) return 0;
                    const outletPrice = this.selectedProduct.product_outlets?.[0]?.custom_price;
                    let basePrice = parseFloat(this.selectedVariant ? this.selectedVariant.price : (outletPrice ?? this.selectedProduct.base_price)) || 0;
                    let modTotal = 0;
                    if (this.selectedProduct.modifier_groups) {
                        this.selectedProduct.modifier_groups.forEach(g => {
                            g.modifiers.forEach(m => {
                                if (this.selectedModifiers.includes(m.id)) modTotal += parseFloat(m.price) || 0;
                            });
                        });
                    }
                    return (basePrice + modTotal) * this.itemQuantity;
                },

                quickAddToCart(product) {
                    const outletPrice = product.product_outlets?.[0]?.custom_price;
                    const price = parseFloat(outletPrice ?? product.base_price) || 0;

                    // Merge if same product (no variant, no modifiers, no notes)
                    const existing = this.cart.find(i => i.product_id === product.id && !i.variant_id && i.modifiers.length === 0 && !i.notes);
                    if (existing) {
                        existing.quantity++;
                        existing.subtotal = existing.unitPrice * existing.quantity;
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            variant_id: null,
                            name: product.name,
                            variantName: '',
                            modifiers: [],
                            modifierNames: '',
                            unitPrice: price,
                            quantity: 1,
                            subtotal: price,
                            notes: ''
                        });
                    }
                    this.saveCart();
                },

                addToCartFromDetail() {
                    const outletPrice = this.selectedProduct.product_outlets?.[0]?.custom_price;
                    let unitPrice = parseFloat(this.selectedVariant ? this.selectedVariant.price : (outletPrice ?? this.selectedProduct.base_price)) || 0;
                    let modifiers = [];
                    let modifierNames = [];
                    let modTotal = 0;

                    if (this.selectedProduct.modifier_groups) {
                        this.selectedProduct.modifier_groups.forEach(g => {
                            g.modifiers.forEach(m => {
                                if (this.selectedModifiers.includes(m.id)) {
                                    modifiers.push(m.id);
                                    modifierNames.push(m.display_name || m.name);
                                    modTotal += parseFloat(m.price) || 0;
                                }
                            });
                        });
                    }

                    unitPrice += modTotal;

                    // Merge if same product + variant + modifiers + no notes
                    const variantId = this.selectedVariant?.id || null;
                    const modKey = modifiers.slice().sort().join(',');
                    const existing = !this.itemNotes ? this.cart.find(i =>
                        i.product_id === this.selectedProduct.id &&
                        i.variant_id === variantId &&
                        i.modifiers.slice().sort().join(',') === modKey &&
                        !i.notes
                    ) : null;

                    if (existing) {
                        existing.quantity += this.itemQuantity;
                        existing.subtotal = existing.unitPrice * existing.quantity;
                    } else {
                        this.cart.push({
                            product_id: this.selectedProduct.id,
                            variant_id: variantId,
                            name: this.selectedProduct.name,
                            variantName: this.selectedVariant?.name || '',
                            modifiers: modifiers,
                            modifierNames: modifierNames.join(', '),
                            unitPrice: unitPrice,
                            quantity: this.itemQuantity,
                            subtotal: unitPrice * this.itemQuantity,
                            notes: this.itemNotes
                        });
                    }
                    this.saveCart();
                    this.showProductDetail = false;
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    this.saveCart();
                },

                updateQuantity(index, qty) {
                    if (qty < 1) {
                        this.removeFromCart(index);
                        return;
                    }
                    this.cart[index].quantity = qty;
                    this.cart[index].subtotal = this.cart[index].unitPrice * qty;
                    this.saveCart();
                },

                async checkout(paymentMethod) {
                    if (this.cart.length === 0) return;
                    this.isSubmitting = true;
                    this.errorMessage = '';

                    const items = this.cart.map(item => ({
                        product_id: item.product_id,
                        variant_id: item.variant_id,
                        modifiers: item.modifiers,
                        quantity: item.quantity,
                        notes: item.notes
                    }));

                    try {
                        const response = await fetch('{{ route("qr-menu.order", $table->qr_token) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                items: items,
                                customer_name: this.customerName,
                                customer_phone: this.customerPhone,
                                notes: this.orderNotes,
                                payment_method: paymentMethod
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Clear cart
                            this.cart = [];
                            this.saveCart();

                            if (data.order.payment_url) {
                                window.location.href = data.order.payment_url;
                            } else {
                                window.location.href = data.order.status_url;
                            }
                        } else {
                            this.errorMessage = data.message || 'Failed to place order. Please try again.';
                        }
                    } catch (e) {
                        this.errorMessage = 'Connection error. Please try again.';
                    }

                    this.isSubmitting = false;
                }
            };
        }
    </script>
    @endpush
</x-qr-menu-layout>
