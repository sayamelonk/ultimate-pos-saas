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
            <div class="w-[420px] flex flex-col bg-surface rounded-xl border border-border overflow-hidden">
                <!-- Header with Actions -->
                <div class="px-4 py-3 border-b border-border bg-white flex items-center justify-between">
                    <h3 class="font-bold text-lg text-text">Order</h3>
                    <div class="flex items-center gap-1.5">
                        <button
                            @click="holdOrder()"
                            :disabled="!sessionActive || cart.length === 0"
                            class="p-2 text-warning-600 hover:bg-warning-50 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                            title="Hold Order"
                        >
                            <x-icon name="pause" class="w-5 h-5" />
                        </button>
                        <button
                            @click="showHeldOrdersModal = true; loadHeldOrders()"
                            :disabled="!sessionActive"
                            class="p-2 text-info-600 hover:bg-info-50 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition-all relative"
                            title="Recall Order"
                        >
                            <x-icon name="clock" class="w-5 h-5" />
                            <span x-show="heldOrdersCount > 0" x-text="heldOrdersCount" class="absolute -top-1 -right-1 w-4 h-4 bg-danger text-white text-[10px] rounded-full flex items-center justify-center font-bold"></span>
                        </button>
                        <button
                            @click="showCashDrawerModal = true; loadCashDrawer()"
                            :disabled="!sessionActive"
                            class="p-2 text-secondary-600 hover:bg-secondary-100 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                            title="Cash Drawer"
                        >
                            <x-icon name="cash" class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Customer Selection (Compact) -->
                <div class="px-4 py-2 border-b border-border bg-secondary-50/50" x-data="{ showCustomerSearch: false }">
                    <template x-if="!selectedCustomer">
                        <div class="relative">
                            <input
                                type="text"
                                x-model="customerSearch"
                                @input.debounce.300ms="searchCustomers()"
                                @focus="showCustomerSearch = true"
                                placeholder="Search customer..."
                                class="w-full pl-9 pr-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                            />
                            <x-icon name="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" />
                        </div>
                    </template>

                    <template x-if="selectedCustomer">
                        <div class="flex items-center justify-between bg-primary/5 border border-primary/20 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 bg-primary rounded-full flex items-center justify-center text-white font-bold text-xs" x-text="selectedCustomer.name.charAt(0).toUpperCase()"></div>
                                <div>
                                    <p class="font-medium text-sm text-text leading-tight" x-text="selectedCustomer.name"></p>
                                    <p class="text-xs text-muted"><span x-text="formatNumber(selectedCustomer.total_points)"></span> pts</p>
                                </div>
                            </div>
                            <button @click="clearCustomer()" class="text-muted hover:text-danger p-1 hover:bg-danger/10 rounded transition-colors">
                                <x-icon name="x" class="w-4 h-4" />
                            </button>
                        </div>
                    </template>

                    <!-- Customer Search Dropdown -->
                    <div
                        x-show="showCustomerSearch && customers.length > 0"
                        @click.away="showCustomerSearch = false"
                        class="absolute left-4 right-4 mt-1 bg-white border border-border rounded-lg shadow-lg z-10 max-h-40 overflow-y-auto"
                    >
                        <template x-for="customer in customers" :key="customer.id">
                            <button
                                @click="selectCustomer(customer); showCustomerSearch = false"
                                class="w-full px-3 py-2 text-left hover:bg-secondary-50 flex items-center gap-2 border-b border-border last:border-0"
                            >
                                <div class="w-7 h-7 bg-secondary-200 rounded-full flex items-center justify-center font-bold text-xs" x-text="customer.name.charAt(0).toUpperCase()"></div>
                                <div>
                                    <p class="text-sm font-medium" x-text="customer.name"></p>
                                    <p class="text-xs text-muted" x-text="customer.phone || customer.email"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Cart Items - Scrollable (Maximum space) -->
                <div class="flex-1 overflow-y-auto">
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-muted py-12">
                            <x-icon name="shopping-cart" class="w-12 h-12 mb-2 opacity-30" />
                            <p class="text-sm">Cart is empty</p>
                            <p class="text-xs mt-1">Click on items to add</p>
                        </div>
                    </template>

                    <div class="divide-y divide-border">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="px-4 py-3 hover:bg-secondary-50/50 transition-colors">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-text text-sm leading-tight" x-text="item.name"></p>
                                        <p class="text-xs text-muted mt-0.5" x-text="formatCurrency(item.unit_price) + ' × ' + item.quantity"></p>
                                    </div>
                                    <p class="font-semibold text-sm text-primary shrink-0" x-text="formatCurrency(item.subtotal)"></p>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center bg-secondary-100 rounded-md">
                                        <button @click="updateQuantity(index, -1)" class="w-7 h-7 hover:bg-secondary-200 rounded-l-md flex items-center justify-center transition-colors">
                                            <x-icon name="minus" class="w-3.5 h-3.5" />
                                        </button>
                                        <input
                                            type="number"
                                            x-model.number="item.quantity"
                                            @change="recalculate()"
                                            min="0.01"
                                            step="0.01"
                                            class="w-12 h-7 px-1 text-center bg-white border-y border-secondary-200 text-sm font-semibold focus:outline-none focus:ring-0"
                                        />
                                        <button @click="updateQuantity(index, 1)" class="w-7 h-7 hover:bg-secondary-200 rounded-r-md flex items-center justify-center transition-colors">
                                            <x-icon name="plus" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                    <button @click="removeFromCart(index)" class="text-muted hover:text-danger p-1 hover:bg-danger/10 rounded transition-colors">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Cart Summary (Compact) -->
                <template x-if="cart.length > 0">
                    <div class="border-t border-border bg-white px-4 py-3">
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted">Subtotal</span>
                                <span class="font-medium" x-text="formatCurrency(totals.subtotal)"></span>
                            </div>
                            <template x-if="totals.discount_amount > 0">
                                <div class="flex justify-between text-success">
                                    <span>Discount</span>
                                    <span class="font-medium" x-text="'-' + formatCurrency(totals.discount_amount)"></span>
                                </div>
                            </template>
                            <div class="flex justify-between">
                                <span class="text-muted">Tax (<span x-text="totals.tax_percentage"></span>%)</span>
                                <span class="font-medium" x-text="formatCurrency(totals.tax_amount)"></span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-border">
                                <span class="font-bold">Total</span>
                                <span class="text-primary text-xl font-bold" x-text="formatCurrency(totals.grand_total)"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Payment Section - Only shows when cart has items -->
                <template x-if="cart.length > 0">
                    <div class="border-t-2 border-primary/20 bg-gradient-to-b from-secondary-50 to-white p-4" x-data="{ showPayment: false }">
                        <!-- Collapsed: Just Pay Button -->
                        <template x-if="!showPayment">
                            <button
                                @click="showPayment = true"
                                class="w-full py-4 bg-primary text-white font-bold text-lg rounded-xl hover:bg-primary-600 transition-all shadow-lg shadow-primary/25 flex items-center justify-center gap-3"
                            >
                                <x-icon name="credit-card" class="w-5 h-5" />
                                <span>Proceed to Payment</span>
                                <span x-text="formatCurrency(totals.grand_total)"></span>
                            </button>
                        </template>

                        <!-- Expanded: Full Payment Options -->
                        <template x-if="showPayment">
                            <div>
                                <!-- Back Button -->
                                <button @click="showPayment = false" class="flex items-center gap-1 text-sm text-muted hover:text-text mb-3 transition-colors">
                                    <x-icon name="arrow-left" class="w-4 h-4" />
                                    <span>Back to cart</span>
                                </button>

                                <!-- Payment Methods Grid -->
                                <div class="grid grid-cols-3 gap-1.5 mb-3">
                                    @foreach($paymentMethods as $method)
                                        <button
                                            @click="selectPaymentMethod('{{ $method->id }}', '{{ $method->name }}', {{ $method->requires_reference ? 'true' : 'false' }})"
                                            :class="selectedPaymentMethod === '{{ $method->id }}' ? 'bg-primary text-white' : 'bg-white text-text border border-border hover:border-primary'"
                                            class="px-2 py-2 rounded-lg text-xs font-medium transition-all"
                                        >
                                            {{ $method->name }}
                                        </button>
                                    @endforeach
                                </div>

                                <!-- Reference Number -->
                                <template x-if="requiresReference">
                                    <div class="mb-3">
                                        <input
                                            type="text"
                                            x-model="referenceNumber"
                                            placeholder="Reference/Approval Number"
                                            class="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        />
                                    </div>
                                </template>

                                <!-- Payment Amount -->
                                <div class="mb-3">
                                    <label class="block text-xs text-muted mb-1 font-medium">Payment Amount</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">Rp</span>
                                        <input
                                            type="text"
                                            :value="formatNumber(paymentAmount)"
                                            @input="paymentAmount = parseInt($event.target.value.replace(/\./g, '')) || 0"
                                            class="w-full pl-10 pr-3 py-3 text-xl font-bold text-right border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                                        />
                                    </div>
                                </div>

                                <!-- Quick Cash Buttons (Compact) -->
                                <template x-if="selectedPaymentMethodName === 'Cash'">
                                    <div class="grid grid-cols-4 gap-1.5 mb-3">
                                        <template x-for="amount in quickCashAmounts" :key="amount">
                                            <button
                                                @click="paymentAmount = amount"
                                                :class="paymentAmount === amount ? 'bg-primary/10 border-primary text-primary' : 'bg-white border-border hover:border-primary/50'"
                                                class="px-1 py-1.5 border rounded text-xs font-medium transition-all"
                                            >
                                                <span x-text="formatNumber(amount)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <!-- Change Display -->
                                <template x-if="paymentAmount > totals.grand_total">
                                    <div class="mb-3 px-3 py-2 bg-success-50 border border-success-200 rounded-lg">
                                        <div class="flex justify-between items-center">
                                            <span class="text-success-700 text-sm font-medium">Change</span>
                                            <span class="text-lg font-bold text-success-600" x-text="formatCurrency(paymentAmount - totals.grand_total)"></span>
                                        </div>
                                    </div>
                                </template>

                                <!-- Pay Button -->
                                <button
                                    @click="checkout()"
                                    :disabled="!canCheckout || processing"
                                    class="w-full py-4 bg-primary text-white font-bold text-lg rounded-xl hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-primary/25 flex items-center justify-center gap-3"
                                >
                                    <template x-if="processing">
                                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="!processing">
                                        <x-icon name="credit-card" class="w-5 h-5" />
                                    </template>
                                    <span>Pay</span>
                                    <span x-text="formatCurrency(totals.grand_total)"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Empty Cart: Simple Pay Button (Disabled) -->
                <template x-if="cart.length === 0">
                    <div class="border-t border-border bg-white p-4">
                        <button disabled class="w-full py-4 bg-secondary-200 text-secondary-500 font-bold text-lg rounded-xl cursor-not-allowed flex items-center justify-center gap-3">
                            <x-icon name="credit-card" class="w-5 h-5" />
                            <span>Add items to checkout</span>
                        </button>
                    </div>
                </template>
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

        <!-- Hold Order Modal -->
        <div
            x-show="showHoldModal"
            x-transition
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showHoldModal = false"
        >
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-text">Hold Order</h3>
                    <button @click="showHoldModal = false" class="text-muted hover:text-text">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Reference (optional)</label>
                        <input
                            type="text"
                            x-model="holdReference"
                            placeholder="e.g., Customer name, order description..."
                            class="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Table Number (optional)</label>
                        <input
                            type="text"
                            x-model="holdTableNumber"
                            placeholder="e.g., Table 5"
                            class="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Notes (optional)</label>
                        <textarea
                            x-model="holdNotes"
                            rows="2"
                            placeholder="Additional notes..."
                            class="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                        ></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button
                        @click="showHoldModal = false"
                        class="flex-1 px-4 py-2.5 bg-secondary-100 text-secondary-700 font-medium rounded-lg hover:bg-secondary-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmHoldOrder()"
                        :disabled="holdingOrder"
                        class="flex-1 px-4 py-2.5 bg-warning-500 text-white font-medium rounded-lg hover:bg-warning-600 disabled:opacity-50"
                    >
                        <span x-show="!holdingOrder">Hold Order</span>
                        <span x-show="holdingOrder">Holding...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Held Orders Modal -->
        <div
            x-show="showHeldOrdersModal"
            x-transition
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showHeldOrdersModal = false"
        >
            <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-text">Held Orders</h3>
                    <button @click="showHeldOrdersModal = false" class="text-muted hover:text-text">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <template x-if="heldOrders.length === 0">
                        <div class="flex flex-col items-center justify-center py-12 text-muted">
                            <x-icon name="clock" class="w-12 h-12 mb-2 opacity-50" />
                            <p>No held orders</p>
                        </div>
                    </template>
                    <div class="space-y-3">
                        <template x-for="order in heldOrders" :key="order.id">
                            <div class="border border-border rounded-lg p-4 hover:border-primary/50 transition-colors">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <p class="font-semibold text-text" x-text="order.display_name"></p>
                                        <p class="text-xs text-muted">
                                            <span x-text="order.hold_number"></span> &bull;
                                            <span x-text="order.created_at"></span>
                                        </p>
                                    </div>
                                    <p class="font-bold text-primary" x-text="formatCurrency(order.grand_total)"></p>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-muted mb-3">
                                    <span x-text="order.item_count + ' item(s)'"></span>
                                    <template x-if="order.customer">
                                        <span>&bull; <span x-text="order.customer.name"></span></span>
                                    </template>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="recallOrder(order)"
                                        class="flex-1 px-3 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-600"
                                    >
                                        Recall
                                    </button>
                                    <button
                                        @click="deleteHeldOrder(order.id)"
                                        class="px-3 py-2 bg-danger-50 text-danger border border-danger-200 text-sm font-medium rounded-lg hover:bg-danger-100"
                                    >
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Drawer Modal -->
        <div
            x-show="showCashDrawerModal"
            x-transition
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showCashDrawerModal = false"
        >
            <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4 max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-text">Cash Drawer</h3>
                    <button @click="showCashDrawerModal = false" class="text-muted hover:text-text">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Current Balance -->
                <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 mb-4">
                    <p class="text-sm text-muted mb-1">Current Balance</p>
                    <p class="text-2xl font-bold text-primary" x-text="formatCurrency(cashDrawerBalance)"></p>
                </div>

                <!-- Cash In/Out Tabs -->
                <div class="flex gap-2 mb-4">
                    <button
                        @click="cashDrawerTab = 'in'"
                        :class="cashDrawerTab === 'in' ? 'bg-success-500 text-white' : 'bg-secondary-100 text-secondary-700'"
                        class="flex-1 px-4 py-2 rounded-lg font-medium text-sm transition-colors"
                    >
                        Cash In
                    </button>
                    <button
                        @click="cashDrawerTab = 'out'"
                        :class="cashDrawerTab === 'out' ? 'bg-danger-500 text-white' : 'bg-secondary-100 text-secondary-700'"
                        class="flex-1 px-4 py-2 rounded-lg font-medium text-sm transition-colors"
                    >
                        Cash Out
                    </button>
                    <button
                        @click="cashDrawerTab = 'log'"
                        :class="cashDrawerTab === 'log' ? 'bg-primary text-white' : 'bg-secondary-100 text-secondary-700'"
                        class="flex-1 px-4 py-2 rounded-lg font-medium text-sm transition-colors"
                    >
                        Log
                    </button>
                </div>

                <!-- Cash In Form -->
                <div x-show="cashDrawerTab === 'in'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">Rp</span>
                            <input
                                type="text"
                                :value="formatNumber(cashInAmount)"
                                @input="cashInAmount = parseInt($event.target.value.replace(/\./g, '')) || 0"
                                class="w-full pl-10 pr-4 py-2.5 border border-border rounded-lg text-right font-medium focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Reason</label>
                        <textarea
                            x-model="cashInReason"
                            rows="2"
                            placeholder="Reason for cash in..."
                            class="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                        ></textarea>
                    </div>
                    <button
                        @click="submitCashIn()"
                        :disabled="cashInAmount <= 0 || !cashInReason || processingCash"
                        class="w-full px-4 py-2.5 bg-success-500 text-white font-medium rounded-lg hover:bg-success-600 disabled:opacity-50"
                    >
                        <span x-show="!processingCash">Add Cash</span>
                        <span x-show="processingCash">Processing...</span>
                    </button>
                </div>

                <!-- Cash Out Form -->
                <div x-show="cashDrawerTab === 'out'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">Rp</span>
                            <input
                                type="text"
                                :value="formatNumber(cashOutAmount)"
                                @input="cashOutAmount = parseInt($event.target.value.replace(/\./g, '')) || 0"
                                class="w-full pl-10 pr-4 py-2.5 border border-border rounded-lg text-right font-medium focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Reason</label>
                        <textarea
                            x-model="cashOutReason"
                            rows="2"
                            placeholder="Reason for cash out..."
                            class="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                        ></textarea>
                    </div>
                    <button
                        @click="submitCashOut()"
                        :disabled="cashOutAmount <= 0 || !cashOutReason || cashOutAmount > cashDrawerBalance || processingCash"
                        class="w-full px-4 py-2.5 bg-danger-500 text-white font-medium rounded-lg hover:bg-danger-600 disabled:opacity-50"
                    >
                        <span x-show="!processingCash">Withdraw Cash</span>
                        <span x-show="processingCash">Processing...</span>
                    </button>
                    <template x-if="cashOutAmount > cashDrawerBalance">
                        <p class="text-sm text-danger">Amount exceeds current balance</p>
                    </template>
                </div>

                <!-- Cash Log -->
                <div x-show="cashDrawerTab === 'log'" class="flex-1 overflow-y-auto">
                    <template x-if="cashDrawerLogs.length === 0">
                        <div class="text-center py-8 text-muted">
                            <p>No cash drawer logs</p>
                        </div>
                    </template>
                    <div class="space-y-2">
                        <template x-for="log in cashDrawerLogs" :key="log.id">
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-sm" x-text="log.type_label"></p>
                                    <p class="text-xs text-muted" x-text="log.reason || log.reference || '-'"></p>
                                    <p class="text-xs text-muted" x-text="log.created_at"></p>
                                </div>
                                <p
                                    :class="log.is_inflow ? 'text-success' : 'text-danger'"
                                    class="font-semibold"
                                    x-text="(log.is_inflow ? '+' : '-') + formatCurrency(log.amount)"
                                ></p>
                            </div>
                        </template>
                    </div>
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

                // Held Orders State
                showHoldModal: false,
                showHeldOrdersModal: false,
                heldOrders: [],
                heldOrdersCount: 0,
                holdReference: '',
                holdTableNumber: '',
                holdNotes: '',
                holdingOrder: false,

                // Cash Drawer State
                showCashDrawerModal: false,
                cashDrawerTab: 'in',
                cashDrawerBalance: 0,
                cashDrawerLogs: [],
                cashInAmount: 0,
                cashInReason: '',
                cashOutAmount: 0,
                cashOutReason: '',
                processingCash: false,

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
                    if (this.sessionActive) {
                        this.loadHeldOrdersCount();
                    }
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
                },

                // ==================== Held Orders ====================
                holdOrder() {
                    if (this.cart.length === 0) return;
                    this.holdReference = '';
                    this.holdTableNumber = '';
                    this.holdNotes = '';
                    this.showHoldModal = true;
                },

                async confirmHoldOrder() {
                    if (this.holdingOrder) return;
                    this.holdingOrder = true;

                    try {
                        const response = await fetch('/pos/held-orders', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Outlet-Id': this.outletId
                            },
                            body: JSON.stringify({
                                items: this.cart,
                                discounts: [],
                                customer_id: this.selectedCustomer?.id,
                                reference: this.holdReference,
                                table_number: this.holdTableNumber,
                                notes: this.holdNotes,
                                subtotal: this.totals.subtotal,
                                discount_amount: this.totals.discount_amount,
                                tax_amount: this.totals.tax_amount,
                                service_charge_amount: 0,
                                grand_total: this.totals.grand_total
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showHoldModal = false;
                            this.cart = [];
                            this.selectedCustomer = null;
                            this.recalculate();
                            this.loadHeldOrdersCount();
                            alert('Order held: ' + data.held_order.display_name);
                        } else {
                            alert(data.message || 'Failed to hold order');
                        }
                    } catch (error) {
                        console.error('Hold order error:', error);
                        alert('An error occurred while holding order');
                    } finally {
                        this.holdingOrder = false;
                    }
                },

                async loadHeldOrdersCount() {
                    try {
                        const response = await fetch('/pos/held-orders', {
                            headers: { 'X-Outlet-Id': this.outletId }
                        });
                        const data = await response.json();
                        this.heldOrdersCount = data.count || 0;
                    } catch (error) {
                        console.error('Failed to load held orders count:', error);
                    }
                },

                async loadHeldOrders() {
                    try {
                        const response = await fetch('/pos/held-orders', {
                            headers: { 'X-Outlet-Id': this.outletId }
                        });
                        const data = await response.json();
                        this.heldOrders = data.held_orders || [];
                        this.heldOrdersCount = data.count || 0;
                    } catch (error) {
                        console.error('Failed to load held orders:', error);
                    }
                },

                async recallOrder(order) {
                    try {
                        const response = await fetch(`/pos/held-orders/${order.id}/recall`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Outlet-Id': this.outletId
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.cart = data.order_data.items || [];
                            this.selectedCustomer = data.order_data.customer;
                            this.recalculate();
                            this.showHeldOrdersModal = false;
                            this.loadHeldOrdersCount();
                        } else {
                            alert(data.message || 'Failed to recall order');
                        }
                    } catch (error) {
                        console.error('Recall order error:', error);
                        alert('An error occurred while recalling order');
                    }
                },

                async deleteHeldOrder(orderId) {
                    if (!confirm('Are you sure you want to delete this held order?')) return;

                    try {
                        const response = await fetch(`/pos/held-orders/${orderId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Outlet-Id': this.outletId
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.loadHeldOrders();
                        } else {
                            alert(data.message || 'Failed to delete held order');
                        }
                    } catch (error) {
                        console.error('Delete held order error:', error);
                    }
                },

                // ==================== Cash Drawer ====================
                async loadCashDrawer() {
                    try {
                        const response = await fetch('/pos/cash-drawer', {
                            headers: { 'X-Outlet-Id': this.outletId }
                        });
                        const data = await response.json();
                        this.cashDrawerBalance = data.balance || 0;
                        this.cashDrawerLogs = data.logs || [];
                    } catch (error) {
                        console.error('Failed to load cash drawer:', error);
                    }
                },

                async submitCashIn() {
                    if (this.processingCash || this.cashInAmount <= 0 || !this.cashInReason) return;
                    this.processingCash = true;

                    try {
                        const response = await fetch('/pos/cash-drawer/cash-in', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Outlet-Id': this.outletId
                            },
                            body: JSON.stringify({
                                amount: this.cashInAmount,
                                reason: this.cashInReason
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.cashInAmount = 0;
                            this.cashInReason = '';
                            this.loadCashDrawer();
                            alert('Cash added successfully');
                        } else {
                            alert(data.message || 'Failed to add cash');
                        }
                    } catch (error) {
                        console.error('Cash in error:', error);
                        alert('An error occurred');
                    } finally {
                        this.processingCash = false;
                    }
                },

                async submitCashOut() {
                    if (this.processingCash || this.cashOutAmount <= 0 || !this.cashOutReason) return;
                    if (this.cashOutAmount > this.cashDrawerBalance) {
                        alert('Amount exceeds current balance');
                        return;
                    }
                    this.processingCash = true;

                    try {
                        const response = await fetch('/pos/cash-drawer/cash-out', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Outlet-Id': this.outletId
                            },
                            body: JSON.stringify({
                                amount: this.cashOutAmount,
                                reason: this.cashOutReason
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.cashOutAmount = 0;
                            this.cashOutReason = '';
                            this.loadCashDrawer();
                            alert('Cash withdrawn successfully');
                        } else {
                            alert(data.message || 'Failed to withdraw cash');
                        }
                    } catch (error) {
                        console.error('Cash out error:', error);
                        alert('An error occurred');
                    } finally {
                        this.processingCash = false;
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
