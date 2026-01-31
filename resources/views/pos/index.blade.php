<x-app-layout>
    <x-slot name="title">Point of Sale - Ultimate POS</x-slot>

    @section('page-title', 'Point of Sale')

    <div class="h-[calc(100vh-180px)]" x-data="posApp()" x-init="init()">
        @if(!$session)
            <!-- No Session Alert -->
            <div class="mb-4 p-4 bg-warning-50 border border-warning-200 text-warning-700 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-icon name="exclamation-circle" class="w-5 h-5" />
                        <span>No active session. You need to open a session before making sales.</span>
                    </div>
                    <x-button href="{{ route('pos.sessions.open') }}" size="sm" variant="warning">
                        Open Session
                    </x-button>
                </div>
            </div>
        @endif

        <div class="flex gap-4 h-full">
            <!-- Left Panel: Categories & Items -->
            <div class="flex-1 flex flex-col bg-surface rounded-xl border border-border overflow-hidden">
                <!-- Search Bar -->
                <div class="p-4 border-b border-border">
                    <div class="flex gap-4">
                        <div class="flex-1 relative">
                            <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted" />
                            <input
                                type="text"
                                x-model="search"
                                @input.debounce.300ms="loadItems()"
                                placeholder="Search by name, SKU, or barcode..."
                                class="w-full pl-10 pr-4 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                        </div>
                        <select
                            x-model="selectedCategory"
                            @change="loadItems()"
                            class="px-4 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @foreach($category->children as $child)
                                    <option value="{{ $child->id }}">-- {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Items Grid -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                        <template x-for="item in items" :key="item.id">
                            <button
                                @click="addToCart(item)"
                                :disabled="!sessionActive"
                                class="bg-white border border-border rounded-lg p-3 hover:border-primary hover:shadow-md transition-all text-left disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <div class="aspect-square bg-secondary-100 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                                    <template x-if="item.image">
                                        <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                                    </template>
                                    <template x-if="!item.image">
                                        <x-icon name="cube" class="w-8 h-8 text-secondary-400" />
                                    </template>
                                </div>
                                <p class="font-medium text-sm text-text truncate" x-text="item.name"></p>
                                <p class="text-xs text-muted truncate" x-text="item.category_name || 'Uncategorized'"></p>
                                <p class="text-sm font-semibold text-primary mt-1" x-text="formatCurrency(item.selling_price)"></p>
                            </button>
                        </template>
                    </div>

                    <template x-if="items.length === 0 && !loading">
                        <div class="flex flex-col items-center justify-center h-64 text-muted">
                            <x-icon name="cube" class="w-12 h-12 mb-2" />
                            <p>No items found</p>
                        </div>
                    </template>

                    <template x-if="loading">
                        <div class="flex items-center justify-center h-64">
                            <svg class="animate-spin w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Right Panel: Cart -->
            <div class="w-96 flex flex-col bg-surface rounded-xl border border-border overflow-hidden">
                <!-- Customer Selection -->
                <div class="p-4 border-b border-border">
                    <div class="relative" x-data="{ showCustomerSearch: false }">
                        <template x-if="!selectedCustomer">
                            <div class="relative">
                                <input
                                    type="text"
                                    x-model="customerSearch"
                                    @input.debounce.300ms="searchCustomers()"
                                    @focus="showCustomerSearch = true"
                                    placeholder="Search customer..."
                                    class="w-full pl-10 pr-4 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                                <x-icon name="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" />
                            </div>
                        </template>

                        <template x-if="selectedCustomer">
                            <div class="flex items-center justify-between bg-secondary-50 rounded-lg p-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white font-medium text-sm" x-text="selectedCustomer.name.charAt(0).toUpperCase()"></div>
                                    <div>
                                        <p class="font-medium text-sm" x-text="selectedCustomer.name"></p>
                                        <p class="text-xs text-muted">
                                            <span x-text="selectedCustomer.membership_level"></span> -
                                            <span x-text="formatNumber(selectedCustomer.total_points)"></span> points
                                        </p>
                                    </div>
                                </div>
                                <button @click="clearCustomer()" class="text-muted hover:text-danger">
                                    <x-icon name="x" class="w-4 h-4" />
                                </button>
                            </div>
                        </template>

                        <!-- Customer Search Dropdown -->
                        <div
                            x-show="showCustomerSearch && customers.length > 0"
                            @click.away="showCustomerSearch = false"
                            class="absolute top-full left-0 right-0 mt-1 bg-white border border-border rounded-lg shadow-lg z-10 max-h-48 overflow-y-auto"
                        >
                            <template x-for="customer in customers" :key="customer.id">
                                <button
                                    @click="selectCustomer(customer); showCustomerSearch = false"
                                    class="w-full px-4 py-2 text-left hover:bg-secondary-50 flex items-center gap-3"
                                >
                                    <div class="w-8 h-8 bg-secondary-200 rounded-full flex items-center justify-center font-medium text-sm" x-text="customer.name.charAt(0).toUpperCase()"></div>
                                    <div>
                                        <p class="text-sm font-medium" x-text="customer.name"></p>
                                        <p class="text-xs text-muted" x-text="customer.phone || customer.email"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-4">
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-muted">
                            <x-icon name="shopping-cart" class="w-12 h-12 mb-2" />
                            <p>Cart is empty</p>
                        </div>
                    </template>

                    <div class="space-y-3">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="bg-white border border-border rounded-lg p-3">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm text-text" x-text="item.name"></p>
                                        <p class="text-xs text-muted" x-text="formatCurrency(item.unit_price) + ' / ' + item.unit_name"></p>
                                    </div>
                                    <button @click="removeFromCart(index)" class="text-muted hover:text-danger">
                                        <x-icon name="x" class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="updateQuantity(index, -1)"
                                            class="w-7 h-7 bg-secondary-100 rounded flex items-center justify-center hover:bg-secondary-200"
                                        >
                                            <x-icon name="minus" class="w-4 h-4" />
                                        </button>
                                        <input
                                            type="number"
                                            x-model.number="item.quantity"
                                            @change="recalculate()"
                                            min="0.01"
                                            step="0.01"
                                            class="w-16 px-2 py-1 text-center border border-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                                        />
                                        <button
                                            @click="updateQuantity(index, 1)"
                                            class="w-7 h-7 bg-secondary-100 rounded flex items-center justify-center hover:bg-secondary-200"
                                        >
                                            <x-icon name="plus" class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <p class="font-semibold text-text" x-text="formatCurrency(item.subtotal)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="border-t border-border p-4 bg-secondary-50">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted">Subtotal</span>
                            <span x-text="formatCurrency(totals.subtotal)"></span>
                        </div>
                        <template x-if="totals.discount_amount > 0">
                            <div class="flex justify-between text-success">
                                <span>Discount</span>
                                <span x-text="'-' + formatCurrency(totals.discount_amount)"></span>
                            </div>
                        </template>
                        <template x-if="totals.tax_amount > 0">
                            <div class="flex justify-between">
                                <span class="text-muted">Tax (<span x-text="totals.tax_percentage"></span>%)</span>
                                <span x-text="formatCurrency(totals.tax_amount)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t border-border">
                            <span>Total</span>
                            <span class="text-primary" x-text="formatCurrency(totals.grand_total)"></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="p-4 border-t border-border">
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        @foreach($paymentMethods as $method)
                            <button
                                @click="selectPaymentMethod('{{ $method->id }}', '{{ $method->name }}', {{ $method->requires_reference ? 'true' : 'false' }})"
                                :class="selectedPaymentMethod === '{{ $method->id }}' ? 'bg-primary text-white' : 'bg-secondary-100 text-secondary-700 hover:bg-secondary-200'"
                                class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                :disabled="!sessionActive || cart.length === 0"
                            >
                                {{ $method->name }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Reference Number (for card/digital payments) -->
                    <template x-if="requiresReference">
                        <div class="mb-4">
                            <input
                                type="text"
                                x-model="referenceNumber"
                                placeholder="Reference/Approval Number"
                                class="w-full px-4 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                        </div>
                    </template>

                    <!-- Payment Amount -->
                    <div class="mb-4">
                        <label class="block text-sm text-muted mb-1">Payment Amount</label>
                        <input
                            type="number"
                            x-model.number="paymentAmount"
                            :min="totals.grand_total"
                            class="w-full px-4 py-3 text-xl font-bold text-right border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        />
                    </div>

                    <!-- Quick Cash Buttons -->
                    <template x-if="selectedPaymentMethodName === 'Cash'">
                        <div class="grid grid-cols-4 gap-2 mb-4">
                            <template x-for="amount in quickCashAmounts" :key="amount">
                                <button
                                    @click="paymentAmount = amount"
                                    class="px-2 py-2 bg-secondary-100 rounded text-sm font-medium hover:bg-secondary-200"
                                    x-text="formatCurrency(amount)"
                                ></button>
                            </template>
                        </div>
                    </template>

                    <!-- Change -->
                    <template x-if="paymentAmount > totals.grand_total">
                        <div class="mb-4 p-3 bg-success-50 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="text-success-700">Change</span>
                                <span class="text-xl font-bold text-success-700" x-text="formatCurrency(paymentAmount - totals.grand_total)"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Pay Button -->
                    <button
                        @click="checkout()"
                        :disabled="!canCheckout || processing"
                        class="w-full py-4 bg-primary text-white font-bold text-lg rounded-lg hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                    >
                        <template x-if="processing">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span>Pay</span>
                        <span x-text="formatCurrency(totals.grand_total)"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <div
            x-show="showSuccessModal"
            x-transition
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-center">
                <div class="w-20 h-20 bg-success-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-icon name="check" class="w-10 h-10 text-success" />
                </div>
                <h3 class="text-2xl font-bold text-text mb-2">Payment Successful!</h3>
                <p class="text-muted mb-4">Transaction completed</p>
                <p class="text-3xl font-bold text-primary mb-2" x-text="lastTransaction?.transaction_number"></p>
                <template x-if="lastTransaction?.change_amount > 0">
                    <div class="mb-4 p-4 bg-secondary-50 rounded-lg">
                        <p class="text-sm text-muted">Change</p>
                        <p class="text-2xl font-bold text-success" x-text="formatCurrency(lastTransaction.change_amount)"></p>
                    </div>
                </template>
                <template x-if="lastTransaction?.points_earned > 0">
                    <p class="text-sm text-muted mb-4">
                        Customer earned <span class="font-semibold text-primary" x-text="lastTransaction.points_earned"></span> points
                    </p>
                </template>
                <div class="flex gap-3">
                    <button
                        @click="printReceipt()"
                        class="flex-1 px-4 py-3 bg-secondary-100 text-secondary-700 font-medium rounded-lg hover:bg-secondary-200"
                    >
                        Print Receipt
                    </button>
                    <button
                        @click="closeSuccessModal()"
                        class="flex-1 px-4 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary-600"
                    >
                        New Sale
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function posApp() {
            return {
                // State
                outletId: '{{ $currentOutlet->id }}',
                sessionActive: {{ $session ? 'true' : 'false' }},
                items: [],
                cart: [],
                customers: [],
                search: '',
                selectedCategory: '',
                customerSearch: '',
                selectedCustomer: null,
                selectedPaymentMethod: '',
                selectedPaymentMethodName: '',
                requiresReference: false,
                referenceNumber: '',
                paymentAmount: 0,
                loading: false,
                processing: false,
                showSuccessModal: false,
                lastTransaction: null,

                totals: {
                    subtotal: 0,
                    discount_amount: 0,
                    tax_amount: 0,
                    tax_percentage: 11,
                    grand_total: 0
                },

                quickCashAmounts: [10000, 20000, 50000, 100000, 150000, 200000, 500000, 1000000],

                get canCheckout() {
                    return this.sessionActive &&
                           this.cart.length > 0 &&
                           this.selectedPaymentMethod &&
                           this.paymentAmount >= this.totals.grand_total;
                },

                init() {
                    this.loadItems();
                },

                async loadItems() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            outlet_id: this.outletId,
                            search: this.search,
                            category_id: this.selectedCategory
                        });
                        const response = await fetch(`/pos/items?${params}`);
                        const data = await response.json();
                        this.items = data.items;
                    } catch (error) {
                        console.error('Failed to load items:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async searchCustomers() {
                    if (this.customerSearch.length < 2) {
                        this.customers = [];
                        return;
                    }
                    try {
                        const response = await fetch(`/pos/customers?search=${encodeURIComponent(this.customerSearch)}`);
                        const data = await response.json();
                        this.customers = data.customers;
                    } catch (error) {
                        console.error('Failed to search customers:', error);
                    }
                },

                selectCustomer(customer) {
                    this.selectedCustomer = customer;
                    this.customerSearch = '';
                    this.customers = [];
                    this.recalculate();
                },

                clearCustomer() {
                    this.selectedCustomer = null;
                    this.recalculate();
                },

                addToCart(item) {
                    const existing = this.cart.find(i => i.inventory_item_id === item.id);
                    if (existing) {
                        existing.quantity += 1;
                        existing.subtotal = existing.quantity * existing.unit_price;
                    } else {
                        const price = this.selectedCustomer && item.member_price
                            ? item.member_price
                            : item.selling_price;
                        this.cart.push({
                            inventory_item_id: item.id,
                            name: item.name,
                            sku: item.sku,
                            unit_name: item.unit_name,
                            unit_price: price,
                            cost_price: item.cost_price,
                            quantity: 1,
                            subtotal: price
                        });
                    }
                    this.recalculate();
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    this.recalculate();
                },

                updateQuantity(index, delta) {
                    const item = this.cart[index];
                    item.quantity = Math.max(0.01, item.quantity + delta);
                    item.subtotal = item.quantity * item.unit_price;
                    if (item.quantity <= 0) {
                        this.removeFromCart(index);
                    } else {
                        this.recalculate();
                    }
                },

                recalculate() {
                    const subtotal = this.cart.reduce((sum, item) => sum + item.subtotal, 0);
                    const taxRate = this.totals.tax_percentage / 100;
                    const taxAmount = subtotal * taxRate;
                    const grandTotal = subtotal + taxAmount;

                    this.totals.subtotal = subtotal;
                    this.totals.tax_amount = taxAmount;
                    this.totals.grand_total = Math.round(grandTotal);
                    this.paymentAmount = this.totals.grand_total;
                },

                selectPaymentMethod(id, name, requiresRef) {
                    this.selectedPaymentMethod = id;
                    this.selectedPaymentMethodName = name;
                    this.requiresReference = requiresRef;
                    if (!requiresRef) {
                        this.referenceNumber = '';
                    }
                },

                async checkout() {
                    if (!this.canCheckout || this.processing) return;

                    this.processing = true;
                    try {
                        const response = await fetch('/pos/checkout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Outlet-Id': this.outletId
                            },
                            body: JSON.stringify({
                                items: this.cart.map(item => ({
                                    inventory_item_id: item.inventory_item_id,
                                    quantity: item.quantity,
                                    unit_price: item.unit_price
                                })),
                                customer_id: this.selectedCustomer?.id,
                                payment_method_id: this.selectedPaymentMethod,
                                payment_amount: this.paymentAmount,
                                reference_number: this.referenceNumber,
                                discounts: [],
                                points_to_redeem: 0,
                                notes: ''
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.lastTransaction = data.transaction;
                            this.showSuccessModal = true;
                        } else {
                            alert(data.message || 'Failed to complete transaction');
                        }
                    } catch (error) {
                        console.error('Checkout error:', error);
                        alert('An error occurred during checkout');
                    } finally {
                        this.processing = false;
                    }
                },

                printReceipt() {
                    if (this.lastTransaction) {
                        window.open(`/pos/receipt/${this.lastTransaction.id}`, '_blank');
                    }
                },

                closeSuccessModal() {
                    this.showSuccessModal = false;
                    this.cart = [];
                    this.selectedCustomer = null;
                    this.selectedPaymentMethod = '';
                    this.selectedPaymentMethodName = '';
                    this.requiresReference = false;
                    this.referenceNumber = '';
                    this.paymentAmount = 0;
                    this.lastTransaction = null;
                    this.recalculate();
                },

                formatCurrency(amount) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num);
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
