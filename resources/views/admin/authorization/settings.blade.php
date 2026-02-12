<x-app-layout>
    <x-slot name="title">Authorization Settings - Ultimate POS</x-slot>

    @section('page-title', 'Authorization Settings')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.dashboard') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Authorization Settings</h2>
                <p class="text-muted mt-1">Configure PIN authorization requirements for sensitive actions</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        <form action="{{ route('admin.authorization.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Actions Requiring Authorization -->
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                        <x-icon name="shield-check" class="w-5 h-5 text-danger" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">Actions Requiring SPV Authorization</h3>
                        <p class="text-sm text-muted">Select which actions require supervisor PIN approval</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_void"
                            value="1"
                            @checked(old('require_auth_void', $settings->require_auth_void))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">Void Transaction</span>
                            <p class="text-sm text-muted">Cancel a completed transaction</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_refund"
                            value="1"
                            @checked(old('require_auth_refund', $settings->require_auth_refund))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">Refund</span>
                            <p class="text-sm text-muted">Process customer refunds</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_cancel_order"
                            value="1"
                            @checked(old('require_auth_cancel_order', $settings->require_auth_cancel_order))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">Cancel Order</span>
                            <p class="text-sm text-muted">Cancel order before payment</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_price_override"
                            value="1"
                            @checked(old('require_auth_price_override', $settings->require_auth_price_override))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">Price Override</span>
                            <p class="text-sm text-muted">Change item price manually</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_no_sale"
                            value="1"
                            @checked(old('require_auth_no_sale', $settings->require_auth_no_sale))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">No Sale (Open Drawer)</span>
                            <p class="text-sm text-muted">Open cash drawer without sale</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                        <input
                            type="checkbox"
                            name="require_auth_reprint"
                            value="1"
                            @checked(old('require_auth_reprint', $settings->require_auth_reprint))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div>
                            <span class="font-medium text-text">Reprint Receipt</span>
                            <p class="text-sm text-muted">Print receipt again</p>
                        </div>
                    </label>
                </div>

                <!-- Discount Threshold -->
                <div class="mt-6 p-4 border border-border rounded-lg">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="require_auth_discount"
                            value="1"
                            @checked(old('require_auth_discount', $settings->require_auth_discount))
                            class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                        />
                        <div class="flex-1">
                            <span class="font-medium text-text">Manual Discount</span>
                            <p class="text-sm text-muted mb-3">Require authorization for discounts above threshold</p>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted">Threshold:</span>
                                <input
                                    type="number"
                                    name="discount_threshold_percent"
                                    value="{{ old('discount_threshold_percent', $settings->discount_threshold_percent) }}"
                                    min="0"
                                    max="100"
                                    step="1"
                                    class="w-20 px-3 py-1 border border-border rounded text-center"
                                />
                                <span class="text-sm text-muted">%</span>
                            </div>
                        </div>
                    </label>
                </div>
            </x-card>

            <!-- PIN Settings -->
            <x-card class="mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <x-icon name="key" class="w-5 h-5 text-primary" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">PIN Settings</h3>
                        <p class="text-sm text-muted">Configure PIN security settings</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1">PIN Length</label>
                        <x-select name="pin_length">
                            <option value="4" @selected(old('pin_length', $settings->pin_length) == 4)>4 digits</option>
                            <option value="6" @selected(old('pin_length', $settings->pin_length) == 6)>6 digits</option>
                        </x-select>
                        <p class="text-xs text-muted mt-1">Number of digits for PIN</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Max Attempts</label>
                        <x-input
                            type="number"
                            name="max_pin_attempts"
                            :value="old('max_pin_attempts', $settings->max_pin_attempts)"
                            min="1"
                            max="10"
                        />
                        <p class="text-xs text-muted mt-1">Failed attempts before lockout</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1">Lockout Duration</label>
                        <div class="flex items-center gap-2">
                            <x-input
                                type="number"
                                name="lockout_minutes"
                                :value="old('lockout_minutes', $settings->lockout_minutes)"
                                min="1"
                                max="60"
                            />
                            <span class="text-sm text-muted">minutes</span>
                        </div>
                        <p class="text-xs text-muted mt-1">Duration after max attempts</p>
                    </div>
                </div>
            </x-card>

            <!-- Authorizers List -->
            <x-card class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                            <x-icon name="users" class="w-5 h-5 text-success" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Authorized Users</h3>
                            <p class="text-sm text-muted">Users who can authorize with their PIN</p>
                        </div>
                    </div>
                </div>

                @if($authorizers->count() > 0)
                    <div class="divide-y divide-border">
                        @foreach($authorizers as $authorizer)
                            <div class="flex items-center justify-between py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary">{{ $authorizer->initials }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-text">{{ $authorizer->name }}</p>
                                        <p class="text-sm text-muted">{{ $authorizer->roles->pluck('name')->join(', ') }}</p>
                                    </div>
                                </div>
                                <x-badge type="success">PIN Active</x-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-icon name="user-plus" class="w-12 h-12 text-muted mx-auto mb-3" />
                        <p class="text-muted">No users have set up their PIN yet.</p>
                        <p class="text-sm text-muted mt-1">Supervisors and managers need to set their PIN in their profile.</p>
                    </div>
                @endif
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button href="{{ route('admin.dashboard') }}" variant="secondary">
                    Cancel
                </x-button>
                <x-button type="submit" icon="check">
                    Save Settings
                </x-button>
            </div>
        </form>
    </div>
</x-app-layout>
